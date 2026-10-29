<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Saazify\LaravelSeo\Llms\LlmsTxt;
use Saazify\LaravelSeo\Local\KmlDocument;
use Saazify\LaravelSeo\Models\Location;
use Saazify\LaravelSeo\Robots\RobotsTxt;
use Saazify\LaravelSeo\Sitemap\NewsSitemap;
use Saazify\LaravelSeo\Sitemap\SitemapRepository;
use Saazify\LaravelSeo\Sitemap\VideoSitemap;
use Saazify\LaravelSeo\Sitemap\XmlSitemap;

class SeoController extends Controller
{
    public function index(SitemapRepository $repository, XmlSitemap $sitemap): Response
    {
        $entries = [
            ['loc' => $this->absolute('/sitemap-pages.xml')],
        ];

        if ($repository->news() !== []) {
            $entries[] = ['loc' => $this->absolute('/sitemap-news.xml')];
        }

        if ($repository->videos() !== []) {
            $entries[] = ['loc' => $this->absolute('/sitemap-video.xml')];
        }

        return $this->xml($sitemap->index($entries));
    }

    public function pages(SitemapRepository $repository, XmlSitemap $sitemap): Response
    {
        return $this->xml($sitemap->pages($repository->pages()));
    }

    public function news(SitemapRepository $repository, NewsSitemap $sitemap): Response
    {
        return $this->xml($sitemap->render(
            $repository->news(),
            (string) config('seo.news.publication_name'),
            (string) config('seo.news.language', 'en'),
        ));
    }

    public function videos(SitemapRepository $repository, VideoSitemap $sitemap): Response
    {
        return $this->xml($sitemap->render($repository->videos()));
    }

    public function robots(RobotsTxt $robots): Response
    {
        $body = $robots->render(config('seo'), $this->absolute('/sitemap.xml'));

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function llms(SitemapRepository $repository, LlmsTxt $llms): Response
    {
        $body = $llms->render(
            (string) config('seo.site_name'),
            (string) config('seo.tagline'),
            $repository->llms(),
        );

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function kml(KmlDocument $kml): Response
    {
        $places = [];

        try {
            if (Schema::hasTable('seo_locations')) {
                foreach (Location::query()->where('is_active', true)->orderBy('id')->get() as $location) {
                    $places[] = [
                        'name' => (string) $location->name,
                        'description' => $location->description,
                        'latitude' => $location->latitude,
                        'longitude' => $location->longitude,
                        'address' => implode(', ', array_filter([
                            $location->street,
                            $location->city,
                            $location->region,
                            $location->postal_code,
                            $location->country,
                        ])),
                    ];
                }
            }
        } catch (\Throwable) {
            $places = [];
        }

        if ($places === [] && is_numeric(config('seo.organization.geo.latitude')) && is_numeric(config('seo.organization.geo.longitude'))) {
            $address = config('seo.organization.address', []);
            $places[] = [
                'name' => (string) config('seo.organization.name'),
                'description' => config('seo.organization.description'),
                'latitude' => config('seo.organization.geo.latitude'),
                'longitude' => config('seo.organization.geo.longitude'),
                'address' => is_array($address) ? implode(', ', array_filter($address)) : '',
            ];
        }

        return $this->xml($kml->render($places), 'application/vnd.google-earth.kml+xml');
    }

    public function indexNowKey(): Response
    {
        $key = (string) config('seo.indexnow.key');

        abort_if($key === '', 404);

        return response($key."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private function xml(string $body, string $type = 'application/xml'): Response
    {
        $headers = ['Content-Type' => $type.'; charset=UTF-8'];
        $cache = (int) config('seo.sitemap.cache_seconds', 0);

        if ($cache > 0) {
            $headers['Cache-Control'] = 'public, max-age='.$cache;
        }

        return response($body, 200, $headers);
    }

    private function absolute(string $path): string
    {
        return rtrim((string) config('seo.site_url'), '/').$path;
    }
}
