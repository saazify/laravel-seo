<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Rendering;

use Saazify\LaravelSeo\Data\Breadcrumb;
use Saazify\LaravelSeo\Data\SeoData;
use Saazify\LaravelSeo\Data\VideoMeta;
use Saazify\LaravelSeo\Support\Text;
use Saazify\LaravelSeo\Support\TitleComposer;

final class HeadRenderer
{
    public function __construct(
        private array $config,
        private TitleComposer $titles = new TitleComposer,
        private SchemaBuilder $schema = new SchemaBuilder,
    ) {}

    /**
     * @param  array<string, mixed>  $organization
     * @param  list<array<string, mixed>>  $branches
     */
    public function render(SeoData $page, array $organization = [], array $branches = []): string
    {
        $composed = $this->titles->compose($page, $this->config);
        $description = $page->socialDescription ?: $page->description;
        $image = $page->socialImage ?: $page->image;
        $socialTitle = $page->socialTitle ?: $composed;
        $canonical = $page->canonical ?: $page->url;
        $locale = $page->locale ?: (string) ($this->config['locale'] ?? 'en_US');
        $siteName = (string) ($this->config['site_name'] ?? '');
        $siteUrl = rtrim((string) ($this->config['site_url'] ?? ''), '/');
        $type = ($page->type === 'article' || $page->news !== null) ? 'article' : ($page->type ?: 'website');

        $html = [];
        $html[] = '<title>'.$this->escape($composed).'</title>';

        if ($page->description) {
            $html[] = $this->meta('name', 'description', $page->description);
        }

        if ($canonical) {
            $html[] = '<link rel="canonical" href="'.$this->escape($canonical).'">';
        }

        if ($page->robots !== '') {
            $html[] = $this->meta('name', 'robots', $page->robots);
        }

        if (($this->config['meta']['keywords'] ?? false) && $page->keywords !== []) {
            $html[] = $this->meta('name', 'keywords', implode(', ', $page->keywords));
        }

        foreach ($this->verification() as $name => $content) {
            $html[] = $this->meta('name', $name, $content);
        }

        $html[] = $this->property('og:title', $socialTitle);
        $html[] = $this->property('og:type', $type);
        $html[] = $this->property('og:site_name', $siteName);
        $html[] = $this->property('og:locale', $locale);

        if ($canonical) {
            $html[] = $this->property('og:url', $canonical);
        }

        if ($description) {
            $html[] = $this->property('og:description', $description);
        }

        if ($image) {
            $html[] = $this->property('og:image', $image);

            if ($page->imageWidth) {
                $html[] = $this->property('og:image:width', (string) $page->imageWidth);
            }

            if ($page->imageHeight) {
                $html[] = $this->property('og:image:height', (string) $page->imageHeight);
            }

            if ($page->imageAlt) {
                $html[] = $this->property('og:image:alt', $page->imageAlt);
            }
        }

        if ($type === 'article') {
            $published = Text::atom($page->news?->publishedAt ?? $page->publishedAt);

            if ($published) {
                $html[] = $this->property('article:published_time', $published);
            }

            $modified = Text::atom($page->modifiedAt);

            if ($modified) {
                $html[] = $this->property('article:modified_time', $modified);
            }

            if ($page->author) {
                $html[] = $this->property('article:author', $page->author);
            }

            if ($page->section) {
                $html[] = $this->property('article:section', $page->section);
            }
        }

        $video = $this->primaryVideo($page);
        $card = $video?->playerUrl ? 'player' : ($image ? 'summary_large_image' : 'summary');
        $html[] = $this->meta('name', 'twitter:card', $card);
        $html[] = $this->meta('name', 'twitter:title', $socialTitle);

        if ($description) {
            $html[] = $this->meta('name', 'twitter:description', $description);
        }

        if ($image) {
            $html[] = $this->meta('name', 'twitter:image', $image);
        }

        array_push($html, ...$this->videoTags($video));

        $twitterSite = $this->config['meta']['twitter_site'] ?? null;

        if (is_string($twitterSite) && $twitterSite !== '') {
            $html[] = $this->meta('name', 'twitter:site', $twitterSite);
        }

        $twitterCreator = $this->config['meta']['twitter_creator'] ?? null;

        if (is_string($twitterCreator) && $twitterCreator !== '') {
            $html[] = $this->meta('name', 'twitter:creator', $twitterCreator);
        }

        $fbAppId = $this->config['meta']['fb_app_id'] ?? null;

        if (is_string($fbAppId) && $fbAppId !== '') {
            $html[] = $this->property('fb:app_id', $fbAppId);
        }

        foreach ($page->alternates as $lang => $href) {
            $html[] = '<link rel="alternate" hreflang="'.$this->escape((string) $lang).'" href="'.$this->escape($href).'">';
        }

        if ($page->prevUrl) {
            $html[] = '<link rel="prev" href="'.$this->escape($page->prevUrl).'">';
        }

        if ($page->nextUrl) {
            $html[] = '<link rel="next" href="'.$this->escape($page->nextUrl).'">';
        }

        if (($this->config['local']['geo_tags'] ?? false) === true) {
            array_push($html, ...$this->geoTags($organization));
        }

        $searchUrl = $this->config['website']['search_url'] ?? null;
        $graph = $this->schema->graph(
            $page,
            $organization,
            $branches,
            $siteUrl !== '' ? $siteUrl : ($canonical ?: ''),
            $siteName,
            $page->title ?: $composed,
            is_string($searchUrl) ? $searchUrl : null,
        );
        $json = json_encode(
            $graph,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_THROW_ON_ERROR
        );
        $html[] = '<script type="application/ld+json">'.$json.'</script>';

        return implode("\n", $html);
    }

    /**
     * @param  list<array{name: string, url: ?string}>|list<Breadcrumb>  $crumbs
     */
    public function breadcrumbs(array $crumbs): string
    {
        if ($crumbs === []) {
            return '';
        }

        $items = [];

        foreach ($crumbs as $crumb) {
            $name = is_object($crumb) ? $crumb->name : (string) ($crumb['name'] ?? '');
            $url = is_object($crumb) ? $crumb->url : ($crumb['url'] ?? null);
            $label = $this->escape($name);

            if (is_string($url) && $url !== '') {
                $items[] = '<li><a href="'.$this->escape($url).'">'.$label.'</a></li>';
            } else {
                $items[] = '<li><span aria-current="page">'.$label.'</span></li>';
            }
        }

        return '<nav aria-label="Breadcrumb"><ol class="seo-breadcrumbs">'.implode('', $items).'</ol></nav>';
    }

    /**
     * @return array<string, string>
     */
    private function verification(): array
    {
        $map = [
            'google' => 'google-site-verification',
            'bing' => 'msvalidate.01',
            'pinterest' => 'p:domain_verify',
            'yandex' => 'yandex-verification',
        ];
        $tags = [];

        foreach ($map as $key => $name) {
            $value = $this->config['verification'][$key] ?? null;

            if (is_string($value) && $value !== '') {
                $tags[$name] = $value;
            }
        }

        return $tags;
    }

    /**
     * @param  array<string, mixed>  $organization
     * @return list<string>
     */
    private function geoTags(array $organization): array
    {
        $geo = $organization['geo'] ?? [];
        $address = $organization['address'] ?? [];
        $tags = [];

        if (! is_array($geo) || ! is_numeric($geo['latitude'] ?? null) || ! is_numeric($geo['longitude'] ?? null)) {
            return [];
        }

        $lat = (string) $geo['latitude'];
        $lng = (string) $geo['longitude'];
        $tags[] = $this->meta('name', 'geo.position', $lat.';'.$lng);
        $tags[] = $this->meta('name', 'ICBM', $lat.', '.$lng);

        $city = is_array($address) ? (string) ($address['city'] ?? '') : '';

        if ($city !== '') {
            $tags[] = $this->meta('name', 'geo.placename', $city);
        }

        $region = $this->geoRegion(is_array($address) ? $address : []);

        if ($region !== null) {
            $tags[] = $this->meta('name', 'geo.region', $region);
        }

        return $tags;
    }

    /**
     * @param  array<string, mixed>  $address
     */
    private function geoRegion(array $address): ?string
    {
        $country = strtoupper(trim((string) ($address['country'] ?? '')));
        $region = trim((string) ($address['region'] ?? ''));

        if (preg_match('/^[A-Z]{2}$/', $country) !== 1) {
            return $region !== '' ? $region : null;
        }

        if ($region !== '' && preg_match('/^[A-Za-z0-9]{1,3}$/', $region) === 1) {
            return $country.'-'.strtoupper($region);
        }

        return $country;
    }

    private function primaryVideo(SeoData $page): ?VideoMeta
    {
        foreach ($page->videos as $video) {
            if ($video->isValid()) {
                return $video;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function videoTags(?VideoMeta $video): array
    {
        if ($video === null) {
            return [];
        }

        $tags = [];
        $content = $video->contentUrl ?: $video->playerUrl;

        if (is_string($content) && $content !== '') {
            $tags[] = $this->property('og:video', $content);

            if (str_starts_with($content, 'https://')) {
                $tags[] = $this->property('og:video:secure_url', $content);
            }

            $mime = $this->videoMime($content);

            if ($mime !== null) {
                $tags[] = $this->property('og:video:type', $mime);
            }
        }

        if ($video->width) {
            $tags[] = $this->property('og:video:width', (string) $video->width);
        }

        if ($video->height) {
            $tags[] = $this->property('og:video:height', (string) $video->height);
        }

        if (is_string($video->playerUrl) && $video->playerUrl !== '') {
            $tags[] = $this->meta('name', 'twitter:player', $video->playerUrl);

            if ($video->width) {
                $tags[] = $this->meta('name', 'twitter:player:width', (string) $video->width);
            }

            if ($video->height) {
                $tags[] = $this->meta('name', 'twitter:player:height', (string) $video->height);
            }
        }

        return $tags;
    }

    private function videoMime(string $url): ?string
    {
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));

        return match (true) {
            str_ends_with($path, '.mp4') => 'video/mp4',
            str_ends_with($path, '.webm') => 'video/webm',
            str_ends_with($path, '.ogv') => 'video/ogg',
            default => str_contains($url, '://') ? 'text/html' : null,
        };
    }

    private function meta(string $attribute, string $name, string $content): string
    {
        return '<meta '.$attribute.'="'.$this->escape($name).'" content="'.$this->escape($content).'">';
    }

    private function property(string $property, string $content): string
    {
        return $this->meta('property', $property, $content);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
