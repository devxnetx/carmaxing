<?php

namespace App\Support\AppApi;

class PublicUrl
{
    public static function storage(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return $url;
        }

        if (preg_match('#^https?://[^/]+(/storage/.*)$#', $url, $matches) !== 1) {
            return $url;
        }

        return self::base().$matches[1];
    }

    public static function page(string $url): string
    {
        $parts = parse_url($url);
        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return self::base().$path.$query;
    }

    private static function base(): string
    {
        return rtrim((string) config('app.public_asset_url'), '/');
    }
}
