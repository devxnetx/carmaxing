<?php

namespace App\Http\Controllers;

use App\Services\NewestListingsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewestListingsController extends Controller
{
    public function __construct(
        private NewestListingsService $newestListings,
    ) {}

    public function index(Request $request): View
    {
        $listings = $this->newestListings->paginate($request);

        $favoritedIds = auth()->check()
            ? auth()->user()->favorites()->pluck('listing_id')->all()
            : [];

        return view('listings.newest', [
            'listings' => $listings,
            'favoritedIds' => $favoritedIds,
            'usesRecentWindow' => $this->newestListings->usesRecentWindow(),
            'recentWindowDays' => $this->newestListings->recentWindowDays(),
        ]);
    }
}