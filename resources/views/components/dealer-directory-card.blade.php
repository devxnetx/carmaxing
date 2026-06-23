@props(['company'])

@php
    $listingsCount = (int) ($company->published_listings_count ?? 0);
    $location = $company->locationLabel();
@endphp

<article class="card p-4 transition hover:border-brand-500/40 hover:shadow-md">
    <div class="flex gap-3">
        <a href="{{ route('company.show', $company) }}" class="shrink-0">
            <div class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-lg border border-[var(--color-border)] bg-[var(--color-surface-3)] text-sm font-bold text-brand-600">
                @if($company->logoUrl())
                    <img src="{{ $company->logoUrl() }}" alt="" class="h-full w-full object-cover">
                @else
                    {{ strtoupper(substr($company->name, 0, 2)) }}
                @endif
            </div>
        </a>

        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-2">
                <a href="{{ route('company.show', $company) }}" class="min-w-0">
                    <h2 class="line-clamp-2 text-base font-semibold leading-snug hover:text-brand-600">{{ $company->name }}</h2>
                </a>
                <x-verified-badge :company="$company" />
            </div>

            @if($location)
                <p class="mt-1 flex items-center gap-1 text-sm text-[var(--color-text-muted)]">
                    <x-icon name="map-pin" class="h-3.5 w-3.5 shrink-0 text-brand-600/80" />
                    <span class="truncate">{{ $location }}</span>
                </p>
            @endif

            <p class="mt-2 text-xs text-[var(--color-text-muted)]">
                {{ trans_choice('messages.dealer_listings_count', $listingsCount, ['count' => number_format($listingsCount)]) }}
            </p>
        </div>
    </div>
</article>