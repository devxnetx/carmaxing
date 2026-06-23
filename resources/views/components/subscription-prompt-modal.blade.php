@auth
    @if(auth()->user()->shouldShowSubscriptionPrompt())
        <div
            x-data="subscriptionPrompt(@js([
                'subscribe_price_digest' => (bool) auth()->user()->subscribe_price_digest,
                'subscribe_new_listings_digest' => (bool) auth()->user()->subscribe_new_listings_digest,
                'subscribe_news' => (bool) auth()->user()->subscribe_news,
            ]), '{{ route('subscriptions.update') }}', '{{ route('subscriptions.dismiss-prompt') }}', '{{ route('subscriptions.index') }}')"
            x-init="init()"
            x-show="visible"
            x-cloak
            class="fixed inset-0 z-[60] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-label="{{ __('messages.subscriptions') }}"
        >
            <div class="absolute inset-0 bg-black/50" @click="dismiss()"></div>

            <div class="relative w-full max-w-lg rounded-2xl border border-[var(--color-border)] bg-[var(--color-surface)] p-6 shadow-2xl">
                <button
                    type="button"
                    @click="dismiss()"
                    class="absolute right-4 top-4 rounded-lg p-1 text-[var(--color-text-muted)] transition hover:bg-[var(--color-surface-3)] hover:text-[var(--color-text)]"
                    aria-label="{{ __('messages.close') }}"
                >
                    <x-icon name="x" class="h-5 w-5" />
                </button>

                <div class="pr-8">
                    <h2 class="text-lg font-semibold">{{ __('messages.subscription_prompt_title') }}</h2>
                    <p class="mt-2 text-sm text-[var(--color-text-muted)]">{{ __('messages.subscription_prompt_subtitle') }}</p>
                </div>

                <div class="mt-5 space-y-3">
                    <div class="rounded-xl border border-[var(--color-border)] p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-sm font-medium">{{ __('messages.subscription_price_digest_title') }}</div>
                                <p class="mt-1 text-xs text-[var(--color-text-muted)]">{{ __('messages.subscription_price_digest_desc') }}</p>
                            </div>
                            <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                                <input type="checkbox" class="peer sr-only" :checked="settings.subscribe_price_digest" @change="toggle('subscribe_price_digest', $event.target.checked)" :disabled="isSaving('subscribe_price_digest')">
                                <span class="h-6 w-11 rounded-full bg-[var(--color-surface-3)] after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition peer-checked:bg-brand-600 peer-checked:after:translate-x-5 peer-disabled:opacity-50"></span>
                            </label>
                        </div>
                    </div>

                    <div class="rounded-xl border border-[var(--color-border)] p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-sm font-medium">{{ __('messages.subscription_new_listings_title') }}</div>
                                <p class="mt-1 text-xs text-[var(--color-text-muted)]">{{ __('messages.subscription_new_listings_desc') }}</p>
                            </div>
                            <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                                <input type="checkbox" class="peer sr-only" :checked="settings.subscribe_new_listings_digest" @change="toggle('subscribe_new_listings_digest', $event.target.checked)" :disabled="isSaving('subscribe_new_listings_digest')">
                                <span class="h-6 w-11 rounded-full bg-[var(--color-surface-3)] after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition peer-checked:bg-brand-600 peer-checked:after:translate-x-5 peer-disabled:opacity-50"></span>
                            </label>
                        </div>
                    </div>

                    <div class="rounded-xl border border-[var(--color-border)] p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-sm font-medium">{{ __('messages.subscription_news_title') }}</div>
                                <p class="mt-1 text-xs text-[var(--color-text-muted)]">{{ __('messages.subscription_news_desc') }}</p>
                            </div>
                            <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                                <input type="checkbox" class="peer sr-only" :checked="settings.subscribe_news" @change="toggle('subscribe_news', $event.target.checked)" :disabled="isSaving('subscribe_news')">
                                <span class="h-6 w-11 rounded-full bg-[var(--color-surface-3)] after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition peer-checked:bg-brand-600 peer-checked:after:translate-x-5 peer-disabled:opacity-50"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                    <a href="{{ route('subscriptions.index') }}" class="text-sm text-brand-600 hover:underline">{{ __('messages.subscription_prompt_manage') }}</a>
                    <button type="button" @click="dismiss()" class="btn-primary text-sm">{{ __('messages.subscription_prompt_ready') }}</button>
                </div>
            </div>
        </div>
    @endif
@endauth