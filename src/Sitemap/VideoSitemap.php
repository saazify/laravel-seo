<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Sitemap;

use Saazify\LaravelSeo\Data\SitemapUrl;
use Saazify\LaravelSeo\Data\VideoMeta;
use Saazify\LaravelSeo\Support\Text;
use Saazify\LaravelSeo\Support\Xml;

final class VideoSitemap
{
    /**
     * @param  list<SitemapUrl>  $urls
     */
    public function render(array $urls): string
    {
        $xml = Xml::open('urlset', [
            'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
            'xmlns:video' => 'http://www.google.com/schemas/sitemap-video/1.1',
        ]);

        foreach ($urls as $url) {
            $videos = array_values(array_filter($url->videos, fn (VideoMeta $video) => $video->isValid()));

            if ($videos === [] || $url->loc === '') {
                continue;
            }

            $xml->startElement('url');
            Xml::element($xml, 'loc', $url->loc);

            foreach ($videos as $video) {
                $xml->startElement('video:video');
                Xml::element($xml, 'video:thumbnail_loc', $video->thumbnailUrl);
                Xml::element($xml, 'video:title', Text::limit($video->title, 100));
                Xml::element($xml, 'video:description', Text::limit($video->description, 2048));
                Xml::element($xml, 'video:content_loc', $video->contentUrl);
                Xml::element($xml, 'video:player_loc', $video->playerUrl);
                Xml::element($xml, 'video:duration', $video->duration !== null ? (string) $video->duration : null);
                Xml::element($xml, 'video:publication_date', Text::atom($video->publishedAt));
                Xml::element($xml, 'video:family_friendly', $video->familyFriendly ? 'yes' : 'no');
                Xml::element($xml, 'video:requires_subscription', $video->requiresSubscription ? 'yes' : 'no');
                Xml::element($xml, 'video:live', $video->live ? 'yes' : 'no');

                foreach (array_slice($video->tags, 0, 32) as $tag) {
                    Xml::element($xml, 'video:tag', $tag);
                }

                $xml->endElement();
            }

            $xml->endElement();
        }

        return Xml::close($xml);
    }
}
