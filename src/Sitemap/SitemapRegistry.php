<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Sitemap;

final class SitemapRegistry
{
    /** @var array<string, callable(): iterable<mixed>> */
    private array $sources = [];

    public function register(string $name, callable $source): void
    {
        $this->sources[$name] = $source;
    }

    /**
     * @return array<string, callable(): iterable<mixed>>
     */
    public function sources(): array
    {
        return $this->sources;
    }
}
