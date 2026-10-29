# Saazify Laravel SEO

`saazify/laravel-seo` is Saazify's Laravel package for on-site SEO. After you install it, one package covers schema, news, video, local business, and the extra tools that usually sit in a premium plugin: redirects, keyphrases, social tags, IndexNow, and content checks.

This is original Laravel code. It is not a copy of any WordPress plugin.

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13

## Install

```bash
composer require saazify/laravel-seo
php artisan seo:install
php artisan migrate
```

Put this in the layout so every page can print the tags:

```blade
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @seoHead
</head>
<body>
    @seoBreadcrumbs
    @yield('content')
</body>
```

`@seoHead` prints the title, description, canonical, robots, Open Graph, Twitter card, geo tags, and the JSON-LD graph for the current page.

Optional config:

```bash
php artisan vendor:publish --tag=seo-config
```

Turn redirects on for the whole site in `config/seo.php`:

```php
'redirects' => [
    'enabled' => true,
    'auto_middleware' => true,
    'allow_external' => false,
],
```

Or register the middleware yourself as `seo.redirects`.

## Schema

Call `seo()` in the controller before the view renders. The graph always includes `Organization` (or `LocalBusiness`) and `WebSite`. The page adds `WebPage`, and the blocks below add their own types.

```php
seo()
    ->title('How to publish a Laravel package')
    ->description('A short guide to tagging a release and submitting it on Packagist.')
    ->canonical(url()->current())
    ->image(asset('images/cover.jpg'), 1200, 630, 'Package release')
    ->type('article')
    ->author('Saazify')
    ->publishedAt($post->published_at)
    ->modifiedAt($post->updated_at)
    ->breadcrumbs([
        ['name' => 'Guides', 'url' => url('/guides')],
        ['name' => $post->title],
    ])
    ->faq([
        ['question' => 'Which command installs it?', 'answer' => 'composer require saazify/laravel-seo'],
    ])
    ->howTo([
        ['name' => 'Install', 'text' => 'Require the package and run the migrations.'],
        ['name' => 'Render', 'text' => 'Add @seoHead to the layout.'],
    ]);
```

That page emits `Article`, `BreadcrumbList`, `FAQPage`, and `HowTo`.

Set a search URL to add `SearchAction` on the website node. The `{search_term_string}` token is required:

```env
SEO_SEARCH_URL="${APP_URL}/search?q={search_term_string}"
```

Organization details come from `config/seo.php` (`SEO_ORG_NAME`, logo, phone, address, `same_as` social profiles). A primary row in `seo_locations` replaces that node with a `LocalBusiness`.

## News

Mark a page as news and it is added to `/sitemap-news.xml` for 48 hours. The schema type becomes `NewsArticle`.

```php
use Saazify\LaravelSeo\Data\NewsMeta;

seo()
    ->title($post->title)
    ->description($post->excerpt)
    ->canonical(route('posts.show', $post))
    ->type('article')
    ->author($post->author->name)
    ->news(new NewsMeta(
        title: $post->title,
        publishedAt: $post->published_at,
        keywords: ['laravel', 'packagist'],
        genres: ['Blog'],
        stockTickers: ['NASDAQ:AAPL'],
    ));
```

Allowed genres are `PressRelease`, `Satire`, `Blog`, `OpEd`, `Opinion`, and `UserGenerated`. Set the publication in `.env`:

```env
SEO_NEWS_NAME="Saazify Times"
SEO_NEWS_LANGUAGE=en
```

Articles older than `seo.news.max_age_hours` stay on the normal sitemap and drop off the news sitemap.

## Video

Pass a video and the page gets `VideoObject` schema, `og:video` tags, and a row in `/sitemap-video.xml`. A player URL also switches the Twitter card to `player`.

```php
seo()
    ->title('Product walkthrough')
    ->description('A two minute tour of the SEO package.')
    ->canonical(url()->current())
    ->video([
        'title' => 'Product walkthrough',
        'description' => 'A two minute tour of the SEO package.',
        'thumbnail' => asset('images/thumb.jpg'),
        'content_url' => asset('videos/tour.mp4'),
        'player_url' => url('/embed/tour'),
        'duration' => 120,
        'width' => 1280,
        'height' => 720,
        'tags' => ['laravel', 'seo'],
        'family_friendly' => true,
    ]);
```

To keep the video with a model, use the `HasSeo` trait and the `seo_videos` table:

```php
use Saazify\LaravelSeo\Concerns\HasSeo;

class Post extends Model
{
    use HasSeo;

    public function getSeoUrl(): string
    {
        return route('posts.show', $this);
    }
}
```

```php
$post->seoVideos()->create([
    'page_url' => route('posts.show', $post),
    'title' => 'Product walkthrough',
    'description' => 'A two minute tour of the SEO package.',
    'thumbnail_url' => asset('images/thumb.jpg'),
    'content_url' => asset('videos/tour.mp4'),
    'duration' => 120,
]);

seo()->for($post);
```

`seo()->for($post)` loads saved videos, the SEO record, and the model's URL.

## Local business

Create a location. The primary active location becomes the schema publisher and fills `geo.position`, `geo.placename`, `geo.region`, and `ICBM`. Every active location is written to `/locations.kml`.

```php
use Saazify\LaravelSeo\Models\Location;

$studio = Location::create([
    'name' => 'Saazify Studio',
    'is_primary' => true,
    'business_type' => 'LocalBusiness',
    'phone' => '+91 79 0000 0000',
    'email' => 'hello@saazify.test',
    'street' => '1 Main Road',
    'city' => 'Ahmedabad',
    'region' => 'GJ',
    'postal_code' => '380001',
    'country' => 'IN',
    'latitude' => 23.0225,
    'longitude' => 72.5714,
    'opening_hours' => [
        ['day' => 'Monday', 'opens' => '09:00', 'closes' => '17:00'],
        ['day' => 'Sunday', 'closed' => true],
    ],
]);

$studio->formattedAddress(); // "1 Main Road, Ahmedabad, GJ, 380001, IN"
$studio->mapUrl();           // Google Maps link
$studio->isOpenAt(now());    // true during Monday opening hours
```

`business_type` accepts a schema.org type such as `Restaurant`, `Store`, or `Dentist`. Closed days are left out of `openingHoursSpecification`. Extra branches are added as separate local businesses and an `ItemList`.

You can skip the database and set one business with environment variables: `SEO_ORG_NAME`, `SEO_ORG_TYPE`, `SEO_ORG_STREET`, `SEO_ORG_CITY`, `SEO_ORG_LATITUDE`, `SEO_ORG_LONGITUDE`, and `SEO_ORG_SAME_AS` (comma-separated profile URLs).

## Premium tools

These are the features that sit beside the core meta tags.

### Redirects

```php
use Saazify\LaravelSeo\Models\Redirect;

Redirect::create([
    'source' => '/old-page',
    'destination' => '/new-page',
    'status_code' => 301,
]);

Redirect::create([
    'source' => '/blog/(.*)',
    'destination' => '/news/$1',
    'status_code' => 301,
    'is_regex' => true,
]);

Redirect::create([
    'source' => '/removed',
    'status_code' => 410,
]);
```

External destinations are ignored unless `seo.redirects.allow_external` is true. `javascript:`, `data:`, and protocol-relative URLs are rejected. A redirect that points at itself is skipped.

```bash
php artisan seo:redirects-export storage/app/redirects.csv
php artisan seo:redirects-export storage/app/redirects.conf --format=nginx
php artisan seo:redirects-export storage/app/redirects.htaccess --format=apache
php artisan seo:redirects-import storage/app/redirects.csv
```

CSV columns are `source,destination,status_code,is_regex,is_active`.

### Keyphrases, social tags, and cornerstone pages

```php
seo()
    ->keyphrases(['laravel seo', 'xml sitemap'])
    ->keywords(['laravel', 'seo'])
    ->social(
        title: 'Share title',
        description: 'Share description',
        image: asset('images/social.jpg'),
    )
    ->cornerstone();
```

The first keyphrase is the focus keyphrase. Up to five are checked. A cornerstone page is given sitemap priority `1.0` when you do not set one yourself.

Save overrides on the model:

```php
$post->seoRecord()->updateOrCreate([], [
    'title' => 'Custom SEO title',
    'description' => 'Custom meta description',
    'keyphrases' => ['laravel seo', 'xml sitemap'],
    'social_title' => 'Share title',
    'cornerstone' => true,
    'robots' => 'index, follow',
]);
```

`noindex`, or `include_in_sitemap` set to false, keeps the URL out of the sitemap.

```bash
php artisan seo:keyphrases-export storage/app/keyphrases.csv
```

### IndexNow

```env
SEO_INDEXNOW_KEY=abcd1234efgh5678
```

The package serves that key at `https://example.com/abcd1234efgh5678.txt`.

```bash
php artisan seo:indexnow https://example.com/posts/hello
```

### Links and content checks

```php
$report = seo()
    ->title($post->seo_title)
    ->description($post->meta_description)
    ->url($post->path)
    ->keyphrases(['laravel seo'])
    ->analyze($post->body);

$report->score;            // 0-100
$report->status;           // good, ok, or poor
$report->readingMinutes;
$report->toArray();

seo()->suggestTitle($post->body);
seo()->suggestDescription($post->body);
```

```php
use Saazify\LaravelSeo\Links\LinkSuggester;
use Saazify\LaravelSeo\Links\OrphanFinder;

app(LinkSuggester::class)->suggest('laravel seo');

app(OrphanFinder::class)->sync([
    ['url' => 'https://example.com/a', 'html' => '<a href="https://example.com/b">B</a>'],
    ['url' => 'https://example.com/b', 'html' => ''],
]);

app(OrphanFinder::class)->find();
```

`sync()` writes inbound counts onto `seo_records` when the canonical URL matches. `find()` returns indexable records that still have a count of zero.

## Routes

These are on by default:

| URL | What it is |
| --- | --- |
| `/sitemap.xml` | Sitemap index |
| `/sitemap-pages.xml` | Pages and images |
| `/sitemap-news.xml` | Google News, recent articles only |
| `/sitemap-video.xml` | Video sitemap |
| `/robots.txt` | Crawl rules |
| `/llms.txt` | Page list for language models |
| `/locations.kml` | Store locations |
| `/{indexnow-key}.txt` | IndexNow key, when `SEO_INDEXNOW_KEY` is set |

Block training crawlers in `config/seo.php`:

```php
'robots' => [
    'ai_bots' => [
        'block' => ['GPTBot', 'Google-Extended', 'CCBot'],
    ],
],
```

Register models and they are added to the page sitemap:

```php
'sitemap' => [
    'models' => [
        \App\Models\Post::class,
    ],
],
```

## Tests

```bash
composer install
composer test
```

## Publish on Packagist

The package name is `saazify/laravel-seo`. Keep the GitHub repository under the `saazify` organization.

```bash
git init
git add .
git commit -m "Initial release of saazify/laravel-seo"
git branch -M main
git remote add origin git@github.com:saazify/laravel-seo.git
git push -u origin main
git tag v1.0.0
git push origin v1.0.0
```

Sign in at [packagist.org](https://packagist.org) with the Saazify account, submit `https://github.com/saazify/laravel-seo`, and enable the GitHub webhook in the package settings.

## License

MIT.
