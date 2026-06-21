<div x-data="{ open: false }" class="lg:hidden">
    <button
        type="button"
        @click="open = true"
        class="flex h-9 w-9 items-center justify-center rounded-lg border border-[var(--color-border)]"
        aria-label="{{ __('messages.menu') }}"
    >
        <x-icon name="menu" class="h-5 w-5" />
    </button>

    <div x-show="open" x-cloak class="fixed inset-0 z-[70]" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="absolute left-0 top-0 flex h-full w-[min(100%,20rem)] flex-col bg-[var(--color-surface)] shadow-xl"
        >
            <div class="flex items-center justify-between border-b border-[var(--color-border)] px-4 py-3">
                <x-logo size="sm" />
                <button type="button" @click="open = false" class="flex h-9 w-9 items-center justify-center rounded-lg hover:bg-[var(--color-surface-3)]" aria-label="{{ __('messages.close') }}">
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto p-4">
                <div class="space-y-1">
                    <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium hover:bg-[var(--color-surface-3)]">{{ __('messages.home') }}</a>
                    <a href="{{ route('search') }}" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium hover:bg-[var(--color-surface-3)]">{{ __('messages.search') }}</a>
                    <a href="{{ route('docs.api') }}" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium hover:bg-[var(--color-surface-3)]">{{ __('messages.api_docs') }}</a>
                </div>

                @auth
                    <div class="mt-6 border-t border-[var(--color-border)] pt-4">
                        <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wide text-[var(--color-text-muted)]">{{ __('messages.my_account') }}</p>
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm hover:bg-[var(--color-surface-3)]">{{ __('messages.my_listings') }}</a>
                        <a href="{{ route('listings.create') }}" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm hover:bg-[var(--color-surface-3)]">{{ __('messages.publish_listing') }}</a>
                        <a href="{{ route('favorites.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm hover:bg-[var(--color-surface-3)]">{{ __('messages.my_favorites') }}</a>
                        <a href="{{ route('saved-searches.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm hover:bg-[var(--color-surface-3)]">{{ __('messages.saved_searches') }}</a>
                        <a href="{{ route('search-history.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm hover:bg-[var(--color-surface-3)]">{{ __('messages.search_history') }}</a>
                        <a href="{{ route('compare.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm hover:bg-[var(--color-surface-3)]">{{ __('messages.compare') }}</a>
                        <a href="{{ route('settings') }}" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm hover:bg-[var(--color-surface-3)]">{{ __('messages.settings') }}</a>
                        @if(auth()->user()->isCompany() && auth()->user()->company)
                            <a href="{{ route('company.edit') }}" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm hover:bg-[var(--color-surface-3)]">{{ __('messages.company_profile') }}</a>
                        @endif
                    </div>

                    @if(auth()->user()->isAdmin())
                        <div class="mt-6 border-t border-[var(--color-border)] pt-4">
                            <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wide text-[var(--color-text-muted)]">{{ __('admin.title') }}</p>
                            @foreach(\App\Support\AdminNavigation::items() as $link)
                                <a href="{{ route($link['route']) }}" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm hover:bg-[var(--color-surface-3)]">
                                    <x-icon :name="$link['icon']" class="h-4 w-4 text-[var(--color-text-muted)]" />
                                    {{ $link['label'] }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="mt-6 border-t border-[var(--color-border)] pt-4">
                        <a href="{{ route('login') }}" class="btn-primary w-full">{{ __('messages.login') }}</a>
                    </div>
                @endauth
            </nav>
        </div>
    </div>
</div>
