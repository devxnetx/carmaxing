<?php

namespace Tests\Unit;

use App\Services\MobileBg\MobileBgClient;
use App\Services\MobileBg\MobileBgProfileScraper;
use App\Support\LocationCatalog;
use Tests\TestCase;

class MobileBgIcarProfileTest extends TestCase
{
    public function test_region_slug_for_stara_zagora_city(): void
    {
        $this->assertSame('stara-zagora', LocationCatalog::regionSlugForCity('Стара Загора'));
    }

    public function test_scrape_icar_profile_fields_from_live_site(): void
    {
        if (! env('RUN_MOBILE_BG_INTEGRATION')) {
            $this->markTestSkipped('Set RUN_MOBILE_BG_INTEGRATION=1 to run live Mobile.bg scraper test.');
        }

        $profile = app(MobileBgProfileScraper::class)->scrape('https://icar.mobile.bg');

        $this->assertSame('https://icar.mobile.bg', $profile->sourceUrl);
        $this->assertSame('iCar', $profile->name);
        $this->assertSame('0889559669', $profile->phone);
        $this->assertSame('Стара Загора', $profile->city);
        $this->assertNull($profile->regionName);
        $this->assertNotNull($profile->logoUrl);
    }

    public function test_normalize_icar_url_without_trailing_slash(): void
    {
        $this->assertSame(
            'https://icar.mobile.bg',
            app(MobileBgClient::class)->normalizeDealerUrl('https://icar.mobile.bg'),
        );
    }
}