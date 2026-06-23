@props(['listing'])

@php
    use Illuminate\Support\Carbon;

    $locale = app()->getLocale();
    $postedAt = $listing->published_at ?? $listing->created_at;
    $postedAgo = $postedAt
        ? Carbon::parse($postedAt)->locale($locale)->diffForHumans()
        : null;
    $updatedAgo = $listing->updated_at
        ? Carbon::parse($listing->updated_at)->locale($locale)->diffForHumans()
        : null;
    $showUpdated = $postedAt
        && $listing->updated_at
        && $listing->updated_at->gt($postedAt->copy()->addMinute());
@endphp

@if($postedAgo)
    <p {{ $attributes->merge(['class' => 'text-xs text-[var(--color-text-muted)]']) }}>
        {{ __('messages.listing_posted_ago', ['time' => $postedAgo]) }}@if($showUpdated && $updatedAgo)<span class="text-[10px]">, {{ __('messages.listing_updated_ago', ['time' => $updatedAgo]) }}</span>@endif
    </p>
@endif