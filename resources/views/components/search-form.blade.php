@props(['brands', 'regions', 'featureCategories', 'countries' => [], 'filters' => [], 'extendedOpen' => false, 'action' => route('search')])

@php
    $locationType = $filters['location_type'] ?? (
        ! empty($filters['country_code']) ? 'abroad' : (
            (! empty($filters['region_id']) || ! empty($filters['city'])) ? 'bg' : ''
        )
    );
@endphp

@php
    $fuelTypes = ['petrol' => 'Бензин', 'diesel' => 'Дизел', 'lpg' => 'Газ', 'electric' => 'Електричество', 'hybrid' => 'Хибрид', 'plug-in-hybrid' => 'Plug-in хибрид'];
    $transmissions = ['manual' => 'Ръчна', 'automatic' => 'Автоматична'];
    $currentYear = date('Y');
@endphp

<form action="{{ $action }}" method="GET" x-data="searchFilters({{ $extendedOpen ? 'true' : 'false' }})" class="card p-5">
    <div class="search-form-main-grid">
        <div class="min-w-0" x-data="brandModelPicker({{ $filters['brand_id'] ?? 'null' }}, @js($filters['series_ids'] ?? []), @js($filters['model_ids'] ?? []))">
            <label class="label">{{ __('messages.brand') }}</label>
            <select name="brand_id" class="input" x-model="brandId" @change="loadModels($event.target.value)">
                <option value="">{{ __('messages.any') }}</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" @selected(($filters['brand_id'] ?? '') == $brand->id)>{{ $brand->name }}</option>
                @endforeach
            </select>

            <template x-if="brandId && (series.length || flatModels.length)">
                <div class="mt-3 max-h-48 overflow-y-auto rounded-lg border border-[var(--color-border)] p-2 text-sm">
                    <p class="mb-2 font-medium text-[var(--color-text-muted)]">{{ __('messages.select_series') }}</p>
                    <template x-for="item in flattenedTree" :key="'tree-' + item.node.id">
                        <div :style="'margin-left: ' + (item.depth * 1.25) + 'rem'">
                            <template x-if="item.node.type === 'series'">
                                <label class="mb-1 flex items-center gap-2 font-medium">
                                    <input type="checkbox" name="series_ids[]" :value="item.node.id"
                                           :checked="hasSeriesId(item.node.id)"
                                           @change="toggleSeries(item.node.id, descendantModelIds(item.node))">
                                    <span x-text="item.node.name"></span>
                                    <span class="text-xs text-[var(--color-text-muted)]" x-show="item.depth === 0">({{ __('messages.select_all_series') }})</span>
                                </label>
                            </template>
                            <template x-if="item.node.type === 'model'">
                                <label class="mb-1 flex items-center gap-2">
                                    <input type="checkbox" name="model_ids[]" :value="item.node.id"
                                           :checked="hasModelId(item.node.id)"
                                           @change="toggleModel(item.node.id)">
                                    <span x-text="item.node.name"></span>
                                </label>
                            </template>
                        </div>
                    </template>
                    <template x-for="m in flatModels" :key="m.id">
                        <label class="mb-1 flex items-center gap-2">
                            <input type="checkbox" name="model_ids[]" :value="m.id"
                                   :checked="hasModelId(m.id)"
                                   @change="toggleModel(m.id)">
                            <span x-text="m.name"></span>
                        </label>
                    </template>
                </div>
            </template>
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
                <label class="label">{{ __('messages.price_from') }} / {{ __('messages.price_to') }} (EUR)</label>
                <div class="flex gap-2">
                    <input type="number" name="price_from" value="{{ $filters['price_from'] ?? '' }}" class="input" placeholder="{{ __('messages.from') }}">
                    <input type="number" name="price_to" value="{{ $filters['price_to'] ?? '' }}" class="input" placeholder="{{ __('messages.to') }}">
                </div>
            </div>
        </div>

        <div class="min-w-0" x-data="locationPicker(@js($countries), @js([
            'location_type' => $locationType,
            'region_id' => $filters['region_id'] ?? null,
            'city' => $filters['city'] ?? null,
            'country_code' => $filters['country_code'] ?? null,
        ]))">
            <label class="label">{{ __('messages.location') }}</label>
            <select name="location_type" class="input" x-model="locationType" @change="onTypeChange()">
                <option value="">{{ __('messages.location_any') }}</option>
                <option value="bg">{{ __('messages.location_bulgaria') }}</option>
                <option value="abroad">{{ __('messages.location_abroad') }}</option>
            </select>

            <div class="mt-3 space-y-3" x-show="locationType === 'bg'" x-cloak>
                <div>
                    <label class="label">{{ __('messages.oblast') }}</label>
                    <select name="region_id" class="input" x-model="regionId" @change="onRegionChange()" :disabled="locationType !== 'bg'">
                        <option value="">{{ __('messages.location_select_oblast') }}</option>
                        @foreach($regions as $region)
                            <option value="{{ $region->id }}">{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="relative">
                    <label class="label">{{ __('messages.city') }}</label>
                    <input
                        type="text"
                        class="input"
                        x-model="cityQuery"
                        @focus="cityOpen = true"
                        @click.outside="cityOpen = false"
                        :placeholder="regionId ? '{{ __('messages.location_search_city') }}' : '{{ __('messages.location_select_oblast') }}'"
                        :disabled="!regionId || locationType !== 'bg'"
                        autocomplete="off"
                    >
                    <input type="hidden" name="city" :value="city" :disabled="locationType !== 'bg'">
                    <div
                        x-show="cityOpen && regionId && filteredCities.length"
                        x-cloak
                        class="absolute z-20 mt-1 max-h-48 w-full overflow-y-auto rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] shadow-lg"
                    >
                        <template x-for="cityName in filteredCities" :key="cityName">
                            <button
                                type="button"
                                class="block w-full px-3 py-2 text-left text-sm hover:bg-[var(--color-surface-3)]"
                                @click="selectCity(cityName)"
                                x-text="cityName"
                            ></button>
                        </template>
                    </div>
                    <p class="mt-1 text-xs text-[var(--color-text-muted)]" x-show="loadingCities" x-cloak>...</p>
                </div>
            </div>

            <div class="relative mt-3" x-show="locationType === 'abroad'" x-cloak>
                <label class="label">{{ __('messages.location_country') }}</label>
                <input
                    type="text"
                    class="input"
                    x-model="countryQuery"
                    @focus="countryOpen = true"
                    @click.outside="countryOpen = false"
                    @input="countryCode = ''"
                    placeholder="{{ __('messages.location_search_country') }}"
                    autocomplete="off"
                >
                <input type="hidden" name="country_code" :value="countryCode" :disabled="locationType !== 'abroad'">
                <div
                    x-show="countryOpen && filteredCountries.length"
                    x-cloak
                    class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] shadow-lg"
                >
                    <template x-for="country in filteredCountries" :key="country.code">
                        <button
                            type="button"
                            class="block w-full px-3 py-2 text-left text-sm hover:bg-[var(--color-surface-3)]"
                            @click="selectCountry(country.code, country.name)"
                            x-text="country.name"
                        ></button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-3">
        <button type="submit" class="btn-primary">{{ __('messages.search') }}</button>
        <button type="button" @click="toggleExtended()" class="btn-secondary">
            <span x-text="extended ? '−' : '+'"></span> {{ __('messages.search_extended') }}
        </button>
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

    <div x-show="extended" x-cloak class="mt-6 border-t border-[var(--color-border)] pt-6">
        <div class="grid gap-6 lg:grid-cols-3">
            <div>
                <label class="label">{{ __('messages.mileage_from') }} / {{ __('messages.mileage_to') }}</label>
                <div class="flex gap-2">
                    <input type="number" name="mileage_from" value="{{ $filters['mileage_from'] ?? '' }}" class="input" step="1000">
                    <input type="number" name="mileage_to" value="{{ $filters['mileage_to'] ?? '' }}" class="input" step="1000">
                </div>
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
</form>