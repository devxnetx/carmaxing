<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileBgImportRun extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'company_id',
        'source_url',
        'status',
        'total_found',
        'created_count',
        'updated_count',
        'skipped_count',
        'failed_count',
        'errors',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'errors' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED], true);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_RUNNING], true);
    }

    public function markAsFailed(string $message): void
    {
        if ($this->isFinished()) {
            return;
        }

        $errors = $this->errors ?? [];
        $errors[] = ['message' => $message];

        $this->update([
            'status' => self::STATUS_FAILED,
            'failed_count' => max(1, (int) $this->failed_count),
            'errors' => $errors,
            'completed_at' => now(),
        ]);
    }

    public function isStale(int $minutes = 30): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        $anchor = $this->started_at ?? $this->created_at;

        return $anchor !== null && $anchor->lte(now()->subMinutes($minutes));
    }

    public static function latestForCompany(Company $company): ?self
    {
        $latest = $company->mobileBgImportRuns()->first();

        if ($latest?->isStale()) {
            $latest->markAsFailed(__('messages.mobile_bg_import_stale'));
            $latest->refresh();
        }

        return $latest;
    }
}