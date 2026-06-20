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

@if($viewMode === 'map')
    @push('meta')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    @endpush
@endif

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8">
    <h1 class="sr-only">{{ $searchSeo['heading'] }}</h1>

    <x-search-form
        :brands="$brands"
        :regions="$regions"
        :feature-categories="$featureCategories"
        :countries="$countries"
        :filters="$filters"
        :extended-open="$extendedOpen"
    />

    <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-[var(--color-text-muted)]">
            {{ $listings->total() }} {{ __('messages.results') }}
        </p>
        <div class="flex flex-wrap items-center gap-2">
            @auth
                <form method="POST" action="{{ route('saved-searches.store') }}" class="inline">
                    @csrf
                    @foreach(request()->except('page') as $key => $value)
                        @if(is_array($value))
                            @foreach($value as $v)
                                <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <button type="submit" class="btn-secondary text-xs">
                        <x-icon name="bell" class="h-4 w-4" /> {{ __('messages.save_search') }}
                    </button>
                </form>
            @endauth

            <div class="flex rounded-lg border border-[var(--color-border)] text-xs">
                <a href="{{ request()->fullUrlWithQuery(['view' => 'list', 'page' => null]) }}" class="hidden items-center gap-1 px-3 py-2 sm:flex {{ $viewMode === 'list' ? 'bg-brand-600 text-white rounded-lg' : '' }}">
                    <x-icon name="list" class="h-4 w-4" /> {{ __('messages.view_list') }}
                </a>
                <a href="{{ request()->fullUrlWithQuery(['view' => 'grid', 'page' => null]) }}" class="flex items-center gap-1 px-3 py-2 {{ $viewMode === 'grid' ? 'bg-brand-600 text-white rounded-lg' : '' }}">
                    <x-icon name="grid" class="h-4 w-4" /> {{ __('messages.view_grid') }}
                </a>
                <a href="{{ request()->fullUrlWithQuery(['view' => 'map', 'page' => null]) }}" class="flex items-center gap-1 px-3 py-2 {{ $viewMode === 'map' ? 'bg-brand-600 text-white rounded-lg' : '' }}">
                    <x-icon name="map" class="h-4 w-4" /> {{ __('messages.view_map') }}
                </a>
            </div>

            <form method="GET" class="flex items-center gap-2">
                @foreach(request()->except('sort', 'page') as $key => $value)
                    @if(is_array($value))
                        @foreach($value as $v)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <select name="sort" class="input w-auto" onchange="this.form.submit()">
                    <option value="newest" @selected(request('sort') === 'newest')>{{ __('messages.sort_newest') }}</option>
                    <option value="price_asc" @selected(request('sort') === 'price_asc')>{{ __('messages.sort_price_asc') }}</option>
                    <option value="price_desc" @selected(request('sort') === 'price_desc')>{{ __('messages.sort_price_desc') }}</option>
                    <option value="year_desc" @selected(request('sort') === 'year_desc')>{{ __('messages.sort_year_desc') }}</option>
                    <option value="mileage_asc" @selected(request('sort') === 'mileage_asc')>{{ __('messages.sort_mileage_asc') }}</option>
                </select>
            </form>
        </div>
    </div>

    @if($viewMode === 'map')
        <form method="GET" class="card mt-4 flex flex-wrap items-end gap-4 p-4">
            @foreach(request()->except('map_lat', 'map_lng', 'radius_km', 'page') as $key => $value)
                @if(is_array($value))
                    @foreach($value as $v)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <input type="hidden" name="view" value="map">
            <input type="hidden" name="map_lat" id="map-lat-input" value="{{ request('map_lat', $mapCenter['lat']) }}">
            <input type="hidden" name="map_lng" id="map-lng-input" value="{{ request('map_lng', $mapCenter['lng']) }}">
            <div class="min-w-[12rem] flex-1">
                <label class="label">{{ __('messages.search_radius') }} (<span id="radius-label">{{ request('radius_km', 50) }}</span> km)</label>
                <input type="range" name="radius_km" min="10" max="200" step="10" value="{{ request('radius_km', 50) }}" class="w-full" oninput="document.getElementById('radius-label').textContent = this.value">
            </div>
            <button type="submit" class="btn-primary">{{ __('messages.apply_radius') }}</button>
        </form>

        <div
            id="search-map"
            class="card mt-4 h-[28rem] overflow-hidden"
            data-center="{{ json_encode($mapCenter) }}"
            data-markers="{{ $mapMarkers->toJson() }}"
            data-price-on-request="{{ __('messages.price_on_request') }}"
        ></div>

        @push('scripts')
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        @endpush
    @elseif($listings->isEmpty())
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