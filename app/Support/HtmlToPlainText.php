<?php

namespace App\Support;

class HtmlToPlainText
{
    public static function convert(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $text = $html;
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<\/p>/i', "\n\n", $text) ?? $text;
        $text = preg_replace('/<\/li>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<\/h[1-6]>/i', "\n\n", $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xc2\xa0", ' ', $text);

        $lines = preg_split("/\r\n|\r|\n/", $text) ?: [];
        $lines = array_map(static fn (string $line): string => trim(preg_replace('/[ \t]+/u', ' ', $line) ?? $line), $lines);
        $lines = array_values(array_filter($lines, static fn (string $line): bool => $line !== ''));
        $lines = self::stripLeadingSectionHeadings($lines);
        $text = trim(implode("\n", $lines));
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return $text !== '' ? $text : null;
    }

    public static function sanitize(?string $value): ?string
    {
        return self::convert($value);
    }

    /**
     * @param  list<string>  $lines
     * @return list<string>
     */
    private static function stripLeadingSectionHeadings(array $lines): array
    {
        $headings = [
            'Допълнителна информация',
            'Additional information',
        ];

        while ($lines !== [] && in_array($lines[0], $headings, true)) {
            array_shift($lines);
        }

        return $lines;
    }
}