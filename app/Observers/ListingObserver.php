<?php

namespace App\Observers;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Services\FavoriteNotificationService;
use App\Services\ListingShowService;

class ListingObserver
{
    public function saved(Listing $listing): void
    {
        app(ListingShowService::class)->forget($listing);
    }

    public function updated(Listing $listing): void
    {
        if (! $listing->wasChanged('status')) {
            return;
        }

        $status = $listing->status;

        if (! $status->isInactive()) {
            return;
        }

        $original = ListingStatus::from($listing->getRawOriginal('status'));

        if ($original->isInactive()) {
            return;
        }

        app(FavoriteNotificationService::class)->notifyArchived($listing);
    }
}