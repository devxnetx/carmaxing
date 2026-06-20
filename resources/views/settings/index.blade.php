@extends('layouts.app')

@section('title', __('messages.settings'))

@section('content')
<div class="mx-auto max-w-3xl px-4 py-6 sm:py-8 space-y-8">
    <h1 class="text-2xl font-bold">{{ __('messages.settings') }}</h1>

    <div class="card flex items-center gap-4 p-5">
        <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full bg-brand-600 text-lg font-bold text-white">
            <div class="relative h-full w-full">
                <span class="absolute inset-0 flex items-center justify-center">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                @if($avatarUrl = $user->avatarUrl())
                    <img src="{{ $avatarUrl }}" alt="" class="relative z-10 h-full w-full object-cover" referrerpolicy="no-referrer" loading="lazy" onerror="this.remove()">
                @endif
            </div>
        </div>
        <div class="min-w-0">
            <div class="truncate font-semibold">{{ $user->name }}</div>
            <div class="truncate text-sm text-[var(--color-text-muted)]">{{ $user->email }}</div>
            <div class="mt-1 text-xs text-[var(--color-text-muted)]">{{ $user->isCompany() ? __('messages.account_company') : __('messages.account_private') }}</div>
        </div>
    </div>

    <form method="POST" action="{{ route('settings.update') }}" class="card space-y-4 p-5 sm:p-6">
        @csrf @method('PUT')
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label">{{ __('messages.language') }}</label>
                <select name="locale" class="input">
                    <option value="bg" @selected($user->locale === 'bg')>Български</option>
                    <option value="en" @selected($user->locale === 'en')>English</option>
                </select>
            </div>
            <div>
                <label class="label">{{ __('messages.theme_light') }} / {{ __('messages.theme_dark') }}</label>
                <select name="theme" class="input">
                    <option value="light" @selected($user->theme === 'light')>{{ __('messages.theme_light') }}</option>
                    <option value="dark" @selected($user->theme === 'dark')>{{ __('messages.theme_dark') }}</option>
                </select>
            </div>
        </div>
        @if($user->isPrivate())
            <div>
                <label class="label">{{ __('messages.phone') }}</label>
                <x-phone-input name="phone" :value="$user->phone" />
            </div>
        @endif
        <button type="submit" class="btn-primary">{{ __('messages.save') }}</button>
    </form>

    @if($user->isCompany())
        <section class="card space-y-4 p-5 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold">{{ __('messages.api_keys') }}</h2>
                    <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('messages.api_keys_help') }}</p>
                </div>
                <a href="{{ route('docs.api') }}" class="text-sm text-brand-600 hover:underline">{{ __('messages.api_docs') }}</a>
            </div>

            @if(session('new_api_key'))
                <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm dark:border-amber-700 dark:bg-amber-950">
                    <p class="font-medium">{{ __('messages.api_key_generated') }}</p>
                </div>
            @endif

            @php
                $apiKeyValue = session('new_api_key')
                    ?? ($activeApiKey
                        ? $activeApiKey->key_prefix.str_repeat('•', 44)
                        : '');
            @endphp

            <div x-data="{ visible: false }">
                <label class="label" for="api-key-display">{{ __('messages.api_key') }}</label>
                <div class="relative">
                    <input
                        id="api-key-display"
                        class="input w-full pr-11 font-mono text-xs disabled:cursor-not-allowed disabled:opacity-80"
                        value="{{ $apiKeyValue }}"
                        placeholder="{{ __('messages.api_key_empty') }}"
                        readonly
                        disabled
                        :type="visible ? 'text' : 'password'"
                    >
                    @if($apiKeyValue)
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-[var(--color-text-muted)] hover:text-brand-600"
                            @click="visible = !visible"
                            :aria-label="visible ? '{{ __('messages.hide_api_key') }}' : '{{ __('messages.show_api_key') }}'"
                        >
                            <x-icon x-show="!visible" name="eye" class="h-5 w-5" />
                            <x-icon x-show="visible" x-cloak name="eye-off" class="h-5 w-5" />
                        </button>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <form method="POST" action="{{ route('settings.api-keys.generate') }}">
                    @csrf
                    <button type="submit" class="btn-primary" @disabled($hasActiveApiKey)>
                        {{ __('messages.generate_key') }}
                    </button>
                </form>

                @if($hasActiveApiKey && $activeApiKey)
                    <form method="POST" action="{{ route('settings.api-keys.revoke', $activeApiKey) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:underline">{{ __('messages.revoke') }}</button>
                    </form>
                @endif
            </div>

            @if($hasActiveApiKey)
                <p class="text-sm text-[var(--color-text-muted)]">{{ __('messages.api_key_limit_reached') }}</p>
            @endif
        </section>
    @endif
</div>
@endsection