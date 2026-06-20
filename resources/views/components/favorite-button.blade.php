@props(['listing', 'favorited' => false, 'size' => 'md'])

@php
    $sizeClasses = match($size) {
        'lg' => 'h-12 w-12 text-xl',
        default => 'h-10 w-10 text-lg',
    };
@endphp

<div
    x-data="favoriteButton('{{ $listing->slug }}', {{ $favorited ? 'true' : 'false' }}, {{ auth()->check() ? 'true' : 'false' }}, '{{ route('login') }}')"
    {{ $attributes->class(['inline-flex']) }}
>
    <button
        type="button"
        @click="toggle()"
        :disabled="loading"
        class="flex items-center justify-center rounded-full border border-[var(--color-border)] transition hover:border-brand-500 hover:bg-[var(--color-surface-3)] {{ $sizeClasses }}"
        :class="favorited ? 'text-red-500 border-red-200 dark:border-red-900' : 'text-[var(--color-text-muted)]'"
        :title="favorited ? '{{ __('messages.remove_from_favorites') }}' : '{{ __('messages.add_to_favorites') }}'"
    >
        <x-icon name="heart" variant="solid" class="h-5 w-5" x-show="favorited" x-cloak />
        <x-icon name="heart" class="h-5 w-5" x-show="!favorited" />
    </button>
</div>