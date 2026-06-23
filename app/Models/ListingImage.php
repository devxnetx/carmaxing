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

        return Storage::disk('public')->url($this->pathForSize($size));
    }

    public function srcset(): string
    {
        if ($this->isRemote()) {
            return $this->path.' 1x';
        }

        $disk = Storage::disk('public');
        $parts = [];

        if ($this->path_thumb) {
            $parts[] = $disk->url($this->path_thumb).' 320w';
        }

        if ($this->path_medium) {
            $parts[] = $disk->url($this->path_medium).' 800w';
        }

        $parts[] = $disk->url($this->path).' '.($this->width ?: 1600).'w';

        return implode(', ', $parts);
    }

    private function pathForSize(string $size): string
    {
        return match ($size) {
            'thumb' => $this->path_thumb ?? $this->path_medium ?? $this->path,
            'medium' => $this->path_medium ?? $this->path,
            default => $this->path,
        };
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