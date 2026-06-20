<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FavoriteListing extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'listing_id'];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}