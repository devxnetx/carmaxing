<?php

namespace App\Services\MobileBg;

use App\Enums\ListingStatus;
use App\Support\HtmlToPlainText;
use App\Models\Company;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\MobileBgImportRun;
use App\Services\ImageProcessor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MobileBgImporter
{
    private const MAX_IMAGES = 20;

    public function __construct(
        private readonly MobileBgClient $client,
        private readonly MobileBgScraper $scraper,
        private readonly MobileBgCatalogMapper $catalog,
        private readonly ImageProcessor $imageProcessor,
    ) {}

    public function run(MobileBgImportRun $run, bool $syncImages = true): MobileBgImportRun
    {
        $company = $run->company;
        $errors = [];
        $created = 0;
        $updated = 0;
        $failed = 0;

        $run->update([
            'status' => MobileBgImportRun::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        try {
            $refs = $this->scraper->collectListingRefs($run->source_url);
            $run->update(['total_found' => count($refs)]);

            foreach ($refs as $index => $ref) {
                try {
                    if ($index > 0) {
                        usleep(400_000);
                    }

                    $ad = $this->scraper->scrapeAd($ref['url']);
                    $result = $this->upsertListing($company, $ad, $syncImages);

                    if ($result === 'created') {
                        $created++;
                    } else {
                        $updated++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $errors[] = [
                        'external_id' => $ref['external_id'],
                        'url' => $ref['url'],
                        'message' => $e->getMessage(),
                    ];
                }
            }

            $company->update([
                'mobile_bg_url' => $run->source_url,
                'mobile_bg_last_sync_at' => now(),
            ]);

            $run->update([
                'status' => MobileBgImportRun::STATUS_COMPLETED,
                'created_count' => $created,
                'updated_count' => $updated,
                'failed_count' => $failed,
                'errors' => $errors ?: null,
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $run->update([
                'status' => MobileBgImportRun::STATUS_FAILED,
                'failed_count' => max(1, $failed),
                'errors' => array_merge($errors, [['message' => $e->getMessage()]]),
                'completed_at' => now(),
            ]);
        }

        return $run->fresh();
    }

    private function upsertListing(Company $company, MobileBgAdData $ad, bool $syncImages): string
    {
        return DB::transaction(function () use ($company, $ad, $syncImages) {
            $brand = $this->catalog->resolveBrand($ad->brandName);
            $model = $this->catalog->resolveModel($brand, $ad->modelName);

            $listing = Listing::query()
                ->where('company_id', $company->id)
                ->where('external_id', $ad->externalId)
                ->first();

            $isNew = $listing === null;

            if ($isNew) {
                $listing = new Listing;
                $listing->user_id = $company->user_id;
                $listing->company_id = $company->id;
                $listing->external_id = $ad->externalId;
                $listing->status = ListingStatus::Published;
                $listing->published_at = now();
            }

            $listing->fill([
                'brand_id' => $brand->id,
                'model_id' => $model->id,
                'car_variant' => $ad->variant,
                'description' => HtmlToPlainText::sanitize($ad->description),
                'price_on_request' => $ad->priceOnRequest,
                'price' => $ad->priceOnRequest ? 0 : ($ad->price ?? 0),
                'currency' => $ad->currency,
                'year' => $ad->year > 0 ? $ad->year : (int) date('Y'),
                'month' => $ad->month,
                'mileage' => $ad->mileage,
                'fuel_type' => $ad->fuelType,
                'engine_power_hp' => $ad->enginePowerHp,
                'engine_displacement_cc' => $ad->engineDisplacementCc,
                'transmission' => $ad->transmission,
                'body_type' => $ad->bodyType,
                'color_exterior' => $ad->colorExterior,
                'euro_standard' => $ad->euroStandard,
                'city' => $ad->city ?? $company->city,
                'region_id' => $this->catalog->resolveRegion($ad->regionName) ?? $company->region_id,
                'country_code' => null,
                'condition' => 'used',
            ]);

            if ($isNew && empty($listing->slug)) {
                $base = Str::slug($listing->composeDisplayTitle() ?: $ad->title ?: 'listing');
                $listing->slug = $base.'-'.Str::random(6);
            }

            $listing->save();

            if ($syncImages && $ad->imageUrls !== []) {
                $this->syncImages($listing, $ad->imageUrls);
            }

            return $isNew ? 'created' : 'updated';
        });
    }

    /**
     * @param  list<string>  $imageUrls
     */
    private function syncImages(Listing $listing, array $imageUrls): void
    {
        $imageUrls = array_slice(array_values(array_unique($imageUrls)), 0, self::MAX_IMAGES);

        foreach ($listing->images as $image) {
            $image->deleteFiles();
            $image->delete();
        }

        foreach ($imageUrls as $index => $url) {
            try {
                $binary = $this->client->download($url);
                $processed = $this->imageProcessor->processBinary(
                    $binary,
                    "listings/{$listing->id}",
                    config('images.listing'),
                );

                ListingImage::query()->create([
                    'listing_id' => $listing->id,
                    ...$processed,
                    'sort_order' => $index,
                    'is_primary' => $index === 0,
                ]);
            } catch (\Throwable) {
                continue;
            }
        }
    }
}