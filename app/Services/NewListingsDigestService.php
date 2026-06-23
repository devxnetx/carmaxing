<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Mail\NewListingsDigestMail;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class NewListingsDigestService
{
    /** @return Collection<int, Listing> */
    public function listingsForDay(?Carbon $day = null): Collection
    {
        $day ??= now()->subDay();
        $start = $day->copy()->startOfDay();
        $end = $day->copy()->endOfDay();

        return Listing::query()
            ->where('status', ListingStatus::Published)
            ->whereBetween('published_at', [$start, $end])
            ->with(['brand', 'model.parent', 'images'])
            ->orderByDesc('published_at')
            ->get();
    }

    public function sendDue(): int
    {
        $listings = $this->listingsForDay();

        if ($listings->isEmpty()) {
            return 0;
        }

        $sent = 0;

        User::query()
            ->where('subscribe_new_listings_digest', true)
            ->whereNotNull('email')
            ->chunkById(50, function ($users) use ($listings, &$sent) {
                foreach ($users as $user) {
                    Mail::to($user->email)->send(new NewListingsDigestMail($user, $listings));
                    $sent++;
                }
            });

        return $sent;
    }
}