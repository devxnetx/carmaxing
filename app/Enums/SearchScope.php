<?php

namespace App\Enums;

enum SearchScope: string
{
    case Listings = 'listings';
    case Imports = 'imports';
    case Auctions = 'auctions';

    public function resultsRouteName(): string
    {
        return match ($this) {
            self::Listings => 'search',
            self::Imports => 'search.imports',
            self::Auctions => 'search.auctions',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Listings => __('messages.search_scope_listings'),
            self::Imports => __('messages.search_scope_imports'),
            self::Auctions => __('messages.search_scope_auctions'),
        };
    }

    public static function fromRequest(?string $value): self
    {
        return match ($value) {
            'imports' => self::Imports,
            'auctions' => self::Auctions,
            default => self::Listings,
        };
    }

    /** @return list<self> */
    public static function casesOrdered(): array
    {
        return [self::Listings, self::Imports, self::Auctions];
    }
}