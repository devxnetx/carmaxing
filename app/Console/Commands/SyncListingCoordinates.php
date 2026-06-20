<?php

namespace App\Console\Commands;

use App\Models\Listing;
use App\Services\ListingGeoService;
use Illuminate\Console\Command;

class SyncListingCoordinates extends Command
{
    protected $signature = 'listings:sync-geo {--force : Re-sync listings that already have coordinates}';

    protected $description = 'Backfill listing map coordinates from region centers';

    public function handle(ListingGeoService $geo): int
    {
        $query = Listing::query()->with('region');

        if (! $this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('latitude')->orWhereNull('longitude');
            });
        }

        $count = 0;

        $query->chunkById(100, function ($listings) use ($geo, &$count) {
            foreach ($listings as $listing) {
                if ($this->option('force')) {
                    $listing->latitude = null;
                    $listing->longitude = null;
                }

                $geo->syncCoordinates($listing);
                $count++;
            }
        });

        $this->info("Synced coordinates for {$count} listing(s).");

        return self::SUCCESS;
    }
}