<?php

namespace App\Support;

use App\Models\Region;

class GeoCatalog
{
    /** @var array<string, array{0: float, 1: float}>|null */
    private static ?array $regionCoords = null;

    /** @return array{lat: float, lng: float}|null */
    public static function coordinatesForRegion(?Region $region): ?array
    {
        if (! $region) {
            return null;
        }

        $coords = self::regionCoords()[$region->slug] ?? null;

        if (! $coords) {
            return null;
        }

        return ['lat' => $coords[0], 'lng' => $coords[1]];
    }

    public static function haversineSql(string $latColumn, string $lngColumn, float $lat, float $lng): string
    {
        return "(6371 * acos(cos(radians({$lat})) * cos(radians({$latColumn})) * cos(radians({$lngColumn}) - radians({$lng})) + sin(radians({$lat})) * sin(radians({$latColumn}))))";
    }

    /** @return array<string, array{0: float, 1: float}> */
    private static function regionCoords(): array
    {
        if (self::$regionCoords === null) {
            self::$regionCoords = json_decode(
                file_get_contents(database_path('data/region_coordinates.json')),
                true,
                flags: JSON_THROW_ON_ERROR,
            );
        }

        return self::$regionCoords;
    }
}