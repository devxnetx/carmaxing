<?php

namespace App\Support;

class TenderRules
{
    public static function version(): string
    {
        return (string) config('tenders.rules_version', '1');
    }

    /**
     * @return array<int, string>
     */
    public static function items(): array
    {
        return trans('tenders.rules_items');
    }

    public static function userHasAccepted(?\App\Models\User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->tender_rules_accepted_at !== null
            && $user->tender_rules_version === self::version();
    }
}