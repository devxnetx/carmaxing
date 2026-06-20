<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CompanyApiKey extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'key_prefix',
        'key_hash',
        'last_used_at',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function requestLogs(): HasMany
    {
        return $this->hasMany(ApiRequestLog::class);
    }

    public static function generate(string $name, Company $company): array
    {
        $plainKey = 'ac_'.Str::random(48);
        $prefix = substr($plainKey, 0, 12);

        $apiKey = $company->apiKeys()->create([
            'name' => $name,
            'key_prefix' => $prefix,
            'key_hash' => hash('sha256', $plainKey),
        ]);

        return ['model' => $apiKey, 'plain_key' => $plainKey];
    }

    public function matches(string $plainKey): bool
    {
        return hash_equals($this->key_hash, hash('sha256', $plainKey));
    }

    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}