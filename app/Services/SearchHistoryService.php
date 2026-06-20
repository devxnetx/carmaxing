<?php

namespace App\Services;

use App\Models\SearchHistory;
use App\Models\User;
use App\Support\SearchSeoBuilder;
use Illuminate\Http\Request;

class SearchHistoryService
{
    public function __construct(
        private SearchFilterHelper $filterHelper,
        private SearchSeoBuilder $searchSeoBuilder,
    ) {}

    public function record(User $user, Request $request): void
    {
        $filters = $this->filterHelper->filtersFromRequest($request);

        if ($filters === []) {
            return;
        }

        $hash = $this->filterHelper->filtersHash($filters);
        $label = $this->searchSeoBuilder->searchLabel(
            Request::create(route('search'), 'GET', $filters)
        );

        $existing = SearchHistory::query()
            ->where('user_id', $user->id)
            ->where('filters_hash', $hash)
            ->first();

        if ($existing) {
            $existing->update([
                'label' => $label,
                'filters' => $filters,
                'searched_at' => now(),
            ]);

            $this->prune($user);

            return;
        }

        SearchHistory::query()->create([
            'user_id' => $user->id,
            'label' => $label,
            'filters' => $filters,
            'filters_hash' => $hash,
            'searched_at' => now(),
        ]);

        $this->prune($user);
    }

    private function prune(User $user): void
    {
        $limit = (int) config('listings.search_history_limit', 20);

        $keepIds = SearchHistory::query()
            ->where('user_id', $user->id)
            ->orderByDesc('searched_at')
            ->limit($limit)
            ->pluck('id');

        SearchHistory::query()
            ->where('user_id', $user->id)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }
}