@props(['mobile' => false])

@php
    use App\Support\AdminNavigation;
    $links = AdminNavigation::items();
@endphp

@foreach($links as $link)
    @php $active = AdminNavigation::isActive($link['route']); @endphp
    <a
        href="{{ route($link['route']) }}"
        @class([
            'flex items-center gap-2 rounded-lg px-3 py-2 text-sm transition',
            'bg-brand-600 text-white' => $active && ! $mobile,
            'bg-brand-100 text-brand-700 dark:bg-brand-950 dark:text-brand-300' => $active && $mobile,
            'text-[var(--color-text)] hover:bg-[var(--color-surface-3)]' => ! $active && ! $mobile,
            'rounded-full border border-[var(--color-border)] px-3 py-1.5' => $mobile && ! $active,
        ])
    >
        <x-icon :name="$link['icon']" class="h-4 w-4 shrink-0" />
        <span>{{ $link['label'] }}</span>
    </a>
@endforeach