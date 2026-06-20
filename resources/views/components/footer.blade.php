<footer class="mt-auto bg-[var(--color-surface-2)]">
    <div class="mx-auto max-w-7xl px-4 py-10">
        <div class="flex flex-col items-center text-center">
            <a href="{{ route('home') }}" class="inline-block hover:opacity-90" aria-label="{{ config('app.name', 'CARMAXING') }} — {{ __('messages.tagline') }}">
                <span class="text-lg font-semibold">{{ config('app.name', 'CARMAXING') }}</span>
                <span class="mt-1 block text-xs text-[var(--color-text-muted)]">{{ __('messages.tagline') }}</span>
            </a>

            <div class="mt-4 flex flex-wrap items-center justify-center gap-3">
                @foreach(config('seo.social') as $network => $social)
                    @if($social['url'] ?? null)
                        <a href="{{ $social['url'] }}" rel="noopener noreferrer" target="_blank"
                           class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-[var(--color-surface)] text-[var(--color-text-muted)] transition hover:bg-brand-600 hover:text-white"
                           aria-label="{{ $social['label'] ?? $network }}">
                            <x-icon :name="$social['icon']" class="h-5 w-5" />
                        </a>
                    @endif
                @endforeach
            </div>

            <p class="mt-4 max-w-xl text-sm leading-relaxed text-[var(--color-text-muted)]">
                {{ __('messages.footer_mission') }}
            </p>

            <nav class="mt-6 flex flex-wrap justify-center gap-x-5 gap-y-2 text-sm text-[var(--color-text-muted)]">
                <a href="{{ route('pages.about') }}" class="transition hover:text-brand-600">{{ __('messages.about_us') }}</a>
                <a href="{{ route('pages.contact') }}" class="transition hover:text-brand-600">{{ __('messages.contact_us') }}</a>
                <a href="{{ route('search') }}" class="transition hover:text-brand-600">{{ __('messages.search') }}</a>
                <a href="{{ route('sitemap.page') }}" class="transition hover:text-brand-600">{{ __('messages.sitemap') }}</a>
                @guest
                    <a href="{{ route('login') }}" class="transition hover:text-brand-600">{{ __('messages.publish_listing') }}</a>
                    <a href="{{ route('login') }}" class="transition hover:text-brand-600">{{ __('messages.login') }}</a>
                @else
                    <a href="{{ route('listings.create') }}" class="transition hover:text-brand-600">{{ __('messages.publish_listing') }}</a>
                    <a href="{{ route('dashboard') }}" class="transition hover:text-brand-600">{{ __('messages.dashboard') }}</a>
                    <a href="{{ route('settings') }}" class="transition hover:text-brand-600">{{ __('messages.settings') }}</a>
                @endguest
                <a href="{{ route('docs.api') }}" class="transition hover:text-brand-600">{{ __('messages.api_docs') }}</a>
            </nav>

            <nav class="mt-4 flex flex-wrap justify-center gap-x-5 gap-y-2 text-sm text-[var(--color-text-muted)]">
                <a href="{{ route('legal.privacy') }}" class="transition hover:text-brand-600">{{ __('messages.privacy_policy') }}</a>
                <a href="{{ route('legal.cookies') }}" class="transition hover:text-brand-600">{{ __('messages.cookie_policy') }}</a>
                <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-cookie-settings'))" class="transition hover:text-brand-600">
                    {{ __('messages.cookie_settings') }}
                </button>
                <a href="{{ route('legal.terms') }}" class="transition hover:text-brand-600">{{ __('messages.terms_of_service') }}</a>
            </nav>

            @if($footerPopularBrands->isNotEmpty())
                <nav class="mt-4 flex max-w-3xl flex-wrap justify-center gap-x-5 gap-y-2 text-sm text-[var(--color-text-muted)]">
                    @foreach($footerPopularBrands->take(8) as $brand)
                        <a href="{{ route('search', ['brand_id' => $brand->id]) }}" class="transition hover:text-brand-600">{{ $brand->name }}</a>
                    @endforeach
                </nav>
            @endif

            <p class="mt-8 text-sm text-[var(--color-text-muted)]">&copy; {{ date('Y') }} {{ config('app.name', 'CARMAXING') }}. {{ __('messages.footer_rights') }}</p>
        </div>
    </div>
</footer>