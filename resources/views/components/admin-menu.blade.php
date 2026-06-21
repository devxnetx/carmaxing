@php
    use App\Support\AdminNavigation;
    $links = AdminNavigation::items();

@endphp

<div x-data="{ open: false }" class="relative">
    <button
        type="button"
        @click="open = !open"
        @keydown.escape.window="open = false"
        class="flex h-9 w-9 items-center justify-center rounded-full border-2 border-[var(--color-border)] bg-[var(--color-surface-3)] text-brand-600 transition hover:border-brand-500"
        aria-label="{{ __('admin.title') }}"
        aria-haspopup="true"
        :aria-expanded="open"
    >
        <x-icon name="dashboard" class="h-5 w-5" />
    </button>

    <div
        x-show="open"
        x-cloak
        @click.outside="open = false"
        x-transition
        class="absolute right-0 z-50 mt-2 w-56 rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] py-2 shadow-xl"
        role="menu"
    >
        <div class="border-b border-[var(--color-border)] px-4 py-3">
            <div class="font-medium">{{ __('admin.title') }}</div>
            <div class="text-xs text-[var(--color-text-muted)]">{{ config('app.name') }}</div>
        </div>

        <div class="py-1">
            @foreach($links as $link)
                <a
                    href="{{ route($link['route']) }}"
                    class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-[var(--color-surface-3)] {{ AdminNavigation::isActive($link) ? 'bg-[var(--color-surface-3)] font-medium text-brand-600' : '' }}"
                    role="menuitem"
                >
                    <x-icon :name="$link['icon']" class="h-4 w-4 text-[var(--color-text-muted)]" />
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</div>
