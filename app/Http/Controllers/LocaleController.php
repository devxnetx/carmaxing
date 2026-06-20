<?php

namespace App\Http\Controllers;

use App\Support\CookieConsent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, ['bg', 'en'], true), 404);

        if ($request->user()) {
            $request->user()->update(['locale' => $locale]);
            cookie()->queue('locale', $locale, 60 * 24 * 365);
        } elseif (CookieConsent::fromRequest($request)->allowsFunctional()) {
            cookie()->queue('locale', $locale, 60 * 24 * 365);
        } else {
            $request->session()->put('locale', $locale);
        }

        return back();
    }
}