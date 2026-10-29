<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Links;

use Illuminate\Support\Collection;
use Saazify\LaravelSeo\Models\SeoRecord;

final class LinkSuggester
{
    /**
     * @return Collection<int, SeoRecord>
     */
    public function suggest(string $phrase, ?int $exceptId = null, int $limit = 5): Collection
    {
        $phrase = trim($phrase);

        if ($phrase === '') {
            return new Collection;
        }

        $like = '%'.$this->escape($phrase).'%';

        return SeoRecord::query()
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->where(function ($query) use ($like) {
                $query->where('title', 'like', $like)
                    ->orWhere('keyphrases', 'like', $like)
                    ->orWhere('canonical', 'like', $like);
            })
            ->limit($limit)
            ->get();
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
