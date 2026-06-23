@props([
    'scope',
    'regions',
    'featureCategories',
    'brands',
    'brandCounts' => [],
    'regionCounts' => [],
    'countries' => [],
    'filters' => [],
    'extendedOpen' => false,
    'showScopeTabs' => true,
    'showExtendedToggle' => true,
    'scopeTabTarget' => 'form',
])

@php
    use App\Enums\SearchScope;

    $fuelTypes = ['petrol' => 'Бензин', 'diesel' => 'Дизел', 'lpg' => 'Газ', 'electric' => 'Електричество', 'hybrid' => 'Хибрид', 'plug-in-hybrid' => 'Plug-in хибрид'];
    $transmissions = ['manual' => 'Ръчна', 'automatic' => 'Автоматична'];
    $currentYear = date('Y');
    $tenderPeriod = $filters['tender_period'] ?? '';
    $priceCurrency = $scope === SearchScope::Imports ? 'USD' : 'EUR';
@endphp

<form
    method="GET"
    action="{{ route($scope->resultsRouteName()) }}"
    @if($scope === SearchScope::Listings && $showExtendedToggle)
        x-data="{ extended: @js($extendedOpen) }"
    @endif
    class="card p-5"
>
    @if($showScopeTabs)
        <div class="mb-5">
            <x-search-scope-tabs :scope="$scope" :filters="$filters" :target="$scopeTabTarget" />
        </div>
    @endif

    @if($scope === SearchScope::Auctions)
        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label class="label">{{ __('messages.tender_period') }}</label>
                <div class="filter-pills" role="radiogroup" aria-label="{{ __('messages.tender_period') }}">
                    <label class="filter-pill">
                        <input type="radio" name="tender_period" value="" @checked($tenderPeriod === '')>
                        <span>{{ __('messages.tender_period_active') }}</span>
                    </label>
                    <label class="filter-pill">
                        <input type="radio" name="tender_period" value="upcoming" @checked($tenderPeriod === 'upcoming')>
                        <span>{{ __('messages.tender_period_upcoming') }}</span>
                    </label>
                    <label class="filter-pill">
                        <input type="radio" name="tender_period" value="today" @checked($tenderPeriod === 'today')>
                        <span>{{ __('messages.tender_period_today') }}</span>
                    </label>
                    <label class="filter-pill">
                        <input type="radio" name="tender_period" value="past" @checked($tenderPeriod === 'past')>
                        <span>{{ __('messages.tender_period_past') }}</span>
                    </label>
                </div>
            </div>

            <div>
                <label class="label">{{ __('messages.oblast') }}</label>
                <select name="region_id" class="input">
                    <option value="">{{ __('messages.any') }}</option>
                    @foreach($regions as $region)
                        <option value="{{ $region->id }}" @selected(($filters['region_id'] ?? '') == $region->id)>{{ $region->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    @else
        <div @class([
            'search-form-main-grid',
            'search-form-main-grid--two' => $scope === SearchScope::Imports,
        ])>
            <div
                class="min-w-0"
                x-data="brandModelPicker(
                    @js($filters['brand_id'] ?? null),
                    @js($filters['series_ids'] ?? []),
                    @js($filters['model_ids'] ?? []),
                    @js($scope->value)
                )"
            >
                <label class="label">{{ __('messages.brand') }}</label>
                <select name="brand_id" class="input" x-model="brandId" @change="loadModels($event.target.value)">
                    <option value="">{{ __('messages.any') }}</option>
                    @foreach($brands as $brand)
                        @php $count = $brandCounts[$brand->id] ?? 0; @endphp
                        <option value="{{ $brand->id }}" @selected(($filters['brand_id'] ?? '') == $brand->id)>
                            {{ $brand->name }}@if($count > 0) [{{ $count }}]@endif
                        </option>
                    @endforeach
                </select>

                <div
                    class="mt-3 max-h-48 overflow-y-auto rounded-lg border border-[var(--color-border)] p-2 text-sm transition-opacity"
                    :class="!brandId ? 'pointer-events-none opacity-40' : ''"
                >
                    <p class="mb-2 font-medium text-[var(--color-text-muted)]">{{ __('messages.select_series') }}</p>
                    <template x-if="brandId && !loading && !series.length && !flatModels.length">
                        <p class="text-xs text-[var(--color-text-muted)]">{{ __('messages.no_models_for_brand') }}</p>
                    </template>
                    <template x-if="loading">
                        <p class="text-xs text-[var(--color-text-muted)]">...</p>
                    </template>
                    <template x-for="item in flattenedTree" :key="'tree-' + item.node.id">
                        <div :style="'margin-left: ' + (item.depth * 1.25) + 'rem'">
                            <template x-if="item.node.type === 'series'">
                                <label class="mb-1 flex items-center gap-2 font-medium">
                                    <input type="checkbox" name="series_ids[]" :value="item.node.id"
                                           :checked="hasSeriesId(item.node.id)"
                                           :disabled="!brandId"
                                           @change="toggleSeries(item.node.id, descendantModelIds(item.node))">
                                    <span x-text="item.node.name + (item.node.count > 0 ? ' [' + item.node.count + ']' : '')"></span>
                                    <span class="text-xs text-[var(--color-text-muted)]" x-show="item.depth === 0">({{ __('messages.select_all_series') }})</span>
                                </label>
                            </template>
                            <template x-if="item.node.type === 'model'">
                                <label class="mb-1 flex items-center gap-2">
                                    <input type="checkbox" name="model_ids[]" :value="item.node.id"
                                           :checked="hasModelId(item.node.id)"
                                           :disabled="!brandId"
                                           @change="toggleModel(item.node.id)">
                                    <span x-text="item.node.name + (item.node.count > 0 ? ' [' + item.node.count + ']' : '')"></span>
                                </label>
                            </template>
                        </div>
                    </template>
                    <template x-for="m in flatModels" :key="m.id">
                        <label class="mb-1 flex items-center gap-2">
                            <input type="checkbox" name="model_ids[]" :value="m.id"
                                   :checked="hasModelId(m.id)"
                                   :disabled="!brandId"
                                   @change="toggleModel(m.id)">
                            <span x-text="m.name + (m.count > 0 ? ' [' + m.count + ']' : '')"></span>
                        </label>
                    </template>
                </div>
            </div>

            <div class="min-w-0 space-y-4">
                <div>
                    <label class="label">{{ __('messages.year_from') }} / {{ __('messages.year_to') }}</label>
                    <div class="flex gap-2">
                        <select name="year_from" class="input">
                            <option value="">{{ __('messages.from') }}</option>
                            @for($y = $currentYear; $y >= 1990; $y--)
                                <option value="{{ $y }}" @selected(($filters['year_from'] ?? '') == $y)>{{ $y }}</option>
                            @endfor
                        </select>
                        <select name="year_to" class="input">
                            <option value="">{{ __('messages.to') }}</option>
                            @for($y = $currentYear; $y >= 1990; $y--)
                                <option value="{{ $y }}" @selected(($filters['year_to'] ?? '') == $y)>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div>
                    <label class="label">
                        {{ __('messages.price_from') }} / {{ __('messages.price_to') }}
                        ({{ $priceCurrency }})
                    </label>
                    <div class="flex gap-2">
                        <input type="number" name="price_from" value="{{ $filters['price_from'] ?? '' }}" class="input" placeholder="{{ __('messages.from') }}">
                        <input type="number" name="price_to" value="{{ $filters['price_to'] ?? '' }}" class="input" placeholder="{{ __('messages.to') }}">
                    </div>
                </div>
            </div>

            @if($scope === SearchScope::Listings)
                <div
                    class="min-w-0 space-y-3"
                    x-data="locationPicker(@js([
                        'region_id' => $filters['region_id'] ?? null,
                        'city' => $filters['city'] ?? null,
                    ]))"
                >
                    <input type="hidden" name="location_type" value="bg">

                    <div>
                        <label class="label">{{ __('messages.location_region') }}</label>
                        <select name="region_id" class="input" x-model="regionId" @change="onRegionChange()">
                            <option value="">{{ __('messages.any') }}</option>
                            @foreach($regions as $region)
                                @php $count = $regionCounts[$region->id] ?? 0; @endphp
                                <option value="{{ $region->id }}" @selected(($filters['region_id'] ?? '') == $region->id)>
                                    {{ $region->name }}@if($count > 0) [{{ $count }}]@endif
                                </option>
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
                </div>
            @endif
        </div>
    @endif

    <div class="mt-4 flex flex-wrap items-center gap-3">
        <button type="submit" class="btn-primary">{{ __('messages.search') }}</button>
        @if($showExtendedToggle && $scope === SearchScope::Listings)
            <button type="button" @click="extended = !extended" class="btn-secondary">
                <span x-text="extended ? '−' : '+'"></span> {{ __('messages.search_extended') }}
            </button>
        @endif
        <a
            href="{{ auth()->check() ? route('saved-searches.index') : route('login', ['redirect' => route('saved-searches.index')]) }}"
            class="btn-secondary"
            title="{{ __('messages.saved_searches_subtitle') }}"
        >
            <x-icon name="bell" class="h-4 w-4" /> {{ __('messages.saved_searches') }}
        </a>
        <a
            href="{{ auth()->check() ? route('search-history.index') : route('login', ['redirect' => route('search-history.index')]) }}"
            class="btn-secondary"
            title="{{ __('messages.search_history_subtitle') }}"
        >
            <x-icon name="clock" class="h-4 w-4" /> {{ __('messages.search_history') }}
        </a>
    </div>

    @if($scope === SearchScope::Listings)
        <div
            @class([
                'mt-6 border-t border-[var(--color-border)] pt-6',
                'hidden' => ! $showExtendedToggle && ! $extendedOpen,
            ])
            @if($showExtendedToggle)
                x-show="extended" x-cloak
            @endif
        >
            <div class="grid gap-6 lg:grid-cols-3">
                <div
                    x-data="mileageMax(0, 300000, @js(isset($filters['mileage_to']) && $filters['mileage_to'] !== '' ? (int) $filters['mileage_to'] : null), @js(__('messages.mileage_any')), @js(__('messages.km')))"
                >
                    <label class="label">{{ __('messages.mileage_up_to') }}</label>
                    <div class="mileage-range">
                        <div class="mileage-range__track">
                            <div class="mileage-range__fill" :style="fillStyle"></div>
                            <input type="range" class="mileage-range__input" min="0" max="300000" step="1000" x-model.number="value" @input="onInput">
                        </div>
                        <div class="mt-2 text-sm text-[var(--color-text-muted)]">
                            <span x-text="label"></span>
                        </div>
                    </div>
                    <input type="hidden" name="mileage_from" value="">
                    <input type="hidden" name="mileage_to" :value="hiddenTo">
                </div>

                <div>
                    <label class="label">{{ __('messages.fuel_type') }}</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($fuelTypes as $value => $label)
                            <label class="flex items-center gap-1.5 text-sm">
                                <input type="checkbox" name="fuel_type[]" value="{{ $value }}" @checked(in_array($value, (array)($filters['fuel_type'] ?? [])))>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="label">{{ __('messages.transmission') }}</label>
                    <div class="flex flex-wrap gap-3">
                        @foreach($transmissions as $value => $label)
                            <label class="flex items-center gap-1.5 text-sm">
                                <input type="checkbox" name="transmission[]" value="{{ $value }}" @checked(in_array($value, (array)($filters['transmission'] ?? [])))>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <label class="label">{{ __('messages.features') }}</label>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($featureCategories as $category)
                        <div class="rounded-lg border border-[var(--color-border)] p-3">
                            <h4 class="mb-2 text-sm font-semibold">{{ $category->name }}</h4>
                            <div class="max-h-36 space-y-1 overflow-y-auto text-sm">
                                @foreach($category->features as $feature)
                                    <label class="flex items-start gap-2">
                                        <input type="checkbox" name="features[]" value="{{ $feature->id }}"
                                               @checked(in_array($feature->id, array_map('intval', (array)($filters['features'] ?? []))))>
                                        <span>{{ $feature->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-4 text-sm">
                <label class="flex items-center gap-2"><input type="checkbox" name="price_negotiable" value="1" @checked($filters['price_negotiable'] ?? false)> {{ __('messages.negotiable') }}</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="has_vin" value="1" @checked($filters['has_vin'] ?? false)> VIN</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="has_video" value="1" @checked($filters['has_video'] ?? false)> Video</label>
            </div>
        </div>
    @endif
</form>