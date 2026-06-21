<?php

namespace App\Console\Commands;

use App\Models\MobileBgImportRun;
use Illuminate\Console\Command;

class ResetStaleMobileBgImports extends Command
{
    protected $signature = 'imports:reset-stale {--minutes=30 : Mark active imports older than this as failed}';

    protected $description = 'Mark stuck Mobile.bg import runs as failed when their queue job is gone';

    public function handle(): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $reset = 0;

        MobileBgImportRun::query()
            ->whereIn('status', [MobileBgImportRun::STATUS_PENDING, MobileBgImportRun::STATUS_RUNNING])
            ->orderBy('id')
            ->each(function (MobileBgImportRun $run) use ($minutes, &$reset) {
                if (! $run->isStale($minutes)) {
                    return;
                }

                $run->markAsFailed(__('messages.mobile_bg_import_stale'));
                $reset++;
                $this->line("Import #{$run->id} marked as failed.");
            });

        $this->info("Reset {$reset} stale import run(s).");

        return self::SUCCESS;
    }
}