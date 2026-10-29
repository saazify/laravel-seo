<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Saazify\LaravelSeo\IndexNow\IndexNowPinger;
use Saazify\LaravelSeo\Support\Url;

class IndexNowCommand extends Command
{
    protected $signature = 'seo:indexnow {urls* : Absolute URLs to submit}';

    protected $description = 'Submit URLs to IndexNow';

    public function handle(): int
    {
        $key = (string) config('seo.indexnow.key');
        $host = (string) (Url::host((string) config('seo.site_url')) ?? '');
        $pinger = new IndexNowPinger(
            $key,
            $host,
            Http::getFacadeRoot(),
            (string) config('seo.indexnow.endpoint', 'https://api.indexnow.org/indexnow'),
        );

        $sent = $pinger->ping(array_values($this->argument('urls')));

        if (! $sent) {
            $this->error('IndexNow was not notified. Check the key, host, and URLs.');

            return self::FAILURE;
        }

        $this->info('IndexNow accepted the URL list.');

        return self::SUCCESS;
    }
}
