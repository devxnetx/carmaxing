<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleFeatureCategory extends Model
{
    public $timestamps = false;

    protected $fillable = ['slug', 'name_bg', 'name_en', 'sort_order'];

    public function features(): HasMany
    {
        return $this->hasMany(VehicleFeature::class, 'category_id')->orderBy('sort_order');
    }

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'en' ? $this->name_en : $this->name_bg;
    }
}