<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Company;
use App\Models\Listing;
use App\Models\ListingPriceChange;
use App\Models\Role;
use App\Models\User;
use App\Models\VehicleBrand;
use App\Services\ListingShowService;
use App\Services\MobileBg\MobileBgAdData;
use App\Services\MobileBg\MobileBgImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class ListingShowCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\CatalogSeeder::class);
        Cache::flush();
    }

    #[Test]
    public function test_listing_show_cache_is_invalidated_when_listing_is_updated(): void
    {
        $listing = $this->createListing(ListingStatus::Published, price: 10000);

        $this->get(route('listings.show', $listing))->assertOk()->assertSee('10,000');

        $listing->update(['price' => 15000]);

        $this->get(route('listings.show', $listing))->assertOk()->assertSee('15,000');
    }

    #[Test]
    public function test_listing_show_cache_is_invalidated_when_price_change_is_recorded(): void
    {
        $listing = $this->createListing(ListingStatus::Published, price: 12000);

        app(ListingShowService::class)->cachedShowData($listing);

        ListingPriceChange::query()->create([
            'listing_id' => $listing->id,
            'old_price' => 12000,
            'new_price' => 11500,
            'created_at' => now(),
        ]);

        $this->assertFalse(Cache::has('listing:show:'.$listing->id.':'.app()->getLocale()));
    }

    #[Test]
    public function test_user_panel_update_purges_listing_show_cache(): void
    {
        $owner = User::factory()->create();
        $listing = $this->createListing(ListingStatus::Published, $owner, price: 10000);

        $this->get(route('listings.show', $listing))->assertOk()->assertSee('10,000');

        $this->actingAs($owner)
            ->put(route('listings.update', $listing), $this->listingUpdatePayload($listing, 18500))
            ->assertRedirect(route('listings.show', $listing));

        $this->get(route('listings.show', $listing))->assertOk()->assertSee('18,500');
    }

    #[Test]
    public function test_admin_panel_update_purges_listing_show_cache(): void
    {
        Role::query()->firstOrCreate(['slug' => Role::ADMIN], ['name' => 'Administrator']);

        $admin = User::factory()->admin()->create();
        $listing = $this->createListing(ListingStatus::Published, price: 10000);

        $this->get(route('listings.show', $listing))->assertOk()->assertSee('10,000');

        $this->actingAs($admin)
            ->put(route('admin.listings.update', $listing), $this->listingUpdatePayload($listing, 22000))
            ->assertRedirect(route('admin.listings.edit', $listing));

        $this->get(route('listings.show', $listing))->assertOk()->assertSee('22,000');
    }

    #[Test]
    public function test_mobile_bg_import_purges_listing_show_cache(): void
    {
        $owner = User::factory()->create();
        $company = Company::query()->create([
            'user_id' => $owner->id,
            'name' => 'Import Dealer',
            'slug' => 'import-dealer',
            'region_id' => (int) \App\Models\Region::query()->value('id'),
            'city' => 'Sofia',
        ]);

        $brand = VehicleBrand::query()->firstOrFail();
        $model = $brand->models()->firstOrFail();

        $listing = Listing::query()->create([
            'user_id' => $owner->id,
            'company_id' => $company->id,
            'external_id' => 'mb-12345',
            'brand_id' => $brand->id,
            'model_id' => $model->id,
            'region_id' => $company->region_id,
            'city' => 'Sofia',
            'title' => 'Imported listing',
            'slug' => 'imported-listing-'.Str::lower(Str::random(8)),
            'status' => ListingStatus::Published,
            'price' => 14000,
            'year' => 2019,
            'published_at' => now(),
            'description' => 'Old import description',
        ]);

        $this->get(route('listings.show', $listing))->assertOk()->assertSee('Old import description');

        $ad = new MobileBgAdData(
            externalId: 'mb-12345',
            url: 'https://import-dealer.mobile.bg/obiava-12345',
            brandName: $brand->name,
            modelName: $model->name,
            price: 13500,
            year: 2019,
            description: 'Fresh mobile.bg description',
        );

        $method = new ReflectionMethod(MobileBgImporter::class, 'upsertListing');
        $method->setAccessible(true);
        $method->invoke(app(MobileBgImporter::class), $company, $ad, false);

        $this->get(route('listings.show', $listing))->assertOk()
            ->assertSee('Fresh mobile.bg description')
            ->assertSee('13,500');
    }

    #[Test]
    public function test_draft_listing_show_is_not_cached(): void
    {
        $owner = User::factory()->create();
        $listing = $this->createListing(ListingStatus::Draft, $owner, price: 8000);

        $this->actingAs($owner)
            ->get(route('listings.show', $listing))
            ->assertOk()
            ->assertSee('8,000');

        $this->assertFalse(Cache::has('listing:show:'.$listing->id.':'.app()->getLocale()));
    }

    /**
     * @return array<string, mixed>
     */
    private function listingUpdatePayload(Listing $listing, int $price): array
    {
        return [
            'brand_id' => $listing->brand_id,
            'model_id' => $listing->model_id,
            'price' => $price,
            'currency' => 'EUR',
            'year' => $listing->year,
            'location_type' => 'bg',
            'region_id' => $listing->region_id,
            'city' => $listing->city ?? 'Sofia',
            'description' => $listing->description,
        ];
    }

    private function createListing(ListingStatus $status, ?User $user = null, int $price = 10000): Listing
    {
        $user ??= User::factory()->create();

        return Listing::query()->create([
            'user_id' => $user->id,
            'brand_id' => (int) \App\Models\VehicleBrand::query()->value('id'),
            'model_id' => (int) \App\Models\VehicleModel::query()->value('id'),
            'region_id' => (int) \App\Models\Region::query()->value('id'),
            'city' => 'Sofia',
            'title' => 'Test listing '.Str::random(4),
            'slug' => 'test-listing-'.Str::lower(Str::random(8)),
            'status' => $status,
            'price' => $price,
            'year' => 2020,
            'published_at' => $status === ListingStatus::Published ? now() : now()->subMonth(),
            'archived_at' => $status->isInactive() ? now() : null,
        ]);
    }
}