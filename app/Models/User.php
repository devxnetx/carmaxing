<?php

namespace App\Models;

use App\Enums\AccountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'account_type',
        'locale',
        'theme',
        'subscribe_price_digest',
        'subscribe_new_listings_digest',
        'subscribe_news',
        'subscription_prompted_at',
        'onboarding_completed_at',
        'tender_rules_accepted_at',
        'tender_rules_version',
        'email_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'tender_rules_accepted_at' => 'datetime',
            'account_type' => AccountType::class,
            'subscribe_price_digest' => 'boolean',
            'subscribe_new_listings_digest' => 'boolean',
            'subscribe_news' => 'boolean',
            'subscription_prompted_at' => 'datetime',
        ];
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function company(): HasOne
    {
        return $this->hasOne(Company::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(FavoriteListing::class);
    }

    public function savedSearches(): HasMany
    {
        return $this->hasMany(SavedSearch::class);
    }

    public function searchHistories(): HasMany
    {
        return $this->hasMany(SearchHistory::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function needsOnboarding(): bool
    {
        return $this->onboarding_completed_at === null;
    }

    public function isCompany(): bool
    {
        return $this->account_type === AccountType::Company;
    }

    public function isPrivate(): bool
    {
        return $this->account_type === AccountType::Private;
    }

    public function hasRole(string $slug): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('slug', $slug);
        }

        return $this->roles()->where('slug', $slug)->exists();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN);
    }

    public function assignRole(string $slug): void
    {
        $role = Role::findBySlug($slug);

        if ($role) {
            $this->roles()->syncWithoutDetaching([$role->id]);
        }
    }

    public function removeRole(string $slug): void
    {
        $role = Role::findBySlug($slug);

        if ($role) {
            $this->roles()->detach($role->id);
        }
    }

    public function syncAdminRole(bool $isAdmin): void
    {
        if ($isAdmin) {
            $this->assignRole(Role::ADMIN);
        } else {
            $this->removeRole(Role::ADMIN);
        }
    }

    public function hasFavorited(int $listingId): bool
    {
        return $this->favorites()->where('listing_id', $listingId)->exists();
    }

    public function hasAnySubscription(): bool
    {
        return $this->subscribe_price_digest
            || $this->subscribe_new_listings_digest
            || $this->subscribe_news;
    }

    public function shouldShowSubscriptionPrompt(): bool
    {
        if ($this->hasAnySubscription()) {
            return false;
        }

        if ($this->subscription_prompted_at === null) {
            return true;
        }

        return $this->subscription_prompted_at->lte(now()->subWeek());
    }

    public function avatarUrl(): ?string
    {
        $avatar = $this->avatar;

        if (! filled($avatar)) {
            $avatar = $this->relationLoaded('socialAccounts')
                ? $this->socialAccounts->firstWhere('avatar')?->avatar
                : $this->socialAccounts()->whereNotNull('avatar')->value('avatar');
        }

        if (! filled($avatar)) {
            return null;
        }

        if (Str::startsWith($avatar, ['http://', 'https://'])) {
            return $avatar;
        }

        return Storage::disk('public')->url($avatar);
    }
}