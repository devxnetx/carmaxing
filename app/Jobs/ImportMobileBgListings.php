<?php

namespace App\Jobs;

use App\Models\MobileBgImportRun;
use App\Services\MobileBg\MobileBgImporter;
use App\Support\ManagedQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportMobileBgListings implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(
        public MobileBgImportRun $run,
        public bool $syncImages = true,
    ) {
        if ($queue = ManagedQueue::name()) {
            $this->onQueue($queue);
        }
    }

    public function handle(MobileBgImporter $importer): void
    {
        $importer->run($this->run->fresh(), $this->syncImages);
    }

    public function failed(\Throwable $exception): void
    {
        $this->run->fresh()?->markAsFailed($exception->getMessage());
    }
}