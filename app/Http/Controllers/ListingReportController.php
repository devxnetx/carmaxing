<?php

namespace App\Http\Controllers;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\ListingReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ListingReportController extends Controller
{
    public function store(Request $request, Listing $listing): RedirectResponse
    {
        abort_unless($listing->status === ListingStatus::Published, 404);

        $data = $request->validate([
            'reason' => ['required', 'in:scam,wrong_info,duplicate,other'],
            'details' => ['nullable', 'string', 'max:2000'],
        ]);

        ListingReport::query()->create([
            'listing_id' => $listing->id,
            'user_id' => $request->user()?->id,
            'reason' => $data['reason'],
            'details' => $data['details'] ?? null,
        ]);

        return back()->with('success', __('messages.report_submitted'));
    }
}