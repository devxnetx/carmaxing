<?php

namespace App\Models;

use App\Enums\ListingStatus;
use App\Support\LocationCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Listing extends Model
{
    protected $fillable = [
        'user_id', 'company_id', 'brand_id', 'model_id', 'car_variant', 'ad_name', 'title', 'slug', 'description',
        'status', 'price', 'currency', 'price_negotiable', 'price_on_request', 'year', 'month', 'mileage',
        'mileage_unit', 'fuel_type', 'engine_power_hp', 'engine_displacement_cc',
        'transmission', 'drivetrain', 'body_type', 'color_exterior', 'color_interior',
        'doors', 'seats', 'euro_standard', 'registration_type', 'vin', 'region_id',
        'city', 'latitude', 'longitude', 'country_code', 'condition', 'warranty_until', 'wltp_consumption', 'battery_capacity_kwh',
        'first_registration_date', 'has_vin', 'has_video', 'has_vr360', 'external_id', 'ad_number',
        'published_at', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ListingStatus::class,
            'price_negotiable' => 'boolean',
            'price_on_request' => 'boolean',
            'has_vin' => 'boolean',
            'has_video' => 'boolean',
            'has_vr360' => 'boolean',
            'warranty_until' => 'date',
            'first_registration_date' => 'date',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
            'wltp_consumption' => 'decimal:1',
            'battery_capacity_kwh' => 'decimal:1',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::saving(function (Listing $listing) {
            if ($listing->brand_id && $listing->model_id) {
                $listing->loadMissing(['brand', 'model.parent']);
                $listing->title = $listing->composeDisplayTitle();
            }
        });

        static::creating(function (Listing $listing) {
            if ($listing->company_id && ! $listing->ad_number) {
                $listing->ad_number = static::nextAdNumberForCompany($listing->company_id);
            }

            if (empty($listing->slug)) {
                $base = Str::slug($listing->title ?: 'listing');
                $listing->slug = $base.'-'.Str::random(6);
            }
        });
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

    public function composeDisplayTitle(): string
    {
        $name = $this->vehicleName();

        if ($this->ad_name) {
            return trim($name.' '.$this->ad_name);
        }

        return $name ?: ($this->title ?? '');
    }

    public function breadcrumbModelName(): string
    {
        $this->loadMissing('model.parent');

        if ($this->model?->parent) {
            return trim($this->model->parent->name.' '.$this->model->name);
        }

        return $this->model?->name ?? '';
    }

    public function breadcrumbAdName(): string
    {
        $name = $this->vehicleName();

        if ($this->ad_name) {
            return trim($name.' — '.$this->ad_name);
        }

        if ($name !== '') {
            return $name;
        }

        return $this->car_variant ?: ($this->title ?? '');
    }

    public function displayAdNumber(): ?string
    {
        if ($this->ad_number && $this->company_id) {
            return (string) $this->ad_number;
        }

        return null;
    }

    public function locationLabel(): ?string
    {
        if (! LocationCatalog::isBulgaria($this->country_code)) {
            return LocationCatalog::countryName($this->country_code);
        }

        $parts = array_filter([$this->city, $this->region?->name]);

        return $parts ? implode(', ', $parts) : null;
    }

    public function mapsUrl(): ?string
    {
        $query = $this->locationLabel();

        return $query ? 'https://www.google.com/maps/search/?api=1&query='.urlencode($query) : null;
    }

    public static function nextAdNumberForCompany(int $companyId): int
    {
        $max = static::query()->where('company_id', $companyId)->max('ad_number');

        return ($max ?? 1000) + 1;
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
        return $this->hasMany(ListingImage::class)->orderBy('sort_order');
    }

    public function priceChanges(): HasMany
    {
        return $this->hasMany(ListingPriceChange::class)->orderByDesc('created_at');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ListingReport::class);
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(ListingInquiry::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(FavoriteListing::class);
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(VehicleFeature::class, 'listing_feature');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ListingStatus::Published);
    }

    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->whereNotIn('status', [ListingStatus::Archived]);
    }

    public function archive(): void
    {
        $this->update([
            'status' => ListingStatus::Archived,
            'archived_at' => now(),
        ]);
    }

    public function publish(): void
    {
        $this->update([
            'status' => ListingStatus::Published,
            'published_at' => $this->published_at ?? now(),
            'archived_at' => null,
        ]);
    }

    public function contactPhone(): ?string
    {
        if ($this->company_id) {
            return $this->company?->phone;
        }

        return $this->user?->phone;
    }

    public function sellerEmail(): ?string
    {
        if ($this->company_id) {
            return $this->company?->email ?: $this->user?->email;
        }

        return $this->user?->email;
    }

    public function isNewAd(?int $days = null): bool
    {
        if (! $this->published_at) {
            return false;
        }

        $days ??= (int) config('listings.new_ad_days', 7);

        return $this->published_at->gte(now()->subDays($days));
    }

    public function hasFixedPrice(): bool
    {
        return ! $this->price_on_request;
    }

    public function priceInBgn(): float
    {
        $rate = match ($this->currency) {
            'BGN' => 1,
            'EUR' => 1.95583,
            'USD' => 1.80,
            default => 1.95583,
        };

        return round($this->price * $rate, 2);
    }
}