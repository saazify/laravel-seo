<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Data;

final readonly class Breadcrumb
{
    public function __construct(
        public string $name,
        public ?string $url = null,
    ) {}
}
