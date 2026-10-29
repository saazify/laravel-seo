<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Data;

final readonly class VideoMeta
{
    /**
     * @param  list<string>  $tags
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $thumbnailUrl,
        public ?string $contentUrl = null,
        public ?string $playerUrl = null,
        public ?int $duration = null,
        public ?\DateTimeInterface $publishedAt = null,
        public ?int $width = null,
        public ?int $height = null,
        public bool $familyFriendly = true,
        public array $tags = [],
        public bool $requiresSubscription = false,
        public bool $live = false,
        public ?string $pageUrl = null,
    ) {}

    public function isValid(): bool
    {
        return $this->title !== ''
            && $this->description !== ''
            && $this->thumbnailUrl !== ''
            && ($this->contentUrl !== null || $this->playerUrl !== null);
    }
}
