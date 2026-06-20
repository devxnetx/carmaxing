<?php

namespace App\Services\Leads;

use App\Enums\LeadContactedStatus;
use App\Models\Company;
use App\Models\Lead;
use App\Models\LeadExtractionRun;
use App\Models\Region;
use App\Services\MobileBg\MobileBgClient;
use App\Services\MobileBg\MobileBgDealersDirectoryScraper;
use App\Services\MobileBg\MobileBgProfileData;
use App\Services\MobileBg\MobileBgProfileScraper;
use App\Services\MobileBg\MobileBgScraper;
use App\Support\PhoneNumber;
use Illuminate\Support\Str;

class LeadExtractionService
{
    private const DEALER_SLEEP_SECONDS = 5;

    public function __construct(
        private readonly MobileBgClient $client,
        private readonly MobileBgDealersDirectoryScraper $directoryScraper,
        private readonly MobileBgProfileScraper $profileScraper,
        private readonly MobileBgScraper $listingScraper,
    ) {}

    public function run(LeadExtractionRun $run): LeadExtractionRun
    {
        $errors = [];
        $created = 0;
        $updated = 0;
        $failed = 0;

        $run->update([
            'status' => LeadExtractionRun::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        try {
            $baseUrl = $this->directoryScraper->normalizeDirectoryUrl($run->source_url);
            $firstHtml = $this->client->get($baseUrl);
            $totalPages = $this->directoryScraper->lastPageNumber($firstHtml);
            $dealerUrls = $this->directoryScraper->parseDealerUrls($firstHtml);

            $run->update([
                'total_pages' => $totalPages,
                'current_page' => 1,
            ]);

            for ($page = 2; $page <= $totalPages; $page++) {
                usleep(500_000);
                $html = $this->client->get($this->directoryScraper->pageUrl($baseUrl, $page));
                $dealerUrls = array_values(array_unique(array_merge(
                    $dealerUrls,
                    $this->directoryScraper->parseDealerUrls($html),
                )));
                $run->update(['current_page' => $page]);
            }

            $dealerUrls = array_values(array_unique($dealerUrls));
            $run->update(['total_found' => count($dealerUrls)]);

            foreach ($dealerUrls as $index => $dealerUrl) {
                if ($index > 0) {
                    sleep(self::DEALER_SLEEP_SECONDS);
                }

                try {
                    $profile = $this->profileScraper->scrape($dealerUrl);
                    $listingsCount = $this->listingScraper->countListings($dealerUrl);
                    $result = $this->upsertLead($run, $profile, $listingsCount);

                    if ($result === 'created') {
                        $created++;
                    } else {
                        $updated++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $errors[] = [
                        'url' => $dealerUrl,
                        'message' => $e->getMessage(),
                    ];
                }

                $run->update([
                    'processed_count' => $index + 1,
                    'created_count' => $created,
                    'updated_count' => $updated,
                    'failed_count' => $failed,
                    'errors' => $errors ?: null,
                ]);
            }

            $run->update([
                'status' => LeadExtractionRun::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $run->update([
                'status' => LeadExtractionRun::STATUS_FAILED,
                'failed_count' => $failed + 1,
                'errors' => array_merge($errors, [['message' => $e->getMessage()]]),
                'completed_at' => now(),
            ]);
        }

        return $run->refresh();
    }

    private function upsertLead(LeadExtractionRun $run, MobileBgProfileData $profile, int $listingsCount = 0): string
    {
        $normalizedUrl = $this->client->normalizeDealerUrl($profile->sourceUrl);
        $existing = Lead::query()->where('mobile_bg_url', $normalizedUrl)->first();
        $company = $this->findMatchingCompany($normalizedUrl);
        $regionId = $this->resolveRegionId($profile->regionName, $profile->city);

        $attributes = [
            'lead_extraction_run_id' => $run->id,
            'company_id' => $company?->id,
            'name' => $profile->name,
            'description' => $profile->description,
            'phone' => ($profile->phone && ($local = PhoneNumber::localPart($profile->phone)))
                ? PhoneNumber::fromLocalPart($local)
                : null,
            'website' => $profile->website,
            'address' => $profile->address,
            'city' => $profile->city,
            'source_city' => $run->city_label,
            'region_id' => $regionId,
            'logo' => $profile->logoUrl,
            'cover_image' => $profile->coverUrl,
            'listings_count' => $listingsCount,
            'extracted_at' => now(),
        ];

        if ($existing) {
            $existing->update($attributes);

            return 'updated';
        }

        Lead::query()->create(array_merge($attributes, [
            'mobile_bg_url' => $normalizedUrl,
            'slug' => $this->uniqueSlug($normalizedUrl),
            'contacted_status' => LeadContactedStatus::PendingInvite,
        ]));

        return 'created';
    }

    private function findMatchingCompany(string $mobileBgUrl): ?Company
    {
        $company = Company::query()->where('mobile_bg_url', $mobileBgUrl)->first();

        if ($company) {
            return $company;
        }

        $host = parse_url($mobileBgUrl, PHP_URL_HOST);
        $subdomain = $host ? explode('.', $host)[0] : null;

        if (! $subdomain) {
            return null;
        }

        return Company::query()->where('slug', $subdomain)->first();
    }

    private function uniqueSlug(string $mobileBgUrl): string
    {
        $host = parse_url($mobileBgUrl, PHP_URL_HOST);
        $base = Str::slug($host ? explode('.', $host)[0] : 'lead');
        $slug = $base;
        $suffix = 1;

        while (Lead::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
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
}