<?php

namespace App\Services\BidCars;

use App\Models\AuctionLot;
use App\Models\BidCarsImportRun;
use App\Services\MobileBg\MobileBgCatalogMapper;

class BidCarsLotImporter
{
    public function __construct(
        private readonly BidCarsTitleParser $titleParser,
        private readonly BidCarsMoneyParser $moneyParser,
        private readonly MobileBgCatalogMapper $catalogMapper,
    ) {}

    /**
     * @param  array<string, mixed>  $item
     */
    public function upsert(array $item, ?BidCarsImportRun $importRun = null): AuctionLot
    {
        $lot = (string) ($item['lot'] ?? '');

        if ($lot === '') {
            throw new \InvalidArgumentException('Bid.cars lot item is missing a lot identifier.');
        }

        [$auctionSource] = $this->resolveAuctionSource($lot);
        $parsed = $this->titleParser->parse((string) ($item['name_long'] ?? $item['name'] ?? ''));
        $brand = $parsed['make'] ? $this->catalogMapper->resolveBrand($parsed['make']) : null;
        $model = ($brand && $parsed['model']) ? $this->catalogMapper->resolveModel($brand, $parsed['model']) : null;
        $tag = (string) ($item['tag'] ?? '');
        $vin = (string) ($item['vin'] ?? '');

        $attributes = [
            'auction_source' => $auctionSource,
            'vin' => $vin !== '' ? $vin : null,
            'tag' => $tag !== '' ? $tag : null,
            'title' => (string) ($item['name_long'] ?? $item['name'] ?? $lot),
            'brand_id' => $brand?->id,
            'model_id' => $model?->id,
            'car_variant' => $parsed['variant'],
            'year' => $parsed['year'],
            'odometer' => isset($item['odometer']) ? (int) $item['odometer'] : null,
            'odometer_km' => isset($item['odometer_km_substr']) ? (int) $item['odometer_km_substr'] : null,
            'location' => $item['location'] ?? null,
            'loss_type' => $item['loss_type'] ?? null,
            'primary_damage' => $item['primary_damage'] ?? null,
            'start_code' => $item['start_code'] ?? null,
            'start_code_color' => $item['start_code_color'] ?? null,
            'seller' => $item['seller'] ?? null,
            'seller_long' => $item['seller_long'] ?? null,
            'seller_trusted' => (bool) ($item['seller_trusted'] ?? false),
            'sale_document' => $item['sale_document'] ?? null,
            'sale_document_external' => $item['sale_document_external'] ?? null,
            'sale_document_state' => $item['sale_document_state'] ?? null,
            'search_status' => $item['search_status'] ?? null,
            'status_code' => isset($item['status']) ? (int) $item['status'] : null,
            'prebid_price_usd' => $this->moneyParser->parseUsd($item['prebid_price'] ?? null),
            'final_bid_usd' => $this->moneyParser->parseUsd($item['final_bid_formatted'] ?? $item['final_bid'] ?? null),
            'buy_now_price_usd' => $this->moneyParser->parseUsd($item['buy_now_price'] ?? null),
            'estimated_min_usd' => isset($item['estimated_min']) ? (int) $item['estimated_min'] : null,
            'estimated_max_usd' => isset($item['estimated_max']) ? (int) $item['estimated_max'] : null,
            'time_left_seconds' => isset($item['time_left']) ? (int) $item['time_left'] : null,
            'prebid_close_time' => $item['prebid_close_time_lang']['en'] ?? null,
            'has_video' => (bool) ($item['has_video'] ?? false),
            'has_360_view' => (bool) ($item['has_360_view'] ?? false),
            'video_url' => $item['video_url'] ?? null,
            'view_360_url' => $item['view_360_url'] ?? null,
            'sold_before' => (bool) ($item['sold_before'] ?? false),
            'specs' => $item['specs'] ?? null,
            'images' => [
                'thumb' => $item['img'] ?? null,
                'large' => $item['img_large'] ?? null,
            ],
            'raw_payload' => $item,
            'source_url' => $this->buildSourceUrl($lot, $tag, $vin),
            'last_seen_at' => now(),
            'bid_cars_import_run_id' => $importRun?->id,
        ];

        return AuctionLot::query()->updateOrCreate(
            ['external_lot' => $lot],
            $attributes,
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveAuctionSource(string $lot): array
    {
        $prefix = str_contains($lot, '-') ? explode('-', $lot, 2)[0] : '';

        return match ($prefix) {
            '0' => ['iaai', $prefix],
            '1' => ['copart', $prefix],
            default => ['unknown', $prefix],
        };
    }

    private function buildSourceUrl(string $lot, string $tag, string $vin): string
    {
        $slug = $tag !== '' ? $tag : trim($lot.'-'.$vin, '-');

        return 'https://bid.cars/en/lot/'.$lot.'/'.$slug;
    }
}