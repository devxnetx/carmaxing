<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\RecentlyViewedListing;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class RecentlyViewedService
{
    public function record(Listing $listing, Request $request): void
    {
        if ($listing->status !== ListingStatus::Published) {
            return;
        }

        $userId = $request->user()?->id;
        $sessionId = $userId ? null : $request->session()->getId();

        $match = RecentlyViewedListing::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(! $userId, fn ($q) => $q->where('session_id', $sessionId))
            ->where('listing_id', $listing->id)
            ->first();

        if ($match) {
            $match->update(['viewed_at' => now()]);

            return;
        }

        RecentlyViewedListing::query()->create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'listing_id' => $listing->id,
            'viewed_at' => now(),
        ]);

        $this->prune($userId, $sessionId);
    }

    /** @return Collection<int, Listing> */
    public function listings(Request $request, int $limit = 8): Collection
    {
        $userId = $request->user()?->id;
        $sessionId = $userId ? null : $request->session()->getId();

        $ids = RecentlyViewedListing::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(! $userId, fn ($q) => $q->where('session_id', $sessionId))
            ->orderByDesc('viewed_at')
            ->limit($limit)
            ->pluck('listing_id');

        if ($ids->isEmpty()) {
            return collect();
        }

        return Listing::query()
            ->with(['brand', 'model.parent', 'images', 'region', 'features'])
            ->whereIn('id', $ids)
            ->where('status', ListingStatus::Published)
            ->get()
            ->sortBy(fn (Listing $listing) => $ids->search($listing->id))
            ->values();
    }

    private function prune(?int $userId, ?string $sessionId): void
    {
        $query = RecentlyViewedListing::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(! $userId, fn ($q) => $q->where('session_id', $sessionId))
            ->orderByDesc('viewed_at');

        $keepIds = $query->clone()->limit(30)->pluck('id');

        RecentlyViewedListing::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(! $userId, fn ($q) => $q->where('session_id', $sessionId))
            ->whereNotIn('id', $keepIds)
            ->delete();
    }
}