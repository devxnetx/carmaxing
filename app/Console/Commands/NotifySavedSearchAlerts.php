<?php

namespace App\Console\Commands;

use App\Services\SavedSearchAlertService;
use Illuminate\Console\Command;

class NotifySavedSearchAlerts extends Command
{
    protected $signature = 'searches:notify';

    protected $description = 'Send email alerts for saved searches with new matching listings';

    public function handle(SavedSearchAlertService $service): int
    {
        $sent = $service->notifyDue();
        $this->info("Sent {$sent} saved search alert(s).");

        return self::SUCCESS;
    }
}