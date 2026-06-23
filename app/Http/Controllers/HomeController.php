<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Tender;
use App\Enums\SearchScope;
use App\Services\CatalogCountService;
use App\Services\NewestListingsService;
use App\Services\RecentlyViewedService;
use App\Services\SearchFilterHelper;
use App\Support\CatalogCache;
use App\Support\LocationCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private RecentlyViewedService $recentlyViewed,
        private CatalogCountService $catalogCounts,
        private SearchFilterHelper $filterHelper,
        private NewestListingsService $newestListings,
    ) {}

    public function index(Request $request): View
    {
        $scope = SearchScope::fromRequest($request->input('scope'));

        $favoritedIds = auth()->check()
            ? auth()->user()->favorites()->pluck('listing_id')->all()
            : [];

        return view('home.index', [
            'expiringTenders' => Tender::query()
                ->active()
                ->where('bid_count', '>', 0)
                ->with(['brand', 'model.parent', 'images', 'region', 'bids'])
                ->orderBy('ends_at')
                ->limit(6)
                ->get(),
            'allBrands' => CatalogCache::brands(),
            'searchScope' => $scope,
            'brandCounts' => $this->catalogCounts->brandCounts($scope),
            'regionCounts' => $this->catalogCounts->regionCounts(),
            'filters' => $this->filterHelper->normalizeFilters($request->all()),
            'regions' => CatalogCache::regions(),
            'featureCategories' => CatalogCache::featureCategories(),
            'newestListings' => $this->newestListings->preview(12),
            'stats' => [
                'total' => Cache::remember('stats:published_listings', 300, fn () => Listing::query()->published()->count()),
            ],
            'favoritedIds' => $favoritedIds,
            'countries' => LocationCatalog::countriesForLocale(),
            'recentlyViewed' => $this->recentlyViewed->listings($request),
        ]);
    }
}