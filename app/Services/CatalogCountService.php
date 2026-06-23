<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Enums\SearchScope;
use App\Enums\TenderStatus;
use App\Models\AuctionLot;
use App\Models\Listing;
use App\Models\Region;
use App\Models\Tender;
use App\Models\User;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Support\LocationCatalog;
use Illuminate\Support\Facades\Cache;

class CatalogCountService
{
    /** @return array<int, int> */
    public function brandCounts(SearchScope $scope): array
    {
        return Cache::remember(
            "catalog:counts:{$scope->value}:brands",
            $this->ttlSeconds(),
            fn () => $this->loadBrandCounts($scope),
        );
    }

    /** @return array<int, int> */
    public function modelCounts(SearchScope $scope): array
    {
        return Cache::remember(
            "catalog:counts:{$scope->value}:models",
            $this->ttlSeconds(),
            fn () => $this->loadModelCounts($scope),
        );
    }

    public function countForBrand(int $brandId, SearchScope $scope): int
    {
        return $this->brandCounts($scope)[$brandId] ?? 0;
    }

    public function countForModel(int $modelId, SearchScope $scope): int
    {
        return $this->modelCounts($scope)[$modelId] ?? 0;
    }

    /** @return array<int, int> */
    public function regionCounts(): array
    {
        return Cache::remember(
            'catalog:counts:listings:regions',
            $this->ttlSeconds(),
            fn () => $this->loadRegionCounts(),
        );
    }

    /**
     * Full city list with counts for a region (cached API payload).
     *
     * @return list<array{name: string, count: int}>
     */
    public function citiesWithCounts(Region $region): array
    {
        return Cache::remember(
            "catalog:response:listings:cities:{$region->id}",
            $this->ttlSeconds(),
            fn () => $this->buildCitiesWithCounts($region),
        );
    }

    /**
     * Full brand models tree with counts (cached API payload).
     *
     * @return array{brand: VehicleBrand, series: mixed, flat_models: mixed}
     */
    public function brandModelsResponse(VehicleBrand $brand, SearchScope $scope): array
    {
        return Cache::remember(
            "catalog:response:{$scope->value}:brand-models:{$brand->id}",
            $this->ttlSeconds(),
            fn () => $this->buildBrandModelsResponse($brand, $scope),
        );
    }

    public function newsSubscriberCount(): int
    {
        return (int) User::query()
            ->where('subscribe_news', true)
            ->whereNotNull('email')
            ->count();
    }

    public function newsNonSubscriberCount(): int
    {
        return (int) User::query()
            ->where('subscribe_news', false)
            ->whereNotNull('email')
            ->count();
    }

    private function ttlSeconds(): int
    {
        $hours = max(1, (int) config('listings.catalog_counts_ttl_hours', 6));

        return $hours * 3600;
    }

    /** @return list<array{name: string, count: int}> */
    private function buildCitiesWithCounts(Region $region): array
    {
        $counts = $this->loadCityCountsForRegion($region->id);

        return collect(LocationCatalog::citiesForRegion($region))
            ->map(fn (string $name) => [
                'name' => $name,
                'count' => $counts[$name] ?? 0,
            ])
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /** @return array{brand: VehicleBrand, series: mixed, flat_models: mixed} */
    private function buildBrandModelsResponse(VehicleBrand $brand, SearchScope $scope): array
    {
        $modelCounts = $this->loadModelCounts($scope);

        $all = $brand->models()->orderBy('sort_order')->orderBy('name')->get();

        $attachChildren = function (?int $parentId) use ($all, &$attachChildren, $modelCounts) {
            return $all
                ->where('parent_id', $parentId)
                ->values()
                ->map(function (VehicleModel $model) use (&$attachChildren, $modelCounts) {
                    $model->setRelation('children', $attachChildren($model->id));
                    $model->setAttribute('count', $this->nodeCount($model, $modelCounts));

                    return $model;
                });
        };

        $series = $attachChildren(null)
            ->filter(fn (VehicleModel $model) => $model->isSeries())
            ->values();

        $flatModels = $all
            ->filter(fn (VehicleModel $model) => $model->type === 'model' && $model->parent_id === null)
            ->map(function (VehicleModel $model) use ($modelCounts) {
                $model->setAttribute('count', $modelCounts[$model->id] ?? 0);

                return $model;
            })
            ->values();

        return [
            'brand' => $brand,
            'series' => $series,
            'flat_models' => $flatModels,
        ];
    }

    /** @param  array<int, int>  $modelCounts */
    private function nodeCount(VehicleModel $model, array $modelCounts): int
    {
        if ($model->relationLoaded('children') && $model->children->isNotEmpty()) {
            return $model->children->sum(fn (VehicleModel $child) => $this->nodeCount($child, $modelCounts));
        }

        return $modelCounts[$model->id] ?? 0;
    }

    /** @return array<int, int> */
    private function loadBrandCounts(SearchScope $scope): array
    {
        $query = match ($scope) {
            SearchScope::Listings => Listing::query()->where('status', ListingStatus::Published),
            SearchScope::Imports => AuctionLot::query(),
            SearchScope::Auctions => Tender::query()
                ->where('status', TenderStatus::Active)
                ->where('ends_at', '>', now()),
        };

        return $query
            ->whereNotNull('brand_id')
            ->selectRaw('brand_id, COUNT(*) as aggregate_count')
            ->groupBy('brand_id')
            ->pluck('aggregate_count', 'brand_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    /** @return array<int, int> */
    private function loadModelCounts(SearchScope $scope): array
    {
        $query = match ($scope) {
            SearchScope::Listings => Listing::query()->where('status', ListingStatus::Published),
            SearchScope::Imports => AuctionLot::query(),
            SearchScope::Auctions => Tender::query()
                ->where('status', TenderStatus::Active)
                ->where('ends_at', '>', now()),
        };

        return $query
            ->whereNotNull('model_id')
            ->selectRaw('model_id, COUNT(*) as aggregate_count')
            ->groupBy('model_id')
            ->pluck('aggregate_count', 'model_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    /** @return array<int, int> */
    private function loadRegionCounts(): array
    {
        return Listing::query()
            ->where('status', ListingStatus::Published)
            ->where(function ($query) {
                $query->whereNull('country_code')
                    ->orWhere('country_code', LocationCatalog::BULGARIA_CODE);
            })
            ->whereNotNull('region_id')
            ->selectRaw('region_id, COUNT(*) as aggregate_count')
            ->groupBy('region_id')
            ->pluck('aggregate_count', 'region_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    /** @return array<string, int> */
    private function loadCityCountsForRegion(int $regionId): array
    {
        return Listing::query()
            ->where('status', ListingStatus::Published)
            ->where('region_id', $regionId)
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->selectRaw('city, COUNT(*) as aggregate_count')
            ->groupBy('city')
            ->pluck('aggregate_count', 'city')
            ->map(fn ($count) => (int) $count)
            ->all();
    }
}