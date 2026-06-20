@props(['listing', 'href' => null])

@php
    $thumb = $listing->relationLoaded('images') ? $listing->images->first() : null;
    $link = $href ?? route('admin.listings.edit', $listing);
@endphp

<a href="{{ $link }}" {{ $attributes->class(['block shrink-0 overflow-hidden rounded-lg border border-[var(--color-border)] bg-[var(--color-surface-3)]']) }}>
    @if($thumb)
        <x-listing-image
            :image="$thumb"
            size="thumb"
            :alt="$listing->title"
            class="aspect-[4/3] h-14 w-20 object-cover sm:h-16 sm:w-24"
            :width="96"
            :height="72"
        />
    @else
        <div class="flex aspect-[4/3] h-14 w-20 items-center justify-center px-1 text-center text-[10px] text-[var(--color-text-muted)] sm:h-16 sm:w-24">{{ __('messages.no_photo') }}</div>
    @endif
</a>