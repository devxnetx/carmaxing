<?php

namespace App\Http\Controllers;

use App\Enums\ListingStatus;
use App\Models\Listing;
use Illuminate\Http\Response;

class ListingPhoneClickController extends Controller
{
    public function store(Listing $listing): Response
    {
        abort_unless($listing->status === ListingStatus::Published, 404);

        $listing->increment('phone_clicks_count');

        return response()->noContent();
    }
}