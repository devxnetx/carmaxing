@extends('layouts.app')

@section('title', __('messages.company_profile'))

@section('content')
<div class="mx-auto max-w-3xl px-4 py-6 sm:py-8">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">{{ __('messages.company_profile') }}</h1>
        @if($company)
            <a href="{{ route('company.show', $company) }}" class="btn-secondary text-sm">{{ __('messages.public_dealer_page') }}</a>
        @endif
    </div>

    <form method="POST" action="{{ route('company.update') }}" enctype="multipart/form-data" class="card space-y-6 p-5 sm:p-6">
        @csrf @method('PUT')

        <div>
            <label class="label">{{ __('messages.cover_banner') }}</label>
            <div class="overflow-hidden rounded-xl border border-[var(--color-border)]">
                <div class="relative aspect-[3/1] bg-gradient-to-r from-brand-700 to-brand-500">
                    @if($company->coverUrl())
                        <img src="{{ $company->coverUrl() }}" alt="" class="h-full w-full object-cover">
                    @endif
                </div>
            </div>
            <input type="file" name="cover_image" accept="image/*" class="input mt-3 file:mr-3 file:rounded-md file:border-0 file:bg-brand-600 file:px-3 file:py-1.5 file:text-sm file:text-white">
            @if($company->cover_image)
                <label class="mt-2 flex items-center gap-2 text-sm"><input type="checkbox" name="remove_cover" value="1"> {{ __('messages.remove_cover') }}</label>
            @endif
        </div>

        <div>
            <label class="label">{{ __('messages.profile_picture') }}</label>
            <div class="flex items-center gap-4">
                <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border-2 border-[var(--color-border)] bg-brand-600 text-xl font-bold text-white">
                    @if($company->logoUrl())
                        <img src="{{ $company->logoUrl() }}" alt="" class="h-full w-full object-cover">
                    @else
                        {{ strtoupper(substr($company->name, 0, 2)) }}
                    @endif
                </div>
                <input type="file" name="logo" accept="image/*" class="input file:mr-3 file:rounded-md file:border-0 file:bg-brand-600 file:px-3 file:py-1.5 file:text-sm file:text-white">
            </div>
            @if($company->logo)
                <label class="mt-2 flex items-center gap-2 text-sm"><input type="checkbox" name="remove_logo" value="1"> {{ __('messages.remove_logo') }}</label>
            @endif
        </div>

        <div>
            <label class="label">{{ __('messages.company_name') }} *</label>
            <input type="text" name="name" class="input" value="{{ old('name', $company->name) }}" required>
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="label">{{ __('messages.phone') }} *</label>
            <x-phone-input name="phone" :value="$company->phone" :required="true" />
        </div>

        <div>
            <label class="label">{{ __('messages.email') }}</label>
            <input type="email" name="email" class="input" value="{{ old('email', $company->email) }}">
            @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="label">{{ __('messages.website') }}</label>
            <input type="url" name="website" class="input" value="{{ old('website', $company->website) }}" placeholder="https://">
            @error('website')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="label">{{ __('messages.address') }}</label>
            <input type="text" name="address" class="input" value="{{ old('address', $company->address) }}">
            @error('address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div x-data="regionCityPicker(@js(old('region_id', $company->region_id)), @js(old('city', $company->city)))" x-init="init()">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">{{ __('messages.oblast') }}</label>
                    <select name="region_id" class="input @error('region_id') border-red-500 @enderror" x-model="regionId" @change="onRegionChange()">
                        <option value="">{{ __('messages.location_select_oblast') }}</option>
                        @foreach($regions as $region)
                            <option value="{{ $region->id }}">{{ $region->name }}</option>
                        @endforeach
                    </select>
                    @error('region_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="relative">
                    <label class="label">{{ __('messages.city') }}</label>
                    <input
                        type="text"
                        class="input @error('city') border-red-500 @enderror"
                        x-model="cityQuery"
                        @focus="cityOpen = true"
                        @click.outside="cityOpen = false"
                        :placeholder="regionId ? '{{ __('messages.location_search_city') }}' : '{{ __('messages.location_select_oblast') }}'"
                        :disabled="!regionId"
                        autocomplete="off"
                    >
                    <input type="hidden" name="city" :value="city">
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
                    <p class="mt-1 text-xs text-[var(--color-text-muted)]" x-show="loadingCities" x-cloak>{{ __('messages.loading_cities') }}</p>
                    @error('city')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div>
            <label class="label">{{ __('messages.description') }}</label>
            <textarea name="description" rows="5" class="input">{{ old('description', $company->description) }}</textarea>
            @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="btn-primary">{{ __('messages.save') }}</button>
    </form>

    <section class="card mt-8 space-y-5 p-5 sm:p-6">
        <div>
            <h2 class="text-lg font-semibold">{{ __('messages.mobile_bg_profile_extract') }}</h2>
            <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('messages.mobile_bg_profile_extract_help') }}</p>
        </div>

        <form method="POST" action="{{ route('company.mobile-bg-profile') }}" class="space-y-4">
            @csrf
            <div>
                <label class="label" for="mobile_bg_profile_url">{{ __('messages.mobile_bg_profile_url') }}</label>
                <input
                    type="url"
                    name="mobile_bg_profile_url"
                    id="mobile_bg_profile_url"
                    class="input"
                    placeholder="https://ratola.mobile.bg/"
                    value="{{ old('mobile_bg_profile_url', $company->mobile_bg_url) }}"
                    required
                >
                @error('mobile_bg_profile_url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn-primary">{{ __('messages.mobile_bg_profile_extract_button') }}</button>
        </form>
    </section>

    <section class="card mt-8 space-y-5 p-5 sm:p-6" x-data="mobileBgImport({{ $latestImport && ! $latestImport->isFinished() ? $latestImport->id : 'null' }}, @js($latestImport ? route('company.mobile-bg-import.status', $latestImport) : ''))">
        <div>
            <h2 class="text-lg font-semibold">{{ __('messages.mobile_bg_import') }}</h2>
            <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('messages.mobile_bg_import_help') }}</p>
        </div>

        @if($company->mobile_bg_last_sync_at)
            <p class="text-sm text-[var(--color-text-muted)]">
                {{ __('messages.mobile_bg_last_sync') }}: {{ $company->mobile_bg_last_sync_at->format('d.m.Y H:i') }}
            </p>
        @endif

        @if($latestImport)
            <div class="rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] p-4 text-sm">
                <div class="font-medium">{{ __('messages.mobile_bg_import_status') }}:
                    @if($latestImport->status === 'completed')
                        {{ __('messages.mobile_bg_import_completed') }}
                    @elseif($latestImport->status === 'failed')
                        {{ __('messages.mobile_bg_import_failed') }}
                    @elseif($latestImport->status === 'running')
                        {{ __('messages.mobile_bg_import_running_status') }}
                    @else
                        {{ __('messages.mobile_bg_import_pending') }}
                    @endif
                </div>
                <dl class="mt-3 grid gap-2 sm:grid-cols-2">
                    <div><dt class="text-[var(--color-text-muted)]">{{ __('messages.mobile_bg_found') }}</dt><dd x-text="status?.total_found ?? {{ $latestImport->total_found }}">{{ $latestImport->total_found }}</dd></div>
                    <div><dt class="text-[var(--color-text-muted)]">{{ __('messages.mobile_bg_created') }}</dt><dd x-text="status?.created_count ?? {{ $latestImport->created_count }}">{{ $latestImport->created_count }}</dd></div>
                    <div><dt class="text-[var(--color-text-muted)]">{{ __('messages.mobile_bg_updated') }}</dt><dd x-text="status?.updated_count ?? {{ $latestImport->updated_count }}">{{ $latestImport->updated_count }}</dd></div>
                    <div><dt class="text-[var(--color-text-muted)]">{{ __('messages.mobile_bg_failed') }}</dt><dd x-text="status?.failed_count ?? {{ $latestImport->failed_count }}">{{ $latestImport->failed_count }}</dd></div>
                </dl>
                @if($latestImport->errors)
                    <ul class="mt-3 list-disc space-y-1 pl-5 text-red-600">
                        @foreach($latestImport->errors as $error)
                            <li>{{ ($error['external_id'] ?? '').(($error['external_id'] ?? false) ? ': ' : '').($error['message'] ?? '') }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        <form method="POST" action="{{ route('company.mobile-bg-import') }}" class="space-y-4">
            @csrf
            <div>
                <label class="label" for="mobile_bg_import_url">{{ __('messages.mobile_bg_import_url') }}</label>
                <input
                    type="url"
                    name="mobile_bg_url"
                    id="mobile_bg_import_url"
                    class="input"
                    placeholder="https://ratola.mobile.bg/"
                    value="{{ old('mobile_bg_url', $company->mobile_bg_url) }}"
                    required
                >
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="sync_images" value="1" checked>
                {{ __('messages.mobile_bg_sync_images') }}
            </label>
            <button type="submit" class="btn-primary" :disabled="polling">{{ __('messages.mobile_bg_start_import') }}</button>
        </form>
    </section>
</div>

@endsection