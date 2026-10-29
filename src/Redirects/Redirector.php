<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Redirects;

use Illuminate\Http\Request;
use Saazify\LaravelSeo\Models\Redirect;
use Saazify\LaravelSeo\Support\Url;

final class Redirector
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private array $config) {}

    /**
     * @return array{model: Redirect, target: string, status: int}|null
     */
    public function match(Request $request): ?array
    {
        if (($this->config['redirects']['enabled'] ?? true) !== true) {
            return null;
        }

        $path = '/'.ltrim($request->path(), '/');

        if ($path !== '/') {
            $path = rtrim($path, '/') ?: '/';
        }

        $exact = Redirect::query()
            ->where('is_active', true)
            ->where('is_regex', false)
            ->whereIn('source', array_values(array_unique([$path, $path.'/', rtrim($path, '/')])))
            ->first();

        if ($exact instanceof Redirect) {
            return $this->result($exact, $exact->destination, $path, $request);
        }

        $patterns = Redirect::query()->where('is_active', true)->where('is_regex', true)->get();

        foreach ($patterns as $redirect) {
            $pattern = Redirect::pattern($redirect->source);

            if (@preg_match($pattern, $path) !== 1) {
                continue;
            }

            $target = $redirect->destination === null
                ? null
                : preg_replace($pattern, $redirect->destination, $path);

            $resolved = $this->result($redirect, is_string($target) ? $target : null, $path, $request);

            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    /**
     * @return array{model: Redirect, target: string, status: int}|null
     */
    private function result(Redirect $redirect, ?string $target, string $path, Request $request): ?array
    {
        $status = (int) $redirect->status_code;

        if ($status === 410) {
            return ['model' => $redirect, 'target' => '', 'status' => 410];
        }

        if (! is_string($target) || $target === '' || Redirect::hasForbiddenScheme($target) || ! $this->isSafe($target, $request)) {
            return null;
        }

        if ($this->isSameLocation($target, $path, $request)) {
            return null;
        }

        return ['model' => $redirect, 'target' => $target, 'status' => $status];
    }

    private function isSameLocation(string $target, string $path, Request $request): bool
    {
        $targetPath = parse_url($target, PHP_URL_PATH);
        $normalized = is_string($targetPath) && $targetPath !== '' ? $targetPath : $target;
        $normalized = '/'.ltrim(rtrim($normalized, '/'), '/');
        $normalized = $normalized === '/' ? '/' : rtrim($normalized, '/');

        if ($normalized !== $path) {
            return false;
        }

        $host = Url::host($target);

        return $host === null || strcasecmp($host, $request->getHost()) === 0;
    }

    private function isSafe(string $target, Request $request): bool
    {
        if (str_starts_with($target, '/')) {
            return true;
        }

        $parts = parse_url($target);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        if (! in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        if (($this->config['redirects']['allow_external'] ?? false) === true) {
            return true;
        }

        $appHost = Url::host((string) ($this->config['site_url'] ?? ''));

        return strcasecmp((string) $parts['host'], (string) $request->getHost()) === 0
            || ($appHost !== null && strcasecmp((string) $parts['host'], $appHost) === 0);
    }
}
