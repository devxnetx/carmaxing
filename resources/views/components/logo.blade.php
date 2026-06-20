@props(['size' => 'md', 'showText' => true, 'stacked' => true])

@php
    $sizes = [
        'sm' => ['box' => 'h-8 w-8', 'icon' => 'h-4 w-4', 'title' => 'text-sm', 'tagline' => 'text-[10px]'],
        'md' => ['box' => 'h-9 w-9', 'icon' => 'h-5 w-5', 'title' => 'text-base', 'tagline' => 'text-xs'],
        'lg' => ['box' => 'h-10 w-10', 'icon' => 'h-5 w-5', 'title' => 'text-lg', 'tagline' => 'text-xs'],
    ];
    $s = $sizes[$size] ?? $sizes['md'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
    <span class="{{ $s['box'] }} flex shrink-0 items-center justify-center rounded-lg bg-brand-600" aria-hidden="true">
        <svg class="{{ $s['icon'] }} text-white" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
            <path d="M5 11h1.4l1-3a1.5 1.5 0 0 1 1.4-1h8.4a1.5 1.5 0 0 1 1.4 1l1 3H20v1.5h-1.2a1.8 1.8 0 1 1-3.6 0H8.8a1.8 1.8 0 1 1-3.6 0H5V11zm2.7 2.8a.9.9 0 1 0 0-1.8.9.9 0 0 0 0 1.8zm8.6 0a.9.9 0 1 0 0-1.8.9.9 0 0 0 0 1.8zM8.3 10.5h7.4l-.9-2.7H9.2l-.9 2.7z"/>
        </svg>
    </span>
    @if($showText)
        <span class="{{ $stacked ? 'leading-tight' : 'flex items-baseline gap-2' }}">
            <span class="{{ $s['title'] }} font-semibold">{{ config('app.name', 'CARMAXING') }}</span>
            @if($stacked)
                <span class="{{ $s['tagline'] }} block text-[var(--color-text-muted)]">{{ __('messages.tagline') }}</span>
            @endif
        </span>
    @endif
</span>