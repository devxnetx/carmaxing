<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;

class WebsiteManager
{
    public static function email(): string
    {
        $configured = config('site.website_manager_email');

        if (filled($configured)) {
            return (string) $configured;
        }

        $adminEmail = User::query()
            ->whereHas('roles', fn ($query) => $query->where('slug', Role::ADMIN))
            ->orderBy('id')
            ->value('email');

        if (filled($adminEmail)) {
            return (string) $adminEmail;
        }

        return (string) config('mail.from.address');
    }
}