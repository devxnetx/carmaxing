<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    public const ADMIN = 'admin';

    public const MEMBER = 'member';

    protected $fillable = [
        'name',
        'slug',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::query()->where('slug', $slug)->first();
    }
}