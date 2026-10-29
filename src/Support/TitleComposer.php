<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Support;

use Saazify\LaravelSeo\Data\SeoData;

final class TitleComposer
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function compose(SeoData $data, array $config): string
    {
        $site = (string) ($config['site_name'] ?? '');
        $separator = (string) ($config['title']['separator'] ?? '|');
        $tagline = (string) ($config['tagline'] ?? '');
        $raw = trim((string) $data->title);

        if ($data->titleTemplate !== null && $data->titleTemplate !== '') {
            $template = $data->titleTemplate;
        } elseif ($raw === '') {
            $template = (string) ($config['title']['homepage'] ?? '%site_name%');
        } else {
            $template = (string) ($config['title']['default'] ?? '%title% %separator% %site_name%');
        }

        $title = strtr($template, [
            '%title%' => $raw,
            '%site_name%' => $site,
            '%separator%' => $separator,
            '%tagline%' => $tagline,
            '%page%' => $data->pageNumber !== null ? (string) $data->pageNumber : '',
        ]);

        $title = trim(preg_replace('/\s+/u', ' ', $title) ?? $title);
        $quoted = preg_quote($separator, '/');
        $title = preg_replace('/^(?:\s*'.$quoted.'\s*)+|(?:\s*'.$quoted.'\s*)+$/u', '', $title) ?? $title;

        return trim($title);
    }
}
