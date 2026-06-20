<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiRequestLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_api_key_id',
        'method',
        'path',
        'status_code',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(CompanyApiKey::class, 'company_api_key_id');
    }
}