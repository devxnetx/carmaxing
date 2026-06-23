<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Tender;
use App\Services\ListingSearchService;
use App\Services\RecentlyViewedService;
use App\Support\CatalogCache;
use App\Support\LocationCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private RecentlyViewedService $recentlyViewed,
        private ListingSearchService $searchService,
    ) {}

    public function index(Request $request): View
    {
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
            'regions' => CatalogCache::regions(),
            'featureCategories' => CatalogCache::featureCategories(),
            'featuredListings' => Listing::query()
                ->published()
                ->with($this->searchService->gridEagerLoads())
                ->latest('published_at')
                ->limit(12)
                ->get(),
            'stats' => [
                'total' => Cache::remember('stats:published_listings', 300, fn () => Listing::query()->published()->count()),
            ],
            'favoritedIds' => $favoritedIds,
            'countries' => LocationCatalog::countriesForLocale(),
            'recentlyViewed' => $this->recentlyViewed->listings($request),
        ]);
    }
}