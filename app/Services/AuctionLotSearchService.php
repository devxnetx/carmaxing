<?php

namespace App\Services;

use App\Models\AuctionLot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class AuctionLotSearchService
{
    public function search(Request $request): LengthAwarePaginator
    {
        $query = AuctionLot::query()
            ->with(['brand', 'model.parent']);

        $this->applyFilters($query, $request);

        $sort = $request->input('sort', 'newest');

        match ($sort) {
            'ending_soon' => $query->orderByRaw('time_left_seconds IS NULL')
                ->orderBy('time_left_seconds')
                ->orderByDesc('id'),
            'price_asc' => $query->orderBy('estimated_min_usd'),
            'price_desc' => $query->orderByDesc('estimated_min_usd'),
            'year_desc' => $query->orderByDesc('year'),
            'mileage_asc' => $query->orderBy('odometer_km'),
            default => $query->orderByDesc('last_seen_at')->orderByDesc('id'),
        };

        return $query->paginate(24)->withQueryString();
    }

    public function count(Request $request): int
    {
        $query = AuctionLot::query();
        $this->applyFilters($query, $request);

        return $query->count();
    }

    public function applyFilters(Builder $query, Request $request): void
    {
        if ($brandId = $request->integer('brand_id')) {
            $query->where('brand_id', $brandId);
        }

        if ($modelIds = SearchModelFilter::resolveIds($request)) {
            $query->whereIn('model_id', $modelIds);
        }

        if ($request->filled('year_from')) {
            $query->where('year', '>=', $request->integer('year_from'));
        }

        if ($request->filled('year_to')) {
            $query->where('year', '<=', $request->integer('year_to'));
        }

        if ($request->filled('price_from')) {
            $query->where('estimated_min_usd', '>=', $request->integer('price_from'));
        }

        if ($request->filled('price_to')) {
            $query->where('estimated_min_usd', '<=', $request->integer('price_to'));
        }

        if ($request->filled('q')) {
            $term = '%'.$request->input('q').'%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('vin', 'like', $term)
                    ->orWhere('external_lot', 'like', $term);
            });
        }
    }
}