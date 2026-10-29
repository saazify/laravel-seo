<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Data;

use Saazify\LaravelSeo\Support\Url;

final readonly class SitemapUrl
{
    /**
     * @param  list<string>  $images
     * @param  list<VideoMeta>  $videos
     */
    public function __construct(
        public string $loc,
        public ?\DateTimeInterface $lastmod = null,
        public ?string $changefreq = null,
        public ?string $priority = null,
        public array $images = [],
        public ?string $title = null,
        public ?string $description = null,
        public ?NewsMeta $news = null,
        public array $videos = [],
        public bool $indexable = true,
    ) {}

    /**
     * @param  array<string, mixed>  $page
     */
    public static function fromArray(array $page, string $base): ?self
    {
        $loc = isset($page['loc']) ? trim((string) $page['loc']) : '';

        if ($loc === '') {
            return null;
        }

        return new self(
            loc: Url::absolute($loc, $base),
            lastmod: $page['lastmod'] ?? null,
            changefreq: isset($page['changefreq']) ? (string) $page['changefreq'] : null,
            priority: isset($page['priority']) ? (string) $page['priority'] : null,
            images: array_values(array_filter((array) ($page['images'] ?? []))),
            title: isset($page['title']) ? (string) $page['title'] : null,
            description: isset($page['description']) ? (string) $page['description'] : null,
            indexable: (bool) ($page['indexable'] ?? true),
        );
    }

    public static function fromSeoData(SeoData $data, string $base): ?self
    {
        $loc = $data->canonical ?: $data->url;

        if ($loc === null || trim($loc) === '') {
            return null;
        }

        $images = [];

        if ($data->image) {
            $images[] = $data->image;
        }

        foreach ($data->images as $image) {
            if (is_string($image) && $image !== '') {
                $images[] = $image;
            }
        }

        return new self(
            loc: Url::absolute($loc, $base),
            lastmod: $data->modifiedAt ?? $data->publishedAt,
            changefreq: $data->sitemapChangefreq,
            priority: $data->sitemapPriority !== null
                ? number_format($data->sitemapPriority, 1, '.', '')
                : ($data->cornerstone ? '1.0' : null),
            images: array_values(array_unique($images)),
            title: $data->title,
            description: $data->description,
            news: $data->news,
            videos: $data->videos,
            indexable: $data->includeInSitemap && ! str_contains(strtolower($data->robots), 'noindex'),
        );
    }
}
