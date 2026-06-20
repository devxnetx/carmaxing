@props(['listing'])

@php
    $image = $listing->images->firstWhere('is_primary', true) ?? $listing->images->first();
@endphp

<a
    href="{{ route('listings.show', $listing) }}"
    {{ $attributes->merge([
        'class' => 'recently-viewed-pill inline-flex max-w-full items-center gap-2 rounded-full border border-[var(--color-border)] bg-[var(--color-surface)] py-1.5 pl-1 text-sm transition-colors hover:border-brand-500 hover:text-brand-600',
    ]) }}
>
    <div class="h-9 w-12 shrink-0 overflow-hidden rounded-full bg-[var(--color-surface-3)]">
        @if($image)
            <x-listing-image
                :image="$image"
                size="thumb"
                :alt="$listing->vehicleName()"
                class="h-full w-full object-cover"
                :width="96"
                :height="72"
            />
        @else
            <div class="flex h-full w-full items-center justify-center text-[var(--color-text-muted)]">
                <x-icon name="image" class="h-4 w-4 opacity-50" />
            </div>
        @endif
    </div>
    <span class="recently-viewed-pill-text min-w-0 leading-tight">
        <span class="block truncate font-medium">{{ $listing->vehicleName() }}</span>
        @if($listing->ad_name)
            <span class="block truncate text-xs text-[var(--color-text-muted)]">{{ $listing->ad_name }}</span>
        @endif
    </span>
</a>