<?php

namespace App\Jobs;

use App\Models\MobileBgImportRun;
use App\Services\MobileBg\MobileBgImporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportMobileBgListings implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public function __construct(
        public MobileBgImportRun $run,
        public bool $syncImages = true,
    ) {}

    public function handle(MobileBgImporter $importer): void
    {
        $importer->run($this->run, $this->syncImages);
    }
}