<?php

namespace App\Services\Leads;

use App\Models\Lead;
use App\Services\MobileBg\MobileBgScraper;

class LeadListingCountService
{
    private const DEALER_SLEEP_SECONDS = 5;

    public function __construct(
        private readonly MobileBgScraper $scraper,
    ) {}

    public function refresh(Lead $lead): int
    {
        $count = $this->scraper->countListings($lead->mobile_bg_url);

        $lead->update([
            'listings_count' => $count,
        ]);

        return $count;
    }

    /**
     * @return array{processed: int, failed: int, errors: list<array{lead_id: int, url: string, message: string}>}
     */
    public function refreshAll(?string $sourceCity = null): array
    {
        $query = Lead::query()->orderBy('id');

        if ($sourceCity) {
            $query->where('source_city', $sourceCity);
        }

        $leads = $query->get();
        $processed = 0;
        $failed = 0;
        $errors = [];

        foreach ($leads as $index => $lead) {
            if ($index > 0) {
                sleep(self::DEALER_SLEEP_SECONDS);
            }

            try {
                $this->refresh($lead);
                $processed++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = [
                    'lead_id' => $lead->id,
                    'url' => $lead->mobile_bg_url,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'processed' => $processed,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }
}