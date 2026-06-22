<?php

namespace App\Support;

use App\Models\Region;

class LocationCatalog
{
    public const BULGARIA_CODE = 'BG';

    /** @var array<string, list<string>>|null */
    private static ?array $bgCities = null;

    /** @var list<array{code: string, name_bg: string, name_en: string}>|null */
    private static ?array $countries = null;

    public static function isBulgaria(?string $countryCode): bool
    {
        return $countryCode === null || strtoupper($countryCode) === self::BULGARIA_CODE;
    }

    /** @return list<array{code: string, name: string}> */
    public static function countriesForLocale(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();

        return array_map(function (array $country) use ($locale) {
            return [
                'code' => $country['code'],
                'name' => $locale === 'en' ? $country['name_en'] : $country['name_bg'],
            ];
        }, self::countries());
    }

    /** @return list<string> */
    public static function citiesForRegion(Region $region): array
    {
        $cities = self::bgCities()[$region->slug] ?? [];

        sort($cities, SORT_LOCALE_STRING);

        return $cities;
    }

    public static function regionSlugForCity(string $city): ?string
    {
        $normalized = mb_strtolower(trim($city));

        foreach (self::bgCities() as $slug => $cities) {
            foreach ($cities as $name) {
                if (mb_strtolower($name) === $normalized) {
                    return $slug;
                }
            }
        }

        return null;
    }

    public static function countryName(string $code, ?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();

        foreach (self::countries() as $country) {
            if ($country['code'] === strtoupper($code)) {
                return $locale === 'en' ? $country['name_en'] : $country['name_bg'];
            }
        }

        return null;
    }

    /** @return list<string> */
    public static function abroadCountryCodes(): array
    {
        return array_column(self::countries(), 'code');
    }

    /**
     * @return array{region_id: ?int, city: ?string, country_code: ?string}
     */
    public static function normalizeListingLocation(
        string $locationType,
        ?int $regionId,
        ?string $city,
        ?string $countryCode,
    ): array {
        if ($locationType === 'abroad') {
            return [
                'region_id' => null,
                'city' => null,
                'country_code' => strtoupper((string) $countryCode),
            ];
        }

        return [
            'region_id' => $regionId,
            'city' => $city ? trim($city) : null,
            'country_code' => null,
        ];
    }

    /** @return array<string, list<string>> */
    private static function bgCities(): array
    {
        if (self::$bgCities === null) {
            self::$bgCities = json_decode(
                file_get_contents(database_path('data/bg_cities.json')),
                true,
                flags: JSON_THROW_ON_ERROR,
            );
        }

        return self::$bgCities;
    }

    /** @return list<array{code: string, name_bg: string, name_en: string}> */
    private static function countries(): array
    {
        if (self::$countries === null) {
            self::$countries = json_decode(
                file_get_contents(database_path('data/countries.json')),
                true,
                flags: JSON_THROW_ON_ERROR,
            );
        }

        return self::$countries;
    }
}