<?php

namespace App\Services\MobileBg;

use App\Models\Region;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Illuminate\Support\Str;

class MobileBgCatalogMapper
{
    /** @var array<string, string> */
    private array $brandAliases = [
        'mercedes benz' => 'Mercedes-Benz',
        'mercedes-benz' => 'Mercedes-Benz',
        'vw' => 'VW',
        'volkswagen' => 'VW',
        'ssangyong' => 'SsangYong',
        'ssang yong' => 'SsangYong',
        'land rover' => 'Land Rover',
        'alfa romeo' => 'Alfa Romeo',
    ];

    public function resolveBrand(string $name): VehicleBrand
    {
        $normalized = $this->normalizeName($name);
        $canonical = $this->brandAliases[$normalized] ?? $name;

        $brand = VehicleBrand::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($canonical)])
            ->first();

        if ($brand) {
            return $brand;
        }

        return VehicleBrand::query()->firstOrCreate(
            ['slug' => Str::slug($canonical)],
            [
                'name' => $canonical,
                'is_popular' => false,
                'sort_order' => 999,
            ],
        );
    }

    public function resolveModel(VehicleBrand $brand, string $modelName): VehicleModel
    {
        $modelName = trim($modelName) ?: 'Other';

        $model = VehicleModel::query()
            ->where('brand_id', $brand->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($modelName)])
            ->first();

        if ($model) {
            return $model;
        }

        $series = VehicleModel::query()
            ->where('brand_id', $brand->id)
            ->where('type', 'series')
            ->whereNull('parent_id')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($modelName)])
            ->first();

        if ($series) {
            $child = $series->children()->first();
            if ($child) {
                return $child;
            }
        }

        return VehicleModel::query()->firstOrCreate(
            [
                'brand_id' => $brand->id,
                'slug' => Str::slug($brand->name.'-'.$modelName),
            ],
            [
                'name' => $modelName,
                'type' => 'model',
                'sort_order' => 999,
            ],
        );
    }

    public function resolveRegion(?string $regionName): ?int
    {
        if (! $regionName) {
            return null;
        }

        $needle = mb_strtolower(trim($regionName));

        $region = Region::query()
            ->get()
            ->first(function (Region $region) use ($needle) {
                return str_contains(mb_strtolower($region->name_bg), $needle)
                    || str_contains($needle, mb_strtolower($region->name_bg));
            });

        return $region?->id;
    }

    private function normalizeName(string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $name) ?? $name));
    }
}