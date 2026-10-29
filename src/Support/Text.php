<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Support;

final class Text
{
    public static function plain(string $html): string
    {
        $text = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html) ?? $html;
        $text = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * @return list<string>
     */
    public static function sentences(string $plain): array
    {
        $parts = preg_split('/(?<=[.!?])\s+/u', trim($plain), -1, PREG_SPLIT_NO_EMPTY);

        return array_values($parts ?: []);
    }

    /**
     * @return list<string>
     */
    public static function words(string $plain): array
    {
        $parts = preg_split('/\s+/u', trim($plain), -1, PREG_SPLIT_NO_EMPTY);

        return array_values($parts ?: []);
    }

    public static function limit(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        $slice = rtrim(mb_substr($text, 0, $max));
        $space = mb_strrpos($slice, ' ');

        if ($space !== false && $space > (int) ($max * 0.6)) {
            $slice = rtrim(mb_substr($slice, 0, $space));
        }

        return $slice;
    }

    public static function duration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remain = $seconds % 60;
        $value = 'PT';

        if ($hours > 0) {
            $value .= $hours.'H';
        }

        if ($minutes > 0) {
            $value .= $minutes.'M';
        }

        if ($remain > 0 || $value === 'PT') {
            $value .= $remain.'S';
        }

        return $value;
    }

    public static function atom(?\DateTimeInterface $date): ?string
    {
        return $date?->format(DATE_ATOM);
    }

    public static function containsPhrase(string $haystack, string $phrase): bool
    {
        $phrase = trim($phrase);

        if ($phrase === '') {
            return false;
        }

        return mb_stripos($haystack, $phrase) !== false;
    }
}
