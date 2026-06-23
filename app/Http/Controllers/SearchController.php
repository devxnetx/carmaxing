<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\VehicleModel;
use App\Support\CatalogCache;
use App\Services\ListingSearchService;
use App\Services\SearchFilterHelper;
use App\Services\SearchHistoryService;
use App\Support\GeoCatalog;
use App\Support\LocationCatalog;
use App\Support\SearchSeoBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __construct(
        private ListingSearchService $searchService,
        private SearchFilterHelper $filterHelper,
        private SearchHistoryService $searchHistory,
    ) {}

    public function index(Request $request): View
    {
        if ($user = $request->user()) {
            $this->searchHistory->record($user, $request);
        }

        $listings = $this->searchService->search($request);

        $favoritedIds = auth()->check()
            ? auth()->user()->favorites()->pluck('listing_id')->all()
            : [];

        $searchSeo = app(SearchSeoBuilder::class)->fromRequest($request, $listings->total());

        $mapCenter = $this->resolveMapCenter($request);
        $viewMode = match ($request->input('view', 'grid')) {
            'map' => 'map',
            'grid' => 'grid',
            default => 'list',
        };

        return view('search.index', [
            'listings' => $listings,
            'brands' => CatalogCache::brands(),
            'regions' => CatalogCache::regions(),
            'featureCategories' => CatalogCache::featureCategories(),
            'filters' => $this->filterHelper->normalizeFilters($request->all()),
            'extendedOpen' => $this->filterHelper->shouldOpenExtendedSearch($request),
            'favoritedIds' => $favoritedIds,
            'countries' => LocationCatalog::countriesForLocale(),
            'searchSeo' => $searchSeo,
            'mapCenter' => $mapCenter,
            'mapMarkers' => $viewMode === 'map' ? $this->searchService->mapMarkers($request) : collect(),
            'viewMode' => $viewMode,
        ]);
    }

    public function mapMarkers(Request $request): JsonResponse
    {
        return response()->json([
            'markers' => $this->searchService->mapMarkers($request),
        ]);
    }

    /** @return array{lat: float, lng: float, zoom: int} */
    private function resolveMapCenter(Request $request): array
    {
        if ($request->filled('map_lat') && $request->filled('map_lng')) {
            return [
                'lat' => (float) $request->input('map_lat'),
                'lng' => (float) $request->input('map_lng'),
                'zoom' => 10,
            ];
        }

        if ($regionId = $request->integer('region_id')) {
            $region = Region::query()->find($regionId);
            $coords = GeoCatalog::coordinatesForRegion($region);

            if ($coords) {
                return ['lat' => $coords['lat'], 'lng' => $coords['lng'], 'zoom' => 9];
            }
        }

        return ['lat' => 42.6977, 'lng' => 23.3219, 'zoom' => 8];
    }

    public function cities(Region $region): JsonResponse
    {
        return response()->json([
            'cities' => LocationCatalog::citiesForRegion($region),
        ]);
    }

    public function models(VehicleBrand $brand): JsonResponse
    {
        $all = $brand->models()->orderBy('sort_order')->orderBy('name')->get();

        $attachChildren = function (?int $parentId) use ($all, &$attachChildren) {
            return $all
                ->where('parent_id', $parentId)
                ->values()
                ->map(function (VehicleModel $model) use (&$attachChildren) {
                    $model->setRelation('children', $attachChildren($model->id));

                    return $model;
                });
        };

        $series = $attachChildren(null)
            ->filter(fn (VehicleModel $model) => $model->isSeries())
            ->values();

        $flatModels = $all
            ->filter(fn (VehicleModel $model) => $model->type === 'model' && $model->parent_id === null)
            ->values();

        return response()->json([
            'brand' => $brand,
            'series' => $series,
            'flat_models' => $flatModels,
        ]);
    }

    public function modelTree(VehicleBrand $brand): JsonResponse
    {
        return response()->json(
            VehicleModel::query()
                ->where('brand_id', $brand->id)
                ->whereNull('parent_id')
                ->with('children')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
        );
    }
}