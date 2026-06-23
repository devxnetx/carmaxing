<?php

namespace App\Enums;

enum ListingStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
    case Sold = 'sold';

    public function isPublic(): bool
    {
        return $this === self::Published;
    }

    public function isInactive(): bool
    {
        return in_array($this, [self::Archived, self::Sold], true);
    }
}