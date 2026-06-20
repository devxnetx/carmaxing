<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\VehicleModel;
use App\Support\GeoCatalog;
use App\Support\LocationCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ListingSearchService
{
    public function search(Request $request): LengthAwarePaginator
    {
        $query = Listing::query()
            ->with(['brand', 'model.parent', 'region', 'images', 'company', 'user', 'features'])
            ->where('status', ListingStatus::Published);

        $this->applyFilters($query, $request);

        $sort = $request->input('sort', 'newest');

        match ($sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'year_desc' => $query->orderByDesc('year'),
            'mileage_asc' => $query->orderBy('mileage'),
            default => $query->orderByDesc('published_at'),
        };

        return $query->paginate(24)->withQueryString();
    }

    public function applyFilters(Builder $query, Request $request): void
    {
        if ($brandId = $request->integer('brand_id')) {
            $query->where('brand_id', $brandId);
        }

        if ($modelIds = $this->resolveModelIds($request)) {
            $query->whereIn('model_id', $modelIds);
        }

        if ($request->filled('year_from')) {
            $query->where('year', '>=', $request->integer('year_from'));
        }

        if ($request->filled('year_to')) {
            $query->where('year', '<=', $request->integer('year_to'));
        }

        if ($request->filled('price_from')) {
            $query->where('price', '>=', $request->integer('price_from'));
        }

        if ($request->filled('price_to')) {
            $query->where('price', '<=', $request->integer('price_to'));
        }

        if ($request->filled('mileage_from')) {
            $query->where('mileage', '>=', $request->integer('mileage_from'));
        }

        if ($request->filled('mileage_to')) {
            $query->where('mileage', '<=', $request->integer('mileage_to'));
        }

        if ($request->filled('fuel_type')) {
            $query->whereIn('fuel_type', (array) $request->input('fuel_type'));
        }

        if ($request->filled('transmission')) {
            $query->whereIn('transmission', (array) $request->input('transmission'));
        }

        if ($request->filled('drivetrain')) {
            $query->whereIn('drivetrain', (array) $request->input('drivetrain'));
        }

        if ($request->filled('body_type')) {
            $query->whereIn('body_type', (array) $request->input('body_type'));
        }

        $this->applyLocationFilters($query, $request);

        if ($request->filled('engine_power_from')) {
            $query->where('engine_power_hp', '>=', $request->integer('engine_power_from'));
        }

        if ($request->filled('engine_power_to')) {
            $query->where('engine_power_hp', '<=', $request->integer('engine_power_to'));
        }

        if ($request->filled('euro_standard')) {
            $query->whereIn('euro_standard', (array) $request->input('euro_standard'));
        }

        if ($request->filled('doors')) {
            $query->whereIn('doors', array_map('intval', (array) $request->input('doors')));
        }

        if ($request->filled('seats')) {
            $query->whereIn('seats', array_map('intval', (array) $request->input('seats')));
        }

        if ($request->boolean('price_negotiable')) {
            $query->where('price_negotiable', true);
        }

        if ($request->boolean('has_vin')) {
            $query->where('has_vin', true);
        }

        if ($request->boolean('has_video')) {
            $query->where('has_video', true);
        }

        if ($featureIds = $request->input('features')) {
            foreach ((array) $featureIds as $featureId) {
                $query->whereHas('features', fn ($q) => $q->where('vehicle_features.id', $featureId));
            }
        }

        if ($request->filled('q')) {
            $term = '%'.$request->input('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('car_variant', 'like', $term)
                    ->orWhere('ad_name', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }
    }

    private function applyLocationFilters(Builder $query, Request $request): void
    {
        $locationType = $request->input('location_type');

        if ($locationType === 'abroad' && $request->filled('country_code')) {
            $query->where('country_code', strtoupper($request->input('country_code')));

            return;
        }

        if ($locationType === 'bg' || (! $locationType && ($request->filled('region_id') || $request->filled('city')))) {
            $query->where(function (Builder $q) {
                $q->whereNull('country_code')
                    ->orWhere('country_code', LocationCatalog::BULGARIA_CODE);
            });
        }

        if ($request->filled('region_id')) {
            $query->where('region_id', $request->integer('region_id'));
        }

        if ($request->filled('city')) {
            $query->where('city', $request->input('city'));
        }

        $this->applyRadiusFilter($query, $request);
    }

    private function applyRadiusFilter(Builder $query, Request $request): void
    {
        if (! $request->filled('map_lat') || ! $request->filled('map_lng')) {
            return;
        }

        $lat = (float) $request->input('map_lat');
        $lng = (float) $request->input('map_lng');
        $radiusKm = max(5, min(500, $request->integer('radius_km', 50)));

        $haversine = GeoCatalog::haversineSql('listings.latitude', 'listings.longitude', $lat, $lng);

        $query
            ->whereNotNull('listings.latitude')
            ->whereNotNull('listings.longitude')
            ->whereRaw("{$haversine} <= ?", [$radiusKm]);
    }

    /** @return Collection<int, array{id: int, lat: float, lng: float, title: string, price: ?int, price_on_request: bool, url: string}> */
    public function mapMarkers(Request $request, int $limit = 200): Collection
    {
        $query = Listing::query()
            ->select(['id', 'slug', 'title', 'price', 'price_on_request', 'latitude', 'longitude', 'brand_id', 'model_id', 'car_variant', 'ad_name'])
            ->with(['brand', 'model.parent'])
            ->where('status', ListingStatus::Published)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        $this->applyFilters($query, $request);

        return $query
            ->limit($limit)
            ->get()
            ->map(fn (Listing $listing) => [
                'id' => $listing->id,
                'lat' => (float) $listing->latitude,
                'lng' => (float) $listing->longitude,
                'title' => $listing->composeDisplayTitle(),
                'price' => $listing->price_on_request ? null : (int) $listing->price,
                'price_on_request' => $listing->price_on_request,
                'url' => route('listings.show', $listing),
            ]);
    }

    private function resolveModelIds(Request $request): array
    {
        $ids = [];

        if ($request->filled('model_ids')) {
            $ids = array_map('intval', (array) $request->input('model_ids'));
        } elseif ($request->filled('series_ids')) {
            foreach ((array) $request->input('series_ids') as $seriesId) {
                $series = VehicleModel::query()->find($seriesId);
                if ($series) {
                    $ids = array_merge($ids, $series->descendantIds());
                }
            }
        } elseif ($request->filled('model_id')) {
            $ids = [$request->integer('model_id')];
        }

        return array_unique($ids);
    }
}