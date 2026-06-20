<?php

namespace App\Models;

use App\Enums\TenderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Tender extends Model
{
    protected $fillable = [
        'reference_number',
        'slug',
        'user_id',
        'company_id',
        'status',
        'brand_id',
        'model_id',
        'car_variant',
        'description',
        'year',
        'mileage',
        'fuel_type',
        'engine_power_hp',
        'transmission',
        'body_type',
        'color_exterior',
        'condition',
        'region_id',
        'city',
        'starting_price',
        'minimum_price',
        'bid_increment',
        'duration_days',
        'starts_at',
        'ends_at',
        'current_high_bid_amount',
        'bid_count',
        'winning_bid_id',
        'awarded_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenderStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'awarded_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::creating(function (Tender $tender) {
            if (empty($tender->reference_number)) {
                $tender->reference_number = static::nextReferenceNumber();
            }

            if (empty($tender->slug)) {
                $tender->slug = 'tender-'.Str::lower(Str::random(10));
            }
        });
    }

    public static function nextReferenceNumber(): string
    {
        $latest = static::query()
            ->where('reference_number', 'like', 'T-%')
            ->orderByDesc('id')
            ->value('reference_number');

        $sequence = 1;

        if ($latest && preg_match('/T-(\d+)/', $latest, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return 'T-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(VehicleBrand::class, 'brand_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'model_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(TenderImage::class)->orderBy('sort_order');
    }

    public function bids(): HasMany
    {
        return $this->hasMany(TenderBid::class)->latest('amount');
    }

    public function winningBid(): BelongsTo
    {
        return $this->belongsTo(TenderBid::class, 'winning_bid_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', TenderStatus::Active)
            ->where('ends_at', '>', now());
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->whereIn('status', [
            TenderStatus::Active,
            TenderStatus::Ended,
            TenderStatus::Awarded,
        ]);
    }

    public function vehicleName(): string
    {
        $this->loadMissing(['brand', 'model.parent']);

        $parts = array_filter([
            $this->brand?->name,
            $this->model?->parent?->name,
            $this->model?->name,
            $this->car_variant,
        ]);

        return implode(' ', $parts);
    }

    public function isOwnedBy(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id;
    }

    public function isBiddable(): bool
    {
        return $this->status->isBiddable() && $this->ends_at->isFuture();
    }

    public function secondsRemaining(): int
    {
        return max(0, (int) now()->diffInSeconds($this->ends_at, false));
    }

    public function leadingBidId(): ?int
    {
        if ($this->status === TenderStatus::Awarded && $this->winning_bid_id) {
            return (int) $this->winning_bid_id;
        }

        if ($this->relationLoaded('bids')) {
            $active = $this->bids->first(
                fn ($bid) => $bid->status === \App\Enums\TenderBidStatus::Active,
            );

            return $active !== null ? (int) $active->id : null;
        }

        $id = $this->bids()
            ->where('status', \App\Enums\TenderBidStatus::Active)
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    public function activeHighBidAmount(): ?int
    {
        $amount = $this->bids()
            ->where('status', \App\Enums\TenderBidStatus::Active)
            ->max('amount');

        return $amount !== null ? (int) $amount : null;
    }

    public function minimumNextBidAmount(): int
    {
        $high = $this->activeHighBidAmount();

        if ($high === null) {
            return (int) $this->starting_price;
        }

        return $high + (int) $this->bid_increment;
    }

    public function isValidBidAmount(int $amount): bool
    {
        $increment = (int) $this->bid_increment;
        $starting = (int) $this->starting_price;

        if ($amount < $this->minimumNextBidAmount()) {
            return false;
        }

        if ($increment < 1) {
            return false;
        }

        return ($amount - $starting) % $increment === 0;
    }

    public function syncHighBidCache(): void
    {
        $this->update([
            'current_high_bid_amount' => $this->activeHighBidAmount(),
        ]);
    }

    public function sellerIsVerifiedDealer(): bool
    {
        return $this->company?->isVerifiedDealer() ?? false;
    }

    public function sellerTypeKey(): string
    {
        if ($this->company_id) {
            return $this->sellerIsVerifiedDealer() ? 'verified_dealer' : 'dealer';
        }

        return 'private_seller';
    }

    public function publicLocationLabel(): ?string
    {
        $parts = array_filter([
            $this->city,
            $this->region?->name,
        ]);

        return $parts ? implode(', ', $parts) : null;
    }

    public function meetsMinimumPrice(?int $amount = null): bool
    {
        $amount ??= $this->current_high_bid_amount;

        if ($this->minimum_price === null || $amount === null) {
            return true;
        }

        return $amount >= (int) $this->minimum_price;
    }
}