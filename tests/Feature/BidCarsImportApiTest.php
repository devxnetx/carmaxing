<?php

namespace Tests\Feature;

use App\Models\AuctionLot;
use App\Models\BidCarsImportRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BidCarsImportApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.bid_cars.import_api_key' => 'test-bid-cars-import-key']);
    }

    #[Test]
    public function test_it_rejects_requests_without_api_key(): void
    {
        $this->postJson('/api/v1/bid-cars/import', [
            'pages' => [],
        ])->assertUnauthorized();
    }

    #[Test]
    public function test_it_imports_auction_lots_from_worker_payload(): void
    {
        $response = $this->withHeader('X-API-Key', 'test-bid-cars-import-key')
            ->postJson('/api/v1/bid-cars/import', [
                'brands' => ['BMW'],
                'pages_per_brand' => 1,
                'pages' => [
                    [
                        'brand' => 'BMW',
                        'current_page' => 1,
                        'items' => [
                            [
                                'lot' => '1-12345678',
                                'vin' => 'WBA12345678901234',
                                'name' => '2018 BMW 320, Sport',
                                'name_long' => '2018 BMW 320, Sport',
                                'tag' => '2018-BMW-320-WBA12345678901234',
                                'search_status' => 'active',
                                'estimated_min' => 5000,
                                'estimated_max' => 7000,
                                'img' => [
                                    'img_1' => 'https://images.bid.cars/test-1.jpg',
                                ],
                                'img_large' => [
                                    'img_1' => 'https://pluto.bid.car/test-1.jpg',
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('created_count', 1)
            ->assertJsonPath('total_fetched', 1);

        $this->assertDatabaseCount('auction_lots', 1);
        $this->assertDatabaseHas('auction_lots', [
            'external_lot' => '1-12345678',
            'vin' => 'WBA12345678901234',
            'search_status' => 'active',
        ]);

        $lot = AuctionLot::query()->where('external_lot', '1-12345678')->first();
        $this->assertNotNull($lot);
        $this->assertSame(2018, $lot->year);
        $this->assertSame('BMW', $lot->brand?->name);
        $this->assertSame('https://images.bid.cars/test-1.jpg', $lot->mainImageUrl());

        $this->assertSame(1, BidCarsImportRun::query()->count());
    }

    #[Test]
    public function test_it_accepts_full_pages_mode_from_worker_payload(): void
    {
        $response = $this->withHeader('X-API-Key', 'test-bid-cars-import-key')
            ->postJson('/api/v1/bid-cars/import', [
                'brands' => ['Audi'],
                'pages_per_brand' => 'full',
                'pages' => [
                    [
                        'brand' => 'Audi',
                        'current_page' => 1,
                        'items' => [
                            [
                                'lot' => '1-87654321',
                                'vin' => 'WAU12345678901234',
                                'name' => '2019 Audi A4, Premium',
                                'name_long' => '2019 Audi A4, Premium',
                                'tag' => '2019-Audi-A4-WAU12345678901234',
                                'search_status' => 'active',
                                'estimated_min' => 6000,
                                'estimated_max' => 8000,
                            ],
                        ],
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('pages_per_brand', 'full');

        $this->assertDatabaseHas('bid_cars_import_runs', [
            'pages_per_brand' => 0,
        ]);
    }
}