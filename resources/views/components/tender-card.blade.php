@props(['tender'])

@php
    use App\Services\Tenders\TenderBidRanking;

    $images = $tender->images;
    $mainImage = $images->firstWhere('is_primary', true) ?? $images->first();
    $extraImages = $images->filter(fn ($img) => $mainImage === null || $img->id !== $mainImage->id)->take(4);
    $photoCount = $images->count();
    $imageAlt = $tender->vehicleName();

    $secondsRemaining = $tender->secondsRemaining();
    $daysRemaining = intdiv($secondsRemaining, 86400);
    $hoursRemaining = intdiv($secondsRemaining % 86400, 3600);
    $minutesRemaining = intdiv($secondsRemaining % 3600, 60);
    $secsRemaining = $secondsRemaining % 60;
    $countdownTime = sprintf('%02d:%02d:%02d', $hoursRemaining, $minutesRemaining, $secsRemaining);
    $countdownDisplay = $daysRemaining > 0
        ? ($daysRemaining === 1
            ? __('tenders.countdown_one_day').' '.$countdownTime
            : __('tenders.countdown_many_days', ['count' => $daysRemaining]).' '.$countdownTime)
        : $countdownTime;

    $displayAmount = $tender->current_high_bid_amount ?? $tender->starting_price;
    $priceLabel = $tender->current_high_bid_amount ? __('tenders.current_high_bid') : __('tenders.starting_price');
    $displayAmountBgn = round($displayAmount * config('leasing.eur_to_bgn', 1.95583), 0);

    $topRanking = array_slice(app(TenderBidRanking::class)->forTender($tender, auth()->user()), 0, 3);

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

<article class="card group flex h-full flex-col overflow-hidden transition hover:border-brand-500/40 hover:shadow-md">
    <a href="{{ route('tenders.show', $tender) }}" class="tender-card-photos">
        <div class="tender-card-photos-main">
            @if($mainImage)
                <img
                    src="{{ $mainImage->url('medium') }}"
                    alt="{{ $imageAlt }}"
                    class="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                    width="400"
                    height="300"
                    loading="lazy"
                >
            @else
                <div class="flex h-full items-center justify-center text-xs text-[var(--color-text-muted)]">{{ __('messages.no_photo') }}</div>
            @endif
            @if($photoCount > 0)
                <span class="absolute bottom-2 left-2 rounded-md bg-black/70 px-1.5 py-0.5 text-[10px] text-white">
                    <x-icon name="image" class="mr-0.5 inline h-3 w-3" />{{ $photoCount }}
                </span>
            @endif
        </div>

        @if($photoCount > 1)
            <div class="tender-card-photos-thumbs">
                @for($i = 0; $i < 4; $i++)
                    @php $thumb = $extraImages->values()->get($i); @endphp
                    <div class="tender-card-photos-thumb">
                        @if($thumb)
                            <img
                                src="{{ $thumb->url('thumb') }}"
                                alt=""
                                class="absolute inset-0 h-full w-full object-cover"
                                width="44"
                                height="34"
                                loading="lazy"
                            >
                        @endif
                    </div>
                @endfor
            </div>
        @endif
    </a>

    <a href="{{ route('tenders.show', $tender) }}" class="flex min-h-0 flex-1 flex-col px-3 pb-3 pt-1 sm:px-4 sm:pb-4">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <div class="text-[10px] uppercase tracking-wide text-[var(--color-text-muted)]">{{ __('tenders.ends_in') }}</div>
                <div class="font-mono text-base font-bold leading-tight tabular-nums text-brand-600">{{ $countdownDisplay }}</div>
            </div>
            <span class="shrink-0 rounded bg-[var(--color-surface-3)] px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-[var(--color-text-muted)]">
                {{ $tender->reference_number }}
            </span>
        </div>

        <h3 class="mt-1.5 line-clamp-2 text-base font-semibold leading-snug group-hover:text-brand-600">
            {{ $tender->vehicleName() }}
        </h3>

        @if($tender->publicLocationLabel())
            <p class="mt-1 truncate text-xs text-[var(--color-text-muted)]">
                <x-icon name="map-pin" class="mr-1 inline h-3.5 w-3.5 text-brand-600/80" />
                {{ $tender->publicLocationLabel() }}
            </p>
        @endif

        <div class="mt-2 flex items-end justify-between gap-2">
            <div class="min-w-0">
                <div class="text-[10px] uppercase tracking-wide text-[var(--color-text-muted)]">{{ $priceLabel }}</div>
                <div class="text-lg font-bold leading-tight text-brand-600">{{ number_format($displayAmount) }} €</div>
                <div class="text-[11px] leading-tight text-[var(--color-text-muted)]">{{ number_format($displayAmountBgn) }} {{ __('messages.bgn') }}</div>
            </div>
            <div class="shrink-0 text-right text-xs text-[var(--color-text-muted)]">
                {{ trans_choice('tenders.bid_count', $tender->bid_count) }}
            </div>
        </div>

        <div class="mt-2.5">
            @if($topRanking === [])
                <p class="text-xs text-[var(--color-text-muted)]">{{ __('tenders.bid_ranking_empty') }}</p>
            @else
                <div class="flex flex-row flex-nowrap items-center gap-1.5 overflow-x-auto pb-0.5" role="list">
                    @foreach($topRanking as $entry)
                        <div
                            role="listitem"
                            class="inline-flex shrink-0 flex-row items-center gap-1.5 rounded-lg border px-2 py-1.5 {{ $entry['is_leader'] ? 'border-amber-400 bg-amber-50 dark:border-amber-500 dark:bg-amber-950/30' : 'border-[var(--color-border)] bg-[var(--color-surface-3)]' }}"
                        >
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-white {{ $avatarColors[$entry['avatar_index']] ?? $avatarColors[0] }}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-3 w-3 opacity-90" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="min-w-0 leading-tight">
                                <div class="tender-card-rank-label text-[10px] font-medium">{{ $entry['anonymous_label'] }}</div>
                                <div class="text-[11px] font-bold tabular-nums">{{ number_format($entry['amount']) }} €</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </a>
</article>