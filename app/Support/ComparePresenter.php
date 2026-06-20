<?php

namespace App\Support;

use App\Models\Listing;
use Illuminate\Support\Collection;

class ComparePresenter
{
    /** @return list<array{label: string, rows: list<array{label: string, value: callable}>}> */
    public static function specSections(): array
    {
        return [
            [
                'label' => __('messages.basic_data'),
                'rows' => [
                    ['label' => __('messages.price_from'), 'value' => fn (Listing $l) => self::priceLabel($l)],
                    ['label' => __('messages.brand'), 'value' => fn (Listing $l) => $l->brand->name],
                    ['label' => __('messages.model'), 'value' => fn (Listing $l) => $l->model->name],
                    ['label' => __('messages.year'), 'value' => fn (Listing $l) => $l->month ? sprintf('%02d/%d', $l->month, $l->year) : (string) $l->year],
                    ['label' => __('messages.mileage'), 'value' => fn (Listing $l) => $l->mileage ? number_format($l->mileage).' '.__('messages.km') : null],
                    ['label' => __('messages.fuel_type'), 'value' => fn (Listing $l) => ListingPresenter::fuelLabel($l->fuel_type)],
                    ['label' => __('messages.power'), 'value' => fn (Listing $l) => $l->engine_power_hp ? $l->engine_power_hp.' '.__('messages.hp') : null],
                    ['label' => __('messages.displacement'), 'value' => fn (Listing $l) => $l->engine_displacement_cc ? $l->engine_displacement_cc.' '.__('messages.cc') : null],
                    ['label' => __('messages.transmission'), 'value' => fn (Listing $l) => ListingPresenter::transmissionLabel($l->transmission)],
                    ['label' => __('messages.drivetrain'), 'value' => fn (Listing $l) => ListingPresenter::drivetrainLabel($l->drivetrain)],
                    ['label' => __('messages.body_type'), 'value' => fn (Listing $l) => ListingPresenter::bodyLabel($l->body_type)],
                    ['label' => __('messages.location'), 'value' => fn (Listing $l) => $l->locationLabel()],
                ],
            ],
            [
                'label' => __('messages.specifications'),
                'rows' => [
                    ['label' => __('messages.color'), 'value' => fn (Listing $l) => $l->color_exterior],
                    ['label' => __('messages.interior'), 'value' => fn (Listing $l) => $l->color_interior],
                    ['label' => __('messages.doors'), 'value' => fn (Listing $l) => $l->doors ? (string) $l->doors : null],
                    ['label' => __('messages.seats'), 'value' => fn (Listing $l) => $l->seats ? (string) $l->seats : null],
                    ['label' => __('messages.euro_standard'), 'value' => fn (Listing $l) => ListingPresenter::euroLabel($l->euro_standard)],
                    ['label' => __('messages.registration_type'), 'value' => fn (Listing $l) => ListingPresenter::registrationLabel($l->registration_type)],
                    ['label' => __('messages.condition'), 'value' => fn (Listing $l) => match ($l->condition) {
                        'new' => __('messages.condition_new'),
                        'used' => __('messages.condition_used'),
                        default => null,
                    }],
                    ['label' => __('messages.vin'), 'value' => fn (Listing $l) => $l->vin],
                    ['label' => __('messages.wltp_consumption'), 'value' => fn (Listing $l) => $l->wltp_consumption ? $l->wltp_consumption.' l/100km' : null],
                    ['label' => __('messages.battery_capacity'), 'value' => fn (Listing $l) => $l->battery_capacity_kwh ? $l->battery_capacity_kwh.' kWh' : null],
                    ['label' => __('messages.warranty'), 'value' => fn (Listing $l) => $l->warranty_until?->format('d.m.Y')],
                    ['label' => __('messages.first_registration'), 'value' => fn (Listing $l) => $l->first_registration_date?->format('d.m.Y')],
                    ['label' => __('messages.seller_info'), 'value' => fn (Listing $l) => $l->company?->name ?? __('messages.private_seller')],
                ],
            ],
        ];
    }

    /**
     * @param  Collection<int, Listing>  $listings
     * @return list<array{name: string, sort_order: int, features: list<array{name: string, presence: array<int, bool>}>}>
     */
    public static function featureSections(Collection $listings): array
    {
        if ($listings->isEmpty()) {
            return [];
        }

        $features = $listings
            ->flatMap(fn (Listing $listing) => $listing->features)
            ->unique('id')
            ->sortBy([
                fn ($feature) => $feature->category?->sort_order ?? 999,
                fn ($feature) => $feature->sort_order,
                fn ($feature) => $feature->name,
            ])
            ->values();

        return $features
            ->groupBy(fn ($feature) => $feature->category_id)
            ->map(function (Collection $categoryFeatures) use ($listings) {
                $category = $categoryFeatures->first()->category;

                return [
                    'name' => $category?->name ?? __('messages.features'),
                    'sort_order' => $category?->sort_order ?? 999,
                    'features' => $categoryFeatures->map(function ($feature) use ($listings) {
                        $presence = [];
                        foreach ($listings as $listing) {
                            $presence[$listing->id] = $listing->features->contains('id', $feature->id);
                        }

                        return [
                            'name' => $feature->name,
                            'presence' => $presence,
                        ];
                    })->values()->all(),
                ];
            })
            ->sortBy('sort_order')
            ->values()
            ->all();
    }

    private static function priceLabel(Listing $listing): string
    {
        if ($listing->price_on_request) {
            return __('messages.price_on_request');
        }

        return number_format($listing->price).' '.__('messages.eur')
            .' / '.number_format($listing->priceInBgn()).' '.__('messages.bgn');
    }
}