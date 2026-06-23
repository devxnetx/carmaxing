<?php

namespace App\Support;

use App\Models\Region;
use App\Models\VehicleBrand;
use App\Models\VehicleFeature;
use App\Models\VehicleFeatureCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class CatalogCache
{
    private const TTL_SECONDS = 3600;

    /** @return Collection<int, VehicleBrand> */
    public static function brands(): Collection
    {
        /** @var array<int, array<string, mixed>> $rows */
        $rows = Cache::remember('catalog:brands', self::TTL_SECONDS, fn () => VehicleBrand::query()
            ->orderBy('name')
            ->get()
            ->map(fn (VehicleBrand $brand) => $brand->getAttributes())
            ->all());

        return self::hydrateModels(VehicleBrand::class, $rows);
    }

    /** @return Collection<int, Region> */
    public static function regions(): Collection
    {
        /** @var array<int, array<string, mixed>> $rows */
        $rows = Cache::remember('catalog:regions', self::TTL_SECONDS, fn () => Region::query()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Region $region) => $region->getAttributes())
            ->all());

        return self::hydrateModels(Region::class, $rows);
    }

    /** @return Collection<int, VehicleFeatureCategory> */
    public static function featureCategories(): Collection
    {
        /** @var array<int, array{category: array<string, mixed>, features: array<int, array<string, mixed>>}> $payload */
        $payload = Cache::remember('catalog:feature_categories', self::TTL_SECONDS, fn () => VehicleFeatureCategory::query()
            ->with('features')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (VehicleFeatureCategory $category) => [
                'category' => $category->getAttributes(),
                'features' => $category->features
                    ->map(fn (VehicleFeature $feature) => $feature->getAttributes())
                    ->values()
                    ->all(),
            ])
            ->all());

        return collect($payload)->map(function (array $row) {
            $category = (new VehicleFeatureCategory)->newFromBuilder($row['category']);
            $category->setRelation('features', VehicleFeature::hydrate($row['features']));

            return $category;
        });
    }

    /** @return Collection<int, VehicleBrand> */
    public static function popularBrands(): Collection
    {
        /** @var array<int, array<string, mixed>> $rows */
        $rows = Cache::remember('catalog:popular_brands', self::TTL_SECONDS, fn () => VehicleBrand::query()
            ->where('is_popular', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (VehicleBrand $brand) => $brand->getAttributes())
            ->all());

        return self::hydrateModels(VehicleBrand::class, $rows);
    }

    public static function clear(): void
    {
        foreach (['catalog:brands', 'catalog:regions', 'catalog:feature_categories', 'catalog:popular_brands'] as $key) {
            Cache::forget($key);
        }
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<int, array<string, mixed>>  $rows
     * @return Collection<int, Model>
     */
    private static function hydrateModels(string $modelClass, array $rows): Collection
    {
        return $modelClass::query()->hydrate($rows);
    }
}