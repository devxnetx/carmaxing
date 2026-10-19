<?php

namespace App\Http\Controllers\AppApi;

use App\Http\Controllers\Controller;
use App\Models\VehicleBrand;
use App\Services\CatalogCountService;
use App\Support\CatalogCache;
use App\Support\ListingPresenter;
use App\Support\LocationCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CatalogController extends Controller
{
    public function __construct(private CatalogCountService $counts) {}

    public function counts(Request $request): JsonResponse
    {
        $place = (string) $request->input('place', 'all');
        $regionId = $request->filled('region_id') ? $request->integer('region_id') : null;

        return response()->json($this->counts->searchCounts($place, $regionId));
    }

    public function show(): JsonResponse
    {
        $payload = Cache::remember(
            'app-api:catalog:'.app()->getLocale(),
            3600,
            fn () => $this->payload(),
        );

        return response()->json($payload);
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        $brands = VehicleBrand::query()
            ->with(['models' => fn ($query) => $query->orderBy('sort_order')->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->map(fn (VehicleBrand $brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
                'slug' => $brand->slug,
                'models' => $brand->models->map(fn ($model) => [
                    'id' => $model->id,
                    'name' => $model->name,
                    'parent_id' => $model->parent_id,
                    'type' => $model->type,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        $regionCounts = $this->counts->regionCounts();
        $regions = CatalogCache::regions()
            ->map(function ($region) use ($regionCounts) {
                $counts = collect($this->counts->citiesWithCounts($region))->keyBy('name');

                return [
                    'id' => $region->id,
                    'name' => $region->name,
                    'slug' => $region->slug,
                    'count' => (int) ($regionCounts[(int) $region->id] ?? 0),
                    'cities' => collect(LocationCatalog::citiesForRegion($region))
                        ->map(fn (string $name) => [
                            'name' => $name,
                            'count' => (int) data_get($counts->get($name), 'count', 0),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();

        $features = CatalogCache::featureCategories()
            ->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'features' => $category->features->map(fn ($feature) => [
                    'id' => $feature->id,
                    'name' => $feature->name,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        return [
            'brands' => $brands,
            'regions' => $regions,
            'bulgaria_count' => $this->counts->publishedBulgariaCount(),
            'fuel_types' => $this->options(
                ['petrol', 'diesel', 'lpg', 'cng', 'electric', 'hybrid', 'plug-in-hybrid'],
                fn (string $code) => ListingPresenter::fuelLabel($code),
            ),
            'transmissions' => $this->options(
                ['manual', 'automatic', 'semi-automatic'],
                fn (string $code) => ListingPresenter::transmissionLabel($code),
            ),
            'drivetrains' => $this->options(
                ['fwd', 'rwd', 'awd', '4x4'],
                fn (string $code) => ListingPresenter::drivetrainLabel($code),
            ),
            'body_types' => $this->options(
                ['sedan', 'hatchback', 'wagon', 'suv', 'coupe', 'cabrio', 'van', 'pickup'],
                fn (string $code) => ListingPresenter::bodyLabel($code),
            ),
            'features' => $features,
        ];
    }

    /**
     * @param  list<string>  $codes
     * @param  callable(string): ?string  $label
     * @return list<array{code: string, label: ?string}>
     */
    private function options(array $codes, callable $label): array
    {
        return array_map(fn (string $code) => [
            'code' => $code,
            'label' => $label($code),
        ], $codes);
    }
}
