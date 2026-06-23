<?php

namespace App\Http\Controllers;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Support\CatalogCache;
use App\Services\ListingPersistenceService;
use App\Services\MarketValueService;
use App\Services\RecentlyViewedService;
use App\Support\LocationCatalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ListingController extends Controller
{
    public function __construct(
        private RecentlyViewedService $recentlyViewed,
        private MarketValueService $marketValue,
        private ListingPersistenceService $listingPersistence,
    ) {}

    public function show(Request $request, Listing $listing): View
    {
        abort_unless($listing->status === ListingStatus::Published || auth()->id() === $listing->user_id, 404);

        if ($listing->status === ListingStatus::Published && auth()->id() !== $listing->user_id) {
            $listing->increment('views_count');
            $this->recentlyViewed->record($listing, $request);
        }

        $listing->load([
            'brand',
            'model.parent',
            'region',
            'images',
            'features.category',
            'user',
            'priceChanges',
            'company' => fn ($query) => $query
                ->with('region')
                ->withCount([
                    'listings as listings_count' => fn ($q) => $q->where('status', ListingStatus::Published),
                ]),
        ]);

        $featureCategories = $listing->features
            ->groupBy(fn ($feature) => $feature->category_id)
            ->map(function ($features) {
                $category = $features->first()->category;

                return (object) [
                    'name' => $category->name,
                    'sort_order' => $category->sort_order,
                    'features' => $features->sortBy('sort_order')->values(),
                ];
            })
            ->sortBy('sort_order')
            ->values();

        $dealerListings = $listing->company_id
            ? Listing::query()
                ->published()
                ->where('company_id', $listing->company_id)
                ->where('id', '!=', $listing->id)
                ->with(['brand', 'model.parent', 'images', 'region', 'features', 'company'])
                ->latest('published_at')
                ->limit(4)
                ->get()
            : collect();

        $similar = Listing::query()
            ->published()
            ->where('brand_id', $listing->brand_id)
            ->where('id', '!=', $listing->id)
            ->with(['brand', 'model.parent', 'images', 'region', 'features', 'company'])
            ->limit(4)
            ->get();

        $isFavorited = auth()->check() && auth()->user()->hasFavorited($listing->id);
        $favoritedIds = auth()->check()
            ? auth()->user()->favorites()->pluck('listing_id')->all()
            : [];

        $marketEstimate = $this->marketValue->estimate($listing);
        $latestPriceChange = $listing->priceChanges->first();

        return view('listings.show', compact(
            'listing',
            'similar',
            'dealerListings',
            'featureCategories',
            'isFavorited',
            'favoritedIds',
            'marketEstimate',
            'latestPriceChange',
        ));
    }

    public function create(): View
    {
        $this->authorizeListing();

        return view('listings.form', [
            'listing' => new Listing,
            'brands' => CatalogCache::brands(),
            'regions' => CatalogCache::regions(),
            'countries' => LocationCatalog::countriesForLocale(),
            'featureCategories' => CatalogCache::featureCategories(),
        ]);
    }

    public function edit(Listing $listing): View
    {
        abort_unless(auth()->id() === $listing->user_id, 403);

        $listing->load(['features', 'images']);

        return view('listings.form', [
            'listing' => $listing,
            'brands' => CatalogCache::brands(),
            'regions' => CatalogCache::regions(),
            'countries' => LocationCatalog::countriesForLocale(),
            'featureCategories' => CatalogCache::featureCategories(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeListing();

        $listing = $this->listingPersistence->persist(new Listing, $request, $request->user());
        $listing->publish();

        return redirect()->route('listings.show', $listing)->with('success', __('messages.listing_published'));
    }

    public function update(Request $request, Listing $listing)
    {
        abort_unless(auth()->id() === $listing->user_id, 403);

        $this->listingPersistence->persist($listing, $request, $request->user());

        return redirect()->route('listings.show', $listing)->with('success', __('messages.listing_updated'));
    }

    public function archive(Listing $listing)
    {
        abort_unless(auth()->id() === $listing->user_id, 403);
        $listing->archive();

        return redirect()->route('dashboard')->with('success', __('messages.listing_archived'));
    }

    private function authorizeListing(): void
    {
        abort_unless(auth()->check(), 403);
    }
}