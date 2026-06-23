<nav class="no-print fixed inset-x-0 bottom-0 z-40 border-t border-[var(--color-border)] bg-[var(--color-surface)] pb-[env(safe-area-inset-bottom,0)] lg:hidden" aria-label="{{ __('messages.mobile_nav') }}">
    <div class="mx-auto flex max-w-lg items-stretch justify-around">
        <a href="{{ route('home') }}" class="flex flex-1 flex-col items-center gap-0.5 px-2 py-2.5 text-[10px] {{ request()->routeIs('home') ? 'text-brand-600' : 'text-[var(--color-text-muted)]' }}">
            <x-icon name="home" class="h-5 w-5" />
            <span>{{ __('messages.home') }}</span>
        </a>
        <a href="{{ route('search.form') }}" class="flex flex-1 flex-col items-center gap-0.5 px-2 py-2.5 text-[10px] {{ request()->routeIs('search*') ? 'text-brand-600' : 'text-[var(--color-text-muted)]' }}">
            <x-icon name="search" class="h-5 w-5" />
            <span>{{ __('messages.search') }}</span>
        </a>
        @auth
            <a href="{{ route('listings.create') }}" class="flex flex-1 flex-col items-center gap-0.5 px-2 py-2.5 text-[10px] {{ request()->routeIs('listings.create') ? 'text-brand-600' : 'text-[var(--color-text-muted)]' }}">
                <x-icon name="plus" class="h-5 w-5" />
                <span>{{ __('messages.publish_short') }}</span>
            </a>
            <a href="{{ route('favorites.index') }}" class="flex flex-1 flex-col items-center gap-0.5 px-2 py-2.5 text-[10px] {{ request()->routeIs('favorites.*') ? 'text-brand-600' : 'text-[var(--color-text-muted)]' }}">
                <x-icon name="heart" class="h-5 w-5" />
                <span>{{ __('messages.my_favorites') }}</span>
            </a>
            <a href="{{ route('dashboard') }}" class="flex flex-1 flex-col items-center gap-0.5 px-2 py-2.5 text-[10px] {{ request()->routeIs('dashboard') ? 'text-brand-600' : 'text-[var(--color-text-muted)]' }}">
                <x-icon name="dashboard" class="h-5 w-5" />
                <span>{{ __('messages.dashboard') }}</span>
            </a>
        @else
            <a href="{{ route('compare.index') }}" class="flex flex-1 flex-col items-center gap-0.5 px-2 py-2.5 text-[10px] {{ request()->routeIs('compare.*') ? 'text-brand-600' : 'text-[var(--color-text-muted)]' }}">
                <x-icon name="compare" class="h-5 w-5" />
                <span>{{ __('messages.compare') }}</span>
            </a>
            <a href="{{ route('login') }}" class="flex flex-1 flex-col items-center gap-0.5 px-2 py-2.5 text-[10px] text-[var(--color-text-muted)]">
                <x-icon name="user" class="h-5 w-5" />
                <span>{{ __('messages.login') }}</span>
            </a>
        @endauth
    </div>
</nav>