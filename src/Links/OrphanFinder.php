<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Links;

use Illuminate\Support\Collection;
use Saazify\LaravelSeo\Models\SeoRecord;

final class OrphanFinder
{
    /**
     * @return Collection<int, SeoRecord>
     */
    public function find(int $limit = 50): Collection
    {
        return SeoRecord::query()
            ->where('include_in_sitemap', true)
            ->where('inbound_links', 0)
            ->where('robots', 'not like', '%noindex%')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  iterable<int, array{url: string, html: string}>  $pages
     * @return array<string, int>
     */
    public function countFromHtml(iterable $pages): array
    {
        $counts = [];
        $documents = [];

        foreach ($pages as $page) {
            $url = $this->normalize($page['url']);
            $counts[$url] = 0;
            $documents[$url] = $page['html'];
        }

        foreach ($documents as $from => $html) {
            preg_match_all('/href=["\']([^"\']+)["\']/i', $html, $matches);

            foreach ($matches[1] as $href) {
                $to = $this->normalize($href, $from);

                if ($to === $from || ! array_key_exists($to, $counts)) {
                    continue;
                }

                $counts[$to]++;
            }
        }

        return $counts;
    }

    /**
     * @param  iterable<int, array{url: string, html: string}>  $pages
     */
    public function sync(iterable $pages): int
    {
        $updated = 0;

        foreach ($this->countFromHtml($pages) as $url => $count) {
            $updated += SeoRecord::query()->where('canonical', $url)->update(['inbound_links' => $count]);
        }

        return $updated;
    }

    private function normalize(string $url, ?string $base = null): string
    {
        $url = trim($url);

        if ($url === '' || str_starts_with($url, '#') || str_starts_with(strtolower($url), 'mailto:')) {
            return $url;
        }

        if (str_starts_with($url, '/') && is_string($base)) {
            $scheme = parse_url($base, PHP_URL_SCHEME);
            $host = parse_url($base, PHP_URL_HOST);

            if (is_string($scheme) && is_string($host) && $scheme !== '' && $host !== '') {
                $url = $scheme.'://'.$host.$url;
            }
        }

        $parts = parse_url($url);

        if (! is_array($parts)) {
            return $url;
        }

        $path = $parts['path'] ?? '/';
        $path = $path === '' ? '/' : $path;
        $path = $path === '/' ? '/' : rtrim($path, '/');

        if (isset($parts['host'])) {
            return strtolower((string) $parts['scheme']).'://'.strtolower((string) $parts['host']).$path;
        }

        return $path;
    }
}
