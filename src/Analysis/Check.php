<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Analysis;

final readonly class Check
{
    public function __construct(
        public string $id,
        public string $title,
        public string $status,
        public string $message,
    ) {}

    public function points(): int
    {
        return match ($this->status) {
            'good' => 2,
            'ok' => 1,
            default => 0,
        };
    }

    /**
     * @return array{id: string, title: string, status: string, message: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status,
            'message' => $this->message,
        ];
    }
}
