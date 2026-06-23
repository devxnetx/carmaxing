<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuctionLot extends Model
{
    protected $fillable = [
        'external_lot',
        'auction_source',
        'vin',
        'tag',
        'title',
        'brand_id',
        'model_id',
        'car_variant',
        'year',
        'odometer',
        'odometer_km',
        'location',
        'loss_type',
        'primary_damage',
        'start_code',
        'start_code_color',
        'seller',
        'seller_long',
        'seller_trusted',
        'sale_document',
        'sale_document_external',
        'sale_document_state',
        'search_status',
        'status_code',
        'prebid_price_usd',
        'final_bid_usd',
        'buy_now_price_usd',
        'estimated_min_usd',
        'estimated_max_usd',
        'time_left_seconds',
        'prebid_close_time',
        'has_video',
        'has_360_view',
        'video_url',
        'view_360_url',
        'sold_before',
        'specs',
        'images',
        'raw_payload',
        'source_url',
        'last_seen_at',
        'bid_cars_import_run_id',
    ];

    protected function casts(): array
    {
        return [
            'seller_trusted' => 'boolean',
            'has_video' => 'boolean',
            'has_360_view' => 'boolean',
            'sold_before' => 'boolean',
            'specs' => 'array',
            'images' => 'array',
            'raw_payload' => 'array',
            'last_seen_at' => 'datetime',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(VehicleBrand::class, 'brand_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'model_id');
    }

    public function importRun(): BelongsTo
    {
        return $this->belongsTo(BidCarsImportRun::class, 'bid_cars_import_run_id');
    }
}