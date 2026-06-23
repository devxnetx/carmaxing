@props(['lot'])

@php
    use Illuminate\Support\Str;

    $vehicleName = $lot->vehicleName();
    $imageUrl = $lot->mainImageUrl();
    $detailUrl = $lot->detailUrl();
    $auctionTime = $lot->auctionTimeLabel();
@endphp

<article class="listing-grid-card card group flex h-full flex-col overflow-hidden transition hover:border-brand-500/40 hover:shadow-md">
    <div class="relative">
        <a href="{{ $detailUrl }}" target="_blank" rel="noopener noreferrer" class="listing-grid-card-photos block w-full">
            <div class="listing-grid-card-photos-main">
                @if($imageUrl)
                    <img
                        src="{{ $imageUrl }}"
                        alt="{{ $vehicleName }}"
                        class="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                        loading="lazy"
                        decoding="async"
                    >
                @else
                    <div class="flex h-full items-center justify-center text-xs text-[var(--color-text-muted)]">{{ __('messages.no_photo') }}</div>
                @endif
                @if($lot->auction_source)
                    <span class="pointer-events-none absolute left-2 top-2 rounded-md bg-black/70 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-white">
                        {{ $lot->auction_source }}
                    </span>
                @endif
            </div>
        </a>
    </div>

    <a href="{{ $detailUrl }}" target="_blank" rel="noopener noreferrer" class="flex min-h-0 flex-1 flex-col p-2.5 sm:p-3">
        <h3 class="line-clamp-2 text-sm font-semibold leading-snug group-hover:text-brand-600 sm:text-base">
            {{ $vehicleName }}
        </h3>

        <p class="mt-0.5 line-clamp-1 text-xs text-[var(--color-text-muted)]">{{ $lot->external_lot }}</p>

        <p class="listing-grid-card-spec mt-1 text-[var(--color-text-muted)]">
            @if($lot->odometer_km)
                {{ number_format($lot->odometer_km) }} km
            @elseif($lot->odometer)
                {{ number_format($lot->odometer) }} mi
            @endif
            @if($lot->primary_damage)
                · {{ $lot->primary_damage }}
            @endif
        </p>

        <div class="mt-auto flex items-end justify-between gap-2 pt-1.5">
            <div class="min-w-0 text-xs text-[var(--color-text-muted)]">
                @if($auctionTime)
                    <span class="flex items-center gap-1 text-brand-700 dark:text-brand-300">
                        <x-icon name="clock" class="h-3.5 w-3.5 shrink-0" />
                        <span class="line-clamp-2">{{ $auctionTime }}</span>
                    </span>
                @endif
            </div>

            <div class="shrink-0 text-right leading-tight">
                @if($lot->estimated_min_usd || $lot->estimated_max_usd)
                    <div class="text-base font-bold text-brand-600 sm:text-lg">
                        @if($lot->estimated_min_usd && $lot->estimated_max_usd && $lot->estimated_min_usd !== $lot->estimated_max_usd)
                            ${{ number_format($lot->estimated_min_usd) }}–${{ number_format($lot->estimated_max_usd) }}
                        @else
                            ${{ number_format($lot->estimated_min_usd ?: $lot->estimated_max_usd) }}
                        @endif
                    </div>
                    <div class="text-[11px] text-[var(--color-text-muted)]">{{ __('messages.estimated_value') }}</div>
                @elseif($lot->prebid_price_usd)
                    <div class="text-base font-bold text-brand-600 sm:text-lg">${{ number_format($lot->prebid_price_usd) }}</div>
                    <div class="text-[11px] text-[var(--color-text-muted)]">{{ __('messages.current_bid') }}</div>
                @endif
            </div>
        </div>
    </a>
</article>