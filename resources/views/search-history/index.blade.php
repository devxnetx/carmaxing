@extends('layouts.app')

@php
    $searchFilterHelper = app(\App\Services\SearchFilterHelper::class);
@endphp

@section('title', __('messages.search_history'))

@section('content')
<div class="mx-auto max-w-3xl px-4 py-6 sm:py-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">{{ __('messages.search_history') }}</h1>
            <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('messages.search_history_subtitle') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('saved-searches.index') }}" class="btn-secondary text-sm">
                <x-icon name="bell" class="h-4 w-4" /> {{ __('messages.saved_searches') }}
            </a>
            <a href="{{ route('search') }}" class="btn-secondary text-sm">{{ __('messages.search') }}</a>
        </div>
    </div>

    @if($searches->isNotEmpty())
        <div class="mt-4 flex justify-end">
            <form method="POST" action="{{ route('search-history.destroy-all') }}" onsubmit="return confirm('{{ __('messages.search_history_clear_confirm') }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm text-red-600 hover:underline">{{ __('messages.search_history_clear_all') }}</button>
            </form>
        </div>
    @endif

    <div class="mt-4 space-y-3">
        @forelse($searches as $search)
            <div class="card p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <a href="{{ $searchFilterHelper->searchUrlFromFilters($search->filters ?? []) }}" class="font-medium hover:text-brand-600">
                            {{ $search->label }}
                        </a>
                        <p class="mt-1 text-xs text-[var(--color-text-muted)]">
                            {{ __('messages.search_history_searched_at', ['date' => $search->searched_at->format('d.m.Y H:i')]) }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('search-history.destroy', $search) }}" onsubmit="return confirm('{{ __('messages.search_history_delete_confirm') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:underline">{{ __('messages.delete') }}</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="card p-8 text-center text-[var(--color-text-muted)]">
                <p>{{ __('messages.search_history_empty') }}</p>
                <a href="{{ route('search') }}" class="btn-primary mt-4 inline-flex">{{ __('messages.search') }}</a>
            </div>
        @endforelse
    </div>
</div>
@endsection