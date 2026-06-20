<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ListingImage extends Model
{
    protected $fillable = [
        'listing_id',
        'path',
        'path_medium',
        'path_thumb',
        'width',
        'height',
        'sort_order',
        'is_primary',
    ];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function isRemote(): bool
    {
        return str_starts_with($this->path, 'http://') || str_starts_with($this->path, 'https://');
    }

    public function url(string $size = 'large'): string
    {
        if ($this->isRemote()) {
            return $this->path;
        }

        $candidates = match ($size) {
            'thumb' => array_filter([$this->path_thumb, $this->path_medium, $this->path]),
            'medium' => array_filter([$this->path_medium, $this->path]),
            default => [$this->path],
        };

        foreach ($candidates as $path) {
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->url($path);
            }
        }

        return Storage::disk('public')->url($this->path);
    }

    public function srcset(): string
    {
        if ($this->isRemote()) {
            return $this->path.' 1x';
        }

        $parts = [];

        if ($this->path_thumb) {
            $parts[] = $this->url('thumb').' 320w';
        }

        if ($this->path_medium) {
            $parts[] = $this->url('medium').' 800w';
        }

        $parts[] = $this->url('large').' '.($this->width ?: 1600).'w';

        return implode(', ', $parts);
    }

    public function sizesAttribute(): string
    {
        return '(max-width: 640px) 120px, (max-width: 1024px) 320px, 800px';
    }

    public function deleteFiles(): void
    {
        if ($this->isRemote()) {
            return;
        }

        foreach (array_unique(array_filter([$this->path, $this->path_medium, $this->path_thumb])) as $path) {
            Storage::disk('public')->delete($path);
        }
    }
}