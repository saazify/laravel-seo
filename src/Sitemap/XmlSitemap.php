<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Sitemap;

use Saazify\LaravelSeo\Data\SitemapUrl;
use Saazify\LaravelSeo\Support\Text;
use Saazify\LaravelSeo\Support\Xml;

final class XmlSitemap
{
    /**
     * @param  list<array{loc: string, lastmod?: string|null}>  $sitemaps
     */
    public function index(array $sitemaps): string
    {
        $xml = Xml::open('sitemapindex', [
            'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
        ]);

        foreach ($sitemaps as $sitemap) {
            $xml->startElement('sitemap');
            Xml::element($xml, 'loc', $sitemap['loc']);
            Xml::element($xml, 'lastmod', $sitemap['lastmod'] ?? null);
            $xml->endElement();
        }

        return Xml::close($xml);
    }

    /**
     * @param  list<SitemapUrl>  $urls
     */
    public function pages(array $urls): string
    {
        $xml = Xml::open('urlset', [
            'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
            'xmlns:image' => 'http://www.google.com/schemas/sitemap-image/1.1',
        ]);

        foreach ($urls as $url) {
            if (! $url->indexable || $url->loc === '') {
                continue;
            }

            $xml->startElement('url');
            Xml::element($xml, 'loc', $url->loc);
            Xml::element($xml, 'lastmod', Text::atom($url->lastmod));
            Xml::element($xml, 'changefreq', $url->changefreq);
            Xml::element($xml, 'priority', $url->priority);

            foreach ($url->images as $image) {
                $xml->startElement('image:image');
                Xml::element($xml, 'image:loc', $image);
                $xml->endElement();
            }

            $xml->endElement();
        }

        return Xml::close($xml);
    }
}
