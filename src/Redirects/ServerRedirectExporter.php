<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Redirects;

use Saazify\LaravelSeo\Models\Redirect;

final class ServerRedirectExporter
{
    /**
     * @param  iterable<int, Redirect>  $redirects
     */
    public function apache(iterable $redirects): string
    {
        $lines = ['<IfModule mod_rewrite.c>', 'RewriteEngine On'];

        foreach ($redirects as $redirect) {
            if (! $redirect->is_active || ($redirect->destination === null && $redirect->status_code !== 410)) {
                continue;
            }

            $pattern = $redirect->is_regex
                ? $redirect->source
                : '^'.preg_quote((string) $redirect->source, '#').'$';

            if ($redirect->status_code === 410) {
                $lines[] = 'RewriteRule '.$pattern.' - [G,L]';

                continue;
            }

            $lines[] = 'RewriteRule '.$pattern.' '.$redirect->destination.' [R='.$redirect->status_code.',L]';
        }

        $lines[] = '</IfModule>';

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  iterable<int, Redirect>  $redirects
     */
    public function nginx(iterable $redirects): string
    {
        $lines = [];

        foreach ($redirects as $redirect) {
            if (! $redirect->is_active) {
                continue;
            }

            if ($redirect->status_code === 410) {
                $lines[] = 'location = '.$redirect->source.' { return 410; }';

                continue;
            }

            if ($redirect->destination === null) {
                continue;
            }

            if ($redirect->is_regex) {
                $lines[] = 'rewrite '.$redirect->source.' '.$redirect->destination.' redirect;';

                continue;
            }

            $lines[] = 'location = '.$redirect->source.' { return '.$redirect->status_code.' '.$redirect->destination.'; }';
        }

        return $lines === [] ? '' : implode("\n", $lines)."\n";
    }
}
