<?php

namespace App\Support\AppApi;

use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use App\Services\MarketValueService;
use App\Support\HtmlToPlainText;
use App\Support\ListingPresenter;
use Illuminate\Pagination\LengthAwarePaginator;

class ListingPayload
{
    /** @param  list<int>  $favoritedIds */
    public static function page(LengthAwarePaginator $page, array $favoritedIds): array
    {
        $favorited = array_fill_keys($favoritedIds, true);

        return [
            'data' => $page->getCollection()
                ->map(fn (Listing $listing) => self::card($listing, isset($favorited[$listing->id])))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ];
    }

    public static function card(Listing $listing, bool $favorited): array
    {
        return [
            'id' => $listing->id,
            'title' => $listing->composeDisplayTitle(),
            'year' => $listing->year,
            'price' => $listing->price_on_request ? null : (int) $listing->price,
            'currency' => $listing->currency,
            'price_bgn' => $listing->price_on_request ? null : $listing->priceInBgn(),
            'price_on_request' => $listing->price_on_request,
            'price_negotiable' => $listing->price_negotiable,
            'mileage' => $listing->mileage,
            'fuel_type' => $listing->fuel_type,
            'fuel_label' => ListingPresenter::fuelLabel($listing->fuel_type),
            'transmission' => $listing->transmission,
            'transmission_label' => ListingPresenter::transmissionLabel($listing->transmission),
            'location' => $listing->locationLabel(),
            'city' => $listing->city,
            'brand' => [
                'id' => $listing->brand_id,
                'name' => $listing->brand?->name,
            ],
            'model' => [
                'id' => $listing->model_id,
                'name' => $listing->model?->name,
            ],
            'thumbnail_url' => PublicUrl::storage($listing->images->first()?->url('medium')),
            'is_new' => $listing->isNewAd(),
            'favorited' => $favorited,
            'published_at' => $listing->published_at?->toIso8601String(),
        ];
    }

    /** @param  list<array<string, mixed>>  $similar
     * @param  list<array<string, mixed>>  $dealerListings
     */
    public static function detail(Listing $listing, bool $favorited, array $similar = [], array $dealerListings = []): array
    {
        $listing->loadMissing(['features.category', 'company', 'user', 'images', 'priceChanges']);

        return [
            ...self::card($listing, $favorited),
            'ad_name' => $listing->ad_name,
            'vehicle_name' => $listing->vehicleName(),
            'description' => HtmlToPlainText::convert($listing->description),
            'mileage_unit' => $listing->mileage_unit,
            'engine_power_hp' => $listing->engine_power_hp,
            'engine_displacement_cc' => $listing->engine_displacement_cc,
            'drivetrain' => $listing->drivetrain,
            'drivetrain_label' => ListingPresenter::drivetrainLabel($listing->drivetrain),
            'body_type' => $listing->body_type,
            'body_label' => ListingPresenter::bodyLabel($listing->body_type),
            'color_exterior' => $listing->color_exterior,
            'color_interior' => $listing->color_interior,
            'doors' => $listing->doors,
            'seats' => $listing->seats,
            'euro_standard' => $listing->euro_standard,
            'euro_label' => ListingPresenter::euroLabel($listing->euro_standard),
            'registration_type' => $listing->registration_type,
            'registration_label' => ListingPresenter::registrationLabel($listing->registration_type),
            'condition' => $listing->condition,
            'condition_label' => match ($listing->condition) {
                'new' => __('messages.condition_new'),
                'used' => __('messages.condition_used'),
                default => null,
            },
            'vin' => $listing->vin,
            'has_vin' => $listing->has_vin,
            'wltp_consumption' => $listing->wltp_consumption,
            'battery_capacity_kwh' => $listing->battery_capacity_kwh,
            'warranty_until' => $listing->warranty_until?->toDateString(),
            'first_registration_date' => $listing->first_registration_date?->toDateString(),
            'images' => $listing->images->map(fn (ListingImage $image) => [
                'id' => $image->id,
                'thumb_url' => PublicUrl::storage($image->url('thumb')),
                'medium_url' => PublicUrl::storage($image->url('medium')),
                'large_url' => PublicUrl::storage($image->url('large')),
                'width' => $image->width,
                'height' => $image->height,
            ])->values()->all(),
            'has_video' => (bool) $listing->has_video,
            'has_vr360' => (bool) $listing->has_vr360,
            'views_count' => (int) $listing->views_count,
            'ad_number' => $listing->displayAdNumber(),
            'updated_at' => $listing->updated_at?->toIso8601String(),
            'price_history' => $listing->priceChanges->map(fn ($change) => [
                'old_price' => (int) $change->old_price,
                'new_price' => (int) $change->new_price,
                'changed_at' => $change->created_at?->toIso8601String(),
            ])->values()->all(),
            'market_estimate' => app(MarketValueService::class)->estimate($listing),
            'leasing' => $listing->hasFixedPrice() ? [
                'max_months' => (int) config('leasing.max_months'),
                'min_months' => (int) config('leasing.min_months'),
                'default_down_payment_percent' => (int) config('leasing.default_down_payment_percent'),
                'eur_to_bgn' => (float) config('leasing.eur_to_bgn'),
                'banks' => array_values(config('leasing.banks')),
            ] : null,
            'features' => $listing->features->map(fn ($feature) => [
                'id' => $feature->id,
                'name' => $feature->name,
                'category' => $feature->category?->name,
            ])->values()->all(),
            'seller' => [
                'type' => $listing->company_id ? 'company' : 'private',
                'name' => $listing->company?->name,
                'phone' => $listing->contactPhone(),
                'logo_url' => PublicUrl::storage($listing->company?->logoUrl()),
                'listings_count' => $listing->company?->listings_count,
                'city' => $listing->company?->city,
            ],
            'web_url' => PublicUrl::page(route('listings.show', $listing)),
            'similar' => $similar,
            'dealer_listings' => $dealerListings,
        ];
    }

    public static function user(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar_url' => PublicUrl::storage($user->avatarUrl()),
            'account_type' => $user->account_type?->value,
            'locale' => $user->locale,
        ];
    }
}
