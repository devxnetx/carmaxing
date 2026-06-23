<?php

namespace App\Support;

use InvalidArgumentException;
use RuntimeException;

class BidCarsImportConfig
{
    /** @var array<string, mixed>|null */
    private static ?array $cache = null;

    private const IMPORT_API_PATH = '/api/v1/bid-cars/import';

    public static function path(): string
    {
        return base_path('scripts/bid-cars-worker/import.config.json');
    }

    public static function localPath(): string
    {
        return base_path('scripts/bid-cars-worker/import.config.local.json');
    }

    /**
     * @return array<string, mixed>
     */
    public static function load(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $path = self::path();

        if (! is_file($path)) {
            throw new RuntimeException('Bid.cars import config is missing at '.$path);
        }

        $decoded = self::decodeJsonFile($path);

        $localPath = self::localPath();

        if (is_file($localPath)) {
            $local = self::decodeJsonFile($localPath);

            if (is_array($local)) {
                $decoded = array_replace_recursive($decoded, $local);
            }
        }

        self::$cache = self::mergeEnvOverrides($decoded);

        return self::$cache;
    }

    public static function backendDomain(): string
    {
        $config = self::load();
        $backend = is_array($config['backend'] ?? null) ? $config['backend'] : [];
        $domain = $config['backendDomain'] ?? $backend['domain'] ?? '';

        return rtrim((string) $domain, '/');
    }

    public static function apiKey(): string
    {
        $config = self::load();
        $backend = is_array($config['backend'] ?? null) ? $config['backend'] : [];

        return trim((string) ($config['apiKey'] ?? $backend['apiKey'] ?? ''));
    }

    public static function apiUrl(): string
    {
        if ($apiUrl = trim((string) (self::load()['apiUrl'] ?? ''))) {
            return rtrim($apiUrl, '/');
        }

        $domain = self::backendDomain();

        if ($domain === '') {
            return '';
        }

        return $domain.self::IMPORT_API_PATH;
    }

    public static function resetCache(): void
    {
        self::$cache = null;
    }

    /**
     * @return list<string>
     */
    public static function brands(): array
    {
        $brands = self::load()['brands'] ?? [];

        if (! is_array($brands) || $brands === []) {
            throw new InvalidArgumentException('import.config.json must define at least one brand.');
        }

        return array_values(array_filter(array_map(
            static fn (mixed $brand): string => trim((string) $brand),
            $brands,
        )));
    }

    public static function pagesPerBrand(): int|string
    {
        return self::normalizePagesPerBrand(self::load()['pagesPerBrand'] ?? 1);
    }

    public static function normalizePagesPerBrand(mixed $value): int|string
    {
        if (is_string($value) && strtolower(trim($value)) === 'full') {
            return 'full';
        }

        if (is_int($value) || (is_string($value) && is_numeric($value))) {
            $parsed = (int) $value;

            return $parsed <= 0 ? 'full' : max(1, $parsed);
        }

        return 1;
    }

    public static function isFullPages(int|string $value): bool
    {
        return $value === 'full';
    }

    public static function pagesPerBrandForStorage(int|string $value): int
    {
        return self::isFullPages($value) ? 0 : (int) $value;
    }

    public static function formatPagesPerBrand(int|string $value): string
    {
        return self::isFullPages($value) ? 'full (until no more)' : (string) $value;
    }

    public static function headless(): bool
    {
        if (env('BID_CARS_HEADLESS') !== null) {
            return filter_var(env('BID_CARS_HEADLESS'), FILTER_VALIDATE_BOOL);
        }

        return (bool) (self::load()['headless'] ?? true);
    }

    /**
     * @return array<string, string|int>
     */
    public static function filters(): array
    {
        $filters = self::load()['filters'] ?? [];

        if (! is_array($filters) || $filters === []) {
            throw new InvalidArgumentException('import.config.json must define bid.cars search filters.');
        }

        return array_map(
            static fn (mixed $value): string|int => is_int($value) ? $value : (string) $value,
            $filters,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodeJsonFile(string $path): array
    {
        $contents = self::stripJsonComments((string) file_get_contents($path));
        $decoded = json_decode($contents, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Bid.cars import config must be valid JSON: '.$path);
        }

        return $decoded;
    }

    private static function stripJsonComments(string $json): string
    {
        $result = '';
        $length = strlen($json);
        $inString = false;
        $escaped = false;

        for ($index = 0; $index < $length; $index++) {
            $char = $json[$index];
            $next = $json[$index + 1] ?? '';

            if ($inString) {
                $result .= $char;
                $escaped = $char === '\\' && ! $escaped;

                if ($char === '"' && ! $escaped) {
                    $inString = false;
                }

                continue;
            }

            if ($char === '"') {
                $inString = true;
                $result .= $char;

                continue;
            }

            if ($char === '/' && $next === '/') {
                while ($index < $length && $json[$index] !== "\n") {
                    $index++;
                }

                $result .= "\n";

                continue;
            }

            if ($char === '/' && $next === '*') {
                $index += 2;

                while ($index < $length - 1 && ! ($json[$index] === '*' && $json[$index + 1] === '/')) {
                    $index++;
                }

                $index++;

                continue;
            }

            $result .= $char;
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private static function mergeEnvOverrides(array $config): array
    {
        if ($brands = env('BID_CARS_BRANDS')) {
            $config['brands'] = array_values(array_filter(array_map(
                trim(...),
                explode(',', (string) $brands),
            )));
        }

        if (env('BID_CARS_PAGES_PER_BRAND') !== null) {
            $config['pagesPerBrand'] = self::normalizePagesPerBrand(env('BID_CARS_PAGES_PER_BRAND'));
        }

        if (env('BID_CARS_HEADLESS') !== null) {
            $config['headless'] = filter_var(env('BID_CARS_HEADLESS'), FILTER_VALIDATE_BOOL);
        }

        if ($domain = env('BID_CARS_BACKEND_DOMAIN')) {
            $config['backendDomain'] = rtrim((string) $domain, '/');
        }

        if ($apiKey = env('BID_CARS_IMPORT_API_KEY')) {
            $config['apiKey'] = (string) $apiKey;
        }

        return $config;
    }
}