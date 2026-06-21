<?php

namespace App\Support;

use Illuminate\Http\Request;

class RequestLocale
{
    public static function apply(?Request $request = null): string
    {
        $request ??= request();

        if (! $request) {
            app()->setLocale(config('app.locale', 'bg'));

            return app()->getLocale();
        }

        $consent = CookieConsent::fromRequest($request);

        $locale = $request->user()?->locale
            ?? ($request->hasSession() ? $request->session()->get('locale') : null)
            ?? ($consent->allowsFunctional() ? $request->cookie('locale') : null)
            ?? $request->getPreferredLanguage(['bg', 'en'])
            ?? config('app.locale', 'bg');

        if (! in_array($locale, ['bg', 'en'], true)) {
            $locale = 'bg';
        }

        app()->setLocale($locale);

        return $locale;
    }
}