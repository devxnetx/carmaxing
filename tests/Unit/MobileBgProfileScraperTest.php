<?php

namespace Tests\Unit;

use App\Services\MobileBg\MobileBgClient;
use App\Services\MobileBg\MobileBgProfileScraper;
use Tests\TestCase;

class MobileBgProfileScraperTest extends TestCase
{
    public function test_scrape_extracts_ratola_profile_fields(): void
    {
        if (! env('RUN_MOBILE_BG_INTEGRATION')) {
            $this->markTestSkipped('Set RUN_MOBILE_BG_INTEGRATION=1 to run live Mobile.bg scraper test.');
        }

        $profile = app(MobileBgProfileScraper::class)->scrape('https://ratola.mobile.bg/');

        $this->assertSame('https://ratola.mobile.bg', $profile->sourceUrl);
        $this->assertSame('RATOLA', $profile->name);
        $this->assertNotNull($profile->description);
        $this->assertStringContainsString('Ratola', $profile->description);
        $this->assertNotNull($profile->phone);
        $this->assertNotNull($profile->city);
        $this->assertNotNull($profile->address);
        $this->assertNotNull($profile->logoUrl);
        $this->assertNotNull($profile->coverUrl);
    }

    public function test_normalize_dealer_url_requires_mobile_bg_host(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(MobileBgClient::class)->normalizeDealerUrl('https://example.com/dealer');
    }
}