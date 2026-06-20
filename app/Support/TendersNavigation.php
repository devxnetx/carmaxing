<?php

namespace App\Support;

use App\Services\PlatformSettings;

class TendersNavigation
{
    public static function isVisible(): bool
    {
        return app(PlatformSettings::class)->tendersEnabled();
    }
}