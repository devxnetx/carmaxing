<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedSearch extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'filters',
        'alert_enabled',
        'last_notified_at',
        'last_match_count',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'alert_enabled' => 'boolean',
            'last_notified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}