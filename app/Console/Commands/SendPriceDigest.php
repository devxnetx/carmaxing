<?php

namespace App\Console\Commands;

use App\Services\PriceDigestService;
use Illuminate\Console\Command;

class SendPriceDigest extends Command
{
    protected $signature = 'digests:price-changes';

    protected $description = 'Send daily price change digest to subscribed users';

    public function handle(PriceDigestService $service): int
    {
        $sent = $service->sendDue();

        $this->info($sent > 0
            ? "Sent price digest to {$sent} users."
            : 'No price changes yesterday — digest skipped.');

        return self::SUCCESS;
    }
}