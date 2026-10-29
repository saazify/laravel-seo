<?php

namespace Saazify\LaravelSeo\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Saazify\LaravelSeo\Data\SeoData;
use Saazify\LaravelSeo\Exceptions\InvalidRedirectException;
use Saazify\LaravelSeo\Http\Middleware\RedirectMiddleware;
use Saazify\LaravelSeo\IndexNow\IndexNowPinger;
use Saazify\LaravelSeo\Links\LinkSuggester;
use Saazify\LaravelSeo\Models\Location;
use Saazify\LaravelSeo\Models\Redirect;
use Saazify\LaravelSeo\Models\SeoRecord;
use Saazify\LaravelSeo\Models\Video;
use Saazify\LaravelSeo\Sitemap\SitemapRegistry;
use Saazify\LaravelSeo\Tests\Fixtures\Post;
use Saazify\LaravelSeo\Tests\TestCase;

class SeoPackageTest extends TestCase
{
    public function test_blade_directives_compile_to_render_calls(): void
    {
        $compiled = app('blade.compiler')->compileString('@seoHead @seoBreadcrumbs');

        $this->assertStringContainsString('SeoManager::class)->render()', $compiled);
        $this->assertStringContainsString('breadcrumbsHtml()', $compiled);
    }

    public function test_manager_renders_a_branded_title_and_home_breadcrumb(): void
    {
        config(['seo.site_name' => 'Example']);

        seo()->title('Hello')->breadcrumbs([
            ['name' => 'Guides'],
        ]);

        $html = seo()->render();

        $this->assertStringContainsString('<title>Hello | Example</title>', $html);
        $this->assertSame('Home', seo()->data()->breadcrumbs[0]->name);
        $this->assertSame('https://example.com/', seo()->data()->breadcrumbs[0]->url);
        $this->assertStringContainsString('Guides', seo()->breadcrumbsHtml());
    }

    public function test_sitemap_routes_include_pages_news_and_video(): void
    {
        $this->createPostsTable();

        Post::query()->create([
            'title' => 'Fresh Laravel SEO story',
            'slug' => 'fresh-story',
            'body' => 'A news story about Laravel SEO that explains the release.',
            'is_news' => true,
            'published_at' => now(),
        ]);

        Post::query()->create([
            'title' => 'Stale Laravel SEO story',
            'slug' => 'stale-story',
            'body' => 'An old news story that should leave the news sitemap.',
            'is_news' => true,
            'published_at' => now()->subDays(10),
        ]);

        Post::query()->create([
            'title' => 'Hidden page',
            'slug' => 'hidden',
            'body' => 'Should stay out of the sitemap.',
            'is_news' => false,
            'published_at' => now()->subDays(10),
        ]);

        $hidden = Post::query()->where('slug', 'hidden')->first();
        $hidden->seoRecord()->create([
            'robots' => 'noindex, follow',
            'include_in_sitemap' => false,
        ]);

        Video::query()->create([
            'page_url' => 'https://example.com/posts/fresh-story',
            'title' => 'Fresh story video',
            'description' => 'Video cut of the fresh story.',
            'thumbnail_url' => 'https://example.com/thumb.jpg',
            'content_url' => 'https://example.com/fresh.mp4',
            'duration' => 42,
            'family_friendly' => true,
            'published_at' => now(),
        ]);

        app(SitemapRegistry::class)->register('posts', fn () => Post::query()->get());

        config([
            'seo.news.publication_name' => 'Example Times',
            'seo.site_name' => 'Example',
            'seo.tagline' => 'Notes',
        ]);

        $index = $this->get('/sitemap.xml');
        $index->assertOk();
        $index->assertHeader('content-type', 'application/xml; charset=UTF-8');
        $this->assertStringContainsString('https://example.com/sitemap-pages.xml', $index->getContent());
        $this->assertStringContainsString('https://example.com/sitemap-news.xml', $index->getContent());
        $this->assertStringContainsString('https://example.com/sitemap-video.xml', $index->getContent());

        $pages = $this->get('/sitemap-pages.xml')->assertOk()->getContent();
        $this->assertStringContainsString('https://example.com/posts/fresh-story', $pages);
        $this->assertStringNotContainsString('https://example.com/posts/hidden', $pages);
        $this->assertStringContainsString('<image:loc>https://example.com/images/fresh-story.jpg</image:loc>', $pages);

        $news = $this->get('/sitemap-news.xml')->assertOk()->getContent();
        $this->assertStringContainsString('<news:name>Example Times</news:name>', $news);
        $this->assertStringContainsString('<news:genres>Blog</news:genres>', $news);
        $this->assertStringNotContainsString('NotAGenre', $news);
        $this->assertStringNotContainsString('stale-story', $news);
        $this->assertStringNotContainsString('hidden', $news);

        $video = $this->get('/sitemap-video.xml')->assertOk()->getContent();
        $this->assertStringContainsString('<video:content_loc>https://example.com/fresh.mp4</video:content_loc>', $video);
        $this->assertStringContainsString('<video:duration>42</video:duration>', $video);

        $llms = $this->get('/llms.txt')->assertOk()->getContent();
        $this->assertStringContainsString('# Example', $llms);
        $this->assertStringContainsString('> Notes', $llms);
        $this->assertStringContainsString('https://example.com/posts/fresh-story', $llms);
    }

    public function test_robots_can_block_an_ai_crawler(): void
    {
        config(['seo.robots.ai_bots.block' => ['GPTBot']]);

        $body = $this->get('/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString('User-agent: GPTBot', $body);
        $this->assertStringContainsString('Sitemap: https://example.com/sitemap.xml', $body);
    }

    public function test_primary_location_is_used_for_schema_and_kml(): void
    {
        config(['seo.site_name' => 'Example']);

        Location::query()->create([
            'name' => 'Studio',
            'is_primary' => true,
            'business_type' => 'LocalBusiness',
            'street' => '1 Main',
            'city' => 'Ahmedabad',
            'region' => 'GJ',
            'country' => 'IN',
            'latitude' => 23.0225,
            'longitude' => 72.5714,
            'opening_hours' => [
                ['day' => 'Monday', 'opens' => '09:00', 'closes' => '17:00'],
                ['day' => 'Sunday', 'closed' => true],
            ],
        ]);

        $html = seo()->title('Visit')->canonical('https://example.com/visit')->render();
        $this->assertStringContainsString('"@type":"LocalBusiness"', $html);
        $this->assertStringContainsString('Ahmedabad', $html);
        $this->assertStringContainsString('name="geo.placename" content="Ahmedabad"', $html);

        $kml = $this->get('/locations.kml')->assertOk()->getContent();
        $this->assertStringContainsString('<coordinates>72.5714,23.0225,0</coordinates>', $kml);
    }

    public function test_redirects_cover_exact_regex_gone_and_unsafe_targets(): void
    {
        Route::middleware(RedirectMiddleware::class)->group(function () {
            Route::get('/old-page', fn () => 'old');
            Route::get('/kept', fn () => 'kept');
            Route::get('/old/{rest}', fn () => 'regex')->where('rest', '.*');
            Route::get('/gone', fn () => 'gone');
            Route::get('/loop', fn () => 'stayed');
            Route::get('/safe', fn () => 'safe');
        });

        Redirect::query()->create([
            'source' => '/old-page',
            'destination' => '/kept',
            'status_code' => 301,
        ]);
        Redirect::query()->create([
            'source' => '/old/(.*)',
            'destination' => '/new/$1',
            'status_code' => 302,
            'is_regex' => true,
        ]);
        Redirect::query()->create([
            'source' => '/gone',
            'status_code' => 410,
        ]);
        Redirect::query()->create([
            'source' => '/loop',
            'destination' => '/loop',
        ]);
        Redirect::query()->create([
            'source' => '/safe',
            'destination' => 'https://evil.test/phish',
        ]);

        $this->get('/old-page')->assertRedirect('/kept')->assertStatus(301);
        $this->assertSame(1, (int) Redirect::query()->where('source', '/old-page')->value('hits'));

        $this->get('/old/hello')->assertRedirect('/new/hello')->assertStatus(302);
        $this->get('/gone')->assertStatus(410);
        $this->get('/loop')->assertOk()->assertSee('stayed');
        $this->get('/safe')->assertOk()->assertSee('safe');

        $this->expectException(InvalidRedirectException::class);
        Redirect::query()->create([
            'source' => '/js',
            'destination' => 'javascript:alert(1)',
        ]);
    }

    public function test_redirect_csv_round_trip_and_keyphrase_export(): void
    {
        Redirect::query()->create([
            'source' => '/from',
            'destination' => '/to',
            'status_code' => 301,
        ]);

        $path = tempnam(sys_get_temp_dir(), 'seo-redirects');
        $this->assertNotFalse($path);
        $this->artisan('seo:redirects-export', ['path' => $path])->assertOk();

        Redirect::query()->delete();
        file_put_contents($path, file_get_contents($path)."\n,bad,301,0,1\n");
        $this->artisan('seo:redirects-import', ['path' => $path])->assertOk();
        $this->assertDatabaseHas('seo_redirects', ['source' => '/from', 'destination' => '/to']);
        $this->assertSame(1, Redirect::query()->count());

        SeoRecord::query()->create([
            'seoable_type' => Post::class,
            'seoable_id' => 1,
            'title' => 'Laravel SEO',
            'canonical' => 'https://example.com/posts/laravel-seo',
            'keyphrases' => ['Laravel SEO', 'sitemap'],
            'cornerstone' => true,
        ]);

        $phrases = tempnam(sys_get_temp_dir(), 'seo-phrases');
        $this->assertNotFalse($phrases);
        $this->artisan('seo:keyphrases-export', ['path' => $phrases])->assertOk();
        $csv = file_get_contents($phrases);
        $this->assertIsString($csv);
        $this->assertStringContainsString('Laravel SEO|sitemap', $csv);

        $suggestions = (new LinkSuggester)->suggest('sitemap');
        $this->assertCount(1, $suggestions);
        $this->assertSame('Laravel SEO', $suggestions->first()->title);
    }

    public function test_indexnow_posts_the_public_payload(): void
    {
        Http::fake([
            'api.indexnow.org/*' => Http::response('', 200),
        ]);

        $pinger = new IndexNowPinger('abcd1234efgh5678', 'example.com', Http::getFacadeRoot());

        $this->assertTrue($pinger->ping(['https://example.com/a']));
        $this->assertFalse((new IndexNowPinger('', 'example.com', Http::getFacadeRoot()))->ping(['https://example.com/a']));

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.indexnow.org/indexnow'
                && $request['host'] === 'example.com'
                && $request['key'] === 'abcd1234efgh5678'
                && $request['urlList'] === ['https://example.com/a'];
        });
    }

    public function test_saved_videos_are_rendered_with_the_model(): void
    {
        $this->createPostsTable();
        $post = Post::query()->create([
            'title' => 'Clip page',
            'slug' => 'clip',
            'body' => 'A page with a stored video.',
            'is_news' => false,
        ]);
        $post->seoVideos()->create([
            'page_url' => 'https://example.com/posts/clip',
            'title' => 'Clip',
            'description' => 'A clip stored on the post.',
            'thumbnail_url' => 'https://example.com/thumb.jpg',
            'content_url' => 'https://example.com/clip.mp4',
            'player_url' => 'https://example.com/embed/clip',
            'duration' => 15,
            'width' => 1280,
            'height' => 720,
        ]);

        config(['seo.site_name' => 'Example']);
        $html = seo()->for($post->fresh())->canonical('https://example.com/posts/clip')->render();

        $this->assertStringContainsString('property="og:video" content="https://example.com/clip.mp4"', $html);
        $this->assertStringContainsString('name="twitter:card" content="player"', $html);
        $this->assertStringContainsString('name="twitter:player" content="https://example.com/embed/clip"', $html);
        $this->assertStringContainsString('"@type":"VideoObject"', $html);
    }

    public function test_model_seo_data_prefers_the_saved_record(): void
    {
        $this->createPostsTable();
        $post = Post::query()->create([
            'title' => 'Original',
            'slug' => 'original',
            'body' => 'Body copy',
            'is_news' => false,
        ]);
        $post->seoRecord()->create([
            'title' => 'Custom SEO title',
            'description' => 'Custom description',
            'keyphrases' => ['custom phrase'],
        ]);

        $data = $post->fresh()->toSeoData();

        $this->assertInstanceOf(SeoData::class, $data);
        $this->assertSame('Custom SEO title', $data->title);
        $this->assertSame(['custom phrase'], $data->keyphrases);
        $this->assertSame('https://example.com/posts/original', $data->url);
    }

    private function createPostsTable(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug');
            $table->text('body')->nullable();
            $table->boolean('is_news')->default(false);
            $table->timestamp('published_at')->nullable();
        });
    }
}
