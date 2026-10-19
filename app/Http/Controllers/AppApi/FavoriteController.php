<?php

namespace App\Http\Controllers\AppApi;

use App\Http\Controllers\Controller;
use App\Models\FavoriteListing;
use App\Models\Listing;
use App\Services\ListingSearchService;
use App\Support\AppApi\ListingPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function __construct(
        private ListingSearchService $search,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 50);

        $page = FavoriteListing::query()
            ->where('user_id', $request->user()->id)
            ->whereHas('listing', fn ($query) => $query->published())
            ->with(['listing' => fn ($query) => $query->with($this->search->gridEagerLoads())])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $listings = $page->getCollection()
            ->map(fn (FavoriteListing $favorite) => $favorite->listing)
            ->filter()
            ->values();

        $page->setCollection($listings);

        return response()->json(ListingPayload::page(
            $page,
            $listings->pluck('id')->map(fn ($id) => (int) $id)->all(),
        ));
    }

    public function toggle(Request $request, string $id): JsonResponse
    {
        abort_unless(ctype_digit($id), 404);

        $listing = Listing::query()->published()->findOrFail($id);

        $existing = FavoriteListing::query()
            ->where('user_id', $request->user()->id)
            ->where('listing_id', $listing->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $favorited = false;
        } else {
            FavoriteListing::query()->create([
                'user_id' => $request->user()->id,
                'listing_id' => $listing->id,
            ]);
            $favorited = true;
        }

        return response()->json(['favorited' => $favorited]);
    }
}
