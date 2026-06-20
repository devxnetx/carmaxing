@extends('layouts.app')

@section('title', __('messages.my_favorites'))

@section('content')
<div class="mx-auto max-w-7xl px-4 py-6 sm:py-8">
    <h1 class="text-2xl font-bold">{{ __('messages.my_favorites') }}</h1>
    <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ $listings->total() }} {{ __('messages.results') }}</p>

    @if($listings->isEmpty())
        <div class="card mt-6 p-12 text-center text-[var(--color-text-muted)]">
            <x-icon name="heart" class="mx-auto mb-3 h-10 w-10 text-[var(--color-text-muted)]" />
            <p>{{ __('messages.no_favorites_yet') }}</p>
            <a href="{{ route('search') }}" class="btn-primary mt-4 inline-flex">{{ __('messages.search') }}</a>
        </div>
    @else
        <div class="listing-cards-grid mt-6">
            @foreach($listings as $listing)
                <x-listing-grid-card :listing="$listing" :favorited="true" />
            @endforeach
        </div>
        <div class="mt-8">{{ $listings->links() }}</div>
    @endif
</div>
@endsection