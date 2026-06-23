@extends('layouts.app')

@section('title', __('messages.subscriptions'))

@section('content')
<div class="mx-auto max-w-3xl px-4 py-6 sm:py-8">
    <div>
        <h1 class="text-2xl font-bold">{{ __('messages.subscriptions') }}</h1>
        <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('messages.subscriptions_subtitle') }}</p>
    </div>

    <div
        class="mt-6 space-y-4"
        x-data="subscriptionToggles(@js([
            'subscribe_price_digest' => (bool) $user->subscribe_price_digest,
            'subscribe_new_listings_digest' => (bool) $user->subscribe_new_listings_digest,
            'subscribe_news' => (bool) $user->subscribe_news,
        ]), '{{ route('subscriptions.update') }}')"
    >
        <div class="card p-5">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <x-icon name="activity" class="h-5 w-5 text-brand-600" />
                        <h2 class="font-semibold">{{ __('messages.subscription_price_digest_title') }}</h2>
                    </div>
                    <p class="mt-2 text-sm text-[var(--color-text-muted)]">{{ __('messages.subscription_price_digest_desc') }}</p>
                </div>
                <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                    <input type="checkbox" class="peer sr-only" :checked="settings.subscribe_price_digest" @change="toggle('subscribe_price_digest', $event.target.checked)" :disabled="saving">
                    <span class="h-6 w-11 rounded-full bg-[var(--color-surface-3)] after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition peer-checked:bg-brand-600 peer-checked:after:translate-x-5 peer-disabled:opacity-50"></span>
                </label>
            </div>
        </div>

        <div class="card p-5">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <x-icon name="plus" class="h-5 w-5 text-brand-600" />
                        <h2 class="font-semibold">{{ __('messages.subscription_new_listings_title') }}</h2>
                    </div>
                    <p class="mt-2 text-sm text-[var(--color-text-muted)]">{{ __('messages.subscription_new_listings_desc') }}</p>
                </div>
                <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                    <input type="checkbox" class="peer sr-only" :checked="settings.subscribe_new_listings_digest" @change="toggle('subscribe_new_listings_digest', $event.target.checked)" :disabled="saving">
                    <span class="h-6 w-11 rounded-full bg-[var(--color-surface-3)] after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition peer-checked:bg-brand-600 peer-checked:after:translate-x-5 peer-disabled:opacity-50"></span>
                </label>
            </div>
        </div>

        <div class="card p-5">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <x-icon name="bell" class="h-5 w-5 text-brand-600" />
                        <h2 class="font-semibold">{{ __('messages.subscription_news_title') }}</h2>
                    </div>
                    <p class="mt-2 text-sm text-[var(--color-text-muted)]">{{ __('messages.subscription_news_desc') }}</p>
                </div>
                <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                    <input type="checkbox" class="peer sr-only" :checked="settings.subscribe_news" @change="toggle('subscribe_news', $event.target.checked)" :disabled="saving">
                    <span class="h-6 w-11 rounded-full bg-[var(--color-surface-3)] after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition peer-checked:bg-brand-600 peer-checked:after:translate-x-5 peer-disabled:opacity-50"></span>
                </label>
            </div>
        </div>
    </div>
</div>
@endsection