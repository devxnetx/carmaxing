<?php

namespace App\Console\Commands;

use App\Services\NewListingsDigestService;
use Illuminate\Console\Command;

class SendNewListingsDigest extends Command
{
    protected $signature = 'digests:new-listings';

    protected $description = 'Send daily new listings digest to subscribed users';

    public function handle(NewListingsDigestService $service): int
    {
        $sent = $service->sendDue();

        $this->info($sent > 0
            ? "Sent new listings digest to {$sent} users."
            : 'No new listings yesterday — digest skipped.');

        return self::SUCCESS;
    }
}