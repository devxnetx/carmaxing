<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ListingStatus;
use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Region;
use App\Models\VehicleBrand;
use App\Models\VehicleFeatureCategory;
use App\Services\ListingPersistenceService;
use App\Support\LocationCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ListingController extends Controller
{
    public function __construct(
        private ListingPersistenceService $listingPersistence,
    ) {}

    public function index(Request $request): View
    {
        $query = Listing::query()
            ->with([
                'brand',
                'model.parent',
                'user',
                'company',
                'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order'),
            ])
            ->latest('updated_at');

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('ad_name', 'like', "%{$search}%")
                    ->orWhere('car_variant', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        $listings = $query->paginate(20)->withQueryString();

        return view('admin.listings.index', compact('listings'));
    }

    public function edit(Listing $listing): View
    {
        $listing->load(['features', 'images', 'user', 'company']);

        return view('admin.listings.edit', [
            'listing' => $listing,
            'brands' => VehicleBrand::query()->orderBy('name')->get(),
            'regions' => Region::query()->orderBy('sort_order')->get(),
            'countries' => LocationCatalog::countriesForLocale(),
            'featureCategories' => VehicleFeatureCategory::query()->with('features')->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, Listing $listing): RedirectResponse
    {
        $this->listingPersistence->persist($listing, $request);

        return redirect()
            ->route('admin.listings.edit', $listing)
            ->with('success', __('admin.listing_updated'));
    }

    public function archive(Listing $listing): RedirectResponse
    {
        $listing->archive();

        return back()->with('success', __('admin.listing_archived'));
    }

    public function publish(Listing $listing): RedirectResponse
    {
        $listing->publish();

        return back()->with('success', __('admin.listing_published'));
    }

    public function updateStatus(Request $request, Listing $listing): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_column(ListingStatus::cases(), 'value'))],
        ]);

        $status = ListingStatus::from($data['status']);

        $updates = ['status' => $status];

        if ($status === ListingStatus::Published) {
            $updates['published_at'] = $listing->published_at ?? now();
            $updates['archived_at'] = null;
        }

        if ($status === ListingStatus::Archived) {
            $updates['archived_at'] = now();
        }

        $listing->update($updates);

        return back()->with('success', __('admin.listing_status_updated'));
    }
}