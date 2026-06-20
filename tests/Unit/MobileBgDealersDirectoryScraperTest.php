<?php

namespace Tests\Unit;

use App\Services\MobileBg\MobileBgDealersDirectoryScraper;
use Tests\TestCase;

class MobileBgDealersDirectoryScraperTest extends TestCase
{
    public function test_normalizes_directory_url_and_strips_pagination(): void
    {
        $scraper = app(MobileBgDealersDirectoryScraper::class);

        $this->assertSame(
            'https://www.mobile.bg/dealers/location-grad-stara-zagora',
            $scraper->normalizeDirectoryUrl('https://www.mobile.bg/dealers/location-grad-stara-zagora/p-3')
        );
    }

    public function test_parses_city_from_location_url(): void
    {
        $scraper = app(MobileBgDealersDirectoryScraper::class);

        $city = $scraper->parseCityFromUrl('https://www.mobile.bg/dealers/location-grad-stara-zagora');

        $this->assertSame('grad-stara-zagora', $city['slug']);
        $this->assertSame('Stara Zagora', $city['label']);
    }

    public function test_parses_dealer_urls_from_html(): void
    {
        $scraper = app(MobileBgDealersDirectoryScraper::class);

        $html = <<<'HTML'
            <a href="https://triumphcars.mobile.bg">TRIUMPHCARS</a>
            <a href="https://www.mobile.bg/dealers">All</a>
            <a href="https://citycar.mobile.bg">Citycar</a>
        HTML;

        $urls = $scraper->parseDealerUrls($html);

        $this->assertSame([
            'https://triumphcars.mobile.bg',
            'https://citycar.mobile.bg',
        ], $urls);
    }

    public function test_detects_last_page_number(): void
    {
        $scraper = app(MobileBgDealersDirectoryScraper::class);

        $html = <<<'HTML'
            <a href="https://www.mobile.bg/dealers/p-2">2</a>
            <a href="https://www.mobile.bg/dealers/p-47">47</a>
            <a href="https://www.mobile.bg/dealers/p-2">Next</a>
        HTML;

        $this->assertSame(47, $scraper->lastPageNumber($html));
    }
}