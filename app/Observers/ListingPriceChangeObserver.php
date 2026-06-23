<?php

namespace App\Observers;

use App\Models\ListingPriceChange;
use App\Services\FavoriteNotificationService;
use App\Services\ListingShowService;

class ListingPriceChangeObserver
{
    public function created(ListingPriceChange $change): void
    {
        $change->loadMissing('listing');

        if (! $change->listing) {
            return;
        }

        app(ListingShowService::class)->forget($change->listing);
        app(FavoriteNotificationService::class)->notifyPriceChange($change->listing, $change);
    }
}