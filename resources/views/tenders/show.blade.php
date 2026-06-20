@extends('layouts.app')

@php
    use App\Support\HtmlToPlainText;
    use App\Support\ListingPresenter;
    use App\Support\TenderRules;

    $descriptionText = HtmlToPlainText::sanitize($tender->description);
    $highlightSpecs = array_values(array_filter([
        ['label' => __('messages.year'), 'value' => $tender->year],
        ['label' => __('messages.mileage'), 'value' => $tender->mileage ? number_format($tender->mileage).' '.__('messages.km') : null],
        ['label' => __('messages.fuel_type'), 'value' => ListingPresenter::fuelLabel($tender->fuel_type)],
        ['label' => __('messages.power'), 'value' => $tender->engine_power_hp ? $tender->engine_power_hp.' '.__('messages.hp') : null],
        ['label' => __('messages.transmission'), 'value' => ListingPresenter::transmissionLabel($tender->transmission)],
        ['label' => __('messages.color'), 'value' => $tender->color_exterior],
    ], fn ($spec) => filled($spec['value'])));

    $avatarColors = [
        'bg-sky-500',
        'bg-violet-500',
        'bg-emerald-500',
        'bg-amber-500',
        'bg-rose-500',
        'bg-cyan-500',
        'bg-indigo-500',
        'bg-orange-500',
    ];
@endphp

@section('title', $tender->vehicleName().' — '.__('tenders.title'))

@php
    $displayAmount = $state['current_high_bid'] ?? $state['starting_price'];
    $priceLabel = $state['current_high_bid'] ? __('tenders.current_high_bid') : __('tenders.starting_price');
    $secondsRemaining = $state['seconds_remaining'];
    $daysRemaining = intdiv($secondsRemaining, 86400);
    $hoursRemaining = intdiv($secondsRemaining % 86400, 3600);
    $minutesRemaining = intdiv($secondsRemaining % 3600, 60);
    $secsRemaining = $secondsRemaining % 60;
    $countdownTime = sprintf('%02d:%02d:%02d', $hoursRemaining, $minutesRemaining, $secsRemaining);
    $countdownInitial = $daysRemaining > 0
        ? ($daysRemaining === 1
            ? __('tenders.countdown_one_day').' '.$countdownTime
            : __('tenders.countdown_many_days', ['count' => $daysRemaining]).' '.$countdownTime)
        : $countdownTime;
    $displayAmountBgn = round($displayAmount * config('leasing.eur_to_bgn', 1.95583), 0);
@endphp

@section('content')
<div class="mx-auto max-w-7xl px-4 py-6">
    <div class="grid gap-8 lg:grid-cols-12">
        <div class="space-y-6 lg:col-span-7">
            <x-tender-gallery :tender="$tender" />

            <div>
                <h1 class="text-xl font-semibold lg:text-2xl">{{ $tender->vehicleName() }}</h1>
                <div class="mt-2 flex flex-wrap gap-2">
                    <span class="badge bg-[var(--color-surface-3)]">{{ $tender->reference_number }}</span>
                    <span class="badge bg-[var(--color-surface-3)]">{{ __('tenders.seller_type_'.$tender->sellerTypeKey()) }}</span>
                    <span class="badge bg-[var(--color-surface-3)]">{{ __('tenders.seller_hidden') }}</span>
                    @if($tender->publicLocationLabel())
                        <span class="badge bg-[var(--color-surface-3)]">{{ $tender->publicLocationLabel() }}</span>
                    @endif
                </div>
            </div>

            @if($highlightSpecs !== [])
                <div class="card overflow-hidden">
                    <div class="tender-spec-grid gap-px bg-[var(--color-border)]">
                        @foreach($highlightSpecs as $spec)
                            <div class="flex flex-col bg-[var(--color-surface)] px-4 py-3 text-sm">
                                <span class="text-xs text-[var(--color-text-muted)]">{{ $spec['label'] }}</span>
                                <span class="mt-0.5 font-semibold">{{ $spec['value'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($descriptionText)
                <div class="card p-6">
                    <h2 class="text-lg font-semibold">{{ __('messages.description') }}</h2>
                    <div class="prose prose-sm mt-4 max-w-none whitespace-pre-line text-[var(--color-text-muted)] dark:prose-invert">{{ $descriptionText }}</div>
                </div>
            @endif
        </div>

        <div
            class="space-y-4 lg:col-span-5"
            x-data="tenderBidding(@js([
                    'stateUrl' => route('tenders.state', $tender),
                    'bidUrl' => route('tenders.bids.store', $tender),
                    'acceptRulesUrl' => route('tenders.accept-rules'),
                    'loginUrl' => route('login'),
                    'isAuthenticated' => auth()->check(),
                    'isSeller' => $isSeller,
                    'initialState' => $state,
                    'eurToBgn' => config('leasing.eur_to_bgn', 1.95583),
                    'rulesItems' => TenderRules::items(),
                    'avatarColors' => $avatarColors,
                    'labels' => [
                        'bgn' => __('messages.bgn'),
                        'countdownOneDay' => __('tenders.countdown_one_day'),
                        'countdownManyDays' => __('tenders.countdown_many_days'),
                        'endsIn' => __('tenders.ends_in'),
                        'ended' => __('tenders.ended'),
                        'awarded' => __('tenders.awarded'),
                        'noBids' => __('tenders.no_bids_yet'),
                        'currentHigh' => __('tenders.current_high_bid'),
                        'starting' => __('tenders.starting_price'),
                        'minNext' => __('tenders.minimum_next_bid'),
                        'placeBid' => __('tenders.place_bid'),
                        'yourBid' => __('tenders.your_bid'),
                        'revokeBid' => __('tenders.revoke_bid'),
                        'loginToBid' => __('tenders.login_to_bid'),
                        'bidRanking' => __('tenders.bid_ranking'),
                        'bidRankingEmpty' => __('tenders.bid_ranking_empty'),
                        'bidHistory' => __('tenders.bid_history'),
                        'bidHistoryEmpty' => __('tenders.bid_history_empty'),
                        'rulesTitle' => __('tenders.rules_title'),
                        'rulesAgree' => __('tenders.rules_agree_button'),
                        'rulesClose' => __('tenders.rules_close'),
                        'rulesOpen' => __('tenders.rules_open'),
                        'rulesAgreeLabel' => __('tenders.rules_agree_label'),
                        'rulesLink' => __('tenders.rules_link'),
                        'statusActive' => __('tenders.bid_status_active_short'),
                        'statusOutbid' => __('tenders.bid_status_outbid_short'),
                        'statusWon' => __('tenders.bid_status_won_short'),
                        'bidTooLow' => __('tenders.bid_too_low', ['min' => ':min']),
                    ],
                ]))"
        >
            <div class="card sticky top-24 space-y-4 p-5 lg:p-6">
                @if($isSeller)
                    <a href="{{ route('my.tenders.manage', $tender) }}" class="block text-right text-sm text-brand-600 hover:underline">{{ __('tenders.seller_panel') }}</a>
                @endif
                <div class="text-center">
                    <div class="text-xs uppercase tracking-wide text-[var(--color-text-muted)]" x-text="countdownLabel">{{ __('tenders.ends_in') }}</div>
                    <div class="mt-1 font-mono text-3xl font-bold tabular-nums text-brand-600" x-text="countdownDisplay">{{ $countdownInitial }}</div>
                </div>

                <div class="rounded-lg bg-[var(--color-surface-3)] p-4 text-center">
                    <div class="text-xs text-[var(--color-text-muted)]" x-text="state.current_high_bid ? labels.currentHigh : labels.starting">{{ $priceLabel }}</div>
                    <div class="mt-1 text-2xl font-bold" x-text="formatMoney(displayAmount)">{{ number_format($displayAmount) }} €</div>
                    <div class="mt-0.5 text-sm text-[var(--color-text-muted)]" x-text="formatMoneyBgn(displayAmount)">{{ number_format($displayAmountBgn) }} {{ __('messages.bgn') }}</div>
                    <div class="mt-1 text-xs text-[var(--color-text-muted)]" x-text="bidCountLabel">{{ trans_choice('tenders.bid_count', $state['bid_count']) }}</div>
                </div>

                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--color-text-muted)]" x-text="labels.bidRanking">{{ __('tenders.bid_ranking') }}</h3>
                    <template x-if="!state.bid_ranking?.length">
                        <p class="mt-2 text-sm text-[var(--color-text-muted)]" x-text="labels.bidRankingEmpty">{{ __('tenders.bid_ranking_empty') }}</p>
                    </template>
                    <div class="mt-2 flex flex-row flex-nowrap items-center gap-2 overflow-x-auto pb-0.5" role="list">
                        <template x-for="entry in state.bid_ranking || []" :key="'rank-' + entry.id">
                            <div
                                role="listitem"
                                class="inline-flex shrink-0 flex-row items-center gap-1.5 rounded-lg border-2 px-2 py-1.5"
                                :class="entry.is_leader ? 'border-amber-400 bg-amber-50 dark:border-amber-500 dark:bg-amber-950/30' : 'border-[var(--color-border)] bg-[var(--color-surface-3)]'"
                            >
                                <div
                                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-white"
                                    :class="avatarColors[entry.avatar_index] || avatarColors[0]"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-3 w-3 opacity-90" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="min-w-0 leading-tight">
                                    <div class="truncate text-[10px] font-medium" x-text="entry.anonymous_label"></div>
                                    <div class="text-[11px] font-bold tabular-nums" x-text="formatMoney(entry.amount)"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--color-text-muted)]" x-text="labels.bidHistory">{{ __('tenders.bid_history') }}</h3>
                    <template x-if="!state.bid_history?.length">
                        <p class="mt-3 text-sm text-[var(--color-text-muted)]" x-text="labels.bidHistoryEmpty">{{ __('tenders.bid_history_empty') }}</p>
                    </template>
                    <ul class="mt-3 max-h-56 space-y-3 overflow-y-auto">
                        <template x-for="entry in state.bid_history || []" :key="'hist-' + entry.id">
                            <li
                                class="flex items-center gap-3 rounded-lg border-2 p-2 transition-colors"
                                :class="entry.is_leader ? 'border-amber-400 bg-amber-50 dark:border-amber-500 dark:bg-amber-950/30' : 'border-transparent'"
                            >
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold text-white"
                                    :class="avatarColors[entry.avatar_index] || avatarColors[0]"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5 opacity-90" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="truncate text-sm font-medium" x-text="entry.anonymous_label"></span>
                                        <span
                                            class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wide"
                                            :class="{
                                                'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200': entry.is_leader,
                                                'bg-[var(--color-surface-3)] text-[var(--color-text-muted)]': !entry.is_leader,
                                            }"
                                            x-text="bidStatusLabel(entry)"
                                        ></span>
                                    </div>
                                    <div class="text-sm text-[var(--color-text-muted)]">
                                        <span class="font-semibold text-[var(--color-text)]" x-text="formatMoney(entry.amount)"></span>
                                        <span> · </span>
                                        <span x-text="entry.placed_ago"></span>
                                    </div>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>

                <div class="space-y-3">
                    @guest
                        <template x-if="state.is_biddable">
                            <a href="{{ route('login') }}" class="btn-primary block w-full text-center">{{ __('tenders.login_to_bid') }}</a>
                        </template>
                    @else
                        <template x-if="isSeller && state.is_biddable">
                            <p class="text-center text-sm text-[var(--color-text-muted)]">{{ __('tenders.cannot_bid_own') }}</p>
                        </template>

                        <template x-if="!isSeller && state.my_bid">
                            <div class="space-y-3">
                                <div class="text-center">
                                    <div class="text-xs text-[var(--color-text-muted)]" x-text="labels.yourBid">{{ __('tenders.your_bid') }}</div>
                                    <div class="text-xl font-bold" x-text="formatMoney(state.my_bid.amount)"></div>
                                </div>
                                <button
                                    type="button"
                                    class="text-sm font-medium text-brand-600 hover:underline"
                                    x-show="rulesAccepted"
                                    @click="openRulesModal('read')"
                                    x-text="labels.rulesOpen"
                                >{{ __('tenders.rules_open') }}</button>
                                <button
                                    type="button"
                                    class="btn-secondary w-full"
                                    x-show="state.my_bid.revocable"
                                    :disabled="loading"
                                    @click="revokeBid"
                                    x-text="labels.revokeBid"
                                >{{ __('tenders.revoke_bid') }}</button>
                            </div>
                        </template>

                        <template x-if="!isSeller && !state.my_bid && state.is_biddable">
                            <div class="space-y-3">
                                <template x-if="!rulesAccepted">
                                    <button
                                        type="button"
                                        @click="openRulesModal('agree')"
                                        class="flex w-full items-start gap-3 rounded-lg border border-[var(--color-border)] p-3 text-left transition hover:border-brand-500"
                                    >
                                        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded border-2 border-[var(--color-border)] bg-[var(--color-surface)]"></span>
                                        <span class="text-sm leading-snug">
                                            <span x-text="labels.rulesAgreeLabel"></span>
                                            <span class="font-medium text-brand-600 underline" x-text="labels.rulesLink"></span>
                                        </span>
                                    </button>
                                </template>

                                <button
                                    type="button"
                                    class="text-sm font-medium text-brand-600 hover:underline"
                                    x-show="rulesAccepted"
                                    @click="openRulesModal('read')"
                                    x-text="labels.rulesOpen"
                                >{{ __('tenders.rules_open') }}</button>

                                <div class="space-y-3">
                                    <div>
                                        <label class="text-xs text-[var(--color-text-muted)]" x-text="labels.minNext">{{ __('tenders.minimum_next_bid') }}</label>
                                        <div class="mt-1 flex items-stretch gap-2">
                                            <button type="button" class="btn-secondary px-3" :disabled="loading || bidAmount <= state.minimum_next_bid" @click="decreaseBid()" aria-label="-">−</button>
                                            <div class="input flex flex-1 items-center justify-center font-semibold tabular-nums" x-text="formatMoney(bidAmount)"></div>
                                            <button type="button" class="btn-secondary px-3" :disabled="loading" @click="increaseBid()" aria-label="+">+</button>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-primary w-full" :disabled="loading" @click="placeBid()" x-text="labels.placeBid">{{ __('tenders.place_bid') }}</button>
                                    <p x-show="error" x-text="error" class="text-sm text-red-600"></p>
                                </div>
                            </div>
                        </template>
                    @endguest

                    <template x-if="!state.is_biddable">
                        <p class="text-center text-sm text-[var(--color-text-muted)]" x-text="endedLabel"></p>
                    </template>
                </div>

            </div>

            <template x-teleport="body">
                <div
                    x-show="rulesModalOpen"
                    x-cloak
                    class="fixed inset-0 z-[200] flex items-center justify-center p-4"
                    @keydown.escape.window="closeRulesModal()"
                >
                    <div class="absolute inset-0 bg-black/50" @click="closeRulesModal()"></div>
                    <div class="relative z-10 max-h-[85vh] w-full max-w-lg overflow-y-auto rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] p-6 shadow-xl" role="dialog" aria-modal="true">
                        <h3 class="text-lg font-bold" x-text="labels.rulesTitle">{{ __('tenders.rules_title') }}</h3>
                        <ul class="mt-4 list-disc space-y-2 pl-5 text-sm leading-relaxed text-[var(--color-text-muted)]">
                            <template x-for="(item, index) in rulesItems" :key="index">
                                <li x-text="item"></li>
                            </template>
                        </ul>
                        <div class="mt-6 flex justify-end gap-2">
                            <template x-if="rulesModalMode === 'agree'">
                                <button type="button" class="btn-primary" :disabled="rulesLoading" @click="acceptRules()" x-text="labels.rulesAgree">{{ __('tenders.rules_agree_button') }}</button>
                            </template>
                            <template x-if="rulesModalMode === 'read'">
                                <button type="button" class="btn-secondary" @click="closeRulesModal()" x-text="labels.rulesClose">{{ __('tenders.rules_close') }}</button>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection