<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Support;

final class Url
{
    public static function absolute(string $loc, string $base): string
    {
        if (preg_match('#^https?://#i', $loc) === 1) {
            return $loc;
        }

        return rtrim($base, '/').'/'.ltrim($loc, '/');
    }

    public static function host(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : null;
    }
}
