<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Llms;

use Saazify\LaravelSeo\Data\SitemapUrl;

final class LlmsTxt
{
    /**
     * @param  list<SitemapUrl>  $pages
     */
    public function render(string $siteName, string $tagline, array $pages): string
    {
        $lines = ['# '.$siteName, ''];

        if (trim($tagline) !== '') {
            $lines[] = '> '.trim(preg_replace('/\s+/u', ' ', $tagline) ?? $tagline);
            $lines[] = '';
        }

        $lines[] = '## Pages';
        $lines[] = '';

        foreach ($pages as $page) {
            if (! $page->indexable) {
                continue;
            }

            $title = str_replace(['[', ']'], '', $page->title ?: $page->loc);
            $line = '- ['.$title.']('.$page->loc.')';

            if ($page->description) {
                $description = trim(preg_replace('/\s+/u', ' ', $page->description) ?? $page->description);
                $line .= ': '.$description;
            }

            $lines[] = $line;
        }

        $lines[] = '';

        return implode("\n", $lines);
    }
}
