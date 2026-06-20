<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Models\Listing;

class MarketValueService
{
    /** @return array{average: ?int, delta: ?int, sample_size: int}|null */
    public function estimate(Listing $listing): ?array
    {
        if ($listing->price_on_request || ! $listing->price) {
            return null;
        }

        $query = Listing::query()
            ->where('status', ListingStatus::Published)
            ->where('id', '!=', $listing->id)
            ->where('brand_id', $listing->brand_id)
            ->where('price_on_request', false)
            ->where('price', '>', 0)
            ->whereBetween('year', [max(1990, $listing->year - 2), $listing->year + 2]);

        if ($listing->model_id) {
            $query->where('model_id', $listing->model_id);
        }

        if ($listing->mileage) {
            $margin = max(30000, (int) ($listing->mileage * 0.35));
            $query->whereBetween('mileage', [
                max(0, $listing->mileage - $margin),
                $listing->mileage + $margin,
            ]);
        }

        $prices = $query->pluck('price');
        $sampleSize = $prices->count();

        if ($sampleSize < 3) {
            return null;
        }

        $average = (int) round($prices->avg());

        return [
            'average' => $average,
            'delta' => $average - $listing->price,
            'sample_size' => $sampleSize,
        ];
    }
}