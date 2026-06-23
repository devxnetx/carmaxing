<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\User;
use App\Services\NewestListingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NewestListingsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_newest_page_is_available(): void
    {
        $this->get(route('listings.newest'))
            ->assertOk()
            ->assertSee(__('messages.newest_cars'));
    }

    #[Test]
    public function test_newest_service_prefers_recent_window_when_available(): void
    {
        $this->seed(\Database\Seeders\CatalogSeeder::class);

        $recent = $this->createPublishedListing(now()->subDay());
        $this->createPublishedListing(now()->subDays(10));

        $ids = app(NewestListingsService::class)->preview(12)->pluck('id')->all();

        $this->assertTrue(app(NewestListingsService::class)->usesRecentWindow());
        $this->assertSame([$recent->id], $ids);
    }

    #[Test]
    public function test_newest_service_falls_back_to_latest_when_no_recent_listings(): void
    {
        $this->seed(\Database\Seeders\CatalogSeeder::class);

        $older = $this->createPublishedListing(now()->subDays(10));
        $newer = $this->createPublishedListing(now()->subDays(5));

        $service = app(NewestListingsService::class);

        $this->assertFalse($service->usesRecentWindow());
        $this->assertSame([$newer->id, $older->id], $service->preview(12)->pluck('id')->all());
    }

    private function createPublishedListing(\Illuminate\Support\Carbon $publishedAt): Listing
    {
        $user = User::factory()->create();

        return Listing::query()->create([
            'user_id' => $user->id,
            'brand_id' => (int) \App\Models\VehicleBrand::query()->value('id'),
            'model_id' => (int) \App\Models\VehicleModel::query()->value('id'),
            'region_id' => (int) \App\Models\Region::query()->value('id'),
            'title' => 'Test listing '.Str::random(4),
            'slug' => 'test-listing-'.Str::lower(Str::random(8)),
            'status' => ListingStatus::Published,
            'price' => 10000,
            'year' => 2020,
            'published_at' => $publishedAt,
        ]);
    }
}