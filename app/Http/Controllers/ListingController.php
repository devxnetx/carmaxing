<?php

namespace App\Http\Controllers;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Support\CatalogCache;
use App\Services\ListingPersistenceService;
use App\Services\ListingShowService;
use App\Services\RecentlyViewedService;
use App\Support\LocationCatalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ListingController extends Controller
{
    public function __construct(
        private RecentlyViewedService $recentlyViewed,
        private ListingShowService $listingShow,
        private ListingPersistenceService $listingPersistence,
    ) {}

    public function show(Request $request, Listing $listing): View
    {
        $isOwner = auth()->id() === $listing->user_id;
        $isPubliclyViewable = $listing->status === ListingStatus::Published
            || $listing->status->isInactive();

        abort_unless($isPubliclyViewable || $isOwner, 404);

        if ($listing->status === ListingStatus::Published && ! $isOwner) {
            $listing->increment('views_count');
            $this->recentlyViewed->record($listing, $request);
        }

        $showData = $this->listingShow->cachedShowData($listing);

        $isFavorited = auth()->check() && auth()->user()->hasFavorited($listing->id);
        $favoritedIds = auth()->check()
            ? auth()->user()->favorites()->pluck('listing_id')->all()
            : [];

        return view('listings.show', [
            ...$showData,
            'isFavorited' => $isFavorited,
            'favoritedIds' => $favoritedIds,
        ]);
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

    public function unarchive(Listing $listing)
    {
        abort_unless(auth()->id() === $listing->user_id, 403);
        abort_unless($listing->status->isInactive(), 403);

        $listing->publish();

        return redirect()
            ->route('dashboard', ['tab' => 'archived'])
            ->with('success', __('messages.listing_unarchived'));
    }

    private function authorizeListing(): void
    {
        abort_unless(auth()->check(), 403);
    }
}