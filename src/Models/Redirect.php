<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Models;

use Illuminate\Database\Eloquent\Model;
use Saazify\LaravelSeo\Exceptions\InvalidRedirectException;

class Redirect extends Model
{
    protected $table = 'seo_redirects';

    protected $guarded = [];

    protected $casts = [
        'status_code' => 'integer',
        'is_regex' => 'boolean',
        'is_active' => 'boolean',
        'hits' => 'integer',
        'last_hit_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Redirect $redirect): void {
            $redirect->forceFill(self::normalize($redirect->getAttributes()));
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{source: string, destination: ?string, status_code: int, is_regex: bool, is_active: bool}
     */
    public static function normalize(array $attributes): array
    {
        $source = trim((string) ($attributes['source'] ?? ''));
        $regex = filter_var($attributes['is_regex'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $status = (int) ($attributes['status_code'] ?? 301);
        $destination = $attributes['destination'] ?? null;
        $destination = is_string($destination) ? trim($destination) : $destination;
        $destination = $destination === '' ? null : $destination;

        if ($source === '' || strlen($source) > 2048) {
            throw new InvalidRedirectException('Redirect source is required and must be 2048 characters or fewer.');
        }

        if (! in_array($status, [301, 302, 303, 307, 308, 410], true)) {
            throw new InvalidRedirectException('Redirect status must be 301, 302, 303, 307, 308, or 410.');
        }

        if ($status !== 410 && ($destination === null || $destination === '')) {
            throw new InvalidRedirectException('Redirect destination is required unless the status is 410.');
        }

        if (! $regex && ! str_starts_with($source, '/')) {
            $source = '/'.$source;
        }

        if ($regex && @preg_match(self::pattern($source), '') === false) {
            throw new InvalidRedirectException('Redirect regex is invalid.');
        }

        if (is_string($destination) && self::hasForbiddenScheme($destination)) {
            throw new InvalidRedirectException('Redirect destination scheme is not allowed.');
        }

        return [
            'source' => $source,
            'destination' => is_string($destination) ? $destination : null,
            'status_code' => $status,
            'is_regex' => $regex,
            'is_active' => filter_var($attributes['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ];
    }

    public static function pattern(string $source): string
    {
        $body = $source;

        if (! str_starts_with($body, '^')) {
            $body = '^'.$body;
        }

        if (! str_ends_with($body, '$')) {
            $body .= '$';
        }

        $delimiter = str_contains($body, '#') ? '~' : '#';

        return $delimiter.$body.$delimiter.'u';
    }

    public static function hasForbiddenScheme(string $destination): bool
    {
        return str_starts_with($destination, '//')
            || preg_match('#^\s*(javascript|data|vbscript):#i', $destination) === 1;
    }
}
