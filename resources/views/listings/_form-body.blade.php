@php
    use App\Support\LocationCatalog;

    $selectedFeatures = old('features', $listing->exists ? $listing->features->pluck('id')->toArray() : []);
    $formCountryCode = old('country_code', $listing->country_code);
    $formLocationType = old('location_type', LocationCatalog::isBulgaria($formCountryCode) ? 'bg' : 'abroad');
    $formAction = $formAction ?? ($listing->exists ? route('listings.update', $listing) : route('listings.store'));
    $cancelUrl = $cancelUrl ?? route('dashboard');
    $submitLabel = $submitLabel ?? ($listing->exists ? __('messages.save') : __('messages.publish_listing'));
@endphp

@if($errors->any())
    <div class="card mb-6 border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
        <p class="font-medium">{{ __('messages.form_validation_summary') }}</p>
        <ul class="mt-2 list-inside list-disc space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form
    method="POST"
    action="{{ $formAction }}"
    enctype="multipart/form-data"
    class="space-y-6"
>
    @csrf
    @if($listing->exists) @method('PUT') @endif

    <section class="card p-5 sm:p-6">
        <h2 class="text-lg font-semibold">{{ __('messages.form_section_basic') }}</h2>
        <div class="mt-4 space-y-4">
            <div x-data="listingBrandModel(@js(old('brand_id', $listing->brand_id)), @js(old('model_id', $listing->model_id)))" x-init="init()">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">{{ __('messages.brand') }} *</label>
                        <select
                            name="brand_id"
                            class="input @error('brand_id') border-red-500 @enderror"
                            required
                            x-model="brandId"
                            @change="loadModels($event.target.value)"
                        >
                            <option value="">{{ __('messages.any') }}</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                        @error('brand_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label">{{ __('messages.model') }} *</label>
                        <select
                            name="model_id"
                            class="input @error('model_id') border-red-500 @enderror"
                            required
                            x-model="modelId"
                            :disabled="loading || !brandId"
                        >
                            <option value="">{{ __('messages.any') }}</option>
                            <template x-for="s in series" :key="'s'+s.id">
                                <optgroup :label="s.name">
                                    <template x-for="c in s.children" :key="c.id">
                                        <option :value="String(c.id)" x-text="c.name"></option>
                                    </template>
                                </optgroup>
                            </template>
                            <template x-for="m in flatModels" :key="'m'+m.id">
                                <option :value="String(m.id)" x-text="m.name"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-xs text-[var(--color-text-muted)]" x-show="loading" x-cloak>{{ __('messages.loading_models') }}</p>
                        @error('model_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
            <div>
                <label class="label">{{ __('messages.car_variant') }}</label>
                <input type="text" name="car_variant" class="input" value="{{ old('car_variant', $listing->car_variant) }}" placeholder="{{ __('messages.car_variant_hint') }}">
                <p class="mt-1 text-xs text-[var(--color-text-muted)]">{{ __('messages.car_variant_help') }}</p>
            </div>
            <div>
                <label class="label">{{ __('messages.ad_name') }}</label>
                <input type="text" name="ad_name" class="input" value="{{ old('ad_name', $listing->ad_name) }}" placeholder="{{ __('messages.ad_name_hint') }}">
                <p class="mt-1 text-xs text-[var(--color-text-muted)]">{{ __('messages.ad_name_help') }}</p>
            </div>
        </div>
    </section>

    <section class="card p-5 sm:p-6">
        <h2 class="text-lg font-semibold">{{ __('messages.form_section_price') }}</h2>
        <div class="mt-4 space-y-4" x-data="{ onRequest: {{ old('price_on_request', $listing->price_on_request) ? 'true' : 'false' }} }">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="price_on_request" value="1" x-model="onRequest" @checked(old('price_on_request', $listing->price_on_request))>
                {{ __('messages.price_on_request') }}
            </label>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="label">{{ __('messages.price_from') }} (EUR) <span x-show="!onRequest">*</span></label>
                    <input type="number" name="price" class="input @error('price') border-red-500 @enderror" min="0" :required="!onRequest" :disabled="onRequest" value="{{ old('price', $listing->price_on_request ? '' : $listing->price) }}">
                    @error('price')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">{{ __('messages.year') }} *</label>
                    <input type="number" name="year" class="input @error('year') border-red-500 @enderror" required min="1950" max="{{ date('Y') + 1 }}" value="{{ old('year', $listing->year) }}">
                    @error('year')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">{{ __('messages.month') }}</label>
                    <select name="month" class="input">
                        <option value="">—</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected(old('month', $listing->month) == $m)>{{ sprintf('%02d', $m) }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="label">{{ __('messages.mileage') }}</label>
                    <input type="number" name="mileage" class="input" min="0" value="{{ old('mileage', $listing->mileage) }}">
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm" x-show="!onRequest">
                <input type="checkbox" name="price_negotiable" value="1" @checked(old('price_negotiable', $listing->price_negotiable))>
                {{ __('messages.negotiable') }}
            </label>
            <div x-data="locationPicker(@js($countries), @js([
                'location_type' => $formLocationType,
                'region_id' => old('region_id', $listing->region_id),
                'city' => old('city', $listing->city),
                'country_code' => LocationCatalog::isBulgaria($formCountryCode) ? null : $formCountryCode,
            ]))">
                <label class="label">{{ __('messages.location') }} *</label>
                <select name="location_type" class="input @error('location_type') border-red-500 @enderror" x-model="locationType" @change="onTypeChange()" required>
                    <option value="bg">{{ __('messages.location_bulgaria') }}</option>
                    <option value="abroad">{{ __('messages.location_abroad') }}</option>
                </select>

                <div class="mt-3 grid gap-3 sm:grid-cols-2" x-show="locationType === 'bg'" x-cloak>
                    <div>
                        <label class="label">{{ __('messages.oblast') }} *</label>
                        <select name="region_id" class="input @error('region_id') border-red-500 @enderror" x-model="regionId" @change="onRegionChange()" :disabled="locationType !== 'bg'" :required="locationType === 'bg'">
                            <option value="">{{ __('messages.location_select_oblast') }}</option>
                            @foreach($regions as $region)
                                <option value="{{ $region->id }}">{{ $region->name }}</option>
                            @endforeach
                        </select>
                        @error('region_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="relative">
                        <label class="label">{{ __('messages.city') }} *</label>
                        <input
                            type="text"
                            class="input @error('city') border-red-500 @enderror"
                            x-model="cityQuery"
                            @focus="cityOpen = true"
                            @click.outside="cityOpen = false"
                            :placeholder="regionId ? '{{ __('messages.location_search_city') }}' : '{{ __('messages.location_select_oblast') }}'"
                            :disabled="!regionId || locationType !== 'bg'"
                            autocomplete="off"
                        >
                        <input type="hidden" name="city" :value="city" :disabled="locationType !== 'bg'" :required="locationType === 'bg'">
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
                        @error('city')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="relative mt-3" x-show="locationType === 'abroad'" x-cloak>
                    <label class="label">{{ __('messages.location_country') }} *</label>
                    <input
                        type="text"
                        class="input @error('country_code') border-red-500 @enderror"
                        x-model="countryQuery"
                        @focus="countryOpen = true"
                        @click.outside="countryOpen = false"
                        @input="countryCode = ''"
                        placeholder="{{ __('messages.location_search_country') }}"
                        autocomplete="off"
                    >
                    <input type="hidden" name="country_code" :value="countryCode" :disabled="locationType !== 'abroad'" :required="locationType === 'abroad'">
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
                    @error('country_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
    </section>

    <section class="card p-5 sm:p-6">
        <h2 class="text-lg font-semibold">{{ __('messages.form_section_specs') }}</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label class="label">{{ __('messages.fuel_type') }}</label>
                <select name="fuel_type" class="input">
                    <option value="">—</option>
                    @foreach(['petrol' => 'fuel_petrol', 'diesel' => 'fuel_diesel', 'lpg' => 'fuel_lpg', 'cng' => 'fuel_cng', 'electric' => 'fuel_electric', 'hybrid' => 'fuel_hybrid', 'plug-in-hybrid' => 'fuel_plug_in_hybrid'] as $value => $labelKey)
                        <option value="{{ $value }}" @selected(old('fuel_type', $listing->fuel_type) === $value)>{{ __('messages.'.$labelKey) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">{{ __('messages.transmission') }}</label>
                <select name="transmission" class="input">
                    <option value="">—</option>
                    @foreach(['manual' => 'transmission_manual', 'automatic' => 'transmission_automatic', 'semi-automatic' => 'transmission_semi'] as $value => $labelKey)
                        <option value="{{ $value }}" @selected(old('transmission', $listing->transmission) === $value)>{{ __('messages.'.$labelKey) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">{{ __('messages.drivetrain') }}</label>
                <select name="drivetrain" class="input">
                    <option value="">—</option>
                    @foreach(['fwd' => 'drivetrain_fwd', 'rwd' => 'drivetrain_rwd', 'awd' => 'drivetrain_awd', '4x4' => 'drivetrain_4x4'] as $value => $labelKey)
                        <option value="{{ $value }}" @selected(old('drivetrain', $listing->drivetrain) === $value)>{{ __('messages.'.$labelKey) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">{{ __('messages.body_type') }}</label>
                <select name="body_type" class="input">
                    <option value="">—</option>
                    @foreach(['sedan' => 'body_sedan', 'hatchback' => 'body_hatchback', 'wagon' => 'body_wagon', 'suv' => 'body_suv', 'coupe' => 'body_coupe', 'cabrio' => 'body_cabrio', 'van' => 'body_van', 'pickup' => 'body_pickup'] as $value => $labelKey)
                        <option value="{{ $value }}" @selected(old('body_type', $listing->body_type) === $value)>{{ __('messages.'.$labelKey) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">{{ __('messages.condition') }}</label>
                <select name="condition" class="input">
                    <option value="used" @selected(old('condition', $listing->condition ?? 'used') === 'used')>{{ __('messages.condition_used') }}</option>
                    <option value="new" @selected(old('condition', $listing->condition) === 'new')>{{ __('messages.condition_new') }}</option>
                </select>
            </div>
            <div>
                <label class="label">{{ __('messages.power') }} ({{ __('messages.hp') }})</label>
                <input type="number" name="engine_power_hp" class="input" min="0" value="{{ old('engine_power_hp', $listing->engine_power_hp) }}">
            </div>
            <div>
                <label class="label">{{ __('messages.displacement') }} ({{ __('messages.cc') }})</label>
                <input type="number" name="engine_displacement_cc" class="input" min="0" value="{{ old('engine_displacement_cc', $listing->engine_displacement_cc) }}">
            </div>
            <div>
                <label class="label">{{ __('messages.color') }}</label>
                <input type="text" name="color_exterior" class="input" value="{{ old('color_exterior', $listing->color_exterior) }}">
            </div>
            <div>
                <label class="label">{{ __('messages.interior') }}</label>
                <input type="text" name="color_interior" class="input" value="{{ old('color_interior', $listing->color_interior) }}">
            </div>
            <div>
                <label class="label">{{ __('messages.doors') }}</label>
                <input type="number" name="doors" class="input" min="1" max="7" value="{{ old('doors', $listing->doors) }}">
            </div>
            <div>
                <label class="label">{{ __('messages.seats') }}</label>
                <input type="number" name="seats" class="input" min="1" max="12" value="{{ old('seats', $listing->seats) }}">
            </div>
            <div>
                <label class="label">{{ __('messages.euro_standard') }}</label>
                <select name="euro_standard" class="input">
                    <option value="">—</option>
                    @foreach(['euro1','euro2','euro3','euro4','euro5','euro6','euro6d'] as $euro)
                        <option value="{{ $euro }}" @selected(old('euro_standard', $listing->euro_standard) === $euro)>{{ strtoupper($euro) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">{{ __('messages.registration_type') }}</label>
                <select name="registration_type" class="input">
                    <option value="">—</option>
                    <option value="permanent" @selected(old('registration_type', $listing->registration_type) === 'permanent')>{{ __('messages.registration_permanent') }}</option>
                    <option value="temporary" @selected(old('registration_type', $listing->registration_type) === 'temporary')>{{ __('messages.registration_temporary') }}</option>
                </select>
            </div>
            <div>
                <label class="label">{{ __('messages.vin') }}</label>
                <input type="text" name="vin" class="input" maxlength="17" value="{{ old('vin', $listing->vin) }}">
            </div>
            <div>
                <label class="label">{{ __('messages.wltp_consumption') }}</label>
                <input type="number" step="0.1" name="wltp_consumption" class="input" value="{{ old('wltp_consumption', $listing->wltp_consumption) }}">
            </div>
            <div>
                <label class="label">{{ __('messages.battery_capacity') }}</label>
                <input type="number" step="0.1" name="battery_capacity_kwh" class="input" value="{{ old('battery_capacity_kwh', $listing->battery_capacity_kwh) }}">
            </div>
            <div>
                <label class="label">{{ __('messages.warranty') }}</label>
                <input type="date" name="warranty_until" class="input" value="{{ old('warranty_until', $listing->warranty_until?->format('Y-m-d')) }}">
            </div>
            <div>
                <label class="label">{{ __('messages.first_registration') }}</label>
                <input type="date" name="first_registration_date" class="input" value="{{ old('first_registration_date', $listing->first_registration_date?->format('Y-m-d')) }}">
            </div>
        </div>
    </section>

    <section class="card p-5 sm:p-6">
        <h2 class="text-lg font-semibold">{{ __('messages.features') }}</h2>
        <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('messages.form_features_help') }}</p>
        <div class="mt-4 space-y-4">
            @foreach($featureCategories as $category)
                <div class="rounded-lg border border-[var(--color-border)] p-4">
                    <h3 class="text-sm font-semibold">{{ $category->name }}</h3>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($category->features as $feature)
                            <label class="flex items-start gap-2 text-sm">
                                <input type="checkbox" name="features[]" value="{{ $feature->id }}" class="mt-0.5"
                                       @checked(in_array($feature->id, $selectedFeatures))>
                                <span>{{ $feature->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="card p-5 sm:p-6" x-data="listingPhotoUpload()">
        <h2 class="text-lg font-semibold">{{ __('messages.form_section_photos') }}</h2>
        <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('messages.form_photos_help') }}</p>

        @if($listing->exists && $listing->images->isNotEmpty())
            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach($listing->images as $image)
                    <label class="group relative overflow-hidden rounded-lg border border-[var(--color-border)]">
                        <img src="{{ $image->url('medium') }}" alt="" class="aspect-[4/3] w-full object-cover" loading="lazy" decoding="async">
                        <div class="absolute inset-x-0 bottom-0 bg-black/60 p-2">
                            <span class="flex items-center gap-2 text-xs text-white">
                                <input type="checkbox" name="remove_images[]" value="{{ $image->id }}" class="rounded">
                                {{ __('messages.remove_photo') }}
                            </span>
                        </div>
                    </label>
                @endforeach
            </div>
        @endif

        <div class="mt-4">
            <label class="label">{{ __('messages.add_photos') }}</label>
            <input
                type="file"
                name="images[]"
                class="input file:mr-3 file:rounded-md file:border-0 file:bg-brand-600 file:px-3 file:py-1.5 file:text-sm file:text-white"
                accept="image/*"
                multiple
                @change="handleFiles($event)"
            >
            @error('images')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            @error('images.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4" x-show="previews.length" x-cloak>
            <template x-for="(preview, index) in previews" :key="preview.url">
                <div class="overflow-hidden rounded-lg border border-[var(--color-border)]">
                    <img :src="preview.url" :alt="preview.name" class="aspect-[4/3] w-full object-cover">
                    <p class="truncate px-2 py-1 text-[10px] text-[var(--color-text-muted)]" x-text="preview.name"></p>
                </div>
            </template>
        </div>
    </section>

    <section class="card p-5 sm:p-6">
        <h2 class="text-lg font-semibold">{{ __('messages.form_section_description') }}</h2>
        <div class="mt-4 space-y-4">
            <div>
                <label class="label">{{ __('messages.description') }}</label>
                <textarea name="description" rows="8" class="input">{{ old('description', $listing->description) }}</textarea>
            </div>
            <div class="flex flex-wrap gap-4 text-sm">
                <label class="flex items-center gap-2"><input type="checkbox" name="has_vin" value="1" @checked(old('has_vin', $listing->has_vin))> VIN</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="has_video" value="1" @checked(old('has_video', $listing->has_video))> {{ __('messages.has_video') }}</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="has_vr360" value="1" @checked(old('has_vr360', $listing->has_vr360))> 360°</label>
            </div>
        </div>
    </section>

    <input type="hidden" name="currency" value="EUR">

    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
        <a href="{{ $cancelUrl }}" class="btn-secondary order-2 sm:order-1">{{ __('messages.cancel') }}</a>
        <button type="submit" class="btn-primary order-1 sm:order-2">{{ $submitLabel }}</button>
    </div>
</form>