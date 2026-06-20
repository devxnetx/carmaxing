@extends('layouts.app')

@section('title', $title . ' — ' . config('app.name'))
@section('meta_description', $intro)
@section('canonical', route('legal.cookies'))

@section('content')
<div class="mx-auto max-w-4xl px-4 py-10">
    <h1 class="text-3xl font-bold">{{ $title }}</h1>
    <p class="mt-2 text-sm text-[var(--color-text-muted)]">{{ __('legal.last_updated') }}: {{ $updated }}</p>
    <p class="mt-4 text-sm leading-relaxed text-[var(--color-text-muted)]">{{ $intro }}</p>

    <div class="mt-8 space-y-6">
        @foreach($categories as $category)
            <section class="card p-6">
                <h2 class="text-lg font-semibold">{{ $category['title'] }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-[var(--color-text-muted)]">{{ $category['body'] }}</p>
            </section>
        @endforeach
    </div>

    <section class="card mt-8 overflow-hidden p-0">
        <div class="border-b border-[var(--color-border)] px-6 py-4">
            <h2 class="text-lg font-semibold">{{ __('legal.cookies.table_title') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[42rem] text-left text-sm">
                <thead class="bg-[var(--color-surface-3)] text-xs uppercase tracking-wide text-[var(--color-text-muted)]">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ __('legal.cookies.table_name') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('legal.cookies.table_category') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('legal.cookies.table_provider') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('legal.cookies.table_purpose') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('legal.cookies.table_duration') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-border)]">
                    @foreach($inventory as $cookie)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">{{ $cookie['name'] }}</td>
                            <td class="px-4 py-3">{{ __('messages.cookie_category_'.$cookie['category']) }}</td>
                            <td class="px-4 py-3">{{ $cookie['provider'] }}</td>
                            <td class="px-4 py-3 text-[var(--color-text-muted)]">{{ __($cookie['purpose_key']) }}</td>
                            <td class="px-4 py-3 text-[var(--color-text-muted)]">{{ __($cookie['duration_key']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <p class="mt-6 text-sm text-[var(--color-text-muted)]">
        {{ __('legal.cookies.manage') }}
        <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-cookie-settings'))" class="text-brand-600 underline-offset-2 hover:underline">
            {{ __('messages.cookie_settings') }}
        </button>.
    </p>
</div>
@endsection