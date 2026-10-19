@php
    use App\Support\LocationCatalog;
    use App\Support\RichText;

    $selectedFeatures = old('features', $listing->exists ? $listing->features->pluck('id')->toArray() : []);
    $formCountryCode = old('country_code', $listing->country_code);
    $formLocationType = old('location_type', LocationCatalog::isBulgaria($formCountryCode) ? 'bg' : 'abroad');
    $formAction = $formAction ?? ($listing->exists ? route('listings.update', $listing) : route('listings.store'));
    $cancelUrl = $cancelUrl ?? route('dashboard');
    $submitLabel = $submitLabel ?? ($listing->exists ? __('messages.save') : __('messages.publish_listing'));
    $priceOnRequest = (bool) old('price_on_request', $listing->price_on_request);
    $yearMax = (int) date('Y') + 1;
    $selectedYear = old('year', $listing->year);
    $selectedModelId = old('model_id', $listing->model_id);
    $selectedModelLabel = null;

    if ($selectedModelId) {
        $selectedModelLabel = (string) $listing->model_id === (string) $selectedModelId
            ? ($listing->model?->name ?? (string) $selectedModelId)
            : (string) $selectedModelId;
    }

    $selectedRegionId = (string) old('region_id', $listing->region_id);
    $selectedCityName = (string) old('city', $listing->city ?? '');
    $selectedCityValue = ($selectedRegionId !== '' && $selectedCityName !== '')
        ? $selectedRegionId.'::'.$selectedCityName
        : '';
    $cityGroups = [];
    $knownCityValues = [];

    foreach ($regions as $region) {
        $options = [];

        foreach (LocationCatalog::citiesForRegion($region) as $cityName) {
            $value = $region->id.'::'.$cityName;
            $options[] = ['value' => $value, 'label' => $cityName];
            $knownCityValues[$value] = true;
        }

        if ($options !== []) {
            $cityGroups[] = ['name' => $region->name, 'options' => $options];
        }
    }

    $extraCityOption = ($selectedCityValue !== '' && ! isset($knownCityValues[$selectedCityValue]))
        ? ['value' => $selectedCityValue, 'label' => $selectedCityName]
        : null;
    $selectedCountry = old('country_code', LocationCatalog::isBulgaria($listing->country_code) ? null : $listing->country_code);
    $descriptionInitial = RichText::sanitize(old('description', $listing->description)) ?? '';
    $existingImages = $listing->exists
        ? $listing->images->sortBy([['sort_order', 'asc'], ['id', 'asc']])->values()->map(fn ($image) => [
            'id' => $image->id,
            'url' => $image->url('medium'),
        ])->all()
        : [];
    $stepFields = [
        0 => ['brand_id', 'model_id', 'car_variant', 'title', 'year', 'mileage', 'images', 'photo_order', 'remove_images', 'description', 'has_vin', 'has_video', 'has_vr360'],
        1 => ['price', 'price_on_request', 'currency', 'price_negotiable', 'location_type', 'region_id', 'city', 'country_code'],
        2 => ['fuel_type', 'engine_power_hp', 'engine_displacement_cc', 'transmission', 'drivetrain', 'body_type', 'color_exterior', 'color_interior', 'doors', 'seats', 'euro_standard', 'registration_type', 'vin', 'condition', 'warranty_until', 'first_registration_date', 'wltp_consumption', 'battery_capacity_kwh'],
        3 => ['features'],
    ];
    $initialStep = 0;

    if ($errors->any()) {
        foreach ($errors->keys() as $key) {
            $base = explode('.', $key)[0];

            foreach ($stepFields as $stepIndex => $fields) {
                if (in_array($base, $fields, true)) {
                    $initialStep = $stepIndex;
                    break 2;
                }
            }
        }
    }

    $wizard = [
        'initialStep' => $initialStep,
        'existing' => $listing->exists && ! $errors->any(),
        'labels' => [
            __('messages.form_step_general'),
            __('messages.form_step_price'),
            __('messages.form_step_specs'),
            __('messages.form_step_features'),
        ],
    ];
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
    novalidate
    class="space-y-4"
    x-data="listingFormWizard(@js($wizard))"
    @submit="submitForm($event)"
>
    @csrf
    @if($listing->exists) @method('PUT') @endif
    <input type="hidden" name="currency" value="EUR">

    <div class="sticky top-16 z-30 -mx-4 border-b border-[var(--color-border)] bg-[var(--color-surface)] lg:mx-0 lg:border lg:border-b-0" role="tablist" aria-label="{{ __('messages.new_listing') }}">
        <div class="grid grid-cols-4">
            @foreach($wizard['labels'] as $index => $label)
                <button
                    type="button"
                    role="tab"
                    @click="go({{ $index }})"
                    class="flex items-center justify-center gap-2 border-b-2 px-2 py-3 text-sm font-semibold sm:px-4 sm:text-base"
                    :class="step === {{ $index }} ? 'border-brand-600 text-brand-700' : 'border-transparent text-[var(--color-text-muted)]'"
                    :aria-selected="step === {{ $index }} ? 'true' : 'false'"
                >
                    <span
                        class="flex h-6 w-6 items-center justify-center rounded-full text-xs"
                        :class="done[{{ $index }}] ? 'bg-green-600 text-white' : (step === {{ $index }} ? 'bg-brand-600 text-white' : 'bg-[var(--color-surface-3)]')"
                        x-text="done[{{ $index }}] ? '✓' : '{{ $index + 1 }}'"
                    >{{ $index + 1 }}</span>
                    <span>{{ $label }}</span>
                </button>
            @endforeach
        </div>
    </div>

    <div data-step="0" x-show="step === 0" x-cloak class="space-y-4">
    <section class="card p-5 sm:p-6">
        <h2 class="text-lg font-semibold">{{ __('messages.form_section_general') }}</h2>
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
                                <option value="{{ $brand->id }}" @selected((string) old('brand_id', $listing->brand_id) === (string) $brand->id)>{{ $brand->name }}</option>
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
                            @if($selectedModelLabel)
                                <option value="{{ $selectedModelId }}" selected>{{ $selectedModelLabel }}</option>
                            @endif
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
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">{{ __('messages.year') }} *</label>
                    <select name="year" class="input @error('year') border-red-500 @enderror" required>
                        <option value="">{{ __('messages.date_year') }}</option>
                        @for($year = $yearMax; $year >= 1950; $year--)
                            <option value="{{ $year }}" @selected((string) $selectedYear === (string) $year)>{{ $year }}</option>
                        @endfor
                    </select>
                    @error('year')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">{{ __('messages.mileage') }}</label>
                    <input type="number" name="mileage" class="input" min="0" value="{{ old('mileage', $listing->mileage) }}">
                </div>
            </div>
        </div>
    </section>

    <section class="card p-5 sm:p-6">
        <div x-data="listingPhotos(@js($existingImages))">
            <h2 class="text-lg font-semibold">{{ __('messages.form_section_photos') }}</h2>
            <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('messages.form_photos_help') }}</p>
            <input type="hidden" name="photo_order_submitted" value="1">
            <input type="hidden" name="photos_cleared" value="0" :value="items.length === 0 ? '1' : '0'">

            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3" x-show="items.length" x-cloak>
                <template x-for="(item, index) in items" :key="item.kind + '-' + (item.id || item.url) + '-' + index">
                    <div class="overflow-hidden rounded-lg border border-[var(--color-border)]">
                        <div class="relative">
                            <img :src="item.url" :alt="item.name || ''" class="aspect-[4/3] w-full object-cover">
                            <span x-show="index === 0" class="absolute left-2 top-2 rounded bg-green-600 px-2 py-0.5 text-[10px] font-semibold text-white">{{ __('messages.photo_cover') }}</span>
                        </div>
                        <div class="flex gap-1 p-2">
                            <button type="button" class="btn-secondary flex-1 px-2 py-1.5 text-xs" @click="move(index, -1)" :disabled="index === 0">{{ __('messages.photo_move_earlier') }}</button>
                            <button type="button" class="btn-secondary flex-1 px-2 py-1.5 text-xs" @click="move(index, 1)" :disabled="index === items.length - 1">{{ __('messages.photo_move_later') }}</button>
                            <button type="button" class="btn-secondary px-2 py-1.5 text-xs text-red-600" @click="remove(index)">{{ __('messages.remove_photo') }}</button>
                        </div>
                        <input type="hidden" name="photo_order[]" :value="token(item)">
                    </div>
                </template>
            </div>

            <div class="mt-4">
                <input
                    type="file"
                    name="images[]"
                    x-ref="files"
                    class="sr-only"
                    accept="image/*"
                    multiple
                    @change="addFiles($event)"
                >
                <button type="button" class="btn-secondary" @click="$refs.files.click()" :disabled="items.length >= 20">{{ __('messages.add_photos') }}</button>
                @error('images')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                @error('images.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <section class="card p-5 sm:p-6">
        <h2 class="text-lg font-semibold">{{ __('messages.form_section_description') }}</h2>
        <div class="mt-4 space-y-4">
            <div
                class="description-editor"
                data-skip-validation
                x-data="richTextEditor(@js($descriptionInitial), @js([
                    'bold' => __('messages.form_bold'),
                    'italic' => __('messages.form_italic'),
                    'underline' => __('messages.form_underline'),
                    'ordered' => __('messages.form_list_ordered'),
                    'bullet' => __('messages.form_list'),
                    'link' => __('messages.form_link'),
                    'clear' => __('messages.form_clear_format'),
                ]))"
            >
                <label class="label">{{ __('messages.description') }}</label>
                <p class="mb-2 text-xs text-[var(--color-text-muted)]">{{ __('messages.form_description_help') }}</p>
                <div x-ref="editor"></div>
                <textarea x-ref="input" name="description" class="hidden">{{ $descriptionInitial }}</textarea>
            </div>
            <div class="flex flex-wrap gap-4 text-sm">
                <label class="flex items-center gap-2"><input type="checkbox" name="has_vin" value="1" @checked(old('has_vin', $listing->has_vin))> VIN</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="has_video" value="1" @checked(old('has_video', $listing->has_video))> {{ __('messages.has_video') }}</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="has_vr360" value="1" @checked(old('has_vr360', $listing->has_vr360))> 360°</label>
            </div>
        </div>
    </section>
    </div>

    <div data-step="1" x-show="step === 1" x-cloak>
    <section class="card p-5 sm:p-6">
        <h2 class="text-lg font-semibold">{{ __('messages.form_section_price') }}</h2>
        <div class="mt-4 space-y-4" x-data="{ onRequest: @js($priceOnRequest ? '1' : '0') }">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <label class="flex items-center gap-2 text-sm">
                    <input type="radio" name="price_on_request" value="0" x-model="onRequest" @checked(! $priceOnRequest)>
                    {{ __('messages.form_step_price') }}
                </label>
                <div class="sm:w-56" x-show="onRequest === '0'" x-cloak>
                    <input
                        type="number"
                        name="price"
                        class="input @error('price') border-red-500 @enderror"
                        min="0"
                        placeholder="EUR"
                        @disabled($priceOnRequest)
                        @required(! $priceOnRequest)
                        :required="onRequest === '0'"
                        :disabled="onRequest !== '0'"
                        value="{{ old('price', $listing->price_on_request ? '' : $listing->price) }}"
                        aria-label="{{ __('messages.form_step_price') }} (EUR)"
                    >
                    @error('price')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="radio" name="price_on_request" value="1" x-model="onRequest" @checked($priceOnRequest)>
                {{ __('messages.price_on_request') }}
            </label>
            {{-- <label class="flex items-center gap-2 text-sm" x-show="onRequest === '0'">
                <input type="checkbox" name="price_negotiable" value="1" @checked(old('price_negotiable', $listing->price_negotiable))>
                {{ __('messages.negotiable') }}
            </label> --}}
            <div x-data="{ locationType: @js($formLocationType) }">
                <label class="label">{{ __('messages.location') }} *</label>
                <select name="location_type" class="input @error('location_type') border-red-500 @enderror" x-model="locationType" required>
                    <option value="bg">{{ __('messages.location_bulgaria') }}</option>
                    <option value="abroad">{{ __('messages.location_abroad') }}</option>
                </select>

                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <div :class="locationType === 'bg' ? '' : 'hidden sm:block sm:invisible'">
                        <label class="label">{{ __('messages.city') }} *</label>
                        <select
                            name="city"
                            class="input @error('city') border-red-500 @enderror"
                            @disabled($formLocationType !== 'bg')
                            @required($formLocationType === 'bg')
                            :disabled="locationType !== 'bg'"
                            :required="locationType === 'bg'"
                        >
                            <option value="">{{ __('messages.location_select_city') }}</option>
                            @if($extraCityOption)
                                <option value="{{ $extraCityOption['value'] }}" selected>{{ $extraCityOption['label'] }}</option>
                            @endif
                            @foreach($cityGroups as $group)
                                <optgroup label="{{ $group['name'] }}">
                                    @foreach($group['options'] as $cityOption)
                                        <option value="{{ $cityOption['value'] }}" @selected($cityOption['value'] === $selectedCityValue)>{{ $cityOption['label'] }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @error('city')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        @error('region_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div :class="locationType === 'abroad' ? '' : 'hidden sm:block sm:invisible'">
                        <label class="label">{{ __('messages.location_country') }} *</label>
                        <select
                            name="country_code"
                            class="input @error('country_code') border-red-500 @enderror"
                            @disabled($formLocationType !== 'abroad')
                            @required($formLocationType === 'abroad')
                            :disabled="locationType !== 'abroad'"
                            :required="locationType === 'abroad'"
                        >
                            <option value="">{{ __('messages.location_country') }}</option>
                            @foreach($countries as $country)
                                <option value="{{ $country['code'] }}" @selected((string) $selectedCountry === (string) $country['code'])>{{ $country['name'] }}</option>
                            @endforeach
                        </select>
                        @error('country_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </div>
    </section>
    </div>

    <div data-step="2" x-show="step === 2" x-cloak>
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
                <x-date-select name="warranty_until" :value="old('warranty_until', $listing->warranty_until)" />
            </div>
            <div>
                <label class="label">{{ __('messages.first_registration') }}</label>
                <x-date-select name="first_registration_date" :value="old('first_registration_date', $listing->first_registration_date)" />
            </div>
        </div>
    </section>
    </div>

    <div data-step="3" x-show="step === 3" x-cloak>
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
    </div>

    <div class="sticky bottom-16 z-30 -mx-4 flex items-center justify-between gap-3 border-t border-[var(--color-border)] bg-[var(--color-surface)] px-4 py-3 lg:static lg:mx-0 lg:rounded-xl lg:border lg:px-4">
        <a href="{{ $cancelUrl }}" class="btn-secondary" x-show="step === 0">{{ __('messages.cancel') }}</a>
        <button type="button" class="btn-secondary" x-show="step > 0" x-cloak @click="back()">{{ __('messages.form_previous') }}</button>
        <button type="button" class="btn-primary ml-auto" x-show="step < labels.length - 1" @click="next()">{{ __('messages.form_next') }}</button>
        <button type="submit" class="btn-primary ml-auto" x-show="step === labels.length - 1" x-cloak>{{ $submitLabel }}</button>
    </div>
</form>
