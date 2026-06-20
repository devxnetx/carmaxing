@extends('layouts.app')

@section('title', __('messages.seo_home_title'))
@section('meta_description', __('messages.seo_home_description'))
@section('canonical', route('home'))

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8">
    <section class="mb-10">
        <h1 class="text-3xl font-bold tracking-tight">
            {{ config('app.name', 'CARMAXING') }}
            <span class="mt-1 block text-lg font-medium text-[var(--color-text-muted)] sm:text-xl">{{ __('messages.tagline') }}</span>
        </h1>
        @if($stats['total'] > 0)
            <p class="mt-1 text-sm text-brand-600">{{ number_format($stats['total']) }} {{ __('messages.results') }}</p>
        @endif
    </section>

    <section class="mb-10">
        <x-search-form :brands="$allBrands" :regions="$regions" :feature-categories="$featureCategories" :countries="$countries" />
    </section>

    @if($expiringTenders->isNotEmpty())
        <section class="mb-10">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ __('tenders.expiring_soon') }}</h2>
                <a href="{{ route('tenders.index') }}" class="text-sm text-brand-600 hover:underline">{{ __('tenders.browse') }} →</a>
            </div>
            <div class="tender-cards-grid">
                @foreach($expiringTenders as $tender)
                    <x-tender-card :tender="$tender" />
                @endforeach
            </div>
        </section>
    @endif

    @if($recentlyViewed->isNotEmpty())
        <section class="mb-10">
            <h2 class="mb-4 text-lg font-semibold">{{ __('messages.recently_viewed') }}</h2>
            <div class="flex flex-wrap gap-2">
                @foreach($recentlyViewed as $listing)
                    <x-recently-viewed-pill :listing="$listing" />
                @endforeach
            </div>
        </section>
    @endif

    @if($featuredListings->isNotEmpty())
        <section>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ __('messages.sort_newest') }}</h2>
                <a href="{{ route('search') }}" class="text-sm text-brand-600 hover:underline">{{ __('messages.search') }} →</a>
            </div>
            <div class="listing-cards-grid">
                @foreach($featuredListings as $listing)
                    <x-listing-grid-card :listing="$listing" :favorited="in_array($listing->id, $favoritedIds)" />
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection