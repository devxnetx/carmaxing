<?php

namespace App\Http\Middleware;

use App\Support\CookieConsent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $consent = CookieConsent::fromRequest($request);

        $locale = $request->user()?->locale
            ?? $request->session()->get('locale')
            ?? ($consent->allowsFunctional() ? $request->cookie('locale') : null)
            ?? $request->getPreferredLanguage(['bg', 'en'])
            ?? 'bg';

        if (! in_array($locale, ['bg', 'en'], true)) {
            $locale = 'bg';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}