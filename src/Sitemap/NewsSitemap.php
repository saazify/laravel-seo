<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Sitemap;

use Saazify\LaravelSeo\Data\SitemapUrl;
use Saazify\LaravelSeo\Support\Text;
use Saazify\LaravelSeo\Support\Xml;

final class NewsSitemap
{
    /** @var list<string> */
    private const GENRES = [
        'PressRelease',
        'Satire',
        'Blog',
        'OpEd',
        'Opinion',
        'UserGenerated',
    ];

    /**
     * @param  list<SitemapUrl>  $urls
     */
    public function render(array $urls, string $publication, string $language): string
    {
        $xml = Xml::open('urlset', [
            'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
            'xmlns:news' => 'http://www.google.com/schemas/sitemap-news/0.9',
        ]);

        foreach ($urls as $url) {
            if ($url->news === null || $url->loc === '') {
                continue;
            }

            $xml->startElement('url');
            Xml::element($xml, 'loc', $url->loc);
            $xml->startElement('news:news');
            $xml->startElement('news:publication');
            Xml::element($xml, 'news:name', $publication);
            Xml::element($xml, 'news:language', $language);
            $xml->endElement();
            Xml::element($xml, 'news:publication_date', Text::atom($url->news->publishedAt));
            Xml::element($xml, 'news:title', Text::limit($url->news->title, 150));

            $keywords = array_slice(array_values(array_filter($url->news->keywords)), 0, 10);

            if ($keywords !== []) {
                Xml::element($xml, 'news:keywords', implode(', ', $keywords));
            }

            $genres = array_values(array_intersect($url->news->genres, self::GENRES));

            if ($genres !== []) {
                Xml::element($xml, 'news:genres', implode(', ', $genres));
            }

            $tickers = array_values(array_filter(
                $url->news->stockTickers,
                fn ($ticker) => is_string($ticker) && preg_match('/^[A-Za-z0-9:.\-]+$/', $ticker) === 1
            ));

            if ($tickers !== []) {
                Xml::element($xml, 'news:stock_tickers', implode(', ', $tickers));
            }

            $xml->endElement();
            $xml->endElement();
        }

        return Xml::close($xml);
    }
}
