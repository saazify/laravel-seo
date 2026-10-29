<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Saazify\LaravelSeo\Data\SeoData;
use Saazify\LaravelSeo\Data\VideoMeta;
use Saazify\LaravelSeo\Models\SeoRecord;
use Saazify\LaravelSeo\Models\Video;

trait HasSeo
{
    public function seoRecord(): MorphOne
    {
        return $this->morphOne(SeoRecord::class, 'seoable');
    }

    public function seoVideos(): MorphMany
    {
        return $this->morphMany(Video::class, 'seoable');
    }

    public function toSeoData(): SeoData
    {
        $data = method_exists($this, 'seoDefaults') ? $this->seoDefaults() : new SeoData;

        if (! $data instanceof SeoData) {
            $data = new SeoData;
        }

        $record = $this->seoRecord;

        if ($record instanceof SeoRecord) {
            $data->applyRecord($record);
        }

        if ($data->title === null || $data->title === '') {
            $data->title = $this->seoTitle ?? $this->title ?? $this->name ?? null;
        }

        if (($data->url === null || $data->url === '') && method_exists($this, 'getSeoUrl')) {
            $data->url = $this->getSeoUrl();
        }

        if ($data->modifiedAt === null && isset($this->updated_at) && $this->updated_at !== null) {
            $data->modifiedAt = $this->updated_at;
        }

        if ($data->publishedAt === null && isset($this->published_at) && $this->published_at !== null) {
            $data->publishedAt = $this->published_at;
        }

        if ($data->videos === []) {
            $data->videos = $this->storedVideos();
        }

        return $data;
    }

    /**
     * @return list<VideoMeta>
     */
    private function storedVideos(): array
    {
        try {
            return $this->seoVideos()
                ->get()
                ->map(fn (Video $video) => $video->toMeta())
                ->filter(fn ($video) => $video->isValid())
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }
}
