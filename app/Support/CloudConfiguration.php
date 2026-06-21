<?php

namespace App\Support;

use Illuminate\Foundation\Application;

final class CloudConfiguration
{
    public static function apply(Application $app): void
    {
        if (! function_exists('laravel_cloud') || ! laravel_cloud()) {
            return;
        }

        static::applyLogging();
        static::applyRedisFallbacks();
        static::applyPulseDefaults();
        static::applyPublicDisk();
    }

    private static function applyLogging(): void
    {
        config([
            'logging.default' => env('LOG_CHANNEL', 'stack'),
            'logging.channels.stack.channels' => array_values(array_unique(array_filter([
                env('LOG_STACK', 'daily'),
                'stderr',
            ]))),
        ]);
    }

    private static function applyRedisFallbacks(): void
    {
        if (static::redisAvailable()) {
            return;
        }

        if (in_array(config('session.driver'), ['redis', 'valkey'], true)) {
            config(['session.driver' => 'database']);
        }

        if (config('cache.default') === 'redis') {
            config(['cache.default' => 'database']);
        }

        if (config('pulse.ingest.driver') === 'redis') {
            config(['pulse.ingest.driver' => 'storage']);
        }

        if (config('pulse.cache') === 'redis') {
            config(['pulse.cache' => 'database']);
        }
    }

    private static function applyPulseDefaults(): void
    {
        if (! filter_var(env('PULSE_ENABLED', false), FILTER_VALIDATE_BOOL)) {
            config(['pulse.enabled' => false]);
        }
    }

    private static function applyPublicDisk(): void
    {
        if (! filled(env('AWS_BUCKET'))) {
            return;
        }

        config([
            'filesystems.disks.public' => [
                'driver' => 's3',
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
                'region' => env('AWS_DEFAULT_REGION'),
                'bucket' => env('AWS_BUCKET'),
                'url' => env('AWS_URL'),
                'endpoint' => env('AWS_ENDPOINT'),
                'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
                'visibility' => 'public',
                'throw' => false,
                'report' => false,
            ],
        ]);
    }

    private static function redisAvailable(): bool
    {
        if (filled(env('REDIS_URL'))) {
            return true;
        }

        $host = env('REDIS_HOST');

        return filled($host) && ! in_array($host, ['127.0.0.1', 'localhost'], true);
    }
}