<?php

namespace App\Services;

use App\Mail\FavoriteListingArchivedMail;
use App\Mail\FavoriteListingPriceChangeMail;
use App\Models\Listing;
use App\Models\ListingPriceChange;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class FavoriteNotificationService
{
    public function notifyArchived(Listing $listing): void
    {
        $listing->loadMissing(['brand', 'model.parent']);

        foreach ($this->favoritingUsers($listing) as $user) {
            Mail::to($user->email)->send(new FavoriteListingArchivedMail($listing, $user));
        }
    }

    public function notifyPriceChange(Listing $listing, ListingPriceChange $change): void
    {
        $listing->loadMissing(['brand', 'model.parent']);

        foreach ($this->favoritingUsers($listing) as $user) {
            Mail::to($user->email)->send(new FavoriteListingPriceChangeMail($listing, $change, $user));
        }
    }

    /** @return Collection<int, User> */
    private function favoritingUsers(Listing $listing): Collection
    {
        return User::query()
            ->whereHas('favorites', fn ($query) => $query->where('listing_id', $listing->id))
            ->where('id', '!=', $listing->user_id)
            ->whereNotNull('email')
            ->get();
    }
}