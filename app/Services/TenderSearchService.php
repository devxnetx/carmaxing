<?php

namespace App\Services;

use App\Enums\TenderStatus;
use App\Models\Tender;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class TenderSearchService
{
    public function search(Request $request): LengthAwarePaginator
    {
        $query = Tender::query()
            ->with(['brand', 'model.parent', 'region', 'images']);

        $this->applyPeriodScope($query, $request);
        $this->applyFilters($query, $request);

        $sort = $request->input('sort', 'ending_soon');

        match ($sort) {
            'price_asc' => $query->orderBy('starting_price'),
            'price_desc' => $query->orderByDesc('starting_price'),
            'year_desc' => $query->orderByDesc('year'),
            'newest' => $query->orderByDesc('starts_at'),
            default => $query->orderBy('ends_at'),
        };

        return $query->paginate(24)->withQueryString();
    }

    public function applyPeriodScope(Builder $query, Request $request): void
    {
        match ($request->input('tender_period')) {
            'upcoming' => $query
                ->where('status', TenderStatus::Active)
                ->where('starts_at', '>', now()),
            'today' => $query
                ->where('status', TenderStatus::Active)
                ->where('starts_at', '<=', now())
                ->whereDate('ends_at', today()),
            'past' => $query->where(function (Builder $builder) {
                $builder
                    ->where('ends_at', '<=', now())
                    ->orWhereIn('status', [TenderStatus::Ended, TenderStatus::Awarded]);
            }),
            default => $query->active(),
        };
    }

    public function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('region_id')) {
            $query->where('region_id', $request->integer('region_id'));
        }
    }
}