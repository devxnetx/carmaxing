<?php

namespace App\Support;

use App\Services\SearchFilterHelper;
use App\Models\Region;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SearchSeoBuilder
{
    public function fromRequest(Request $request, int $resultCount = 0): array
    {
        $vehicleLabel = $this->vehicleLabel($request);
        $locationSuffix = $this->locationSuffix($request);
        $heading = $vehicleLabel
            ? __('messages.seo_search_heading', ['label' => $vehicleLabel])
            : __('messages.seo_search_heading_generic');

        if ($locationSuffix) {
            $heading .= ' '.$locationSuffix;
        }

        $title = __('messages.seo_search_title', [
            'heading' => $heading,
            'app' => config('app.name'),
        ]);

        $description = __('messages.seo_search_description', [
            'label' => $vehicleLabel ?: __('messages.seo_search_heading_generic'),
            'count' => number_format($resultCount),
            'app' => config('app.name'),
        ]);

        return [
            'heading' => $heading,
            'title' => $title,
            'description' => $description,
            'vehicle_label' => $vehicleLabel,
            'breadcrumbs' => $this->breadcrumbs($request, $vehicleLabel),
            'canonical' => $this->canonicalUrl($request),
        ];
    }

    public function searchLabel(Request $request): string
    {
        $parts = array_filter([
            $this->vehicleLabel($request),
            $this->locationSuffix($request),
            $this->priceYearSuffix($request),
        ]);

        return $parts !== []
            ? implode(' · ', $parts)
            : __('messages.search_history_default_name');
    }

    public function vehicleLabel(Request $request): ?string
    {
        $brandId = $request->integer('brand_id') ?: null;

        if (! $brandId) {
            return null;
        }

        $brand = VehicleBrand::query()->find($brandId);

        if (! $brand) {
            return null;
        }

        $modelIds = $this->resolvedModelIds($request);

        if (count($modelIds) === 1) {
            $model = VehicleModel::query()->with('parent')->find($modelIds[0]);

            return $this->formatBrandModelLabel($brand->name, $model);
        }

        if (count($modelIds) > 1) {
            $models = VehicleModel::query()
                ->with('parent')
                ->whereIn('id', $modelIds)
                ->orderBy('name')
                ->get();

            return $this->formatMultipleModelsLabel($brand->name, $models);
        }

        $seriesIds = array_values(array_unique(array_map('intval', (array) $request->input('series_ids', []))));
        $seriesIds = array_filter($seriesIds);

        if (count($seriesIds) === 1) {
            $series = VehicleModel::query()->find($seriesIds[0]);

            return trim($brand->name.' '.($series?->name ?? ''));
        }

        if (count($seriesIds) > 1) {
            $names = VehicleModel::query()
                ->whereIn('id', $seriesIds)
                ->orderBy('name')
                ->pluck('name')
                ->take(3);

            if ($names->count() <= 2) {
                return trim($brand->name.' '.$names->join(', '));
            }
        }

        return $brand->name;
    }

    /** @return list<array{name: string, url: string}> */
    private function breadcrumbs(Request $request, ?string $vehicleLabel): array
    {
        $items = [
            ['name' => __('messages.home'), 'url' => route('home')],
            ['name' => __('messages.search'), 'url' => route('search')],
        ];

        $brandId = $request->integer('brand_id') ?: null;

        if (! $brandId) {
            return $items;
        }

        $brand = VehicleBrand::query()->find($brandId);

        if (! $brand) {
            return $items;
        }

        $brandUrl = route('search', ['brand_id' => $brand->id]);
        $items[] = ['name' => $brand->name, 'url' => $brandUrl];

        $modelIds = $this->resolvedModelIds($request);

        if (count($modelIds) === 1) {
            $model = VehicleModel::query()->with('parent')->find($modelIds[0]);

            if ($model) {
                $items[] = [
                    'name' => $this->formatModelName($model),
                    'url' => route('search', [
                        'brand_id' => $brand->id,
                        'model_id' => $model->id,
                    ]),
                ];
            }
        } elseif (count($modelIds) > 1 && $vehicleLabel) {
            $items[] = ['name' => $vehicleLabel, 'url' => $request->fullUrl()];
        } else {
            $seriesIds = array_values(array_unique(array_map('intval', (array) $request->input('series_ids', []))));
            $seriesIds = array_filter($seriesIds);

            if (count($seriesIds) === 1) {
                $series = VehicleModel::query()->find($seriesIds[0]);

                if ($series) {
                    $items[] = [
                        'name' => $series->name,
                        'url' => route('search', [
                            'brand_id' => $brand->id,
                            'series_ids' => [$series->id],
                        ]),
                    ];
                }
            } elseif ($vehicleLabel && $vehicleLabel !== $brand->name) {
                $items[] = ['name' => $vehicleLabel, 'url' => $request->fullUrl()];
            }
        }

        return $items;
    }

    private function priceYearSuffix(Request $request): ?string
    {
        $parts = [];

        if ($request->filled('year_from') || $request->filled('year_to')) {
            $from = $request->input('year_from');
            $to = $request->input('year_to');

            if ($from && $to) {
                $parts[] = $from.'–'.$to;
            } elseif ($from) {
                $parts[] = __('messages.from').' '.$from;
            } else {
                $parts[] = __('messages.to').' '.$to;
            }
        }

        if ($request->filled('price_from') || $request->filled('price_to')) {
            $from = $request->input('price_from');
            $to = $request->input('price_to');

            if ($from && $to) {
                $parts[] = number_format((int) $from).'–'.number_format((int) $to).' '.__('messages.eur');
            } elseif ($from) {
                $parts[] = __('messages.from').' '.number_format((int) $from).' '.__('messages.eur');
            } else {
                $parts[] = __('messages.to').' '.number_format((int) $to).' '.__('messages.eur');
            }
        }

        return $parts !== [] ? implode(' · ', $parts) : null;
    }

    private function locationSuffix(Request $request): ?string
    {
        if ($request->input('location_type') === 'abroad' && $request->filled('country_code')) {
            $country = LocationCatalog::countryName($request->input('country_code'));

            return $country ? __('messages.seo_search_in_country', ['country' => $country]) : null;
        }

        if ($request->input('location_type') === 'bg' || $request->filled('region_id') || $request->filled('city')) {
            if ($request->filled('city')) {
                return __('messages.seo_search_in_city', ['city' => $request->input('city')]);
            }

            if ($request->filled('region_id')) {
                $region = Region::query()->find($request->integer('region_id'));

                return $region
                    ? __('messages.seo_search_in_region', ['region' => $region->name])
                    : null;
            }

            return __('messages.seo_search_in_bulgaria');
        }

        return null;
    }

    private function canonicalUrl(Request $request): string
    {
        $params = collect($request->query())
            ->except(['page', 'sort'])
            ->filter(fn ($value) => $value !== null && $value !== '' && $value !== [])
            ->all();

        return app(SearchFilterHelper::class)->searchUrlFromFilters($params);
    }

    /** @return list<int> */
    private function resolvedModelIds(Request $request): array
    {
        if ($request->filled('model_ids')) {
            return array_values(array_unique(array_map('intval', (array) $request->input('model_ids'))));
        }

        if ($request->filled('model_id')) {
            return [$request->integer('model_id')];
        }

        return [];
    }

    private function formatBrandModelLabel(string $brandName, ?VehicleModel $model): string
    {
        if (! $model) {
            return $brandName;
        }

        return trim($brandName.' '.$this->formatModelName($model));
    }

    private function formatModelName(VehicleModel $model): string
    {
        if ($model->parent) {
            return trim($model->parent->name.' '.$model->name);
        }

        return $model->name;
    }

    /** @param Collection<int, VehicleModel> $models */
    private function formatMultipleModelsLabel(string $brandName, Collection $models): string
    {
        $names = $models
            ->map(fn (VehicleModel $model) => $this->formatModelName($model))
            ->unique()
            ->values();

        if ($names->count() <= 2) {
            return trim($brandName.' '.$names->join(', '));
        }

        return $brandName;
    }
}