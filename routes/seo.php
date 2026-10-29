<?php

use Illuminate\Support\Facades\Route;
use Saazify\LaravelSeo\Http\Controllers\SeoController;

Route::middleware(config('seo.routes.middleware', []))->group(function () {
    if (config('seo.routes.sitemap', true)) {
        Route::get('/sitemap.xml', [SeoController::class, 'index'])->name('seo.sitemap');
        Route::get('/sitemap-pages.xml', [SeoController::class, 'pages'])->name('seo.sitemap.pages');
    }

    if (config('seo.routes.news', true)) {
        Route::get('/sitemap-news.xml', [SeoController::class, 'news'])->name('seo.sitemap.news');
    }

    if (config('seo.routes.video', true)) {
        Route::get('/sitemap-video.xml', [SeoController::class, 'videos'])->name('seo.sitemap.videos');
    }

    if (config('seo.routes.robots', true)) {
        Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
    }

    if (config('seo.routes.llms', true)) {
        Route::get('/llms.txt', [SeoController::class, 'llms'])->name('seo.llms');
    }

    if (config('seo.routes.kml', true)) {
        Route::get('/locations.kml', [SeoController::class, 'kml'])->name('seo.kml');
    }

    $key = (string) config('seo.indexnow.key');

    if (config('seo.routes.indexnow', true) && strlen($key) >= 8 && preg_match('/^[A-Za-z0-9]+$/', $key) === 1) {
        Route::get('/'.$key.'.txt', [SeoController::class, 'indexNowKey'])->name('seo.indexnow');
    }
});
