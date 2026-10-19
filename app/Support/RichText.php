<?php

namespace App\Support;

class RichText
{
    public static function sanitize(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*>.*?</\1>#is', '', $value) ?? $value;
        $value = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*/?>#i', '', $value) ?? $value;
        $value = preg_replace('#<\s*div\b[^>]*>#i', '<p>', $value) ?? $value;
        $value = preg_replace('#<\s*/\s*div\s*>#i', '</p>', $value) ?? $value;

        if (! preg_match('/<(p|br|ul|ol|li|strong|b|em|i|u|a)\b/i', $value)) {
            $value = nl2br(htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'), false);
        }

        $links = [];
        $value = preg_replace_callback('#<a\b([^>]*)>(.*?)</a>#is', function (array $match) use (&$links): string {
            $inner = self::cleanInline($match[2]);
            $href = self::safeHref($match[1]);

            if ($href === null) {
                return $inner;
            }

            $token = "\x1E".count($links)."\x1E";
            $links[$token] = '<a href="'.htmlspecialchars($href, ENT_QUOTES | ENT_HTML5, 'UTF-8').'" rel="nofollow noopener noreferrer" target="_blank">'.$inner.'</a>';

            return $token;
        }, $value) ?? $value;

        $value = strip_tags($value, '<p><br><strong><b><em><i><u><ul><ol><li>');
        $value = self::stripAttributes($value);
        $value = strtr($value, $links);

        $plain = trim(html_entity_decode(strip_tags(str_replace("\xC2\xA0", ' ', $value)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $plain !== '' ? trim($value) : null;
    }

    private static function cleanInline(string $value): string
    {
        $value = strip_tags($value, '<strong><b><em><i><u><br>');

        return self::stripAttributes($value);
    }

    private static function stripAttributes(string $value): string
    {
        return preg_replace('/<([a-z0-9]+)\b[^>]*>/i', '<$1>', $value) ?? $value;
    }

    private static function safeHref(string $attributes): ?string
    {
        if (! preg_match('/href\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'>]+))/i', $attributes, $match)) {
            return null;
        }

        $raw = $match[1] !== '' ? $match[1] : ($match[2] !== '' ? $match[2] : ($match[3] ?? ''));
        $raw = trim(html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($raw === '' || preg_match('/[\x00-\x20<>"\'\\\\]/', $raw)) {
            return null;
        }

        if (preg_match('#^https?://#i', $raw)) {
            return filter_var($raw, FILTER_VALIDATE_URL) ? $raw : null;
        }

        if (preg_match('#^mailto:([^?]+)$#i', $raw, $mail) && filter_var($mail[1], FILTER_VALIDATE_EMAIL)) {
            return 'mailto:'.$mail[1];
        }

        return null;
    }
}
