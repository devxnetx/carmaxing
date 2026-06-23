<?php

namespace App\Services;

use App\Mail\PriceDigestMail;
use App\Models\ListingPriceChange;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class PriceDigestService
{
    /** @return Collection<int, ListingPriceChange> */
    public function changesForDay(?Carbon $day = null): Collection
    {
        $day ??= now()->subDay();
        $start = $day->copy()->startOfDay();
        $end = $day->copy()->endOfDay();

        return ListingPriceChange::query()
            ->whereBetween('created_at', [$start, $end])
            ->with(['listing.brand', 'listing.model.parent'])
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn (ListingPriceChange $change) => $change->listing !== null)
            ->values();
    }

    public function sendDue(): int
    {
        $changes = $this->changesForDay();

        if ($changes->isEmpty()) {
            return 0;
        }

        $sent = 0;

        User::query()
            ->where('subscribe_price_digest', true)
            ->whereNotNull('email')
            ->chunkById(50, function ($users) use ($changes, &$sent) {
                foreach ($users as $user) {
                    Mail::to($user->email)->send(new PriceDigestMail($user, $changes));
                    $sent++;
                }
            });

        return $sent;
    }
}