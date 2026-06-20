<?php

namespace App\Http\Controllers;

use App\Models\FavoriteListing;
use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function index(Request $request): View
    {
        $listings = Listing::query()
            ->published()
            ->whereIn('id', $request->user()->favorites()->pluck('listing_id'))
            ->with(['brand', 'model.parent', 'images', 'region', 'features', 'company'])
            ->latest('published_at')
            ->paginate(12);

        return view('favorites.index', compact('listings'));
    }

    public function toggle(Request $request, Listing $listing): JsonResponse|RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->guest(route('login'));
        }

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

        if ($request->expectsJson()) {
            return response()->json(['favorited' => $favorited]);
        }

        return back();
    }
}