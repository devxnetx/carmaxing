<?php

namespace App\Services\BidCars;

use App\Models\BidCarsImportRun;
use App\Support\BidCarsImportConfig;

class BidCarsSearchImporter
{
    public function __construct(
        private readonly BidCarsClient $client,
        private readonly BidCarsLotImporter $lotImporter,
    ) {}

    /**
     * @param  list<string>  $brands
     * @return array{run: BidCarsImportRun, collected_pages: int, page_summaries: list<array<string, mixed>>}
     */
    public function importBrands(
        array $brands,
        int|string $pagesPerBrand = 3,
        ?int $perPage = null,
        bool $probePerPage = false,
    ): array {
        if ($probePerPage) {
            throw new \InvalidArgumentException('per_page probing is not supported in browser-session mode. Bid.cars uses a 50-item Load More flow.');
        }

        $run = BidCarsImportRun::query()->create([
            'status' => BidCarsImportRun::STATUS_RUNNING,
            'filters' => BidCarsImportConfig::filters(),
            'pages_per_brand' => BidCarsImportConfig::pagesPerBrandForStorage($pagesPerBrand),
            'per_page' => $perPage,
            'started_at' => now(),
        ]);

        $errors = [];
        $pageSummaries = [];

        try {
            $sessionPages = $this->client->fetchSessionPages($brands, $pagesPerBrand);

            foreach ($sessionPages as $sessionPage) {
                $brand = (string) ($sessionPage['brand'] ?? '');
                $currentPage = (int) ($sessionPage['current_page'] ?? 0);
                $payload = $sessionPage['payload'] ?? [];
                $items = $payload['data'] ?? [];

                if (! is_array($items) || $items === []) {
                    $errors[] = [
                        'brand' => $brand,
                        'page' => $currentPage,
                        'message' => 'No lot data returned.',
                    ];

                    continue;
                }

                $firstLot = is_array($items[0] ?? null) ? ($items[0]['lot'] ?? null) : null;
                $pageSummaries[] = [
                    'brand' => $brand,
                    'page' => $currentPage,
                    'count' => count($items),
                    'first_lot' => $firstLot,
                ];

                foreach ($items as $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $lot = $this->lotImporter->upsert($item, $run);

                    $run->increment('total_fetched');

                    if ($lot->wasRecentlyCreated) {
                        $run->increment('created_count');
                    } else {
                        $run->increment('updated_count');
                    }
                }
            }

            $run->update([
                'status' => BidCarsImportRun::STATUS_COMPLETED,
                'errors' => $errors === [] ? null : $errors,
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $errors[] = ['message' => $e->getMessage()];
            $run->update([
                'status' => BidCarsImportRun::STATUS_FAILED,
                'failed_count' => max(1, (int) $run->failed_count),
                'errors' => $errors,
                'completed_at' => now(),
            ]);

            throw $e;
        }

        return [
            'run' => $run->fresh(),
            'collected_pages' => count($pageSummaries),
            'page_summaries' => $pageSummaries,
            'per_page' => $perPage,
            'per_page_counts' => [],
        ];
    }
}