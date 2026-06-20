@extends('layouts.app')

@php
    use App\Support\HtmlToPlainText;
    use App\Support\ListingPresenter;

    $descriptionText = HtmlToPlainText::sanitize($listing->description);
@endphp

@section('title', __('messages.seo_listing_title', [
    'vehicle' => $listing->composeDisplayTitle(),
    'app' => config('app.name'),
]))
@section('meta_description', Str::limit(
    $descriptionText ?? ListingPresenter::specLine($listing).' — '.$listing->composeDisplayTitle(),
    160,
))
@section('canonical', route('listings.show', $listing))
@section('meta_image', $listing->images->first()?->url('large') ?? url('/apple-touch-icon.png'))
@section('meta_image_alt', $listing->composeDisplayTitle())
@section('meta_type', 'article')

@push('jsonld')
    <x-seo.breadcrumb-jsonld :items="[
        ['name' => __('messages.home'), 'url' => route('home')],
        ['name' => $listing->brand->name, 'url' => route('search', ['brand_id' => $listing->brand_id])],
        ['name' => $listing->breadcrumbModelName(), 'url' => route('search', ['brand_id' => $listing->brand_id, 'model_id' => $listing->model_id])],
        ['name' => $listing->breadcrumbAdName(), 'url' => route('listings.show', $listing)],
    ]" />
    <x-seo.listing-jsonld :listing="$listing" />
@endpush

@section('content')
<div class="mx-auto max-w-7xl px-4 py-6">
    <x-listing-print-header
        :listing="$listing"
        :latest-price-change="$latestPriceChange"
        :market-estimate="$marketEstimate"
    />

    <x-breadcrumbs class="no-print mb-4" :items="[
        ['name' => __('messages.home'), 'url' => route('home')],
        ['name' => $listing->brand->name, 'url' => route('search', ['brand_id' => $listing->brand_id])],
        ['name' => $listing->breadcrumbModelName(), 'url' => route('search', ['brand_id' => $listing->brand_id, 'model_id' => $listing->model_id])],
        ['name' => $listing->breadcrumbAdName()],
    ]" />

    <div class="grid gap-8 lg:grid-cols-3">
        <div class="order-2 space-y-6 lg:order-1 lg:col-span-2">
            @if($listing->has_vin || $listing->has_video || $listing->has_vr360)
                <div class="flex flex-wrap items-center gap-2">
                    @if($listing->has_vin)
                        <span class="badge bg-[var(--color-surface-3)]">VIN</span>
                    @endif
                    @if($listing->has_video)
                        <span class="badge bg-[var(--color-surface-3)]"><x-icon name="video" class="mr-1 inline h-3.5 w-3.5" />{{ __('messages.has_video') }}</span>
                    @endif
                    @if($listing->has_vr360)
                        <span class="badge bg-[var(--color-surface-3)]">360°</span>
                    @endif
                </div>
            @endif

            <x-listing-gallery :listing="$listing" />

            @php
                $highlightSpecs = array_values(array_filter([
                    ['label' => __('messages.year'), 'value' => $listing->month ? sprintf('%02d/%d', $listing->month, $listing->year) : $listing->year],
                    ['label' => __('messages.mileage'), 'value' => $listing->mileage ? number_format($listing->mileage).' '.__('messages.km') : null],
                    ['label' => __('messages.fuel_type'), 'value' => ListingPresenter::fuelLabel($listing->fuel_type)],
                    ['label' => __('messages.power'), 'value' => $listing->engine_power_hp ? $listing->engine_power_hp.' '.__('messages.hp') : null],
                    ['label' => __('messages.displacement'), 'value' => $listing->engine_displacement_cc ? $listing->engine_displacement_cc.' '.__('messages.cc') : null],
                    ['label' => __('messages.transmission'), 'value' => ListingPresenter::transmissionLabel($listing->transmission)],
                    ['label' => __('messages.euro_standard'), 'value' => ListingPresenter::euroLabel($listing->euro_standard)],
                    ['label' => __('messages.color'), 'value' => $listing->color_exterior],
                ], fn ($spec) => filled($spec['value'])));
            @endphp

            @if($highlightSpecs !== [])
                <div class="card overflow-hidden">
                    <div class="grid grid-cols-2 gap-px bg-[var(--color-border)] sm:grid-cols-4">
                        @foreach(array_slice($highlightSpecs, 0, 8) as $spec)
                            <div class="flex flex-col bg-[var(--color-surface)] px-4 py-3 text-sm">
                                <span class="text-xs text-[var(--color-text-muted)]">{{ $spec['label'] }}</span>
                                <span class="mt-0.5 font-semibold">{{ $spec['value'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($descriptionText)
                <div class="card p-6">
                    <h2 class="text-lg font-semibold">{{ __('messages.description') }}</h2>
                    <div class="prose prose-sm mt-4 max-w-none whitespace-pre-line text-[var(--color-text-muted)] dark:prose-invert">{{ $descriptionText }}</div>
                </div>
            @endif

            <div class="card p-6">
                <h2 class="text-lg font-semibold">{{ __('messages.technical_data') }}</h2>
                <div class="mt-4 grid gap-6 md:grid-cols-2">
                    <div>
                        <h3 class="mb-3 text-sm font-medium text-[var(--color-text-muted)]">{{ __('messages.basic_data') }}</h3>
                        <dl class="divide-y divide-[var(--color-border)] text-sm">
                            @foreach([
                                __('messages.brand') => $listing->brand->name,
                                __('messages.model') => $listing->model->name,
                                __('messages.year') => $listing->month ? sprintf('%02d/%d', $listing->month, $listing->year) : $listing->year,
                                __('messages.mileage') => $listing->mileage ? number_format($listing->mileage).' '.__('messages.km') : null,
                                __('messages.fuel_type') => ListingPresenter::fuelLabel($listing->fuel_type),
                                __('messages.power') => $listing->engine_power_hp ? $listing->engine_power_hp.' '.__('messages.hp') : null,
                                __('messages.displacement') => $listing->engine_displacement_cc ? $listing->engine_displacement_cc.' '.__('messages.cc') : null,
                                __('messages.transmission') => ListingPresenter::transmissionLabel($listing->transmission),
                                __('messages.drivetrain') => ListingPresenter::drivetrainLabel($listing->drivetrain),
                                __('messages.body_type') => ListingPresenter::bodyLabel($listing->body_type),
                            ] as $label => $value)
                                @if($value)
                                    <div class="flex justify-between gap-4 py-2.5">
                                        <dt class="text-[var(--color-text-muted)]">{{ $label }}</dt>
                                        <dd class="text-right font-medium">{{ $value }}</dd>
                                    </div>
                                @endif
                            @endforeach
                        </dl>
                    </div>
                    <div>
                        <h3 class="mb-3 text-sm font-medium text-[var(--color-text-muted)]">{{ __('messages.specifications') }}</h3>
                        <dl class="divide-y divide-[var(--color-border)] text-sm">
                            @foreach([
                                __('messages.color') => $listing->color_exterior,
                                __('messages.interior') => $listing->color_interior,
                                __('messages.doors') => $listing->doors,
                                __('messages.seats') => $listing->seats,
                                __('messages.euro_standard') => ListingPresenter::euroLabel($listing->euro_standard),
                                __('messages.registration_type') => ListingPresenter::registrationLabel($listing->registration_type),
                                __('messages.condition') => $listing->condition === 'new' ? __('messages.condition_new') : ($listing->condition === 'used' ? __('messages.condition_used') : null),
                                __('messages.vin') => $listing->vin,
                                __('messages.wltp_consumption') => $listing->wltp_consumption ? $listing->wltp_consumption.' l/100km' : null,
                                __('messages.battery_capacity') => $listing->battery_capacity_kwh ? $listing->battery_capacity_kwh.' kWh' : null,
                                __('messages.warranty') => $listing->warranty_until?->format('d.m.Y'),
                                __('messages.first_registration') => $listing->first_registration_date?->format('d.m.Y'),
                            ] as $label => $value)
                                @if($value)
                                    <div class="flex justify-between gap-4 py-2.5">
                                        <dt class="text-[var(--color-text-muted)]">{{ $label }}</dt>
                                        <dd class="text-right font-medium">{{ $value }}</dd>
                                    </div>
                                @endif
                            @endforeach
                        </dl>
                    </div>
                </div>

                @if($featureCategories->isNotEmpty())
                    <div class="mt-8 border-t border-[var(--color-border)] pt-6">
                        <h3 class="mb-4 text-sm font-medium text-[var(--color-text-muted)]">{{ __('messages.features') }}</h3>
                        <div class="space-y-5">
                            @foreach($featureCategories as $category)
                                <div>
                                    <h4 class="mb-2 text-sm font-medium">{{ $category->name }}</h4>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($category->features as $feature)
                                            <span class="badge bg-[var(--color-surface-3)] text-[var(--color-text)]">{{ $feature->name }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="no-print space-y-8">
                <x-listing-seller-info :listing="$listing" />
                <x-listing-contact-form :listing="$listing" />
            </div>
        </div>

        <div class="no-print order-1 lg:order-2">
            <x-listing-sidebar
                :listing="$listing"
                :is-favorited="$isFavorited"
                :latest-price-change="$latestPriceChange"
                :market-estimate="$marketEstimate"
            />
        </div>
    </div>

    @if($dealerListings->isNotEmpty())
        <section class="no-print mt-12">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ __('messages.more_from_dealer') }}</h2>
                @if($listing->company)
                    <a href="{{ route('company.show', $listing->company) }}" class="text-sm text-brand-600 hover:underline">{{ __('messages.all_listings') }} →</a>
                @endif
            </div>
            <div class="listing-cards-grid">
                @foreach($dealerListings as $item)
                    <x-listing-grid-card :listing="$item" :favorited="in_array($item->id, $favoritedIds)" />
                @endforeach
            </div>
        </section>
    @endif

    @if($similar->isNotEmpty())
        <section class="no-print mt-12">
            <h2 class="mb-4 text-lg font-semibold">{{ __('messages.similar') }}</h2>
            <div class="listing-cards-grid">
                @foreach($similar as $item)
                    <x-listing-grid-card :listing="$item" :favorited="in_array($item->id, $favoritedIds)" />
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection