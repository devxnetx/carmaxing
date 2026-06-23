<?php

namespace App\Services\BidCars;

class BidCarsTitleParser
{
    /**
     * @return array{year: ?int, make: ?string, model: ?string, variant: ?string}
     */
    public function parse(string $title): array
    {
        $title = trim($title);

        if ($title === '') {
            return [
                'year' => null,
                'make' => null,
                'model' => null,
                'variant' => null,
            ];
        }

        if (! preg_match('/^((?:19|20)\d{2})\s+(.+)$/', $title, $matches)) {
            return [
                'year' => null,
                'make' => null,
                'model' => null,
                'variant' => null,
            ];
        }

        $year = (int) $matches[1];
        $remainder = trim($matches[2]);

        $main = $remainder;
        $variant = null;

        if (str_contains($remainder, ',')) {
            [$main, $variant] = array_map(trim(...), explode(',', $remainder, 2));
        }

        $parts = preg_split('/\s+/', $main) ?: [];

        return [
            'year' => $year,
            'make' => $parts[0] ?? null,
            'model' => isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : null,
            'variant' => $variant ?: null,
        ];
    }
}