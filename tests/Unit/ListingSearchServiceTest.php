<?php

namespace Tests\Unit;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\User;
use App\Models\VehicleFeature;
use App\Services\ListingSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tests\TestCase;

class ListingSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_feature_filter_requires_all_selected_features(): void
    {
        $this->seed(\Database\Seeders\CatalogSeeder::class);

        $featureA = VehicleFeature::query()->first();
        $featureB = VehicleFeature::query()->skip(1)->first();

        $listingA = $this->createPublishedListing([$featureA->id]);
        $listingB = $this->createPublishedListing([$featureA->id, $featureB->id]);

        $service = app(ListingSearchService::class);

        $singleFeature = $service->count(Request::create('/search', 'GET', [
            'features' => [$featureA->id],
        ]));

        $bothFeatures = $service->count(Request::create('/search', 'GET', [
            'features' => [$featureA->id, $featureB->id],
        ]));

        $this->assertSame(2, $singleFeature);
        $this->assertSame(1, $bothFeatures);
        $this->assertTrue($bothFeatures < $singleFeature);
    }

    /** @param array<int> $featureIds */
    private function createPublishedListing(array $featureIds): Listing
    {
        $user = User::factory()->create();

        $listing = Listing::query()->create([
            'user_id' => $user->id,
            'brand_id' => $this->brandId(),
            'model_id' => $this->modelId(),
            'region_id' => $this->regionId(),
            'title' => 'Test listing '.Str::random(4),
            'slug' => 'test-listing-'.Str::lower(Str::random(8)),
            'status' => ListingStatus::Published,
            'price' => 10000,
            'year' => 2020,
            'published_at' => now(),
        ]);

        $listing->features()->sync($featureIds);

        return $listing;
    }

    private function brandId(): int
    {
        return (int) \App\Models\VehicleBrand::query()->value('id');
    }

    private function modelId(): int
    {
        return (int) \App\Models\VehicleModel::query()->value('id');
    }

    private function regionId(): int
    {
        return (int) \App\Models\Region::query()->value('id');
    }
}