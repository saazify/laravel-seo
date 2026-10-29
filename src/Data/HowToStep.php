<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Data;

final readonly class HowToStep
{
    public function __construct(
        public string $name,
        public string $text,
    ) {}
}
