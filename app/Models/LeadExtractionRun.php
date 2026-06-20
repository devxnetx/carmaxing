<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadExtractionRun extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'source_url',
        'city_slug',
        'city_label',
        'status',
        'total_pages',
        'current_page',
        'total_found',
        'processed_count',
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

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED], true);
    }
}