<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->needsOnboarding() && ! $request->routeIs('onboarding.*', 'logout', 'locale.*', 'subscriptions.*')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Onboarding is not complete.'], 403);
            }

            return redirect()->route('onboarding.show', [], $request->isMethod('GET') || $request->isMethod('HEAD') ? 302 : 303);
        }

        return $next($request);
    }
}