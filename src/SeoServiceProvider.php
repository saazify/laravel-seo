<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Saazify\LaravelSeo\Console\ExportKeyphrasesCommand;
use Saazify\LaravelSeo\Console\ExportRedirectsCommand;
use Saazify\LaravelSeo\Console\ImportRedirectsCommand;
use Saazify\LaravelSeo\Console\IndexNowCommand;
use Saazify\LaravelSeo\Console\InstallCommand;
use Saazify\LaravelSeo\Http\Middleware\RedirectMiddleware;
use Saazify\LaravelSeo\Redirects\Redirector;
use Saazify\LaravelSeo\Sitemap\SitemapRegistry;
use Saazify\LaravelSeo\Sitemap\SitemapRepository;

class SeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/seo.php', 'seo');

        $this->app->singleton(SeoManager::class, function ($app) {
            return new SeoManager($app['config']->get('seo', []));
        });

        $this->app->singleton(SitemapRegistry::class);

        $this->app->singleton(SitemapRepository::class, function ($app) {
            return new SitemapRepository(
                $app->make(SitemapRegistry::class),
                $app['config']->get('seo', []),
            );
        });

        $this->app->singleton(Redirector::class, function ($app) {
            return new Redirector($app['config']->get('seo', []));
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/seo.php' => config_path('seo.php'),
            ], 'seo-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'seo-migrations');

            $this->commands([
                InstallCommand::class,
                ExportRedirectsCommand::class,
                ImportRedirectsCommand::class,
                ExportKeyphrasesCommand::class,
                IndexNowCommand::class,
            ]);
        }

        $this->app['router']->aliasMiddleware('seo.redirects', RedirectMiddleware::class);

        if (config('seo.redirects.auto_middleware')) {
            $this->app['router']->pushMiddlewareToGroup('web', RedirectMiddleware::class);
        }

        if (config('seo.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/seo.php');
        }

        $registry = $this->app->make(SitemapRegistry::class);

        foreach (config('seo.sitemap.models', []) as $model) {
            if (! is_string($model) || ! class_exists($model)) {
                continue;
            }

            $registry->register($model, fn () => $model::query()->with('seoRecord')->lazy(200));
        }

        Blade::directive('seoHead', function () {
            return '<?php echo app(\\'.SeoManager::class.'::class)->render(); ?>';
        });

        Blade::directive('seoBreadcrumbs', function () {
            return '<?php echo app(\\'.SeoManager::class.'::class)->breadcrumbsHtml(); ?>';
        });
    }
}
