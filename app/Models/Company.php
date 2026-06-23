<?php

namespace App\Models;

use App\Support\GeoCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'logo',
        'cover_image',
        'phone',
        'email',
        'website',
        'address',
        'region_id',
        'city',
        'working_hours',
        'member_since_year',
        'is_verified',
        'verified_at',
        'latitude',
        'longitude',
        'mobile_bg_url',
        'mobile_bg_last_sync_at',
    ];

    protected function casts(): array
    {
        return [
            'working_hours' => 'array',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'mobile_bg_last_sync_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(CompanyApiKey::class);
    }

    public function mobileBgImportRuns(): HasMany
    {
        return $this->hasMany(MobileBgImportRun::class)->latest();
    }

    public function isVerifiedDealer(): bool
    {
        return $this->is_verified && $this->verified_at !== null;
    }

    /** @return array{lat: float, lng: float}|null */
    public function mapCoordinates(): ?array
    {
        if ($this->latitude && $this->longitude) {
            return [
                'lat' => (float) $this->latitude,
                'lng' => (float) $this->longitude,
            ];
        }

        $this->loadMissing('region');
        $coords = GeoCatalog::coordinatesForRegion($this->region);

        if (! $coords) {
            return null;
        }

        return [
            'lat' => $coords['lat'] + (($this->id % 17) - 8) * 0.008,
            'lng' => $coords['lng'] + (($this->id % 13) - 6) * 0.008,
        ];
    }

    public function locationLabel(): ?string
    {
        $parts = array_filter([
            $this->city,
            $this->relationLoaded('region') ? $this->region?->name : null,
        ]);

        return $parts !== [] ? implode(', ', $parts) : null;
    }

    public function logoUrl(): ?string
    {
        return $this->assetUrl($this->logo);
    }

    public function coverUrl(): ?string
    {
        return $this->assetUrl($this->cover_image);
    }

    private function assetUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}