@props(['lot'])

@php
    use Illuminate\Support\Str;

    $vehicleName = $lot->vehicleName();
    $imageUrl = $lot->mainImageUrl();
    $detailUrl = $lot->detailUrl();
    $auctionTime = $lot->auctionTimeLabel();
@endphp

<article class="card group overflow-hidden transition hover:border-brand-500/40 hover:shadow-md">
    <div class="flex flex-col gap-4 p-3 sm:flex-row sm:items-stretch sm:gap-5 sm:p-4">
        <a href="{{ $detailUrl }}" target="_blank" rel="noopener noreferrer" class="auction-lot-card-photo shrink-0">
            @if($imageUrl)
                <img src="{{ $imageUrl }}" alt="{{ $vehicleName }}" class="h-full w-full object-cover" loading="lazy">
            @else
                <div class="flex h-full items-center justify-center px-2 text-center text-xs text-[var(--color-text-muted)]">{{ __('messages.no_photo') }}</div>
            @endif
            @if($lot->auction_source)
                <span class="absolute left-2 top-2 rounded-md bg-black/70 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-white">
                    {{ $lot->auction_source }}
                </span>
            @endif
        </a>

        <div class="flex min-w-0 flex-1 flex-col gap-2">
            <div class="flex items-start justify-between gap-3">
                <a href="{{ $detailUrl }}" target="_blank" rel="noopener noreferrer" class="min-w-0 flex-1">
                    <h3 class="line-clamp-2 text-base font-semibold leading-snug group-hover:text-brand-600">
                        {{ $vehicleName }}
                    </h3>
                    <p class="mt-0.5 text-xs text-[var(--color-text-muted)]">{{ $lot->external_lot }}</p>
                </a>

                <div class="shrink-0 text-right leading-tight">
                    @if($lot->estimated_min_usd || $lot->estimated_max_usd)
                        <div class="text-lg font-bold text-brand-600 sm:text-xl">
                            @if($lot->estimated_min_usd && $lot->estimated_max_usd && $lot->estimated_min_usd !== $lot->estimated_max_usd)
                                ${{ number_format($lot->estimated_min_usd) }}–${{ number_format($lot->estimated_max_usd) }}
                            @else
                                ${{ number_format($lot->estimated_min_usd ?: $lot->estimated_max_usd) }}
                            @endif
                        </div>
                        <div class="mt-0.5 text-xs text-[var(--color-text-muted)]">{{ __('messages.estimated_value') }}</div>
                    @elseif($lot->prebid_price_usd)
                        <div class="text-lg font-bold text-brand-600 sm:text-xl">${{ number_format($lot->prebid_price_usd) }}</div>
                        <div class="mt-0.5 text-xs text-[var(--color-text-muted)]">{{ __('messages.current_bid') }}</div>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-[var(--color-text-muted)]">
                @if($lot->odometer_km)
                    <span>{{ number_format($lot->odometer_km) }} km</span>
                @elseif($lot->odometer)
                    <span>{{ number_format($lot->odometer) }} mi</span>
                @endif
                @if($lot->primary_damage)
                    <span>{{ $lot->primary_damage }}</span>
                @endif
                @if($lot->start_code)
                    <span>{{ $lot->start_code }}</span>
                @endif
            </div>

            <div class="mt-auto flex flex-wrap items-center gap-2 pt-1">
                @if($auctionTime)
                    <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-medium text-brand-700 dark:bg-brand-950 dark:text-brand-200">
                        <x-icon name="clock" class="h-3.5 w-3.5" />
                        {{ $auctionTime }}
                    </span>
                @endif
                @if($lot->search_status)
                    <span class="rounded-full bg-[var(--color-surface-3)] px-2.5 py-0.5 text-xs font-medium capitalize">{{ $lot->search_status }}</span>
                @endif
                @if($lot->vin)
                    <span class="text-xs text-[var(--color-text-muted)]">VIN {{ Str::limit($lot->vin, 11, '…') }}</span>
                @endif
            </div>
        </div>
    </div>
</article>