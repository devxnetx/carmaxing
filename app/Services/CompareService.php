<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CompareService
{
    private const MAX = 4;

    private const SESSION_KEY = 'compare_listing_ids';

    /** @return list<int> */
    public function ids(Request $request): array
    {
        return array_values(array_unique(array_map('intval', (array) $request->session()->get(self::SESSION_KEY, []))));
    }

    public function add(Request $request, int $listingId): int
    {
        $ids = $this->ids($request);

        if (! in_array($listingId, $ids, true)) {
            $ids[] = $listingId;
        }

        if (count($ids) > self::MAX) {
            $ids = array_slice($ids, -self::MAX);
        }

        $request->session()->put(self::SESSION_KEY, $ids);

        return count($ids);
    }

    public function remove(Request $request, int $listingId): int
    {
        $ids = array_values(array_filter($this->ids($request), fn (int $id) => $id !== $listingId));
        $request->session()->put(self::SESSION_KEY, $ids);

        return count($ids);
    }

    public function clear(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    /** @return Collection<int, Listing> */
    public function listings(Request $request): Collection
    {
        $ids = $this->ids($request);

        if ($ids === []) {
            return collect();
        }

        return Listing::query()
            ->with(['brand', 'model.parent', 'region', 'images', 'features.category', 'company'])
            ->whereIn('id', $ids)
            ->where('status', ListingStatus::Published)
            ->get()
            ->sortBy(fn (Listing $listing) => array_search($listing->id, $ids, true))
            ->values();
    }
}