<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Robots;

final class RobotsTxt
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function render(array $config, string $sitemapUrl): string
    {
        $lines = [];
        $groups = $config['robots']['groups'] ?? [
            ['user_agent' => '*', 'allow' => ['/'], 'disallow' => []],
        ];

        foreach ($groups as $group) {
            if (! is_array($group)) {
                continue;
            }

            array_push($lines, ...$this->group($group));
        }

        foreach ($config['robots']['ai_bots']['block'] ?? [] as $agent) {
            if (! is_string($agent) || trim($agent) === '') {
                continue;
            }

            array_push($lines, ...$this->group([
                'user_agent' => trim($agent),
                'disallow' => ['/'],
            ]));
        }

        if (($config['robots']['include_sitemap'] ?? true) === true) {
            $lines[] = 'Sitemap: '.$sitemapUrl;
        }

        return rtrim(implode("\n", $lines))."\n";
    }

    /**
     * @param  array<string, mixed>  $group
     * @return list<string>
     */
    private function group(array $group): array
    {
        $lines = ['User-agent: '.($group['user_agent'] ?? '*')];

        foreach ($group['allow'] ?? [] as $path) {
            $lines[] = 'Allow: '.$path;
        }

        foreach ($group['disallow'] ?? [] as $path) {
            $lines[] = 'Disallow: '.$path;
        }

        $lines[] = '';

        return $lines;
    }
}
