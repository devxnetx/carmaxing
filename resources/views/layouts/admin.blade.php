@php
    use App\Support\CookieConsent;
    $cookieConsent = CookieConsent::fromRequest();
    $activeTheme = auth()->user()?->theme ?? $cookieConsent->guestTheme('light');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      class="{{ $activeTheme === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('admin.title')) — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[var(--color-bg)] text-[var(--color-text)]">
    <div class="flex min-h-screen">
        <aside class="hidden w-64 shrink-0 border-r border-[var(--color-border)] bg-[var(--color-surface)] lg:block">
            <div class="sticky top-0 flex h-screen flex-col">
                <div class="border-b border-[var(--color-border)] px-5 py-4">
                    <a href="{{ route('admin.dashboard') }}" class="text-lg font-bold text-brand-600">{{ __('admin.title') }}</a>
                    <p class="mt-1 text-xs text-[var(--color-text-muted)]">{{ config('app.name') }}</p>
                </div>
                <nav class="flex-1 space-y-1 overflow-y-auto p-3">
                    <x-admin-nav />
                </nav>
                <div class="border-t border-[var(--color-border)] p-4">
                    <a href="{{ route('home') }}" class="btn-secondary w-full text-sm">
                        <x-icon name="home" class="h-4 w-4" />
                        {{ __('admin.back_to_site') }}
                    </a>
                </div>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-20 border-b border-[var(--color-border)] bg-[var(--color-surface)] px-4 py-3 lg:px-8">
                <div class="flex items-center justify-between gap-3">
                    <a href="{{ route('admin.dashboard') }}" class="font-bold text-brand-600 lg:hidden">{{ __('admin.title') }}</a>
                    <div class="hidden text-sm text-[var(--color-text-muted)] lg:block">{{ config('app.name') }} {{ __('admin.title') }}</div>
                    <a href="{{ route('home') }}" class="btn-secondary shrink-0 text-sm">
                        <x-icon name="home" class="h-4 w-4" />
                        <span class="hidden sm:inline">{{ __('admin.back_to_site') }}</span>
                        <span class="sm:hidden">{{ __('admin.back_to_site_short') }}</span>
                    </a>
                </div>
                <div class="mt-3 flex flex-wrap gap-2 text-xs lg:hidden">
                    <x-admin-nav :mobile="true" />
                </div>
            </header>

            @if(session('success'))
                <div class="mx-4 mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200 lg:mx-8">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mx-4 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200 lg:mx-8">
                    {{ session('error') }}
                </div>
            @endif

            <main class="w-full min-w-0 flex-1 px-4 py-6 lg:px-8 lg:py-8">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>