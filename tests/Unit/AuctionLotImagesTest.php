<?php

namespace Tests\Unit;

use App\Models\AuctionLot;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuctionLotImagesTest extends TestCase
{
    #[Test]
    public function test_extracts_first_thumb_from_bid_cars_gallery_payload(): void
    {
        $urls = AuctionLot::extractImageUrls([
            'thumb' => [
                'img_1' => 'https://images.bid.cars/example-1.jpg',
                'img_2' => 'https://images.bid.cars/example-2.jpg',
            ],
            'large' => [
                'img_1' => 'https://pluto.bid.car/example-1.jpg',
            ],
        ], 'thumb');

        $this->assertSame(['https://images.bid.cars/example-1.jpg', 'https://images.bid.cars/example-2.jpg'], $urls);
    }

    #[Test]
    public function test_main_image_url_reads_nested_import_images(): void
    {
        $lot = new AuctionLot([
            'images' => [
                'thumb' => [
                    'img_1' => 'https://images.bid.cars/first.jpg',
                ],
            ],
        ]);

        $this->assertSame('https://images.bid.cars/first.jpg', $lot->mainImageUrl());
    }

    #[Test]
    public function test_extracts_simple_string_thumb_and_large_values(): void
    {
        $urls = AuctionLot::extractImageUrls([
            'thumb' => 'https://images.bid.cars/thumb.jpg',
            'large' => 'https://pluto.bid.car/large.jpg',
        ], 'thumb');

        $this->assertSame(['https://images.bid.cars/thumb.jpg'], $urls);
    }
}