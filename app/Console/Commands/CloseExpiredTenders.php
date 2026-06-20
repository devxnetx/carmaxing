<?php

namespace App\Console\Commands;

use App\Services\Tenders\TenderLifecycleService;
use Illuminate\Console\Command;

class CloseExpiredTenders extends Command
{
    protected $signature = 'tenders:close-expired';

    protected $description = 'Mark active tenders as ended when their deadline passes';

    public function handle(TenderLifecycleService $lifecycle): int
    {
        $count = $lifecycle->closeExpired();

        if ($count > 0) {
            $this->info("Closed {$count} tender(s).");
        }

        return self::SUCCESS;
    }
}