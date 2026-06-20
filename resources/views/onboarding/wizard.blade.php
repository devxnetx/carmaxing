@extends('layouts.app')

@section('title', __('messages.onboarding_title'))

@section('content')
<div class="mx-auto max-w-2xl px-4 py-12" x-data="{ type: 'private' }">
    <div class="text-center">
        <h1 class="text-3xl font-bold">{{ __('messages.onboarding_title') }}</h1>
        <p class="mt-2 text-[var(--color-text-muted)]">{{ __('messages.onboarding_subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('onboarding.store') }}" class="mt-8 space-y-6">
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="card cursor-pointer p-5 transition has-[:checked]:border-brand-500 has-[:checked]:ring-2 has-[:checked]:ring-brand-500/20">
                <input type="radio" name="account_type" value="private" class="sr-only" x-model="type">
                <div class="text-lg font-semibold">{{ __('messages.account_private') }}</div>
                <p class="mt-2 text-sm text-[var(--color-text-muted)]">{{ __('messages.account_private_desc') }}</p>
            </label>
            <label class="card cursor-pointer p-5 transition has-[:checked]:border-brand-500 has-[:checked]:ring-2 has-[:checked]:ring-brand-500/20">
                <input type="radio" name="account_type" value="company" class="sr-only" x-model="type">
                <div class="text-lg font-semibold">{{ __('messages.account_company') }}</div>
                <p class="mt-2 text-sm text-[var(--color-text-muted)]">{{ __('messages.account_company_desc') }}</p>
            </label>
        </div>

        <div x-show="type === 'private'" class="card p-5">
            <label class="label">{{ __('messages.phone') }} *</label>
            <x-phone-input name="phone" :value="old('phone')" :required="true" />
        </div>

        <div x-show="type === 'company'" class="card space-y-4 p-5">
            <div>
                <label class="label">{{ __('messages.company_name') }} *</label>
                <input type="text" name="company_name" class="input" value="{{ old('company_name') }}">
                @error('company_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">{{ __('messages.phone') }} *</label>
                <x-phone-input name="company_phone" :value="old('company_phone')" :required="true" />
            </div>
            <div x-data="regionCityPicker(@js(old('region_id')), @js(old('company_city')))" x-init="init()">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">{{ __('messages.oblast') }}</label>
                        <select name="region_id" class="input" x-model="regionId" @change="onRegionChange()">
                            <option value="">{{ __('messages.location_select_oblast') }}</option>
                            @foreach(\App\Models\Region::orderBy('sort_order')->get() as $region)
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
                            :disabled="!regionId"
                            autocomplete="off"
                        >
                        <input type="hidden" name="company_city" :value="city">
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
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-primary w-full">{{ __('messages.continue') }}</button>
    </form>
</div>
@endsection