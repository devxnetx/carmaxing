<?php

namespace App\Jobs;

use App\Models\LeadExtractionRun;
use App\Services\Leads\LeadExtractionService;
use App\Support\ManagedQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExtractLeadsFromMobileBg implements ShouldQueue
{
    use Queueable;

    public int $timeout = 0;

    public function __construct(
        public LeadExtractionRun $run,
    ) {
        $this->onQueue(ManagedQueue::NAME);
    }

    public function handle(LeadExtractionService $service): void
    {
        $service->run($this->run);
    }
}