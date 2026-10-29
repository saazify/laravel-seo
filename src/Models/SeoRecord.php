<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoRecord extends Model
{
    protected $table = 'seo_records';

    protected $guarded = [];

    protected $casts = [
        'keyphrases' => 'array',
        'keywords' => 'array',
        'cornerstone' => 'boolean',
        'include_in_sitemap' => 'boolean',
        'sitemap_priority' => 'float',
        'inbound_links' => 'integer',
    ];

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}
