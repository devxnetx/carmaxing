@extends('layouts.app')

@section('title', $company->name . ' — ' . config('app.name'))
@section('meta_description', Str::limit(strip_tags($company->description ?? $company->name), 160))
@section('canonical', route('company.show', $company))

@push('jsonld')
    <x-seo.company-jsonld :company="$company" :listings-count="$listings->total()" />
@endpush

@section('content')
<div class="mx-auto max-w-7xl px-4 py-6 sm:py-8">
    <x-company-profile-card
        :company="$company"
        :listings-count="$listings->total()"
        :linked="false"
        :page-title="true"
    />

    <div class="mt-8 space-y-4">
        <form method="GET" class="card flex flex-wrap items-end gap-3 p-4">
            <div class="min-w-[10rem] flex-1">
                <label class="label" for="brand_id">{{ __('messages.brand') }}</label>
                <select name="brand_id" id="brand_id" class="input">
                    <option value="">{{ __('messages.any') }}</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" @selected(request('brand_id') == $brand->id)>{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[8rem]">
                <label class="label" for="ad_number">{{ __('messages.ad_number') }}</label>
                <input type="number" name="ad_number" id="ad_number" value="{{ request('ad_number') }}" placeholder="1042" class="input w-full">
            </div>
            @if(request()->filled('sort'))
                <input type="hidden" name="sort" value="{{ request('sort') }}">
            @endif
            @if(request()->filled('view'))
                <input type="hidden" name="view" value="{{ request('view') }}">
            @endif
            <button type="submit" class="btn-secondary text-sm">{{ __('messages.search') }}</button>
        </form>

        <div class="flex flex-wrap items-center justify-between gap-4">
            <p class="text-sm text-[var(--color-text-muted)]">
                {{ $listings->total() }} {{ __('messages.results') }}
            </p>
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex rounded-lg border border-[var(--color-border)] text-xs">
                    <a href="{{ request()->fullUrlWithQuery(['view' => 'list', 'page' => null]) }}" class="hidden items-center gap-1 px-3 py-2 sm:flex {{ $viewMode === 'list' ? 'bg-brand-600 text-white rounded-lg' : '' }}">
                        <x-icon name="list" class="h-4 w-4" /> {{ __('messages.view_list') }}
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['view' => 'grid', 'page' => null]) }}" class="flex items-center gap-1 px-3 py-2 {{ $viewMode === 'grid' ? 'bg-brand-600 text-white rounded-lg' : '' }}">
                        <x-icon name="grid" class="h-4 w-4" /> {{ __('messages.view_grid') }}
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
                        <option value="newest" @selected(request('sort', 'newest') === 'newest')>{{ __('messages.sort_newest') }}</option>
                        <option value="price_asc" @selected(request('sort') === 'price_asc')>{{ __('messages.sort_price_asc') }}</option>
                        <option value="price_desc" @selected(request('sort') === 'price_desc')>{{ __('messages.sort_price_desc') }}</option>
                        <option value="year_desc" @selected(request('sort') === 'year_desc')>{{ __('messages.sort_year_desc') }}</option>
                        <option value="mileage_asc" @selected(request('sort') === 'mileage_asc')>{{ __('messages.sort_mileage_asc') }}</option>
                    </select>
                </form>
            </div>
        </div>
    </div>

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