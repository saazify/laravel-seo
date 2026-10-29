<?php

namespace Saazify\LaravelSeo\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Saazify\LaravelSeo\Concerns\HasSeo;
use Saazify\LaravelSeo\Data\NewsMeta;
use Saazify\LaravelSeo\Data\SeoData;

class Post extends Model
{
    use HasSeo;

    protected $table = 'posts';

    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function getSeoUrl(): string
    {
        return 'https://example.com/posts/'.$this->slug;
    }

    public function seoDefaults(): SeoData
    {
        $data = new SeoData(
            title: $this->title,
            description: $this->body,
            type: $this->is_news ? 'article' : 'website',
            image: 'https://example.com/images/'.$this->slug.'.jpg',
        );

        if ($this->is_news && $this->published_at !== null) {
            $data->news = new NewsMeta(
                title: (string) $this->title,
                publishedAt: $this->published_at,
                keywords: ['laravel', 'seo'],
                genres: ['Blog', 'NotAGenre'],
                stockTickers: ['NASDAQ:AAPL'],
            );
            $data->author = 'A. Writer';
        }

        return $data;
    }
}
