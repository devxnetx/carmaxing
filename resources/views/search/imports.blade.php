@extends('layouts.app')

@section('title', $searchSeo['title'])
@section('meta_description', $searchSeo['description'])
@section('canonical', $searchSeo['canonical'])

@push('jsonld')
    <x-seo.breadcrumb-jsonld :items="$searchSeo['breadcrumbs']" />
@endpush

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8">
    <div class="mb-4">
        <h1 class="text-xl font-bold sm:text-2xl">{{ $searchSeo['heading'] }}</h1>
        <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('messages.search_imports_subtitle') }}</p>
    </div>

    <x-search-results-toolbar
        :total="$lots->total()"
        :scope="$scope"
        :correct-search-url="$correctSearchUrl"
        :view-mode="$viewMode"
    />

    @if($lots->isEmpty())
        <div class="card mt-6 p-12 text-center text-[var(--color-text-muted)]">{{ __('messages.no_results') }}</div>
    @elseif($viewMode === 'grid')
        <div class="listing-cards-grid mt-6">
            @foreach($lots as $lot)
                <x-auction-lot-grid-card :lot="$lot" />
            @endforeach
        </div>
        <div class="mt-8">{{ $lots->links() }}</div>
    @else
        <div class="mt-6 flex flex-col gap-3">
            @foreach($lots as $lot)
                <x-auction-lot-card :lot="$lot" />
            @endforeach
        </div>
        <div class="mt-8">{{ $lots->links() }}</div>
    @endif
</div>
@endsection