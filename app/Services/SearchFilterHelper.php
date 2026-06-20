<?php

namespace App\Services;

use Illuminate\Http\Request;

class SearchFilterHelper
{
    private const ARRAY_INT_KEYS = [
        'model_ids', 'series_ids', 'features',
    ];

    private const UI_KEYS = [
        'page', 'sort', 'view', 'map_lat', 'map_lng', 'radius_km',
    ];

    private const EXTENDED_KEYS = [
        'mileage_from', 'mileage_to', 'fuel_type', 'transmission', 'drivetrain',
        'body_type', 'features', 'price_negotiable', 'has_vin', 'has_video',
        'engine_power_from', 'engine_power_to', 'euro_standard', 'doors', 'seats', 'city',
        'location_type', 'country_code',
    ];

    public function hasExtendedFilters(Request $request): bool
    {
        foreach (self::EXTENDED_KEYS as $key) {
            if ($request->filled($key)) {
                return true;
            }
        }

        return false;
    }

    public function hasSearchCriteria(Request $request): bool
    {
        return $this->filtersFromRequest($request) !== [];
    }

    public function shouldOpenExtendedSearch(Request $request): bool
    {
        return ! $this->hasSearchCriteria($request) || $this->hasExtendedFilters($request);
    }

    /** @return array<string, mixed> */
    public function filtersFromRequest(Request $request): array
    {
        $input = $request->isMethod('GET')
            ? $request->query()
            : $request->except(['_token', 'name', 'alert_enabled']);

        $filters = collect($input)
            ->except(self::UI_KEYS)
            ->filter(fn ($value) => $value !== null && $value !== '' && $value !== [])
            ->all();

        return $this->normalizeFilters($filters);
    }

    /** @param array<string, mixed> $filters */
    public function normalizeFilters(array $filters): array
    {
        foreach (self::ARRAY_INT_KEYS as $key) {
            if (! isset($filters[$key])) {
                continue;
            }

            $filters[$key] = array_values(array_unique(array_filter(
                array_map('intval', (array) $filters[$key]),
                fn (int $id) => $id > 0,
            )));
        }

        if (isset($filters['brand_id'])) {
            $filters['brand_id'] = (int) $filters['brand_id'];
        }

        if (isset($filters['region_id'])) {
            $filters['region_id'] = (int) $filters['region_id'];
        }

        return $filters;
    }

    /** @param array<string, mixed> $filters */
    public function searchUrlFromFilters(array $filters): string
    {
        $filters = $this->normalizeFilters($filters);

        if ($filters === []) {
            return route('search');
        }

        return route('search').'?'.http_build_query($filters, '', '&', PHP_QUERY_RFC3986);
    }

    /** @param array<string, mixed> $filters */
    public function filtersHash(array $filters): string
    {
        $normalized = $this->normalizeFilters($filters);

        ksort($normalized);

        foreach ($normalized as $key => $value) {
            if (is_array($value)) {
                $normalized[$key] = array_values(array_unique(array_map('strval', $value)));
                sort($normalized[$key]);
            }
        }

        return hash('sha256', json_encode($normalized));
    }
}