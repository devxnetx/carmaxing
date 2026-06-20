@php
    use App\Support\CookieConsent;
    $cookieConsent = CookieConsent::fromRequest();
    $activeTheme = auth()->user()?->theme ?? $cookieConsent->guestTheme('light');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      class="{{ $activeTheme === 'dark' ? 'dark' : '' }}"
      data-functional-cookies="{{ $cookieConsent->allowsFunctional() ? '1' : '0' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#2563eb">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon-32x32.png" type="image/png" sizes="32x32">
    <link rel="icon" href="/favicon-16x16.png" type="image/png" sizes="16x16">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    @php
        $socialTitle = trim($__env->yieldContent('title')) ?: config('app.name');
        $socialDescription = trim($__env->yieldContent('meta_description')) ?: __('messages.footer_mission');
        $socialUrl = trim($__env->yieldContent('canonical')) ?: url()->current();
        $socialImage = trim($__env->yieldContent('meta_image')) ?: url('/apple-touch-icon.png');
        $socialImageAlt = trim($__env->yieldContent('meta_image_alt')) ?: $socialTitle;
        $socialType = trim($__env->yieldContent('meta_type')) ?: 'website';
    @endphp
    <title>{{ $socialTitle }}</title>
    <meta name="description" content="{{ $socialDescription }}">
    <link rel="canonical" href="{{ $socialUrl }}">
    <link rel="sitemap" type="application/xml" title="Sitemap" href="{{ route('sitemap') }}">
    <x-seo.social-meta
        :title="$socialTitle"
        :description="$socialDescription"
        :url="$socialUrl"
        :image="$socialImage"
        :image-alt="$socialImageAlt"
        :type="$socialType"
    />
    @stack('meta')
    @vite(['resources/css/app.css'])
    <x-seo.website-jsonld />
    @stack('jsonld')
</head>
<body class="flex min-h-screen flex-col pb-16 lg:pb-0">
    <header class="sticky top-0 z-50 border-b border-[var(--color-border)] bg-[var(--color-surface)]">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3">
            <div class="flex items-center gap-2">
                <x-mobile-nav />
                <a href="{{ route('home') }}" class="hover:opacity-90" aria-label="{{ config('app.name', 'CARMAXING') }} — {{ __('messages.tagline') }}">
                    <x-logo size="md" />
                </a>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <div class="hidden rounded-lg border border-[var(--color-border)] text-xs sm:flex">
                    <a href="{{ route('locale.switch', 'bg') }}" class="px-2 py-1.5 {{ app()->getLocale() === 'bg' ? 'bg-brand-600 text-white rounded-lg' : '' }}">BG</a>
                    <a href="{{ route('locale.switch', 'en') }}" class="px-2 py-1.5 {{ app()->getLocale() === 'en' ? 'bg-brand-600 text-white rounded-lg' : '' }}">EN</a>
                </div>

                <div x-data="themeToggle" class="hidden rounded-lg border border-[var(--color-border)] p-0.5 sm:flex">
                    <button @click="theme !== 'light' && toggle()" :class="theme === 'light' ? 'bg-brand-600 text-white' : ''" class="rounded-md px-2 py-1 text-xs">☀</button>
                    <button @click="theme !== 'dark' && toggle()" :class="theme === 'dark' ? 'bg-brand-600 text-white' : ''" class="rounded-md px-2 py-1 text-xs">☾</button>
                </div>

                <nav class="hidden items-center gap-1 lg:flex">
                    <a href="{{ route('search') }}" class="btn-secondary text-sm">{{ __('messages.search') }}</a>
                    @if(\App\Support\TendersNavigation::isVisible())
                        <a href="{{ route('tenders.index') }}" class="btn-secondary text-sm">{{ __('messages.tenders') }}</a>
                    @endif
                    @guest
                        <a href="{{ route('login') }}" class="btn-primary text-sm">{{ __('messages.login') }}</a>
                    @else
                        <a href="{{ route('listings.create') }}" class="btn-primary text-sm">{{ __('messages.publish_listing') }}</a>
                        @if(auth()->user()->isAdmin())
                            <x-admin-menu />
                        @endif
                        <x-user-menu :user="auth()->user()" />
                    @endguest
                </nav>

                <div class="flex items-center gap-2 lg:hidden">
                    @auth
                        <a href="{{ route('listings.create') }}" class="btn-primary px-3 py-2 text-xs">{{ __('messages.publish_short') }}</a>
                        @if(auth()->user()->isAdmin())
                            <x-admin-menu />
                        @endif
                        <x-user-menu :user="auth()->user()" />
                    @else
                        <a href="{{ route('login') }}" class="btn-primary px-3 py-2 text-xs">{{ __('messages.login') }}</a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    @if(session('success'))
        <div class="no-print mx-auto mt-4 max-w-7xl px-4">
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="no-print mx-auto mt-4 max-w-7xl px-4">
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                {{ session('error') }}
            </div>
        </div>
    @endif

    <main class="flex-1">
        @yield('content')
    </main>

    <x-footer />
    <x-bottom-nav />
    <x-compare-tray />
    <x-cookie-consent />
    @stack('scripts')
    @vite(['resources/js/app.js'])
</body>
</html>