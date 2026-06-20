<?php

namespace Tests\Unit;

use App\Models\Listing;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ListingNewAdTest extends TestCase
{
    public function test_listing_is_new_when_published_within_default_period(): void
    {
        $listing = new Listing;
        $listing->published_at = Carbon::now()->subDays(6);

        $this->assertTrue($listing->isNewAd());
    }

    public function test_listing_is_not_new_after_default_period(): void
    {
        $listing = new Listing;
        $listing->published_at = Carbon::now()->subDays(8);

        $this->assertFalse($listing->isNewAd());
    }

    public function test_listing_without_published_at_is_not_new(): void
    {
        $listing = new Listing;

        $this->assertFalse($listing->isNewAd());
    }

    public function test_custom_day_period_is_respected(): void
    {
        $listing = new Listing;
        $listing->published_at = Carbon::now()->subDays(3);

        $this->assertTrue($listing->isNewAd(7));
        $this->assertFalse($listing->isNewAd(2));
    }
}