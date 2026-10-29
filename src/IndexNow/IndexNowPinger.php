<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\IndexNow;

use Illuminate\Http\Client\Factory;

final class IndexNowPinger
{
    public function __construct(
        private string $key,
        private string $host,
        private Factory $http,
        private string $endpoint = 'https://api.indexnow.org/indexnow',
    ) {}

    /**
     * @param  list<string>  $urls
     */
    public function ping(array $urls): bool
    {
        $urls = array_values(array_filter($urls, fn ($url) => is_string($url) && str_starts_with($url, 'http')));

        if ($this->key === '' || $this->host === '' || $urls === []) {
            return false;
        }

        $response = $this->http->acceptJson()->post($this->endpoint, [
            'host' => $this->host,
            'key' => $this->key,
            'keyLocation' => 'https://'.$this->host.'/'.$this->key.'.txt',
            'urlList' => $urls,
        ]);

        return $response->successful() || $response->status() === 202;
    }
}
