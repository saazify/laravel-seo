<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'seo:install';

    protected $description = 'Publish the SEO config file and migrations';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--tag' => 'seo-config',
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'seo-migrations',
        ]);

        $this->info('Add the HasSeo trait to your models, then run php artisan migrate.');

        return self::SUCCESS;
    }
}
