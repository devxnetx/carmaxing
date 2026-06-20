<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VehicleFeature extends Model
{
    public $timestamps = false;

    protected $fillable = ['category_id', 'slug', 'name_bg', 'name_en', 'sort_order'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(VehicleFeatureCategory::class, 'category_id');
    }

    public function listings(): BelongsToMany
    {
        return $this->belongsToMany(Listing::class, 'listing_feature');
    }

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'en' ? $this->name_en : $this->name_bg;
    }
}