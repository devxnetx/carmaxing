<?php

namespace App\Jobs;

use App\Services\Leads\LeadListingCountService;
use App\Support\ManagedQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncLeadListingCounts implements ShouldQueue
{
    use Queueable;

    public int $timeout = 0;

    public function __construct(
        public ?string $sourceCity = null,
    ) {
        $this->onQueue(ManagedQueue::NAME);
    }

    public function handle(LeadListingCountService $service): void
    {
        $service->refreshAll($this->sourceCity);
    }
}