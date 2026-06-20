@extends('layouts.app')

@section('title', __('tenders.create_heading'))

@section('content')
<div class="mx-auto max-w-3xl px-4 py-6">
    <div class="mb-8">
        <h1 class="text-2xl font-bold">{{ __('tenders.create_heading') }}</h1>
        <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('tenders.create_subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('my.tenders.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="card space-y-4 p-5" x-data="listingBrandModel({{ old('brand_id', 'null') }}, {{ old('model_id', 'null') }})">
            <h2 class="font-semibold">{{ __('tenders.vehicle_details') }}</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-sm">{{ __('messages.brand') }}</label>
                    <select name="brand_id" x-model="brandId" @change="loadModels($event.target.value)" class="input mt-1 w-full" required>
                        <option value="">{{ __('messages.brand') }}</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}" @selected(old('brand_id') == $brand->id)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm">{{ __('messages.model') }}</label>
                    <select name="model_id" x-model="modelId" class="input mt-1 w-full" :disabled="!brandId || loading" required>
                        <option value="">{{ __('messages.model') }}</option>
                        <template x-for="item in flatModels" :key="item.id">
                            <option :value="item.id" x-text="item.name" :selected="modelId == item.id"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div>
                <label class="text-sm">{{ __('messages.car_variant') }}</label>
                <input type="text" name="car_variant" value="{{ old('car_variant') }}" class="input mt-1 w-full">
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-sm">{{ __('messages.year') }}</label>
                    <input type="number" name="year" value="{{ old('year', date('Y')) }}" class="input mt-1 w-full" required>
                </div>
                <div>
                    <label class="text-sm">{{ __('messages.mileage') }}</label>
                    <input type="number" name="mileage" value="{{ old('mileage') }}" class="input mt-1 w-full">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-sm">{{ __('messages.fuel_type') }}</label>
                    <input type="text" name="fuel_type" value="{{ old('fuel_type') }}" class="input mt-1 w-full">
                </div>
                <div>
                    <label class="text-sm">{{ __('messages.transmission') }}</label>
                    <input type="text" name="transmission" value="{{ old('transmission') }}" class="input mt-1 w-full">
                </div>
            </div>

            <div>
                <label class="text-sm">{{ __('messages.description') }}</label>
                <textarea name="description" rows="4" class="input mt-1 w-full">{{ old('description') }}</textarea>
            </div>
        </div>

        <div class="card space-y-4 p-5" x-data="regionCityPicker({{ old('region_id', 'null') }}, @js(old('city', '')))">
            <h2 class="font-semibold">{{ __('tenders.location') }}</h2>
            <input type="hidden" name="location_type" value="bg">

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-sm">{{ __('messages.region') }}</label>
                    <select name="region_id" x-model="regionId" @change="onRegionChange()" class="input mt-1 w-full" required>
                        <option value="">{{ __('messages.region') }}</option>
                        @foreach($regions as $region)
                            <option value="{{ $region->id }}" @selected(old('region_id') == $region->id)>{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm">{{ __('messages.city') }}</label>
                    <input type="hidden" name="city" x-model="city">
                    <input type="text" x-model="cityQuery" @focus="cityOpen = true" class="input mt-1 w-full" required>
                </div>
            </div>
        </div>

        <div class="card space-y-4 p-5">
            <h2 class="font-semibold">{{ __('tenders.title') }}</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-sm">{{ __('tenders.starting_price') }} (€)</label>
                    <input type="number" name="starting_price" value="{{ old('starting_price') }}" min="1" step="1" class="input mt-1 w-full" required>
                </div>
                <div>
                    <label class="text-sm">{{ __('tenders.bid_increment_label') }} (€)</label>
                    <select name="bid_increment" class="input mt-1 w-full" required>
                        @foreach($bidIncrements as $increment)
                            <option value="{{ $increment }}" @selected(old('bid_increment', $defaultBidIncrement) == $increment)>{{ number_format($increment) }} €</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-[var(--color-text-muted)]">{{ __('tenders.bid_increment_help') }}</p>
                </div>
            </div>

            <div>
                <label class="text-sm">{{ __('tenders.minimum_price') }} (€)</label>
                <input type="number" name="minimum_price" value="{{ old('minimum_price') }}" min="0" step="1" class="input mt-1 w-full">
                <p class="mt-1 text-xs text-[var(--color-text-muted)]">{{ __('tenders.minimum_price_help') }}</p>
            </div>

            <div>
                <label class="text-sm">{{ __('tenders.duration') }}</label>
                <select name="duration_days" class="input mt-1 w-full" required>
                    @foreach($durationOptions as $option)
                        <option value="{{ $option['value'] }}" @selected(old('duration_days', 7) == $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-[var(--color-text-muted)]">{{ __('tenders.duration_help', ['max' => $maxDurationDays]) }}</p>
            </div>

            <div x-data="listingPhotoUpload">
                <label class="text-sm">{{ __('tenders.photos') }}</label>
                <input type="file" name="images[]" multiple accept="image/*" class="input mt-1 w-full" @change="handleFiles">
                <div class="mt-3 flex flex-wrap gap-2" x-show="previews.length">
                    <template x-for="preview in previews" :key="preview.url">
                        <img :src="preview.url" alt="" class="h-20 w-28 rounded object-cover">
                    </template>
                </div>
            </div>
        </div>

        @if($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex justify-end gap-3">
            <a href="{{ route('my.tenders.index') }}" class="btn-secondary">{{ __('messages.cancel') }}</a>
            <button type="submit" class="btn-primary">{{ __('tenders.start_tender') }}</button>
        </div>
    </form>
</div>
@endsection