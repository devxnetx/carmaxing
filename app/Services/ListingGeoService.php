<?php

namespace App\Services;

use App\Models\Listing;
use App\Support\GeoCatalog;

class ListingGeoService
{
    public function __construct(
        private ListingShowService $listingShow,
    ) {}
    public function syncCoordinates(Listing $listing): void
    {
        if ($listing->latitude && $listing->longitude) {
            return;
        }

        $listing->loadMissing('region');
        $coords = GeoCatalog::coordinatesForRegion($listing->region);

        if (! $coords) {
            return;
        }

        $listing->latitude = $coords['lat'] + (($listing->id % 17) - 8) * 0.008;
        $listing->longitude = $coords['lng'] + (($listing->id % 13) - 6) * 0.008;
        $listing->saveQuietly();

        $this->listingShow->forget($listing);
    }
}