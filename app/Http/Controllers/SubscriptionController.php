<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(): View
    {
        return view('subscriptions.index', [
            'user' => auth()->user(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'in:subscribe_price_digest,subscribe_new_listings_digest,subscribe_news'],
            'enabled' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $user->update([
            $validated['key'] => $validated['enabled'],
        ]);

        return response()->json([
            'ok' => true,
            'key' => $validated['key'],
            'enabled' => (bool) $user->{$validated['key']},
            'has_any_subscription' => $user->fresh()->hasAnySubscription(),
        ]);
    }

    public function dismissPrompt(Request $request): JsonResponse
    {
        $request->user()->update([
            'subscription_prompted_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }
}