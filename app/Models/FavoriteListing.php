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

    /**
     * The table's key is (user_id, listing_id). A delete by `id` removes nothing.
     */
    protected function setKeysForSaveQuery($query)
    {
        return $query
            ->where('user_id', $this->user_id)
            ->where('listing_id', $this->listing_id);
    }
}