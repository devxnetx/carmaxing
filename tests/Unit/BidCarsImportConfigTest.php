<?php

namespace Tests\Unit;

use App\Support\BidCarsImportConfig;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BidCarsImportConfigTest extends TestCase
{
    protected function tearDown(): void
    {
        BidCarsImportConfig::resetCache();

        parent::tearDown();
    }

    #[Test]
    public function test_it_loads_shared_import_settings(): void
    {
        $brands = BidCarsImportConfig::brands();

        $this->assertContains('Audi', $brands);
        $this->assertContains('BMW', $brands);
        $this->assertContains('Mercedes-Benz', $brands);
        $this->assertSame(3, BidCarsImportConfig::pagesPerBrand());
        $this->assertSame('Automobile', BidCarsImportConfig::filters()['type']);
        $this->assertSame('4500', BidCarsImportConfig::filters()['estimated-min']);
        $this->assertSame('https://carmaxing.online', BidCarsImportConfig::backendDomain());
        $this->assertSame('https://carmaxing.online/api/v1/bid-cars/import', BidCarsImportConfig::apiUrl());
    }

    #[Test]
    public function test_it_normalizes_full_pages_mode(): void
    {
        $this->assertSame('full', BidCarsImportConfig::normalizePagesPerBrand('full'));
        $this->assertSame('full', BidCarsImportConfig::normalizePagesPerBrand('FULL'));
        $this->assertSame('full', BidCarsImportConfig::normalizePagesPerBrand(0));
        $this->assertSame(5, BidCarsImportConfig::normalizePagesPerBrand(5));
        $this->assertSame(0, BidCarsImportConfig::pagesPerBrandForStorage('full'));
        $this->assertSame('full (until no more)', BidCarsImportConfig::formatPagesPerBrand('full'));
    }
}