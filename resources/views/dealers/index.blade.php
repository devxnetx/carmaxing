@extends('layouts.app')

@section('title', __('messages.dealers_page_title'))
@section('meta_description', __('messages.dealers_page_description'))
@section('canonical', route('dealers.index'))

@push('meta')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@endpush

@section('content')
<div class="mx-auto max-w-7xl px-4 py-6 sm:py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight">{{ __('messages.dealers_page_heading') }}</h1>
        <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('messages.dealers_page_subtitle') }}</p>
    </div>

    <div class="dealers-directory-grid">
        <aside class="min-w-0">
            <form
                method="GET"
                action="{{ route('dealers.index') }}"
                class="card space-y-4 p-4"
                x-data="locationPicker(@js([
                    'region_id' => $filters['region_id'] ?? null,
                    'city' => $filters['city'] ?? null,
                ]), 'dealer-cities')"
            >
                <div>
                    <label class="label">{{ __('messages.location_region') }}</label>
                    <select name="region_id" class="input" x-model="regionId" @change="onRegionChange()">
                        <option value="">{{ __('messages.any') }}</option>
                        @foreach($regions as $region)
                            <option value="{{ $region->id }}" @selected(($filters['region_id'] ?? '') == $region->id)>{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label">{{ __('messages.location_subcity') }}</label>
                    <select
                        name="city"
                        class="input"
                        x-model="city"
                        :disabled="!regionId || loadingCities"
                    >
                        <option value="">{{ __('messages.any') }}</option>
                        <template x-for="entry in cities" :key="entry.name">
                            <option :value="entry.name" x-text="entry.name + (entry.count > 0 ? ' [' + entry.count + ']' : '')"></option>
                        </template>
                    </select>
                    <p class="mt-1 text-xs text-[var(--color-text-muted)]" x-show="!regionId">{{ __('messages.location_select_region_first') }}</p>
                    <p class="mt-1 text-xs text-[var(--color-text-muted)]" x-show="loadingCities" x-cloak>...</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="btn-primary">{{ __('messages.apply_filters') }}</button>
                    @if(($filters['region_id'] ?? null) || ($filters['city'] ?? null))
                        <a href="{{ route('dealers.index') }}" class="btn-secondary">{{ __('messages.clear_filters') }}</a>
                    @endif
                </div>
            </form>

            <p class="mt-4 text-sm text-[var(--color-text-muted)]">
                {{ number_format($companies->total()) }} {{ __('messages.dealers_count_label') }}
            </p>

            <div class="mt-4 space-y-3">
                @forelse($companies as $company)
                    <x-dealer-directory-card :company="$company" />
                @empty
                    <div class="card p-8 text-center text-[var(--color-text-muted)]">{{ __('messages.dealers_empty') }}</div>
                @endforelse
            </div>

            @if($companies->hasPages())
                <div class="mt-6">{{ $companies->links() }}</div>
            @endif
        </aside>

        <div class="dealers-directory-map-wrap">
            <div
                id="dealers-map"
                class="dealers-directory-map card overflow-hidden"
                data-center="{{ json_encode($mapCenter) }}"
                data-markers="{{ $mapMarkers->toJson() }}"
                data-listings-label="{{ __('messages.listings_count_short') }}"
            ></div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
@endpush
@endsection