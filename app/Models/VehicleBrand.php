<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleBrand extends Model
{
    protected $fillable = ['name', 'slug', 'is_popular', 'sort_order'];

    protected function casts(): array
    {
        return ['is_popular' => 'boolean'];
    }

    public function models(): HasMany
    {
        return $this->hasMany(VehicleModel::class, 'brand_id');
    }

    public function series(): HasMany
    {
        return $this->models()->where('type', 'series')->whereNull('parent_id')->orderBy('sort_order');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class, 'brand_id');
    }
}