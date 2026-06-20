<?php

namespace App\Services;

use App\Mail\SavedSearchAlertMail;
use App\Models\SavedSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SavedSearchAlertService
{
    public function __construct(
        private ListingSearchService $searchService,
    ) {}

    public function matchCount(SavedSearch $savedSearch): int
    {
        $request = Request::create('/search', 'GET', $savedSearch->filters ?? []);

        return $this->searchService->search($request)->total();
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