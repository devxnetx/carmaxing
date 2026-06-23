@extends('layouts.app')

@section('title', __('messages.newest_cars_title'))
@section('meta_description', __('messages.newest_cars_description'))
@section('canonical', route('listings.newest'))

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight">{{ __('messages.newest_cars') }}</h1>
        <p class="mt-1 text-sm text-[var(--color-text-muted)]">
            @if($usesRecentWindow)
                {{ __('messages.newest_cars_recent_subtitle', ['days' => $recentWindowDays]) }}
            @else
                {{ __('messages.newest_cars_fallback_subtitle', ['days' => $recentWindowDays]) }}
            @endif
        </p>
    </div>

    @if($listings->isEmpty())
        <div class="card p-12 text-center text-[var(--color-text-muted)]">{{ __('messages.no_results') }}</div>
    @else
        <div class="listing-cards-grid">
            @foreach($listings as $listing)
                <x-listing-grid-card :listing="$listing" :favorited="in_array($listing->id, $favoritedIds)" />
            @endforeach
        </div>
        <div class="mt-8">{{ $listings->links() }}</div>
    @endif
</div>
@endsection