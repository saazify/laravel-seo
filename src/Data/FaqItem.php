<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Data;

final readonly class FaqItem
{
    public function __construct(
        public string $question,
        public string $answer,
    ) {}
}
