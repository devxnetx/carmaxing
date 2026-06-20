@extends('layouts.admin')

@section('title', __('admin.nav_api_keys'))

@section('content')
<div class="w-full">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">{{ __('admin.nav_api_keys') }}</h1>
        <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('admin.api_keys_subtitle') }}</p>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="card p-4">
            <div class="text-xs text-[var(--color-text-muted)]">{{ __('admin.active_keys') }}</div>
            <div class="mt-1 text-xl font-bold">{{ $usageSummary['active_keys'] }} / {{ $usageSummary['total_keys'] }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs text-[var(--color-text-muted)]">{{ __('admin.requests_today') }}</div>
            <div class="mt-1 text-xl font-bold">{{ number_format($usageSummary['requests_today']) }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs text-[var(--color-text-muted)]">{{ __('admin.requests_week') }}</div>
            <div class="mt-1 text-xl font-bold">{{ number_format($usageSummary['requests_week']) }}</div>
        </div>
    </div>

    <x-admin-filters :action="route('admin.api-keys.index')">
        <div class="min-w-[200px] flex-1">
            <label class="label">{{ __('admin.search') }}</label>
            <input type="search" name="q" value="{{ request('q') }}" class="input" placeholder="{{ __('admin.search_api_keys_placeholder') }}">
        </div>
        <div>
            <label class="label">{{ __('admin.status') }}</label>
            <select name="active" class="input">
                <option value="">{{ __('admin.all') }}</option>
                <option value="1" @selected(request('active') === '1')>{{ __('admin.active') }}</option>
                <option value="0" @selected(request('active') === '0')>{{ __('admin.revoked') }}</option>
            </select>
        </div>
    </x-admin-filters>

    <x-admin-table :headers="[__('admin.company'), __('admin.key_prefix'), __('admin.status'), __('admin.last_used'), __('admin.requests'), '']">
        @forelse($apiKeys as $apiKey)
            <tr class="hover:bg-[var(--color-surface-3)]">
                <td class="px-4 py-3">
                    <a href="{{ route('admin.companies.show', $apiKey->company) }}" class="font-medium hover:text-brand-600">{{ $apiKey->company?->name }}</a>
                    <div class="text-xs text-[var(--color-text-muted)]">{{ $apiKey->name }}</div>
                </td>
                <td class="px-4 py-3 font-mono text-xs">{{ $apiKey->key_prefix }}…</td>
                <td class="px-4 py-3">
                    @if($apiKey->is_active)
                        <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800 dark:bg-green-950 dark:text-green-200">{{ __('admin.active') }}</span>
                    @else
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ __('admin.revoked') }}</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-[var(--color-text-muted)]">{{ $apiKey->last_used_at?->diffForHumans() ?: '—' }}</td>
                <td class="px-4 py-3">{{ number_format($apiKey->request_logs_count) }}</td>
                <td class="px-4 py-3 text-right">
                    @if($apiKey->is_active)
                        <form method="POST" action="{{ route('admin.api-keys.revoke', $apiKey) }}" class="inline" onsubmit="return confirm(@js(__('admin.revoke_key_confirm')))">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-red-600 hover:underline">{{ __('admin.revoke') }}</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-[var(--color-text-muted)]">{{ __('admin.no_results') }}</td></tr>
        @endforelse
    </x-admin-table>

    <div class="mt-4">{{ $apiKeys->links() }}</div>
</div>
@endsection