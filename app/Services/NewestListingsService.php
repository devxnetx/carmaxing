<?php

namespace App\Services;

use App\Models\Listing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class NewestListingsService
{
    public function __construct(
        private ListingSearchService $listingSearch,
    ) {}

    public function recentWindowDays(): int
    {
        return (int) config('listings.newest_cars_days', 2);
    }

    public function usesRecentWindow(): bool
    {
        return $this->recentQuery()->exists();
    }

    public function paginate(Request $request, int $perPage = 24): LengthAwarePaginator
    {
        return $this->baseQuery()
            ->with($this->listingSearch->gridEagerLoads())
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @return Collection<int, Listing> */
    public function preview(int $limit = 12): Collection
    {
        return $this->baseQuery()
            ->with($this->listingSearch->gridEagerLoads())
            ->limit($limit)
            ->get();
    }

    private function baseQuery(): Builder
    {
        return $this->usesRecentWindow()
            ? $this->recentQuery()->orderByDesc('published_at')
            : Listing::query()->published()->orderByDesc('published_at');
    }

    private function recentQuery(): Builder
    {
        return Listing::query()
            ->published()
            ->where('published_at', '>=', now()->subDays($this->recentWindowDays()));
    }
}