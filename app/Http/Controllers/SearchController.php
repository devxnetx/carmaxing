<?php

namespace App\Http\Controllers;

use App\Enums\SearchScope;
use App\Models\Region;
use App\Models\VehicleBrand;
use App\Services\AuctionLotSearchService;
use App\Services\CatalogCountService;
use App\Services\ListingSearchService;
use App\Services\SearchFilterHelper;
use App\Services\SearchHistoryService;
use App\Services\TenderSearchService;
use App\Support\CatalogCache;
use App\Support\LocationCatalog;
use App\Support\SearchSeoBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __construct(
        private ListingSearchService $listingSearch,
        private AuctionLotSearchService $importSearch,
        private TenderSearchService $auctionSearch,
        private SearchFilterHelper $filterHelper,
        private SearchHistoryService $searchHistory,
        private CatalogCountService $catalogCounts,
    ) {}

    public function form(Request $request): View
    {
        $scope = SearchScope::fromRequest($request->input('scope'));

        return view('search.form', $this->catalogViewData($request, $scope, extendedOpen: true));
    }

    public function index(Request $request): View
    {
        return $this->results($request, SearchScope::Listings);
    }

    public function imports(Request $request): View
    {
        return $this->results($request, SearchScope::Imports);
    }

    public function auctions(Request $request): View
    {
        return $this->results($request, SearchScope::Auctions);
    }

    private function results(Request $request, SearchScope $scope): View
    {
        if ($user = $request->user()) {
            $this->searchHistory->record($user, $request);
        }

        $data = $this->catalogViewData($request, $scope, extendedOpen: false);

        return match ($scope) {
            SearchScope::Listings => $this->listingResults($request, $data),
            SearchScope::Imports => $this->importResults($request, $data),
            SearchScope::Auctions => $this->auctionResults($request, $data),
        };
    }

    /** @param  array<string, mixed>  $data */
    private function listingResults(Request $request, array $data): View
    {
        $listings = $this->listingSearch->search($request);

        $favoritedIds = auth()->check()
            ? auth()->user()->favorites()->pluck('listing_id')->all()
            : [];

        $searchSeo = app(SearchSeoBuilder::class)->fromRequest($request, $listings->total());

        $viewMode = $request->input('view', 'grid') === 'list' ? 'list' : 'grid';

        return view('search.index', array_merge($data, [
            'listings' => $listings,
            'favoritedIds' => $favoritedIds,
            'searchSeo' => $searchSeo,
            'viewMode' => $viewMode,
        ]));
    }

    /** @param  array<string, mixed>  $data */
    private function importResults(Request $request, array $data): View
    {
        $lots = $this->importSearch->search($request);

        $seoRequest = $request->duplicate()->merge(['scope' => SearchScope::Imports->value]);
        $searchSeo = app(SearchSeoBuilder::class)->fromRequest($seoRequest, $lots->total());
        $viewMode = $request->input('view', 'grid') === 'list' ? 'list' : 'grid';

        return view('search.imports', array_merge($data, [
            'lots' => $lots,
            'searchSeo' => $searchSeo,
            'viewMode' => $viewMode,
        ]));
    }

    /** @param  array<string, mixed>  $data */
    private function auctionResults(Request $request, array $data): View
    {
        $tenders = $this->auctionSearch->search($request);

        return view('search.auctions', array_merge($data, [
            'tenders' => $tenders,
            'searchHeading' => __('messages.search_scope_auctions'),
            'searchDescription' => __('messages.search_auctions_description', ['count' => $tenders->total()]),
        ]));
    }

    /** @return array<string, mixed> */
    private function catalogViewData(Request $request, SearchScope $scope, bool $extendedOpen): array
    {
        return [
            'scope' => $scope,
            'brands' => CatalogCache::brands(),
            'brandCounts' => $this->catalogCounts->brandCounts($scope),
            'regionCounts' => $scope === SearchScope::Listings ? $this->catalogCounts->regionCounts() : [],
            'regions' => CatalogCache::regions(),
            'featureCategories' => CatalogCache::featureCategories(),
            'countries' => LocationCatalog::countriesForLocale(),
            'filters' => $this->filterHelper->normalizeFilters($request->all()),
            'extendedOpen' => $extendedOpen,
            'correctSearchUrl' => $this->filterHelper->formUrlFromRequest($request, $scope),
            'resultsRoute' => route($scope->resultsRouteName()),
        ];
    }

    public function cities(Region $region): JsonResponse
    {
        return response()->json([
            'cities' => $this->catalogCounts->citiesWithCounts($region),
        ]);
    }

    public function models(Request $request, VehicleBrand $brand): JsonResponse
    {
        $scope = SearchScope::fromRequest($request->input('scope'));

        return response()->json(
            $this->catalogCounts->brandModelsResponse($brand, $scope),
        );
    }

    public function modelTree(Request $request, VehicleBrand $brand): JsonResponse
    {
        $scope = SearchScope::fromRequest($request->input('scope'));
        $payload = $this->catalogCounts->brandModelsResponse($brand, $scope);

        return response()->json($payload['series']);
    }
}