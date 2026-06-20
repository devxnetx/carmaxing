<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Region;
use App\Models\Tender;
use App\Models\VehicleBrand;
use App\Models\VehicleFeatureCategory;
use App\Services\RecentlyViewedService;
use App\Support\LocationCatalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private RecentlyViewedService $recentlyViewed,
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
            'allBrands' => VehicleBrand::query()->orderBy('name')->get(),
            'regions' => Region::query()->orderBy('sort_order')->get(),
            'featureCategories' => VehicleFeatureCategory::query()->with('features')->orderBy('sort_order')->get(),
            'featuredListings' => Listing::query()->published()->with(['brand', 'model.parent', 'images', 'company', 'region', 'features'])->latest('published_at')->limit(12)->get(),
            'stats' => [
                'total' => Listing::query()->published()->count(),
            ],
            'favoritedIds' => $favoritedIds,
            'countries' => LocationCatalog::countriesForLocale(),
            'recentlyViewed' => $this->recentlyViewed->listings($request),
        ]);
    }
}