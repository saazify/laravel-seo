<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Site identity
    |--------------------------------------------------------------------------
    |
    | These values feed the <title> template, Open Graph site name, schema
    | publisher, llms.txt, and the default organization node.
    |
    */

    'site_name' => env('SEO_SITE_NAME', env('APP_NAME', 'Laravel')),

    'site_url' => env('SEO_SITE_URL', env('APP_URL', 'http://localhost')),

    'tagline' => env('SEO_TAGLINE', ''),

    'locale' => env('SEO_LOCALE', 'en_US'),

    'website' => [
        'search_url' => env('SEO_SEARCH_URL'),
    ],

    'title' => [
        'separator' => env('SEO_TITLE_SEPARATOR', '|'),
        'homepage' => '%site_name%',
        'default' => '%title% %separator% %site_name%',
    ],

    /*
    |--------------------------------------------------------------------------
    | Organization / local business
    |--------------------------------------------------------------------------
    |
    | Used for Organization or LocalBusiness JSON-LD when no primary row
    | exists in seo_locations. A primary location overrides these values.
    |
    */

    'organization' => [
        'name' => env('SEO_ORG_NAME', env('APP_NAME', 'Laravel')),
        'type' => env('SEO_ORG_TYPE', 'Organization'),
        'url' => env('SEO_ORG_URL'),
        'logo' => env('SEO_ORG_LOGO'),
        'image' => env('SEO_ORG_IMAGE'),
        'email' => env('SEO_ORG_EMAIL'),
        'phone' => env('SEO_ORG_PHONE'),
        'description' => env('SEO_ORG_DESCRIPTION'),
        'price_range' => env('SEO_ORG_PRICE_RANGE'),
        'vat_id' => env('SEO_ORG_VAT_ID'),
        'address' => [
            'street' => env('SEO_ORG_STREET'),
            'city' => env('SEO_ORG_CITY'),
            'region' => env('SEO_ORG_REGION'),
            'postal_code' => env('SEO_ORG_POSTAL_CODE'),
            'country' => env('SEO_ORG_COUNTRY'),
        ],
        'geo' => [
            'latitude' => env('SEO_ORG_LATITUDE'),
            'longitude' => env('SEO_ORG_LONGITUDE'),
        ],
        'opening_hours' => [],
        'same_as' => array_values(array_filter(array_map('trim', explode(',', (string) env('SEO_ORG_SAME_AS', ''))))),
    ],

    'local' => [
        'enabled' => true,
        'geo_tags' => true,
    ],

    'breadcrumbs' => [
        'prepend_home' => true,
        'home_label' => 'Home',
    ],

    'meta' => [
        'twitter_site' => env('SEO_TWITTER_SITE'),
        'twitter_creator' => env('SEO_TWITTER_CREATOR'),
        'fb_app_id' => env('SEO_FB_APP_ID'),
        'keywords' => false,
    ],

    'verification' => [
        'google' => env('SEO_GOOGLE_VERIFICATION'),
        'bing' => env('SEO_BING_VERIFICATION'),
        'pinterest' => env('SEO_PINTEREST_VERIFICATION'),
        'yandex' => env('SEO_YANDEX_VERIFICATION'),
    ],

    'canonical' => [
        'use_current_url' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | XML sitemaps
    |--------------------------------------------------------------------------
    */

    'sitemap' => [
        'cache_seconds' => 3600,
        'pages' => [
            ['loc' => '/', 'changefreq' => 'daily', 'priority' => '1.0'],
        ],
        'models' => [
            // \App\Models\Post::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Google News
    |--------------------------------------------------------------------------
    |
    | Articles older than max_age_hours are left out of the news sitemap.
    | Google News only wants recent URLs (typically the last two days).
    |
    */

    'news' => [
        'publication_name' => env('SEO_NEWS_NAME', env('APP_NAME', 'Laravel')),
        'language' => env('SEO_NEWS_LANGUAGE', 'en'),
        'max_age_hours' => 48,
        'max_urls' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | robots.txt and AI crawlers
    |--------------------------------------------------------------------------
    |
    | ai_bots.block lists user agents that should receive Disallow: /.
    | Leave it empty to allow those crawlers. Common names: GPTBot,
    | Google-Extended, CCBot, ClaudeBot, Applebot-Extended.
    |
    */

    'robots' => [
        'include_sitemap' => true,
        'groups' => [
            [
                'user_agent' => '*',
                'allow' => ['/'],
                'disallow' => [],
            ],
        ],
        'ai_bots' => [
            'block' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Redirects
    |--------------------------------------------------------------------------
    |
    | auto_middleware pushes RedirectMiddleware onto the "web" group.
    | External destinations are rejected unless allow_external is true.
    |
    */

    'redirects' => [
        'enabled' => true,
        'auto_middleware' => false,
        'allow_external' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | IndexNow
    |--------------------------------------------------------------------------
    */

    'indexnow' => [
        'key' => env('SEO_INDEXNOW_KEY', ''),
        'endpoint' => 'https://api.indexnow.org/indexnow',
    ],

    /*
    |--------------------------------------------------------------------------
    | Content analysis
    |--------------------------------------------------------------------------
    |
    | inclusive is a map of phrase => preferred alternative. Empty disables
    | the check. Matching is case-insensitive and whole-phrase.
    |
    */

    'analysis' => [
        'reading_wpm' => 200,
        'max_keyphrases' => 5,
        'cornerstone_words' => 900,
        'inclusive' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    */

    'routes' => [
        'enabled' => true,
        'middleware' => [],
        'sitemap' => true,
        'news' => true,
        'video' => true,
        'robots' => true,
        'llms' => true,
        'kml' => true,
        'indexnow' => true,
    ],

];
