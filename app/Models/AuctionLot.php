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

    public function vehicleName(): string
    {
        $name = trim(implode(' ', array_filter([
            $this->year,
            $this->brand?->name,
            $this->model?->parent?->name,
            $this->model?->name,
            $this->car_variant,
        ])));

        return $name !== '' ? $name : (string) $this->title;
    }

    public function detailUrl(): string
    {
        return $this->source_url ?: 'https://bid.cars/en/lot/'.$this->external_lot;
    }

    public function mainImageUrl(): ?string
    {
        $images = is_array($this->images) ? $this->images : [];
        $mainImage = $images[0] ?? null;

        if (! is_string($mainImage) && is_array($mainImage)) {
            return $mainImage['url'] ?? $mainImage['src'] ?? null;
        }

        return is_string($mainImage) ? $mainImage : null;
    }

    public function auctionTimeLabel(): ?string
    {
        if ($this->time_left_seconds > 0) {
            return __('messages.auction_time_left', ['time' => $this->formatDuration($this->time_left_seconds)]);
        }

        if (filled($this->prebid_close_time)) {
            return __('messages.auction_closes_at', ['time' => $this->prebid_close_time]);
        }

        return null;
    }

    private function formatDuration(int $seconds): string
    {
        $days = intdiv($seconds, 86_400);
        $hours = intdiv($seconds % 86_400, 3_600);
        $minutes = intdiv($seconds % 3_600, 60);

        $parts = [];

        if ($days > 0) {
            $parts[] = $days.'d';
        }

        if ($hours > 0) {
            $parts[] = $hours.'h';
        }

        if ($minutes > 0 || $parts === []) {
            $parts[] = max(1, $minutes).'m';
        }

        return implode(' ', $parts);
    }
}