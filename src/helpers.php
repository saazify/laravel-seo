<?php

use Saazify\LaravelSeo\SeoManager;

if (! function_exists('seo')) {
    function seo(): SeoManager
    {
        return app(SeoManager::class);
    }
}
