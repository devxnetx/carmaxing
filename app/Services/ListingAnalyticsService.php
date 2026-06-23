<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Support\Collection;

class ListingAnalyticsService
{
    /** @return array{views: int, favorites: int, inquiries: int, phone_clicks: int} */
    public function statsFor(Listing $listing): array
    {
        return [
            'views' => (int) $listing->views_count,
            'favorites' => $listing->favorites()->count(),
            'inquiries' => (int) $listing->inquiries_count,
            'phone_clicks' => (int) $listing->phone_clicks_count,
        ];
    }

    /** @return Collection<int, array{listing: Listing, stats: array}> */
    public function statsForUser(User $user, string $tab = 'active'): Collection
    {
        $query = $user->listings()
            ->with(['brand', 'model.parent', 'images'])
            ->withCount('favorites')
            ->latest();

        if ($tab === 'archived') {
            $query->whereIn('status', [ListingStatus::Archived, ListingStatus::Sold]);
        } else {
            $query->whereNotIn('status', [ListingStatus::Archived, ListingStatus::Sold]);
        }

        return $query->get()
            ->map(fn (Listing $listing) => [
                'listing' => $listing,
                'stats' => [
                    'views' => (int) $listing->views_count,
                    'favorites' => (int) $listing->favorites_count,
                    'inquiries' => (int) $listing->inquiries_count,
                    'phone_clicks' => (int) $listing->phone_clicks_count,
                ],
            ]);
    }
}