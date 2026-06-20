<?php

namespace App\Enums;

enum TenderBidStatus: string
{
    case Active = 'active';
    case Revoked = 'revoked';
    case Outbid = 'outbid';
    case Won = 'won';
    case Lost = 'lost';

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}