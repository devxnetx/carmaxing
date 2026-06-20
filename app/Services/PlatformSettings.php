<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;

class PlatformSettings
{
    public const TENDERS_ENABLED = 'tenders_enabled';

    public function tendersEnabled(): bool
    {
        return $this->getBool(self::TENDERS_ENABLED, false);
    }

    public function setTendersEnabled(bool $enabled): void
    {
        $this->set(self::TENDERS_ENABLED, $enabled ? '1' : '0');
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return Cache::rememberForever($this->cacheKey($key), function () use ($key, $default) {
            return PlatformSetting::query()->find($key)?->value ?? $default;
        });
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        if ($value === null) {
            return $default;
        }

        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }

    public function set(string $key, ?string $value): void
    {
        PlatformSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );

        Cache::forget($this->cacheKey($key));
    }

    private function cacheKey(string $key): string
    {
        return 'platform_setting:'.$key;
    }
}