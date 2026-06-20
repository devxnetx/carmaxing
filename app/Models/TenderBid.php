<?php

namespace App\Models;

use App\Enums\TenderBidStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderBid extends Model
{
    protected $fillable = [
        'tender_id',
        'user_id',
        'amount',
        'status',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenderBidStatus::class,
            'revoked_at' => 'datetime',
        ];
    }

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRevocable(): bool
    {
        return $this->status->isActive()
            && $this->tender->status->isBiddable()
            && $this->tender->ends_at->isFuture();
    }
}