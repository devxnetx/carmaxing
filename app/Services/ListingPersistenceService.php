<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\ListingPriceChange;
use App\Models\User;
use App\Support\HtmlToPlainText;
use App\Support\LocationCatalog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ListingPersistenceService
{
    public function __construct(
        private ListingGeoService $listingGeo,
        private ImageProcessor $imageProcessor,
    ) {}

    public function persist(Listing $listing, Request $request, ?User $actingUser = null): Listing
    {
        $data = $request->validate($this->rules($request));

        $data['price_on_request'] = $request->boolean('price_on_request');
        $data['price_negotiable'] = $request->boolean('price_negotiable');

        if ($data['price_on_request']) {
            $data['price'] = 0;
            $data['price_negotiable'] = false;
        }

        $data['has_vin'] = $request->boolean('has_vin');
        $data['has_video'] = $request->boolean('has_video');
        $data['has_vr360'] = $request->boolean('has_vr360');

        $location = LocationCatalog::normalizeListingLocation(
            $data['location_type'],
            isset($data['region_id']) ? (int) $data['region_id'] : null,
            $data['city'] ?? null,
            $data['country_code'] ?? null,
        );

        unset($data['location_type']);
        $data = array_merge($data, $location);
        $data['description'] = HtmlToPlainText::sanitize($data['description'] ?? null);

        $previousPrice = $listing->exists && ! $listing->price_on_request ? (int) $listing->price : null;

        $listing->fill($data);
        $listing->latitude = null;
        $listing->longitude = null;

        if ($actingUser) {
            $listing->user_id = $actingUser->id;
            $listing->company_id = $actingUser->isCompany() ? $actingUser->company?->id : null;
        }

        $listing->save();

        $this->listingGeo->syncCoordinates($listing);

        if ($previousPrice !== null && ! $listing->price_on_request && (int) $listing->price !== $previousPrice) {
            ListingPriceChange::query()->create([
                'listing_id' => $listing->id,
                'old_price' => $previousPrice,
                'new_price' => (int) $listing->price,
            ]);
        }

        $listing->features()->sync($data['features'] ?? []);

        $this->removeListingImages($listing, $request->input('remove_images', []));
        $this->storeListingImages($listing, $request->file('images', []));

        return $listing;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(Request $request): array
    {
        return [
            'brand_id' => ['required', 'exists:vehicle_brands,id'],
            'model_id' => ['required', 'exists:vehicle_models,id'],
            'car_variant' => ['nullable', 'string', 'max:120'],
            'ad_name' => ['nullable', 'string', 'max:160'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price_on_request' => ['boolean'],
            'price' => [Rule::requiredIf(! $request->boolean('price_on_request')), 'integer', 'min:0'],
            'currency' => ['required', 'in:EUR,BGN,USD'],
            'price_negotiable' => ['boolean'],
            'year' => ['required', 'integer', 'min:1950', 'max:'.(date('Y') + 1)],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'fuel_type' => ['nullable', 'string'],
            'engine_power_hp' => ['nullable', 'integer'],
            'engine_displacement_cc' => ['nullable', 'integer'],
            'transmission' => ['nullable', 'string'],
            'drivetrain' => ['nullable', 'string'],
            'body_type' => ['nullable', 'string'],
            'color_exterior' => ['nullable', 'string'],
            'color_interior' => ['nullable', 'string'],
            'doors' => ['nullable', 'integer'],
            'seats' => ['nullable', 'integer'],
            'euro_standard' => ['nullable', 'string'],
            'registration_type' => ['nullable', 'string'],
            'vin' => ['nullable', 'string', 'max:17'],
            'location_type' => ['required', 'in:bg,abroad'],
            'region_id' => [Rule::requiredIf($request->input('location_type') === 'bg'), 'nullable', 'exists:regions,id'],
            'city' => [Rule::requiredIf($request->input('location_type') === 'bg'), 'nullable', 'string', 'max:100'],
            'country_code' => [
                Rule::requiredIf($request->input('location_type') === 'abroad'),
                'nullable',
                'string',
                Rule::in(LocationCatalog::abroadCountryCodes()),
            ],
            'condition' => ['nullable', 'in:new,used'],
            'warranty_until' => ['nullable', 'date'],
            'first_registration_date' => ['nullable', 'date'],
            'wltp_consumption' => ['nullable', 'numeric'],
            'battery_capacity_kwh' => ['nullable', 'numeric'],
            'has_vin' => ['boolean'],
            'has_video' => ['boolean'],
            'has_vr360' => ['boolean'],
            'features' => ['nullable', 'array'],
            'features.*' => ['exists:vehicle_features,id'],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['image', 'max:5120'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer', 'exists:listing_images,id'],
        ];
    }

    private function storeListingImages(Listing $listing, array $files): void
    {
        $sortOrder = (int) $listing->images()->max('sort_order');
        $needsPrimary = $listing->images()->where('is_primary', true)->doesntExist();
        $isFirstNew = true;

        foreach ($files as $file) {
            if (! $file) {
                continue;
            }

            try {
                $processed = $this->imageProcessor->processUpload(
                    $file,
                    "listings/{$listing->id}",
                    config('images.listing'),
                );
            } catch (\Throwable) {
                continue;
            }

            $sortOrder++;

            $listing->images()->create([
                ...$processed,
                'sort_order' => $sortOrder,
                'is_primary' => $needsPrimary && $isFirstNew,
            ]);

            $isFirstNew = false;
        }
    }

    private function removeListingImages(Listing $listing, array $imageIds): void
    {
        if ($imageIds === []) {
            return;
        }

        $images = $listing->images()->whereIn('id', $imageIds)->get();

        foreach ($images as $image) {
            $image->deleteFiles();
            $image->delete();
        }

        if ($listing->images()->where('is_primary', true)->doesntExist()) {
            $first = $listing->images()->orderBy('sort_order')->first();
            $first?->update(['is_primary' => true]);
        }
    }
}