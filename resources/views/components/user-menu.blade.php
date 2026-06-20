@props(['user'])

@php
    $initials = collect(explode(' ', $user->name))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->join('');
@endphp

<div x-data="{ open: false }" class="relative">
    <button
        type="button"
        @click="open = !open"
        @keydown.escape.window="open = false"
        class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full border-2 border-[var(--color-border)] bg-[var(--color-surface-3)] text-sm font-semibold text-brand-600 transition hover:border-brand-500"
        aria-label="{{ __('messages.my_account') }}"
        aria-haspopup="true"
        :aria-expanded="open"
    >
        <div class="relative h-full w-full">
            <span class="absolute inset-0 flex items-center justify-center">{{ $initials ?: 'U' }}</span>
            @if($avatarUrl = $user->avatarUrl())
                <img src="{{ $avatarUrl }}" alt="" class="relative z-10 h-full w-full object-cover" referrerpolicy="no-referrer" loading="lazy" onerror="this.remove()">
            @endif
        </div>
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
            <div class="truncate font-medium">{{ $user->name }}</div>
            <div class="truncate text-xs text-[var(--color-text-muted)]">{{ $user->email }}</div>
        </div>

        <div class="py-1">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-[var(--color-surface-3)]" role="menuitem">
                <x-icon name="dashboard" class="h-4 w-4 text-[var(--color-text-muted)]" />{{ __('messages.my_listings') }}
            </a>
            <a href="{{ route('listings.create') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-[var(--color-surface-3)]" role="menuitem">
                <x-icon name="plus" class="h-4 w-4 text-[var(--color-text-muted)]" />{{ __('messages.new_listing') }}
            </a>
            @if(\App\Support\TendersNavigation::isVisible())
                <a href="{{ route('my.tenders.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-[var(--color-surface-3)]" role="menuitem">
                    <x-icon name="clock" class="h-4 w-4 text-[var(--color-text-muted)]" />{{ __('tenders.my_tenders') }}
                </a>
            @endif
            <a href="{{ route('favorites.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-[var(--color-surface-3)]" role="menuitem">
                <x-icon name="heart" class="h-4 w-4 text-[var(--color-text-muted)]" />{{ __('messages.my_favorites') }}
            </a>
            <a href="{{ route('saved-searches.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-[var(--color-surface-3)]" role="menuitem">
                <x-icon name="bell" class="h-4 w-4 text-[var(--color-text-muted)]" />{{ __('messages.saved_searches') }}
            </a>
            <a href="{{ route('search-history.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-[var(--color-surface-3)]" role="menuitem">
                <x-icon name="clock" class="h-4 w-4 text-[var(--color-text-muted)]" />{{ __('messages.search_history') }}
            </a>
            <a href="{{ route('compare.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-[var(--color-surface-3)]" role="menuitem">
                <x-icon name="compare" class="h-4 w-4 text-[var(--color-text-muted)]" />{{ __('messages.compare') }}
            </a>
            <a href="{{ route('settings') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-[var(--color-surface-3)]" role="menuitem">
                <x-icon name="cog" class="h-4 w-4 text-[var(--color-text-muted)]" />{{ __('messages.settings') }}
            </a>
            @if($user->isCompany() && $user->company)
                <a href="{{ route('company.edit') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-[var(--color-surface-3)]" role="menuitem">
                    <x-icon name="building" class="h-4 w-4 text-[var(--color-text-muted)]" />{{ __('messages.company_profile') }}
                </a>
                <a href="{{ route('company.show', $user->company) }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-[var(--color-surface-3)]" role="menuitem">
                    <x-icon name="store" class="h-4 w-4 text-[var(--color-text-muted)]" />{{ __('messages.public_dealer_page') }}
                </a>
            @endif
        </div>

        <div class="border-t border-[var(--color-border)] py-1">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-red-600 hover:bg-[var(--color-surface-3)]" role="menuitem">
                    <x-icon name="logout" class="h-4 w-4" />{{ __('messages.logout') }}
                </button>
            </form>
        </div>
    </div>
</div>