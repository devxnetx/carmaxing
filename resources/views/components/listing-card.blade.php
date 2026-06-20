@props(['listing'])

<a href="{{ route('listings.show', $listing) }}" class="card group overflow-hidden transition hover:shadow-md">
    <div class="aspect-[4/3] bg-[var(--color-surface-3)]">
        @if($listing->images->first())
            <x-listing-image :image="$listing->images->first()" size="medium" :alt="$listing->title" class="h-full w-full object-cover" :width="400" :height="300" />
        @else
            <div class="flex h-full items-center justify-center text-[var(--color-text-muted)]">No photo</div>
        @endif
    </div>
    <div class="p-4">
        <h3 class="line-clamp-2 font-medium group-hover:text-brand-600">{{ $listing->title }}</h3>
        <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ $listing->brand->name }} · {{ $listing->year }} · {{ number_format($listing->mileage ?? 0) }} {{ __('messages.km') }}</p>
        <div class="mt-3 flex items-baseline justify-between">
            <span class="text-lg font-bold text-brand-600">{{ number_format($listing->price) }} {{ __('messages.eur') }}</span>
            <span class="text-xs text-[var(--color-text-muted)]">{{ number_format($listing->priceInBgn()) }} {{ __('messages.bgn') }}</span>
        </div>
        @if($listing->company)
            <p class="mt-2 text-xs text-[var(--color-text-muted)]">{{ $listing->company->name }}</p>
        @endif
    </div>
</a>