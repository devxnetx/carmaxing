@extends('layouts.app')

@php
    $searchFilterHelper = app(\App\Services\SearchFilterHelper::class);
@endphp

@section('title', __('messages.saved_searches'))

@section('content')
<div class="mx-auto max-w-3xl px-4 py-6 sm:py-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">{{ __('messages.saved_searches') }}</h1>
            <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('messages.saved_searches_subtitle') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('search-history.index') }}" class="btn-secondary text-sm">
                <x-icon name="clock" class="h-4 w-4" /> {{ __('messages.search_history') }}
            </a>
            <a href="{{ route('search') }}" class="btn-secondary text-sm">{{ __('messages.search') }}</a>
        </div>
    </div>

    <div class="mt-6 space-y-3">
        @forelse($searches as $search)
            <div class="card p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <a href="{{ $searchFilterHelper->searchUrlFromFilters($search->filters ?? []) }}" class="font-medium hover:text-brand-600">
                            {{ $search->name }}
                        </a>
                        <p class="mt-1 text-xs text-[var(--color-text-muted)]">
                            {{ __('messages.saved_search_matches', ['count' => $search->last_match_count]) }}
                            @if($search->last_notified_at)
                                · {{ __('messages.saved_search_last_alert', ['date' => $search->last_notified_at->format('d.m.Y H:i')]) }}
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <form method="POST" action="{{ route('saved-searches.toggle-alert', $search) }}">
                            @csrf
                            <button type="submit" class="badge {{ $search->alert_enabled ? 'bg-brand-100 text-brand-800 dark:bg-brand-900 dark:text-brand-200' : 'bg-[var(--color-surface-3)]' }}">
                                {{ $search->alert_enabled ? __('messages.alerts_on') : __('messages.alerts_off') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('saved-searches.destroy', $search) }}" onsubmit="return confirm('{{ __('messages.saved_search_delete_confirm') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-red-600 hover:underline">{{ __('messages.delete') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="card p-8 text-center text-[var(--color-text-muted)]">
                <p>{{ __('messages.saved_searches_empty') }}</p>
                <a href="{{ route('search') }}" class="btn-primary mt-4 inline-flex">{{ __('messages.search') }}</a>
            </div>
        @endforelse
    </div>
</div>
@endsection