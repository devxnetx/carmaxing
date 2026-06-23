@extends('layouts.app')

@section('title', $searchHeading.' — '.config('app.name'))
@section('meta_description', $searchDescription)

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold sm:text-2xl">{{ $searchHeading }}</h1>
            <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('messages.search_auctions_subtitle') }}</p>
        </div>
        <x-search-scope-tabs :scope="$scope" :filters="$filters" />
    </div>

    <x-search-results-toolbar
        :total="$tenders->total()"
        :scope="$scope"
        :correct-search-url="$correctSearchUrl"
        :show-view-modes="false"
    />

    @if($tenders->isEmpty())
        <div class="card mt-6 p-12 text-center text-[var(--color-text-muted)]">{{ __('messages.no_results') }}</div>
    @else
        <div class="tender-cards-grid mt-6">
            @foreach($tenders as $tender)
                <x-tender-card :tender="$tender" />
            @endforeach
        </div>
        <div class="mt-8">{{ $tenders->links() }}</div>
    @endif
</div>
@endsection