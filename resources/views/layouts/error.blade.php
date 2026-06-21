@php
    use App\Support\CookieConsent;
    use App\Support\RequestLocale;
    RequestLocale::apply(request());
    $activeTheme = auth()->user()?->theme ?? CookieConsent::fromRequest()->guestTheme('light');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => $activeTheme === 'dark'])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#2563eb">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <title>@yield('title') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen flex-col bg-[var(--color-bg)] text-[var(--color-text)]">
    <header class="border-b border-[var(--color-border)] bg-[var(--color-surface)]">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-4">
            <a href="{{ route('home') }}" class="hover:opacity-90" aria-label="{{ config('app.name') }}">
                <x-logo size="md" />
            </a>

            <div class="flex items-center gap-2 rounded-lg border border-[var(--color-border)] text-xs">
                <a href="{{ route('locale.switch', 'bg') }}" class="px-2.5 py-1.5 {{ app()->getLocale() === 'bg' ? 'rounded-lg bg-brand-600 text-white' : '' }}">BG</a>
                <a href="{{ route('locale.switch', 'en') }}" class="px-2.5 py-1.5 {{ app()->getLocale() === 'en' ? 'rounded-lg bg-brand-600 text-white' : '' }}">EN</a>
            </div>
        </div>
    </header>

    <main class="flex flex-1 items-center justify-center px-4 py-12 sm:py-16">
        <div class="w-full max-w-xl text-center">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-600">
                {{ __('errors.error_code', ['code' => trim($__env->yieldContent('code'))]) }}
            </p>

            <h1 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">
                @yield('heading')
            </h1>

            <p class="mt-4 text-base leading-relaxed text-[var(--color-text-muted)] sm:text-lg">
                @yield('message')
            </p>

            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ route('home') }}" class="btn-primary w-full sm:w-auto">{{ __('errors.back_home') }}</a>
                <a href="{{ route('search') }}" class="btn-secondary w-full sm:w-auto">{{ __('errors.search_cars') }}</a>
            </div>

            <p class="mt-8 text-sm text-[var(--color-text-muted)]">
                <a href="{{ route('pages.contact') }}" class="font-medium text-brand-600 hover:underline">{{ __('errors.contact_us') }}</a>
            </p>
        </div>
    </main>

    <footer class="border-t border-[var(--color-border)] bg-[var(--color-surface-2)] py-6 text-center text-sm text-[var(--color-text-muted)]">
        <p>{{ config('app.name') }} — {{ __('messages.tagline') }}</p>
    </footer>
</body>
</html>