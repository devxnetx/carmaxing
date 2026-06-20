<?php

namespace App\Services\MobileBg;

class MobileBgAdData
{
    public function __construct(
        public string $externalId,
        public string $url,
        public string $brandName,
        public string $modelName,
        public ?string $variant = null,
        public ?string $title = null,
        public ?int $price = null,
        public string $currency = 'EUR',
        public bool $priceOnRequest = false,
        public int $year = 0,
        public ?int $month = null,
        public ?int $mileage = null,
        public ?string $fuelType = null,
        public ?int $enginePowerHp = null,
        public ?int $engineDisplacementCc = null,
        public ?string $transmission = null,
        public ?string $bodyType = null,
        public ?string $colorExterior = null,
        public ?string $euroStandard = null,
        public ?string $city = null,
        public ?string $regionName = null,
        public ?string $description = null,
        /** @var list<string> */
        public array $imageUrls = [],
    ) {}
}