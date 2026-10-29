<?php

namespace Saazify\LaravelSeo\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Saazify\LaravelSeo\Analysis\ContentAnalyzer;
use Saazify\LaravelSeo\Analysis\MetadataSuggester;
use Saazify\LaravelSeo\Analysis\Report;
use Saazify\LaravelSeo\Data\Breadcrumb;
use Saazify\LaravelSeo\Data\FaqItem;
use Saazify\LaravelSeo\Data\NewsMeta;
use Saazify\LaravelSeo\Data\SeoData;
use Saazify\LaravelSeo\Data\SitemapUrl;
use Saazify\LaravelSeo\Data\VideoMeta;
use Saazify\LaravelSeo\Links\OrphanFinder;
use Saazify\LaravelSeo\Llms\LlmsTxt;
use Saazify\LaravelSeo\Local\KmlDocument;
use Saazify\LaravelSeo\Local\OpeningHours;
use Saazify\LaravelSeo\Models\Redirect;
use Saazify\LaravelSeo\Redirects\ServerRedirectExporter;
use Saazify\LaravelSeo\Rendering\HeadRenderer;
use Saazify\LaravelSeo\Rendering\SchemaBuilder;
use Saazify\LaravelSeo\Robots\RobotsTxt;
use Saazify\LaravelSeo\Sitemap\NewsSitemap;
use Saazify\LaravelSeo\Sitemap\VideoSitemap;
use Saazify\LaravelSeo\Sitemap\XmlSitemap;
use Saazify\LaravelSeo\Support\Text;
use Saazify\LaravelSeo\Support\TitleComposer;

class RenderingTest extends TestCase
{
    public function test_title_template_keeps_hyphens_inside_the_title(): void
    {
        $title = (new TitleComposer)->compose(new SeoData(title: 'COVID-19'), [
            'site_name' => 'Example',
            'title' => ['separator' => '-', 'default' => '%title% %separator% %site_name%'],
        ]);

        $this->assertSame('COVID-19 - Example', $title);
    }

    public function test_empty_title_uses_the_homepage_template(): void
    {
        $title = (new TitleComposer)->compose(new SeoData, [
            'site_name' => 'Example',
            'tagline' => 'Notes',
            'title' => ['separator' => '|', 'homepage' => '%site_name% %separator% %tagline%'],
        ]);

        $this->assertSame('Example | Notes', $title);
    }

    public function test_head_escapes_markup_and_emits_social_and_schema_tags(): void
    {
        $data = new SeoData(
            title: '</title><script>alert(1)</script>',
            description: 'A useful description for the page.',
            canonical: 'https://example.com/guides',
            image: 'https://example.com/cover.jpg',
            type: 'article',
            author: 'Sam',
            publishedAt: new \DateTimeImmutable('2026-10-29T10:00:00+00:00'),
            faqs: [new FaqItem('What is this?', '</script><script>alert(1)</script>')],
            breadcrumbs: [
                new Breadcrumb('Home', 'https://example.com/'),
                new Breadcrumb('Guides'),
            ],
            videos: [new VideoMeta(
                title: 'Walkthrough',
                description: 'A short product walkthrough.',
                thumbnailUrl: 'https://example.com/thumb.jpg',
                contentUrl: 'https://example.com/video.mp4',
                duration: 90,
                familyFriendly: false,
            )],
        );

        $html = (new HeadRenderer([
            'site_name' => 'Example',
            'site_url' => 'https://example.com',
            'locale' => 'en_US',
            'local' => ['geo_tags' => true],
            'title' => ['separator' => '|', 'default' => '%title% %separator% %site_name%'],
        ]))->render($data, [
            'name' => 'Example Studio',
            'type' => 'LocalBusiness',
            'address' => ['street' => '1 Main', 'city' => 'Ahmedabad', 'region' => 'GJ', 'country' => 'IN'],
            'geo' => ['latitude' => 23.0225, 'longitude' => 72.5714],
            'opening_hours' => [
                ['day' => 'Monday', 'opens' => '09:00', 'closes' => '17:00'],
                ['day' => 'Sunday', 'closed' => true],
            ],
        ]);

        $this->assertStringContainsString('&lt;/title&gt;&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert', $html);
        $this->assertStringContainsString('property="og:video" content="https://example.com/video.mp4"', $html);
        $this->assertStringContainsString('property="og:video:type" content="video/mp4"', $html);
        $this->assertStringContainsString('property="og:type" content="article"', $html);
        $this->assertStringContainsString('name="twitter:card" content="summary_large_image"', $html);
        $this->assertStringContainsString('name="geo.position" content="23.0225;72.5714"', $html);
        $this->assertStringContainsString('name="geo.region" content="IN-GJ"', $html);
        $this->assertStringContainsString('name="ICBM" content="23.0225, 72.5714"', $html);
        $this->assertStringContainsString('"@type":"LocalBusiness"', $html);
        $this->assertStringContainsString('https://schema.org/Monday', $html);
        $this->assertStringNotContainsString('schema.org/Sunday', $html);
        $this->assertStringContainsString('"duration":"PT1M30S"', $html);
        $this->assertStringContainsString('"isFamilyFriendly":false', $html);
        $this->assertStringContainsString('\u003C/script\u003E', $html);
    }

    public function test_sitemaps_are_well_formed_and_follow_public_specs(): void
    {
        $page = new SitemapUrl(
            loc: 'https://example.com/posts/laravel-seo',
            lastmod: new \DateTimeImmutable('2026-10-29T10:00:00+00:00'),
            changefreq: 'weekly',
            priority: '0.8',
            images: ['https://example.com/cover.jpg'],
            title: 'Laravel SEO',
            description: 'How the package works.',
            news: new NewsMeta(
                title: 'Laravel SEO',
                publishedAt: new \DateTimeImmutable('2026-10-29T10:00:00+00:00'),
                keywords: ['laravel'],
                genres: ['Blog', 'MadeUp'],
                stockTickers: ['NASDAQ:AAPL', 'bad ticker'],
            ),
            videos: [new VideoMeta(
                title: 'Walkthrough',
                description: 'A short product walkthrough.',
                thumbnailUrl: 'https://example.com/thumb.jpg',
                contentUrl: 'https://example.com/video.mp4',
                duration: 90,
                tags: ['demo'],
            )],
        );

        $pages = (new XmlSitemap)->pages([$page]);
        $news = (new NewsSitemap)->render([$page], 'Example Times', 'en');
        $video = (new VideoSitemap)->render([$page]);
        $index = (new XmlSitemap)->index([
            ['loc' => 'https://example.com/sitemap-pages.xml'],
        ]);

        foreach ([$pages, $news, $video, $index] as $xml) {
            $dom = new \DOMDocument;
            $this->assertTrue($dom->loadXML($xml));
        }

        $this->assertStringContainsString('<image:loc>https://example.com/cover.jpg</image:loc>', $pages);
        $this->assertStringContainsString('<news:name>Example Times</news:name>', $news);
        $this->assertStringContainsString('<news:genres>Blog</news:genres>', $news);
        $this->assertStringNotContainsString('MadeUp', $news);
        $this->assertStringContainsString('<news:stock_tickers>NASDAQ:AAPL</news:stock_tickers>', $news);
        $this->assertStringNotContainsString('bad ticker', $news);
        $this->assertStringContainsString('<video:duration>90</video:duration>', $video);
        $this->assertStringContainsString('<video:family_friendly>yes</video:family_friendly>', $video);
        $this->assertStringContainsString('<video:tag>demo</video:tag>', $video);
    }

    public function test_robots_llms_and_kml(): void
    {
        $robots = (new RobotsTxt)->render([
            'robots' => [
                'include_sitemap' => true,
                'groups' => [
                    ['user_agent' => '*', 'allow' => ['/'], 'disallow' => ['/admin']],
                ],
                'ai_bots' => ['block' => ['GPTBot']],
            ],
        ], 'https://example.com/sitemap.xml');

        $this->assertStringContainsString("Disallow: /admin\n", $robots);
        $this->assertStringContainsString("User-agent: GPTBot\nDisallow: /", $robots);
        $this->assertStringContainsString('Sitemap: https://example.com/sitemap.xml', $robots);

        $llms = (new LlmsTxt)->render('Example', 'Notes', [
            new SitemapUrl(loc: 'https://example.com/guides', title: 'Guides]', description: 'Start here'),
        ]);
        $this->assertStringContainsString('# Example', $llms);
        $this->assertStringContainsString('> Notes', $llms);
        $this->assertStringContainsString('[Guides](https://example.com/guides): Start here', $llms);

        $kml = (new KmlDocument)->render([
            ['name' => 'Studio', 'latitude' => 23.0225, 'longitude' => 72.5714, 'address' => '1 Main'],
        ]);
        $this->assertStringContainsString('<coordinates>72.5714,23.0225,0</coordinates>', $kml);
    }

    public function test_content_analysis_and_suggestions(): void
    {
        $html = '<p>Laravel SEO helps teams publish cleaner pages. For example, a sitemap tells Google what changed. Then a schema graph explains the business.</p><h2>Laravel SEO checklist</h2><p>Write a clear introduction.</p>';
        $title = 'Laravel SEO '.str_repeat('a', 38);
        $this->assertSame(50, mb_strlen($title));

        $seo = new SeoData(
            title: $title,
            description: str_repeat('Laravel SEO improves discovery. ', 5),
            url: 'https://example.com/laravel-seo',
            keyphrases: ['Laravel SEO', 'sitemap'],
        );

        $report = (new ContentAnalyzer)->analyze($html, $seo);
        $this->assertSame('good', $this->checkStatus($report, 'title-length'));
        $this->assertSame('good', $this->checkStatus($report, 'keyphrase-title'));
        $this->assertSame('good', $this->checkStatus($report, 'keyphrase-url'));
        $this->assertSame('good', $this->checkStatus($report, 'related-keyphrases'));

        $passive = (new ContentAnalyzer)->analyze('<p>The ball was kicked.</p>', new SeoData(title: $title, description: str_repeat('word ', 30)));
        $this->assertSame('poor', $this->checkStatus($passive, 'passive-voice'));

        $suggestion = (new MetadataSuggester)->description('Laravel SEO is a package. It renders meta tags.');
        $this->assertStringStartsWith('Laravel SEO is a package.', $suggestion);
        $this->assertLessThanOrEqual(155, mb_strlen($suggestion));
    }

    public function test_orphan_counts_ignore_self_links(): void
    {
        $counts = (new OrphanFinder)->countFromHtml([
            ['url' => 'https://example.com/a', 'html' => '<a href="https://example.com/b">B</a><a href="https://example.com/a">Self</a>'],
            ['url' => 'https://example.com/b', 'html' => '<a href="/a">A</a>'],
        ]);

        $this->assertSame(1, $counts['https://example.com/a']);
        $this->assertSame(1, $counts['https://example.com/b']);
    }

    public function test_duration_and_plain_text(): void
    {
        $this->assertSame('PT1M30S', Text::duration(90));
        $this->assertSame('PT1H', Text::duration(3600));
        $this->assertSame('Hello world', Text::plain('<style>x</style><p>Hello</p> <script>alert(1)</script> world'));
    }

    public function test_schema_graph_links_article_to_the_page(): void
    {
        $graph = (new SchemaBuilder)->graph(
            new SeoData(
                title: 'Story',
                description: 'News copy',
                canonical: 'https://example.com/story',
                type: 'article',
                news: new NewsMeta('Story', new \DateTimeImmutable('2026-10-29T10:00:00+00:00'), ['markets', 'laravel']),
            ),
            ['name' => 'Example', 'type' => 'Organization'],
            [],
            'https://example.com',
            'Example',
            'Story',
        );

        $types = array_column($graph['@graph'], '@type');
        $this->assertContains('NewsArticle', $types);
        $this->assertContains('WebPage', $types);
        $this->assertSame('https://schema.org', $graph['@context']);

        $article = collect($graph['@graph'])->firstWhere('@type', 'NewsArticle');
        $this->assertSame('markets, laravel', $article['keywords'] ?? null);
    }

    public function test_website_search_action_local_hours_and_redirect_rules(): void
    {
        $graph = (new SchemaBuilder)->graph(
            new SeoData(title: 'Home', canonical: 'https://example.com'),
            ['name' => 'Example', 'type' => 'Organization'],
            [],
            'https://example.com',
            'Example',
            'Home',
            'https://example.com/search?q={search_term_string}',
        );

        $website = collect($graph['@graph'])->firstWhere('@type', 'WebSite');
        $this->assertSame('SearchAction', $website['potentialAction']['@type']);

        $hours = [
            ['day' => 'Monday', 'opens' => '09:00', 'closes' => '17:00'],
            ['day' => 'Sunday', 'closed' => true],
        ];
        $this->assertTrue(OpeningHours::isOpen($hours, new \DateTimeImmutable('2026-10-26 10:00:00')));
        $this->assertFalse(OpeningHours::isOpen($hours, new \DateTimeImmutable('2026-10-26 18:00:00')));
        $this->assertFalse(OpeningHours::isOpen($hours, new \DateTimeImmutable('2026-11-01 10:00:00')));

        $redirect = new Redirect;
        $redirect->forceFill([
            'source' => '/old',
            'destination' => '/new',
            'status_code' => 301,
            'is_regex' => false,
            'is_active' => true,
        ]);
        $rules = new ServerRedirectExporter;
        $this->assertStringContainsString('RewriteRule ^/old$ /new [R=301,L]', $rules->apache([$redirect]));
        $this->assertStringContainsString('return 301 /new', $rules->nginx([$redirect]));
    }

    private function checkStatus(Report $report, string $id): string
    {
        foreach ($report->checks as $check) {
            if ($check->id === $id) {
                return $check->status;
            }
        }

        $this->fail('Missing check '.$id);
    }
}
