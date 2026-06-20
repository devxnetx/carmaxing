<?php

namespace App\Http\Middleware;

use App\Services\PlatformSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTendersEnabled
{
    public function __construct(
        private PlatformSettings $settings,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->settings->tendersEnabled()) {
            abort(404);
        }

        return $next($request);
    }
}