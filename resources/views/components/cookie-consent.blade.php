@php
    use App\Support\CookieConsent;
    $consent = CookieConsent::fromRequest();
@endphp

<div
    x-data="cookieConsent(@js($consent->toFrontend()), @js(route('cookie-consent.store')), @js(route('legal.cookies')))"
    @open-cookie-settings.window="openSettings()"
    class="no-print fixed inset-x-0 bottom-0 z-[60] px-4 pb-4 sm:px-6"
    x-cloak
>
    <div
        x-show="visible"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-y-full opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-full opacity-0"
        class="mx-auto max-w-3xl"
        role="dialog"
        aria-labelledby="cookie-consent-title"
        aria-describedby="cookie-consent-desc"
        aria-modal="true"
    >
        <div class="card border border-[var(--color-border)] p-5 shadow-2xl sm:p-6">
            <template x-if="!customizing">
                <div>
                    <h2 id="cookie-consent-title" class="text-base font-semibold sm:text-lg">{{ __('messages.cookie_consent_title') }}</h2>
                    <p id="cookie-consent-desc" class="mt-2 text-sm leading-relaxed text-[var(--color-text-muted)]">
                        {{ __('messages.cookie_consent_body') }}
                        <a :href="policyUrl" class="text-brand-600 underline-offset-2 hover:underline">{{ __('messages.cookie_policy') }}</a>.
                    </p>
                    <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                        <button type="button" @click="acceptAll()" class="btn-primary text-sm" :disabled="saving">
                            {{ __('messages.cookie_accept_all') }}
                        </button>
                        <button type="button" @click="rejectNonEssential()" class="btn-secondary text-sm" :disabled="saving">
                            {{ __('messages.cookie_reject_non_essential') }}
                        </button>
                        <button type="button" @click="customizing = true" class="btn-secondary text-sm" :disabled="saving">
                            {{ __('messages.cookie_customize') }}
                        </button>
                    </div>
                </div>
            </template>

            <template x-if="customizing">
                <div>
                    <h2 class="text-base font-semibold sm:text-lg">{{ __('messages.cookie_preferences_title') }}</h2>
                    <p class="mt-2 text-sm text-[var(--color-text-muted)]">{{ __('messages.cookie_preferences_body') }}</p>

                    <div class="mt-4 space-y-3">
                        <div class="rounded-lg border border-[var(--color-border)] p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="font-medium text-sm">{{ __('messages.cookie_category_necessary') }}</div>
                                    <p class="mt-1 text-xs text-[var(--color-text-muted)]">{{ __('messages.cookie_category_necessary_desc') }}</p>
                                </div>
                                <span class="shrink-0 text-xs font-medium text-[var(--color-text-muted)]">{{ __('messages.cookie_always_on') }}</span>
                            </div>
                        </div>

                        <div class="rounded-lg border border-[var(--color-border)] p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="font-medium text-sm">{{ __('messages.cookie_category_functional') }}</div>
                                    <p class="mt-1 text-xs text-[var(--color-text-muted)]">{{ __('messages.cookie_category_functional_desc') }}</p>
                                </div>
                                <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                                    <input type="checkbox" x-model="preferences.functional" class="peer sr-only">
                                    <span class="h-6 w-11 rounded-full bg-[var(--color-surface-3)] after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition peer-checked:bg-brand-600 peer-checked:after:translate-x-5"></span>
                                </label>
                            </div>
                        </div>

                        @if(config('cookies.categories.analytics.enabled'))
                            <div class="rounded-lg border border-[var(--color-border)] p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="font-medium text-sm">{{ __('messages.cookie_category_analytics') }}</div>
                                        <p class="mt-1 text-xs text-[var(--color-text-muted)]">{{ __('messages.cookie_category_analytics_desc') }}</p>
                                    </div>
                                    <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                                        <input type="checkbox" x-model="preferences.analytics" class="peer sr-only">
                                        <span class="h-6 w-11 rounded-full bg-[var(--color-surface-3)] after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition peer-checked:bg-brand-600 peer-checked:after:translate-x-5"></span>
                                    </label>
                                </div>
                            </div>
                        @else
                            <div class="rounded-lg border border-dashed border-[var(--color-border)] p-4 opacity-70">
                                <div class="font-medium text-sm">{{ __('messages.cookie_category_analytics') }}</div>
                                <p class="mt-1 text-xs text-[var(--color-text-muted)]">{{ __('messages.cookie_category_not_used') }}</p>
                            </div>
                        @endif

                        @if(config('cookies.categories.marketing.enabled'))
                            <div class="rounded-lg border border-[var(--color-border)] p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="font-medium text-sm">{{ __('messages.cookie_category_marketing') }}</div>
                                        <p class="mt-1 text-xs text-[var(--color-text-muted)]">{{ __('messages.cookie_category_marketing_desc') }}</p>
                                    </div>
                                    <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                                        <input type="checkbox" x-model="preferences.marketing" class="peer sr-only">
                                        <span class="h-6 w-11 rounded-full bg-[var(--color-surface-3)] after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition peer-checked:bg-brand-600 peer-checked:after:translate-x-5"></span>
                                    </label>
                                </div>
                            </div>
                        @else
                            <div class="rounded-lg border border-dashed border-[var(--color-border)] p-4 opacity-70">
                                <div class="font-medium text-sm">{{ __('messages.cookie_category_marketing') }}</div>
                                <p class="mt-1 text-xs text-[var(--color-text-muted)]">{{ __('messages.cookie_category_not_used') }}</p>
                            </div>
                        @endif
                    </div>

                    <div class="mt-5 flex flex-col gap-2 sm:flex-row">
                        <button type="button" @click="saveCustom()" class="btn-primary text-sm" :disabled="saving">
                            {{ __('messages.cookie_save_preferences') }}
                        </button>
                        <button type="button" @click="customizing = false" class="btn-secondary text-sm" :disabled="saving">
                            {{ __('messages.cancel') }}
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>