@props(['listing', 'isFavorited' => false, 'latestPriceChange' => null, 'marketEstimate' => null])

@php
    $shareUrl = route('listings.show', $listing);
    $shareText = $listing->composeDisplayTitle();
    $banks = collect(config('leasing.banks'))->map(fn ($b) => [
        'slug' => $b['slug'],
        'name' => $b['name'],
        'annual_rate' => $b['annual_rate'],
        'min_down_payment' => $b['min_down_payment'],
        'max_months' => $b['max_months'],
    ])->values();
    $isOwner = auth()->id() === $listing->user_id;
@endphp

<div class="space-y-4 lg:sticky lg:top-20 lg:self-start">
    <div class="card p-6">
        <h1 class="text-xl font-bold leading-tight">
            {{ $listing->vehicleName() }}
        </h1>
        @if($listing->ad_name)
            <p class="mt-1 text-base text-[var(--color-text-muted)]">{{ $listing->ad_name }}</p>
        @endif
        @if($listing->locationLabel())
            <p class="mt-2 text-sm text-[var(--color-text-muted)]">
                <x-icon name="map-pin" class="mr-1 inline h-4 w-4" />{{ $listing->locationLabel() }}
            </p>
        @endif

        <div class="mt-4" @if($listing->hasFixedPrice() && $listing->priceChanges->isNotEmpty()) x-data="{ priceHistoryOpen: false }" @endif>
            @if($listing->price_on_request)
                <div class="text-2xl font-bold text-brand-600">{{ __('messages.price_on_request') }}</div>
            @else
                <div class="flex flex-wrap items-center gap-2">
                    <div class="text-3xl font-bold text-brand-600">
                        {{ number_format($listing->price) }} {{ __('messages.eur') }}
                    </div>
                    @if($listing->priceChanges->isNotEmpty())
                        <button
                            type="button"
                            @click="priceHistoryOpen = true"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-[var(--color-border)] text-[var(--color-text-muted)] transition hover:border-brand-500 hover:bg-[var(--color-surface-3)] hover:text-brand-600"
                            title="{{ __('messages.price_history_title') }}"
                            aria-label="{{ __('messages.price_history_title') }}"
                        >
                            <x-icon name="arrows-up-down" class="h-4 w-4" />
                        </button>
                    @endif
                </div>
                <div class="text-sm text-[var(--color-text-muted)]">{{ number_format($listing->priceInBgn()) }} {{ __('messages.bgn') }}</div>
            @endif
            @if($listing->price_negotiable && $listing->hasFixedPrice())
                <span class="badge mt-2 bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">{{ __('messages.negotiable') }}</span>
            @endif
            @if($listing->hasFixedPrice() && $listing->priceChanges->isNotEmpty())
                <x-listing-price-history-modal :listing="$listing" />
            @endif
            @isset($marketEstimate)
                @if($marketEstimate['delta'] > 0)
                    <p class="mt-2 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-800 dark:bg-green-950 dark:text-green-200">
                        {{ __('messages.market_value_below', ['amount' => number_format($marketEstimate['delta'])]) }}
                    </p>
                @elseif($marketEstimate['delta'] < 0)
                    <p class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:bg-amber-950 dark:text-amber-200">
                        {{ __('messages.market_value_above', ['amount' => number_format(abs($marketEstimate['delta']))]) }}
                    </p>
                @else
                    <p class="mt-2 text-sm text-[var(--color-text-muted)]">{{ __('messages.market_value_fair') }}</p>
                @endif
            @endisset
        </div>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-2" x-data="listingActions(@js($shareUrl), @js($shareText))">
            <div class="flex flex-wrap gap-2">
                <div x-data="compareButton('{{ $listing->slug }}', '{{ route('compare.add', $listing) }}')">
                    <button type="button" @click="add()" class="btn-secondary text-xs" :disabled="loading">
                        <x-icon name="compare" class="h-4 w-4" /> {{ __('messages.compare_add') }}
                    </button>
                </div>
                <div x-data="favoriteButton('{{ $listing->slug }}', {{ $isFavorited ? 'true' : 'false' }}, {{ auth()->check() ? 'true' : 'false' }}, '{{ route('login') }}')">
                    <button type="button" @click="toggle()" class="btn-secondary text-xs" :class="favorited ? 'text-red-500' : ''">
                        <x-icon name="heart" variant="solid" class="h-4 w-4" x-show="favorited" x-cloak />
                        <x-icon name="heart" class="h-4 w-4" x-show="!favorited" />
                        <span x-text="favorited ? '{{ __('messages.saved') }}' : '{{ __('messages.save_ad') }}'"></span>
                    </button>
                </div>
                <button type="button" onclick="window.print()" class="btn-secondary text-xs">
                    <x-icon name="print" class="h-4 w-4" /> {{ __('messages.print') }}
                </button>
            </div>

            <div class="flex flex-wrap gap-2">
                <div class="relative">
                    <button type="button" @click="reportOpen = false; shareOpen = !shareOpen" class="btn-secondary text-xs">
                        <x-icon name="share" class="h-4 w-4" /> {{ __('messages.share') }}
                    </button>
                    <div x-show="shareOpen" x-cloak @click.outside="shareOpen = false" class="absolute right-0 z-10 mt-2 w-48 rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] p-2 shadow-lg">
                        <button type="button" @click="copyLink()" class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-xs hover:bg-[var(--color-surface-3)]">
                            <x-icon name="link" class="h-4 w-4" /> {{ __('messages.copy_link') }}
                        </button>
                        <a :href="facebookUrl" target="_blank" rel="noopener" class="flex items-center gap-2 rounded-md px-3 py-2 text-xs hover:bg-[var(--color-surface-3)]">
                            <x-icon name="facebook" class="h-4 w-4" /> Facebook
                        </a>
                        <a :href="whatsappUrl" target="_blank" rel="noopener" class="flex items-center gap-2 rounded-md px-3 py-2 text-xs hover:bg-[var(--color-surface-3)]">
                            <x-icon name="whatsapp" class="h-4 w-4" /> WhatsApp
                        </a>
                        <a :href="viberUrl" target="_blank" rel="noopener" class="flex items-center gap-2 rounded-md px-3 py-2 text-xs hover:bg-[var(--color-surface-3)]">
                            <x-icon name="viber" class="h-4 w-4" /> Viber
                        </a>
                    </div>
                </div>
                <div class="relative">
                    <button type="button" @click="shareOpen = false; reportOpen = !reportOpen" class="btn-secondary text-xs">
                        <x-icon name="flag" class="h-4 w-4" /> {{ __('messages.report_problem') }}
                    </button>
                    <div x-show="reportOpen" x-cloak @click.outside="reportOpen = false" class="absolute right-0 z-10 mt-2 w-72 rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] p-4 shadow-lg">
                        <form method="POST" action="{{ route('listings.report', $listing) }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="label">{{ __('messages.report_reason') }}</label>
                                <select name="reason" class="input" required>
                                    <option value="scam">{{ __('messages.report_scam') }}</option>
                                    <option value="wrong_info">{{ __('messages.report_wrong_info') }}</option>
                                    <option value="duplicate">{{ __('messages.report_duplicate') }}</option>
                                    <option value="other">{{ __('messages.report_other') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="label">{{ __('messages.report_details') }}</label>
                                <textarea name="details" rows="3" class="input" placeholder="{{ __('messages.report_details_placeholder') }}"></textarea>
                            </div>
                            <button type="submit" class="btn-secondary w-full text-xs">{{ __('messages.report_submit') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 space-y-2 border-t border-[var(--color-border)] pt-6 text-sm text-[var(--color-text-muted)]">
            @if($listing->displayAdNumber())
                <div class="flex justify-between gap-4">
                    <span>{{ __('messages.ad_number') }}</span>
                    <strong class="text-[var(--color-text)]">#{{ $listing->displayAdNumber() }}</strong>
                </div>
            @endif
            <div class="flex justify-between gap-4">
                <span>{{ __('messages.views') }}</span>
                <strong class="text-[var(--color-text)]">{{ number_format($listing->views_count) }}</strong>
            </div>
            <div class="flex justify-between gap-4">
                <span>{{ __('messages.last_updated') }}</span>
                <strong class="text-[var(--color-text)]">{{ $listing->updated_at->format('d.m.Y H:i') }}</strong>
            </div>
            @if($listing->published_at)
                <div class="flex justify-between gap-4">
                    <span>{{ __('messages.published_at') }}</span>
                    <strong class="text-[var(--color-text)]">{{ $listing->published_at->format('d.m.Y') }}</strong>
                </div>
            @endif
        </div>

        @auth
            @if($isOwner)
                <div class="mt-4 flex gap-2">
                    @if($listing->status->isInactive())
                        <form method="POST" action="{{ route('listings.unarchive', $listing) }}" class="flex-1">
                            @csrf
                            <button type="submit" class="btn-secondary w-full text-brand-600">{{ __('messages.unarchive') }}</button>
                        </form>
                    @else
                        <a href="{{ route('listings.edit', $listing) }}" class="btn-secondary flex-1">{{ __('messages.edit') }}</a>
                        <form method="POST" action="{{ route('listings.archive', $listing) }}" class="flex-1">
                            @csrf
                            <button type="submit" class="btn-secondary w-full text-red-600">{{ __('messages.archive') }}</button>
                        </form>
                    @endif
                </div>
            @endif
        @endauth
    </div>

    @if($listing->hasFixedPrice())
        <div class="card p-6" x-data="leasingCalculator(@js($listing->price), @js($banks), @js(config('leasing')))">
            <h3 class="font-semibold">{{ __('messages.leasing_calculator') }}</h3>
            <p class="mt-1 text-xs text-[var(--color-text-muted)]">{{ __('messages.leasing_disclaimer') }}</p>

            <div class="mt-4 space-y-3">
                <div>
                    <label class="label">{{ __('messages.leasing_bank') }}</label>
                    <select x-model="bankSlug" @change="syncBank()" class="input">
                        <template x-for="bank in banks" :key="bank.slug">
                            <option :value="bank.slug" x-text="bank.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="label">{{ __('messages.leasing_down_payment') }} (<span x-text="downPaymentPercent"></span>%)</label>
                    <input type="range" min="0" max="50" step="5" x-model.number="downPaymentPercent" class="w-full">
                    <p class="mt-1 text-xs text-[var(--color-text-muted)]">
                        <span x-text="formatMoney(downPaymentAmount)"></span> {{ __('messages.eur') }}
                    </p>
                </div>
                <div>
                    <label class="label">{{ __('messages.leasing_term') }} (<span x-text="months"></span> {{ __('messages.leasing_months') }})</label>
                    <input type="range" :min="12" :max="maxMonths" step="6" x-model.number="months" class="w-full">
                </div>
            </div>

            <div class="mt-5 rounded-lg bg-[var(--color-surface-3)] p-4">
                <div class="text-xs text-[var(--color-text-muted)]">{{ __('messages.leasing_monthly') }}</div>
                <div class="text-2xl font-bold text-brand-600">
                    <span x-text="formatMoney(monthlyPayment)"></span> {{ __('messages.eur') }}
                </div>
                <div class="mt-1 text-xs text-[var(--color-text-muted)]">
                    ≈ <span x-text="formatMoney(monthlyPaymentBgn)"></span> {{ __('messages.bgn') }} / {{ __('messages.leasing_months') }}
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-[var(--color-text-muted)]">
                    <div>{{ __('messages.leasing_total') }}: <strong class="text-[var(--color-text)]" x-text="formatMoney(totalPaid)"></strong> €</div>
                    <div>{{ __('messages.leasing_interest') }}: <strong class="text-[var(--color-text)]" x-text="formatMoney(totalInterest)"></strong> €</div>
                </div>
                <p class="mt-2 text-[10px] text-[var(--color-text-muted)]">
                    {{ __('messages.leasing_rate_note') }} <span x-text="selectedBank?.annual_rate"></span>%
                </p>
            </div>
        </div>
    @endif
</div>