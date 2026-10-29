<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Local;

final class OpeningHours
{
    /**
     * @param  list<array<string, mixed>>  $hours
     */
    public static function isOpen(array $hours, \DateTimeInterface $moment): bool
    {
        $day = $moment->format('l');
        $time = $moment->format('H:i');

        foreach ($hours as $row) {
            if (! is_array($row) || strcasecmp((string) ($row['day'] ?? ''), $day) !== 0) {
                continue;
            }

            if (! empty($row['closed'])) {
                return false;
            }

            $opens = (string) ($row['opens'] ?? '');
            $closes = (string) ($row['closes'] ?? '');

            if ($opens === '' || $closes === '') {
                return false;
            }

            return $time >= $opens && $time < $closes;
        }

        return false;
    }

    /**
     * @param  list<string|null>  $parts
     */
    public static function formatAddress(array $parts): string
    {
        $clean = [];

        foreach ($parts as $part) {
            $part = trim((string) $part);

            if ($part !== '') {
                $clean[] = $part;
            }
        }

        return implode(', ', $clean);
    }
}
