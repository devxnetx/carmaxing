<?php

namespace App\Support;

final class AdminEnvironmentSnapshot
{
    /**
     * @return array<int, array{key: string, value: string, note: ?string}>
     */
    public static function items(): array
    {
        return [
            self::item('APP_ENV'),
            self::item('APP_DEBUG'),
            self::item('LOG_CHANNEL'),
            self::item('LOG_STACK'),
            self::item('LOG_LEVEL'),
            self::item('QUEUE_CONNECTION'),
            self::item('CACHE_STORE'),
            self::item('PULSE_ENABLED'),
            self::item('PULSE_STORAGE_DRIVER'),
            self::item('PULSE_INGEST_DRIVER'),
            self::item('PULSE_CACHE_DRIVER'),
            self::item('SESSION_DRIVER'),
        ];
    }

    /**
     * @return array{key: string, value: string, note: ?string}
     */
    private static function item(string $key): array
    {
        $raw = env($key);

        return [
            'key' => $key,
            'value' => match (true) {
                $raw === null || $raw === '' => '—',
                self::isSensitive($key) => '••••••••',
                default => (string) $raw,
            },
            'note' => self::noteFor($key, $raw),
        ];
    }

    private static function isSensitive(string $key): bool
    {
        return str_contains(strtoupper($key), 'PASSWORD')
            || str_contains(strtoupper($key), 'SECRET')
            || str_contains(strtoupper($key), 'TOKEN');
    }

    private static function noteFor(string $key, mixed $raw): ?string
    {
        return match ($key) {
            'PULSE_INGEST_DRIVER' => $raw === 'redis'
                ? __('admin.env_note_pulse_redis')
                : null,
            'PULSE_CACHE_DRIVER' => $raw === 'redis'
                ? __('admin.env_note_pulse_redis')
                : null,
            'CACHE_STORE' => $raw === 'redis'
                ? __('admin.env_note_cache_redis')
                : null,
            'QUEUE_CONNECTION' => $raw === 'redis'
                ? __('admin.env_note_queue_redis')
                : null,
            default => null,
        };
    }
}