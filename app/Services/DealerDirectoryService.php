<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Models\Company;
use App\Models\Region;
use App\Support\GeoCatalog;
use App\Support\LocationCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DealerDirectoryService
{
    public function search(Request $request, int $perPage = 20): LengthAwarePaginator
    {
        return $this->baseQuery($request)
            ->with(['region'])
            ->withCount(['listings as published_listings_count' => fn ($query) => $query->where('status', ListingStatus::Published)])
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @return Collection<int, array{id: int, lat: float, lng: float, name: string, city: ?string, url: string, listings: int}> */
    public function mapMarkers(Request $request, int $limit = 300): Collection
    {
        return $this->baseQuery($request)
            ->with(['region'])
            ->withCount(['listings as published_listings_count' => fn ($query) => $query->where('status', ListingStatus::Published)])
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(function (Company $company) {
                $coords = $company->mapCoordinates();

                if (! $coords) {
                    return null;
                }

                return [
                    'id' => $company->id,
                    'lat' => $coords['lat'],
                    'lng' => $coords['lng'],
                    'name' => $company->name,
                    'city' => $company->city,
                    'url' => route('company.show', $company),
                    'listings' => (int) $company->published_listings_count,
                    'verified' => $company->isVerifiedDealer(),
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @return list<array{name: string, count: int}>
     */
    public function citiesWithCounts(Region $region): array
    {
        $counts = Company::query()
            ->where('region_id', $region->id)
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->selectRaw('city, COUNT(*) as aggregate')
            ->groupBy('city')
            ->pluck('aggregate', 'city')
            ->all();

        return collect(LocationCatalog::citiesForRegion($region))
            ->map(fn (string $name) => [
                'name' => $name,
                'count' => (int) ($counts[$name] ?? 0),
            ])
            ->filter(fn (array $entry) => $entry['count'] > 0)
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /** @return array{lat: float, lng: float, zoom: int} */
    public function mapCenter(Request $request): array
    {
        if ($regionId = $request->integer('region_id')) {
            $region = Region::query()->find($regionId);
            $coords = GeoCatalog::coordinatesForRegion($region);

            if ($coords) {
                return ['lat' => $coords['lat'], 'lng' => $coords['lng'], 'zoom' => 9];
            }
        }

        return ['lat' => 42.6977, 'lng' => 23.3219, 'zoom' => 8];
    }

    private function baseQuery(Request $request): Builder
    {
        $query = Company::query();

        if ($regionId = $request->integer('region_id')) {
            $query->where('region_id', $regionId);
        }

        if ($request->filled('city')) {
            $query->where('city', $request->input('city'));
        }

        return $query;
    }
}