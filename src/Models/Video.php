<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Saazify\LaravelSeo\Data\VideoMeta;

class Video extends Model
{
    protected $table = 'seo_videos';

    protected $guarded = [];

    protected $casts = [
        'duration' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'published_at' => 'datetime',
        'family_friendly' => 'boolean',
        'tags' => 'array',
        'requires_subscription' => 'boolean',
        'live' => 'boolean',
    ];

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    public function toMeta(): VideoMeta
    {
        return new VideoMeta(
            title: (string) $this->title,
            description: (string) $this->description,
            thumbnailUrl: (string) $this->thumbnail_url,
            contentUrl: $this->content_url,
            playerUrl: $this->player_url,
            duration: $this->duration,
            publishedAt: $this->published_at,
            width: $this->width,
            height: $this->height,
            familyFriendly: (bool) $this->family_friendly,
            tags: array_values($this->tags ?? []),
            requiresSubscription: (bool) $this->requires_subscription,
            live: (bool) $this->live,
            pageUrl: $this->page_url,
        );
    }
}
