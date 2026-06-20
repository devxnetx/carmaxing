<?php

namespace App\Services\MobileBg;

class MobileBgProfileData
{
    public function __construct(
        public readonly string $sourceUrl,
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly ?string $phone = null,
        public readonly ?string $address = null,
        public readonly ?string $city = null,
        public readonly ?string $regionName = null,
        public readonly ?string $website = null,
        public readonly ?string $logoUrl = null,
        public readonly ?string $coverUrl = null,
    ) {}
}