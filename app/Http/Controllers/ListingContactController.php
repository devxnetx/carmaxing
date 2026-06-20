<?php

namespace App\Http\Controllers;

use App\Enums\ListingStatus;
use App\Mail\ListingInquiryMail;
use App\Models\Listing;
use App\Models\ListingInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ListingContactController extends Controller
{
    public function store(Request $request, Listing $listing): RedirectResponse
    {
        abort_unless($listing->status === ListingStatus::Published, 404);
        abort_if($request->user()->id === $listing->user_id, 403);

        $data = $request->validate([
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $sellerEmail = $listing->sellerEmail();

        if (! $sellerEmail) {
            return back()->withErrors(['message' => __('messages.inquiry_no_seller_email')]);
        }

        Mail::to($sellerEmail)->send(new ListingInquiryMail(
            $listing,
            $request->user(),
            $data['message'],
        ));

        ListingInquiry::query()->create([
            'listing_id' => $listing->id,
            'user_id' => $request->user()->id,
            'message' => $data['message'],
            'created_at' => now(),
        ]);

        $listing->increment('inquiries_count');

        return back()->with('inquiry_sent', true);
    }
}