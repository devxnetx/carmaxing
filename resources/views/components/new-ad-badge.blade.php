@props(['listing'])

@if($listing->isNewAd())
    <span
        {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full bg-yellow-400 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-yellow-950 shadow-sm sm:text-xs dark:bg-yellow-500 dark:text-yellow-950']) }}
        title="{{ __('messages.new_ad_tooltip', ['days' => config('listings.new_ad_days', 7)]) }}"
    >
        {{ __('messages.new_ad') }}
    </span>
@endif