<?php

namespace App\Http\Controllers;

use App\Support\CookieConsent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class CookieConsentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'choice' => ['required', 'in:all,essential,custom'],
            'functional' => ['sometimes', 'boolean'],
            'analytics' => ['sometimes', 'boolean'],
            'marketing' => ['sometimes', 'boolean'],
        ]);

        [$functional, $analytics, $marketing] = match ($data['choice']) {
            'all' => [true, true, true],
            'essential' => [false, false, false],
            default => [
                $request->boolean('functional'),
                $request->boolean('analytics'),
                $request->boolean('marketing'),
            ],
        };

        if (! config('cookies.categories.analytics.enabled', false)) {
            $analytics = false;
        }

        if (! config('cookies.categories.marketing.enabled', false)) {
            $marketing = false;
        }

        $payload = CookieConsent::payload($functional, $analytics, $marketing);
        $consent = new CookieConsent($payload);

        if (! $functional) {
            Cookie::queue(Cookie::forget('theme'));
            Cookie::queue(Cookie::forget('locale'));
        }

        Cookie::queue(cookie(
            name: config('cookies.consent_cookie', 'carmaxing_consent'),
            value: json_encode($payload),
            minutes: 60 * 24 * (int) config('cookies.consent_max_age_days', 365),
            path: '/',
            secure: $request->isSecure(),
            httpOnly: false,
            sameSite: 'lax',
        ));

        return response()->json([
            'consent' => $consent->toFrontend(),
        ]);
    }
}