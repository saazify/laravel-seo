<?php

namespace Saazify\LaravelSeo\Tests\Feature;

use Saazify\LaravelSeo\Tests\TestCase;

class IndexNowRouteTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('seo.indexnow.key', 'abcd1234efgh5678');
    }

    public function test_the_key_file_is_served_at_the_public_location(): void
    {
        $this->get('/abcd1234efgh5678.txt')
            ->assertOk()
            ->assertSee('abcd1234efgh5678');
    }
}
