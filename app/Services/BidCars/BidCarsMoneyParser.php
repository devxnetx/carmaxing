<?php

namespace App\Services\BidCars;

class BidCarsMoneyParser
{
    public function parseUsd(?string $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! preg_match('/(\d[\d,]*)/', $value, $matches)) {
            return null;
        }

        $amount = (int) str_replace(',', '', $matches[1]);

        return $amount > 0 ? $amount : null;
    }
}