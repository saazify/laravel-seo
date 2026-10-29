<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Facades;

use Illuminate\Support\Facades\Facade;
use Saazify\LaravelSeo\SeoManager;

/**
 * @method static \Saazify\LaravelSeo\SeoManager reset()
 * @method static \Saazify\LaravelSeo\Data\SeoData data()
 * @method static \Saazify\LaravelSeo\SeoManager set(\Saazify\LaravelSeo\Data\SeoData $data)
 * @method static \Saazify\LaravelSeo\SeoManager for(object $model)
 * @method static \Saazify\LaravelSeo\SeoManager title(string $title)
 * @method static \Saazify\LaravelSeo\SeoManager description(string $description)
 * @method static \Saazify\LaravelSeo\SeoManager canonical(string $url)
 * @method static \Saazify\LaravelSeo\SeoManager url(string $url)
 * @method static \Saazify\LaravelSeo\SeoManager robots(string $robots)
 * @method static \Saazify\LaravelSeo\SeoManager image(string $url, ?int $width = null, ?int $height = null, ?string $alt = null)
 * @method static \Saazify\LaravelSeo\SeoManager type(string $type)
 * @method static \Saazify\LaravelSeo\SeoManager keyphrases(array $keyphrases)
 * @method static \Saazify\LaravelSeo\SeoManager keywords(array $keywords)
 * @method static \Saazify\LaravelSeo\SeoManager author(string $author)
 * @method static \Saazify\LaravelSeo\SeoManager news(\Saazify\LaravelSeo\Data\NewsMeta $news)
 * @method static \Saazify\LaravelSeo\SeoManager video(array $video)
 * @method static \Saazify\LaravelSeo\SeoManager faq(array $items)
 * @method static \Saazify\LaravelSeo\SeoManager howTo(array $steps)
 * @method static \Saazify\LaravelSeo\SeoManager breadcrumbs(array $crumbs)
 * @method static string render()
 * @method static string breadcrumbsHtml()
 * @method static \Saazify\LaravelSeo\Analysis\Report analyze(string $html)
 *
 * @see SeoManager
 */
class Seo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SeoManager::class;
    }
}
