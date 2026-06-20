<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    public $timestamps = false;

    protected $fillable = ['name_bg', 'name_en', 'slug', 'sort_order'];

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();

        return $locale === 'en' ? $this->name_en : $this->name_bg;
    }
}