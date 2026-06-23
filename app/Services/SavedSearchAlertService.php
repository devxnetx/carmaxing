<?php

namespace App\Services;

use App\Enums\SearchScope;
use App\Mail\SavedSearchAlertMail;
use App\Models\SavedSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SavedSearchAlertService
{
    public function __construct(
        private ListingSearchService $listingSearch,
        private AuctionLotSearchService $importSearch,
    ) {}

    public function matchCount(SavedSearch $savedSearch): int
    {
        $filters = $savedSearch->filters ?? [];
        $scope = SearchScope::fromRequest($filters['scope'] ?? null);
        unset($filters['scope']);

        $request = Request::create(route($scope->resultsRouteName()), 'GET', $filters);

        return match ($scope) {
            SearchScope::Imports => $this->importSearch->count($request),
            default => $this->listingSearch->count($request),
        };
    }

    public function notifyDue(): int
    {
        $sent = 0;

        SavedSearch::query()
            ->where('alert_enabled', true)
            ->with('user')
            ->chunkById(50, function ($searches) use (&$sent) {
                foreach ($searches as $search) {
                    if (! $search->user?->email) {
                        continue;
                    }

                    $count = $this->matchCount($search);

                    if ($count <= $search->last_match_count) {
                        continue;
                    }

                    $newMatches = max(0, $count - $search->last_match_count);

                    if ($newMatches === 0) {
                        continue;
                    }

                    Mail::to($search->user->email)->send(new SavedSearchAlertMail($search, $newMatches, $count));

                    $search->update([
                        'last_notified_at' => now(),
                        'last_match_count' => $count,
                    ]);

                    $sent++;
                }
            });

        return $sent;
    }
}