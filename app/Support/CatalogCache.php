<?php

namespace App\Support;

use App\Models\Region;
use App\Models\VehicleBrand;
use App\Models\VehicleFeatureCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class CatalogCache
{
    private const TTL_SECONDS = 3600;

    /** @return Collection<int, VehicleBrand> */
    public static function brands(): Collection
    {
        return Cache::remember('catalog:brands', self::TTL_SECONDS, fn () => VehicleBrand::query()
            ->orderBy('name')
            ->get());
    }

    /** @return Collection<int, Region> */
    public static function regions(): Collection
    {
        return Cache::remember('catalog:regions', self::TTL_SECONDS, fn () => Region::query()
            ->orderBy('sort_order')
            ->get());
    }

    /** @return Collection<int, VehicleFeatureCategory> */
    public static function featureCategories(): Collection
    {
        return Cache::remember('catalog:feature_categories', self::TTL_SECONDS, fn () => VehicleFeatureCategory::query()
            ->with('features')
            ->orderBy('sort_order')
            ->get());
    }

    /** @return Collection<int, VehicleBrand> */
    public static function popularBrands(): Collection
    {
        return Cache::remember('catalog:popular_brands', self::TTL_SECONDS, fn () => VehicleBrand::query()
            ->where('is_popular', true)
            ->orderBy('sort_order')
            ->get());
    }

    public static function clear(): void
    {
        foreach (['catalog:brands', 'catalog:regions', 'catalog:feature_categories', 'catalog:popular_brands'] as $key) {
            Cache::forget($key);
        }
    }
}