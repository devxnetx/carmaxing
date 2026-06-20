<?php

namespace App\Models;

use App\Enums\LeadContactedStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    protected $fillable = [
        'lead_extraction_run_id',
        'company_id',
        'mobile_bg_url',
        'name',
        'slug',
        'description',
        'logo',
        'cover_image',
        'phone',
        'email',
        'website',
        'listings_count',
        'address',
        'region_id',
        'city',
        'source_city',
        'working_hours',
        'member_since_year',
        'contacted_status',
        'contacted_at',
        'extracted_at',
    ];

    protected function casts(): array
    {
        return [
            'working_hours' => 'array',
            'contacted_status' => LeadContactedStatus::class,
            'contacted_at' => 'datetime',
            'extracted_at' => 'datetime',
        ];
    }

    public function extractionRun(): BelongsTo
    {
        return $this->belongsTo(LeadExtractionRun::class, 'lead_extraction_run_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function isOnboarded(): bool
    {
        return $this->company_id !== null;
    }
}