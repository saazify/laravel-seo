<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Analysis;

final readonly class Report
{
    /**
     * @param  list<Check>  $checks
     */
    public function __construct(
        public int $score,
        public string $status,
        public int $words,
        public int $readingMinutes,
        public array $checks,
    ) {}

    /**
     * @return array{score: int, status: string, words: int, reading_minutes: int, checks: list<array{id: string, title: string, status: string, message: string}>}
     */
    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'status' => $this->status,
            'words' => $this->words,
            'reading_minutes' => $this->readingMinutes,
            'checks' => array_map(fn (Check $check) => $check->toArray(), $this->checks),
        ];
    }
}
