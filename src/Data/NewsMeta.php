<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Data;

final readonly class NewsMeta
{
    /**
     * @param  list<string>  $keywords
     * @param  list<string>  $genres
     * @param  list<string>  $stockTickers
     */
    public function __construct(
        public string $title,
        public \DateTimeInterface $publishedAt,
        public array $keywords = [],
        public array $genres = [],
        public array $stockTickers = [],
    ) {}
}
