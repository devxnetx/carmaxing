<?php

namespace App\Services\MobileBg;

use App\Models\Company;
use App\Models\Region;
use App\Services\ImageProcessor;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Storage;

class MobileBgProfileService
{
    public function __construct(
        private readonly MobileBgProfileScraper $scraper,
        private readonly MobileBgClient $client,
        private readonly ImageProcessor $imageProcessor,
    ) {}

    public function extractAndApply(Company $company, string $dealerUrl): MobileBgProfileData
    {
        $profile = $this->scraper->scrape($dealerUrl);

        $regionId = $this->resolveRegionId($profile->regionName, $profile->city);

        $updates = array_filter([
            'name' => $profile->name,
            'description' => $profile->description,
            'phone' => ($profile->phone && ($local = PhoneNumber::localPart($profile->phone)))
                ? PhoneNumber::fromLocalPart($local)
                : null,
            'address' => $profile->address,
            'city' => $profile->city,
            'website' => $profile->website,
            'region_id' => $regionId,
            'mobile_bg_url' => $profile->sourceUrl,
        ], fn ($value) => $value !== null && $value !== '');

        foreach ([
            'logo' => $profile->logoUrl,
            'cover_image' => $profile->coverUrl,
        ] as $field => $url) {
            if (! $url) {
                continue;
            }

            $path = $this->downloadCompanyImage(
                $company,
                $url,
                $field === 'logo' ? 'logo' : 'cover',
                config($field === 'logo' ? 'images.company_logo' : 'images.company_cover'),
            );

            if ($path) {
                $updates[$field] = $path;
            }
        }

        $company->update($updates);

        return $profile;
    }

    private function resolveRegionId(?string $regionName, ?string $city): ?int
    {
        if ($regionName) {
            $normalized = mb_strtolower($regionName);

            $region = Region::query()
                ->get()
                ->first(function (Region $region) use ($normalized) {
                    $name = mb_strtolower($region->name);

                    return $name === $normalized
                        || str_contains($name, $normalized)
                        || str_contains($normalized, $name);
                });

            if ($region) {
                return $region->id;
            }
        }

        if ($city && mb_strtolower($city) === 'софия') {
            return Region::query()->where('slug', 'sofia-grad')->value('id')
                ?? Region::query()->where('name', 'like', '%София%')->value('id');
        }

        return null;
    }

    /** @param  array{max: int, quality: int}  $config */
    private function downloadCompanyImage(Company $company, string $url, string $type, array $config): ?string
    {
        try {
            $binary = $this->client->download($url);
            $path = $this->imageProcessor->processSingleBinary(
                $binary,
                "companies/{$company->id}/mobile-bg-{$type}",
                $config,
            );

            $existing = $type === 'logo' ? $company->logo : $company->cover_image;

            if ($existing && ! str_starts_with($existing, 'http')) {
                Storage::disk('public')->delete($existing);
            }

            return $path;
        } catch (\Throwable) {
            return null;
        }
    }
}