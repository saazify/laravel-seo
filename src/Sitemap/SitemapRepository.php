<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Sitemap;

use Illuminate\Support\Facades\Schema;
use Saazify\LaravelSeo\Data\SeoData;
use Saazify\LaravelSeo\Data\SitemapUrl;
use Saazify\LaravelSeo\Data\VideoMeta;
use Saazify\LaravelSeo\Models\Video;
use Saazify\LaravelSeo\Support\Url;

final class SitemapRepository
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private SitemapRegistry $registry,
        private array $config,
    ) {}

    /**
     * @return list<SitemapUrl>
     */
    public function pages(): array
    {
        return $this->dedupe($this->collect(false));
    }

    /**
     * @return list<SitemapUrl>
     */
    public function news(): array
    {
        $hours = (int) ($this->config['news']['max_age_hours'] ?? 48);
        $max = (int) ($this->config['news']['max_urls'] ?? 1000);
        $cutoff = new \DateTimeImmutable('-'.$hours.' hours');
        $urls = [];

        foreach ($this->dedupe($this->collect(true)) as $url) {
            if ($url->news === null) {
                continue;
            }

            if ($url->news->publishedAt < $cutoff) {
                continue;
            }

            $urls[] = $url;
        }

        usort($urls, fn (SitemapUrl $a, SitemapUrl $b) => $b->news->publishedAt <=> $a->news->publishedAt);

        return array_slice($urls, 0, max(1, $max));
    }

    /**
     * @return list<SitemapUrl>
     */
    public function videos(): array
    {
        $urls = [];

        foreach ($this->dedupe($this->collect(true)) as $url) {
            $valid = array_values(array_filter($url->videos, fn (VideoMeta $video) => $video->isValid()));

            if ($valid === []) {
                continue;
            }

            $urls[] = new SitemapUrl(
                loc: $url->loc,
                lastmod: $url->lastmod,
                videos: $valid,
            );
        }

        return $urls;
    }

    /**
     * @return list<SitemapUrl>
     */
    public function llms(): array
    {
        return $this->pages();
    }

    /**
     * @return list<SitemapUrl>
     */
    private function collect(bool $includeDatabaseVideos): array
    {
        $items = [];

        foreach ($this->config['sitemap']['pages'] ?? [] as $page) {
            if (! is_array($page)) {
                continue;
            }

            $url = SitemapUrl::fromArray($page, $this->base());

            if ($url !== null) {
                $items[] = $url;
            }
        }

        foreach ($this->registry->sources() as $source) {
            foreach ($source() as $item) {
                $url = $this->normalize($item);

                if ($url !== null) {
                    $items[] = $url;
                }
            }
        }

        if ($includeDatabaseVideos && $this->tableReady('seo_videos')) {
            foreach (Video::query()->orderBy('id')->lazy(200) as $video) {
                $meta = $video->toMeta();

                if (! $meta->isValid() || $video->page_url === null) {
                    continue;
                }

                $items[] = new SitemapUrl(
                    loc: Url::absolute((string) $video->page_url, $this->base()),
                    lastmod: $video->updated_at,
                    title: $video->title,
                    videos: [$meta],
                );
            }
        }

        return $items;
    }

    private function normalize(mixed $item): ?SitemapUrl
    {
        if ($item instanceof SitemapUrl) {
            return $item;
        }

        if ($item instanceof SeoData) {
            return SitemapUrl::fromSeoData($item, $this->base());
        }

        if (is_object($item) && method_exists($item, 'toSeoData')) {
            $data = $item->toSeoData();

            return $data instanceof SeoData ? SitemapUrl::fromSeoData($data, $this->base()) : null;
        }

        if (is_array($item) && isset($item['loc'])) {
            return SitemapUrl::fromArray($item, $this->base());
        }

        return null;
    }

    /**
     * @param  list<SitemapUrl>  $urls
     * @return list<SitemapUrl>
     */
    private function dedupe(array $urls): array
    {
        $byLoc = [];

        foreach ($urls as $url) {
            if (! $url->indexable || $url->loc === '') {
                continue;
            }

            if (! isset($byLoc[$url->loc])) {
                $byLoc[$url->loc] = $url;

                continue;
            }

            $existing = $byLoc[$url->loc];
            $byLoc[$url->loc] = new SitemapUrl(
                loc: $existing->loc,
                lastmod: $existing->lastmod ?? $url->lastmod,
                changefreq: $existing->changefreq ?? $url->changefreq,
                priority: $existing->priority ?? $url->priority,
                images: array_values(array_unique([...$existing->images, ...$url->images])),
                title: $existing->title ?: $url->title,
                description: $existing->description ?: $url->description,
                news: $existing->news ?? $url->news,
                videos: [...$existing->videos, ...$url->videos],
            );
        }

        return array_values($byLoc);
    }

    private function base(): string
    {
        return rtrim((string) ($this->config['site_url'] ?? ''), '/');
    }

    private function tableReady(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }
}
