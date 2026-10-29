<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Saazify\LaravelSeo\Redirects\Redirector;
use Symfony\Component\HttpFoundation\Response;

final class RedirectMiddleware
{
    public function __construct(private Redirector $redirector) {}

    public function handle(Request $request, Closure $next): Response
    {
        $match = $this->redirector->match($request);

        if ($match === null) {
            return $next($request);
        }

        $match['model']->increment('hits', 1, ['last_hit_at' => now()]);

        if ($match['status'] === 410) {
            abort(410, 'Gone');
        }

        return redirect()->to($match['target'], $match['status']);
    }
}
