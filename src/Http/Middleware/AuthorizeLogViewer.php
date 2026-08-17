<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class AuthorizeLogViewer
{
    public function handle(Request $request, Closure $next): Response
    {
        $ability = config('log-monitor.authorization_gate');

        if (is_string($ability) && $ability !== '' && Gate::has($ability) && Gate::denies($ability, $request->user())) {
            abort(403);
        }

        return $next($request);
    }
}
