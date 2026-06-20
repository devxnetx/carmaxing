<?php

namespace App\Support;

class PhoneNumber
{
    public const PREFIX = '+359';

    public static function isValidLocalPart(?string $value): bool
    {
        return is_string($value) && preg_match('/^\d{9}$/', $value) === 1;
    }

    public static function fromLocalPart(string $local): string
    {
        return self::PREFIX.$local;
    }

    public static function localPart(?string $stored): string
    {
        if ($stored === null || $stored === '') {
            return '';
        }

        $digits = preg_replace('/\D+/', '', $stored) ?? '';

        if (str_starts_with($digits, '359') && strlen($digits) >= 12) {
            $digits = substr($digits, 3);
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = substr($digits, 1);
        }

        return strlen($digits) === 9 ? $digits : '';
    }

    public static function formatForDisplay(?string $stored): string
    {
        $local = self::localPart($stored);

        if ($local === '') {
            return '';
        }

        return sprintf(
            '0%s %s %s',
            substr($local, 0, 2),
            substr($local, 2, 3),
            substr($local, 5),
        );
    }

    public static function maskForDisplay(?string $stored): string
    {
        $formatted = self::formatForDisplay($stored);

        if ($formatted === '') {
            return '';
        }

        $parts = explode(' ', $formatted, 3);

        if (count($parts) === 3) {
            return $parts[0].' '.$parts[1].' *****';
        }

        return substr($formatted, 0, 3).' *****';
    }
}