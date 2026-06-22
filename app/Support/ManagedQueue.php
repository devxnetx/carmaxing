<?php

namespace App\Support;

use Illuminate\Contracts\Queue\ShouldQueue;

final class ManagedQueue
{
    /**
     * Optional managed queue name in Laravel Cloud.
     * When empty, jobs use the environment's default queue connection/name.
     */
    public static function name(): ?string
    {
        $name = env('MOBILE_BG_QUEUE');

        return filled($name) ? $name : null;
    }

    public static function dispatch(ShouldQueue $job): void
    {
        if (config('queue.default') === 'sync') {
            dispatch_sync($job);

            return;
        }

        if ($queue = self::name()) {
            dispatch($job)->onQueue($queue);

            return;
        }

        dispatch($job);
    }
}