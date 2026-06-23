@extends('layouts.app')

@section('title', $searchSeo['title'])
@section('meta_description', $searchSeo['description'])
@section('canonical', $searchSeo['canonical'])

@push('jsonld')
    <x-seo.breadcrumb-jsonld :items="$searchSeo['breadcrumbs']" />
    <x-seo.search-results-jsonld
        :listings="$listings"
        :name="$searchSeo['heading']"
        :description="$searchSeo['description']"
        :url="$searchSeo['canonical']"
    />
@endpush

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8">
    <div class="mb-4">
        <h1 class="text-xl font-bold sm:text-2xl">{{ $searchSeo['heading'] }}</h1>
        <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('messages.search_scope_listings') }}</p>
    </div>

    <x-search-results-toolbar
        :total="$listings->total()"
        :scope="$scope"
        :correct-search-url="$correctSearchUrl"
        :view-mode="$viewMode"
    />

    @if($listings->isEmpty())
        <div class="card mt-6 p-12 text-center text-[var(--color-text-muted)]">{{ __('messages.no_results') }}</div>
    @elseif($viewMode === 'grid')
        <div class="listing-cards-grid mt-6">
            @foreach($listings as $listing)
                <x-listing-grid-card :listing="$listing" :favorited="in_array($listing->id, $favoritedIds)" />
            @endforeach
        </div>
        <div class="mt-8">{{ $listings->links() }}</div>
    @else
        <div class="mt-6 flex flex-col gap-3">
            @foreach($listings as $listing)
                <x-listing-result :listing="$listing" :favorited="in_array($listing->id, $favoritedIds)" />
            @endforeach
        </div>
        <div class="mt-8">{{ $listings->links() }}</div>
    @endif
</div>
@endsection