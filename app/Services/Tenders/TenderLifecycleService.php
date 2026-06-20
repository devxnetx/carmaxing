<?php

namespace App\Services\Tenders;

use App\Enums\TenderStatus;
use App\Models\Tender;

class TenderLifecycleService
{
    public function closeExpired(): int
    {
        return Tender::query()
            ->where('status', TenderStatus::Active)
            ->where('ends_at', '<=', now())
            ->update(['status' => TenderStatus::Ended]);
    }
}