<?php

namespace App\Enums;

enum TenderStatus: string
{
    case Active = 'active';
    case Ended = 'ended';
    case Awarded = 'awarded';
    case Cancelled = 'cancelled';

    public function isBiddable(): bool
    {
        return $this === self::Active;
    }
}