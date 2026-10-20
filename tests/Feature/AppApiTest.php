<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Company;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\Region;
use App\Models\User;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AppApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_listings_are_public_and_hide_unpublished_ads(): void
    {
        $this->seed(\Database\Seeders\CatalogSeeder::class);

        $published = $this->createListing(ListingStatus::Published, 'Published car', [
            'ad_name' => 'VisibleToken',
        ]);
        $draft = $this->createListing(ListingStatus::Draft, 'Hidden draft', [
            'ad_name' => 'HiddenToken',
        ]);

        $response = $this->getJson('/api/app/listings?q=VisibleToken')
            ->assertOk()
            ->assertJsonPath('data.0.id', $published->id)
            ->assertJsonPath('data.0.favorited', false)
            ->assertJsonPath('meta.total', 1);

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertFalse($ids->contains($draft->id));
    }

    #[Test]
    public function test_listing_detail_includes_photos_and_seller_phone(): void
    {
        $this->seed(\Database\Seeders\CatalogSeeder::class);

        $listing = $this->createListing(ListingStatus::Published, 'Detail car', [
            'description' => '<p>Clean car</p>',
            'vin' => 'WBA12345678901234',
            'fuel_type' => 'diesel',
        ]);

        $dealer = User::factory()->create();
        $company = Company::query()->create([
            'user_id' => $dealer->id,
            'name' => 'Ratola Motors',
            'slug' => 'ratola-'.Str::lower(Str::random(4)),
            'phone' => '0888000111',
        ]);
        $listing->update(['company_id' => $company->id, 'user_id' => $dealer->id]);

        ListingImage::query()->create([
            'listing_id' => $listing->id,
            'path' => 'listings/'.$listing->id.'/photo_large.webp',
            'path_medium' => 'listings/'.$listing->id.'/photo_medium.webp',
            'path_thumb' => 'listings/'.$listing->id.'/photo_thumb.webp',
            'width' => 1600,
            'height' => 1200,
            'sort_order' => 0,
            'is_primary' => true,
        ]);

        $detail = $this->getJson('/api/app/listings/'.$listing->id)
            ->assertOk()
            ->assertJsonPath('data.description', 'Clean car')
            ->assertJsonPath('data.seller.name', 'Ratola Motors')
            ->assertJsonPath('data.seller.id', $company->id)
            ->assertJsonPath('data.seller.phone', '0888000111')
            ->assertJsonPath('data.vin', 'WBA12345678901234');

        $this->assertStringContainsString(
            'https://proekt-damqnovi.online/storage/',
            (string) $detail->json('data.images.0.medium_url'),
        );
        $this->assertStringContainsString('photo_medium.webp', (string) $detail->json('data.images.0.medium_url'));
        $this->assertStringStartsWith('https://proekt-damqnovi.online/', (string) $detail->json('data.web_url'));

        $this->getJson('/api/app/listings/'.$listing->id.'999')->assertNotFound();
    }

    #[Test]
    public function test_login_returns_a_token_and_logout_revokes_it(): void
    {
        $user = User::factory()->create([
            'email' => 'driver@example.com',
            'password' => 'secret-pass',
        ]);

        $this->postJson('/api/app/login', [
            'email' => 'driver@example.com',
            'password' => 'wrong',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $token = $this->postJson('/api/app/login', [
            'email' => 'driver@example.com',
            'password' => 'secret-pass',
        ])->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', $user->email)
            ->json('token');

        $this->get('/api/app/me')->assertUnauthorized();

        $this->withToken($token)->getJson('/api/app/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);

        $this->withToken($token)->postJson('/api/app/logout')->assertOk();
        $this->assertSame(0, \Laravel\Sanctum\PersonalAccessToken::query()->count());

        // This test process keeps the resolved Sanctum user between calls.
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/app/me')->assertUnauthorized();
    }

    #[Test]
    public function test_favorites_require_login_and_toggle_a_published_ad(): void
    {
        $this->seed(\Database\Seeders\CatalogSeeder::class);

        $user = User::factory()->create(['password' => 'secret-pass']);
        $listing = $this->createListing(ListingStatus::Published, 'Favorite car', [
            'ad_name' => 'FavToken',
        ]);
        $draft = $this->createListing(ListingStatus::Draft, 'Draft car');
        $token = $user->createToken('app')->plainTextToken;

        $this->postJson('/api/app/listings/'.$listing->id.'/favorite')->assertUnauthorized();

        $this->withToken($token)
            ->postJson('/api/app/listings/'.$listing->id.'/favorite')
            ->assertOk()
            ->assertJsonPath('favorited', true);

        $this->withToken($token)
            ->getJson('/api/app/listings?q=FavToken')
            ->assertJsonPath('data.0.favorited', true);

        $this->withToken($token)
            ->getJson('/api/app/favorites')
            ->assertOk()
            ->assertJsonPath('data.0.id', $listing->id)
            ->assertJsonPath('meta.total', 1);

        $this->withToken($token)
            ->postJson('/api/app/listings/'.$listing->id.'/favorite')
            ->assertOk()
            ->assertJsonPath('favorited', false);

        $this->withToken($token)
            ->getJson('/api/app/favorites')
            ->assertJsonPath('meta.total', 0);

        $this->withToken($token)
            ->postJson('/api/app/listings/'.$draft->id.'/favorite')
            ->assertNotFound();
    }

    #[Test]
    public function test_catalog_lists_brands_for_filters(): void
    {
        $this->seed(\Database\Seeders\CatalogSeeder::class);

        $brand = VehicleBrand::query()->whereHas('models')->first();

        $this->getJson('/api/app/catalog')
            ->assertOk()
            ->assertJsonFragment(['id' => $brand->id, 'name' => $brand->name])
            ->assertJsonStructure(['brands', 'regions', 'fuel_types', 'features', 'bulgaria_count']);
    }

    #[Test]
    public function test_catalog_caches_published_car_counts_per_city(): void
    {
        $this->seed(\Database\Seeders\CatalogSeeder::class);

        $sofia = Region::query()->where('slug', 'sofia-grad')->firstOrFail();
        $varna = Region::query()->where('slug', 'varna')->firstOrFail();

        $this->createListing(ListingStatus::Published, 'Sofia one', ['region_id' => $sofia->id]);
        $this->createListing(ListingStatus::Published, 'Sofia two', ['region_id' => $sofia->id]);
        $this->createListing(ListingStatus::Draft, 'Sofia draft', ['region_id' => $sofia->id]);
        $this->createListing(ListingStatus::Published, 'Varna car', [
            'region_id' => $varna->id,
            'city' => 'Варна',
        ]);
        $this->createListing(ListingStatus::Published, 'Chicago car', [
            'country_code' => 'US',
            'city' => 'Chicago',
        ]);

        $payload = $this->getJson('/api/app/catalog')->assertOk()->json();
        $regions = collect($payload['regions']);
        $sofiaCities = collect($regions->firstWhere('slug', 'sofia-grad')['cities']);
        $varnaCities = collect($regions->firstWhere('slug', 'varna')['cities']);

        $this->assertSame(2, $sofiaCities->firstWhere('name', 'София')['count']);
        $this->assertSame(1, $varnaCities->firstWhere('name', 'Варна')['count']);
        $this->assertSame(0, collect($regions->firstWhere('slug', 'blagoevgrad')['cities'])->firstWhere('name', 'Банско')['count']);
        $this->assertSame(2, $regions->firstWhere('slug', 'sofia-grad')['count']);
        $this->assertSame(1, $regions->firstWhere('slug', 'varna')['count']);
        $this->assertSame(0, $regions->firstWhere('slug', 'blagoevgrad')['count']);
        $this->assertSame(3, $payload['bulgaria_count']);
    }

    #[Test]
    public function test_brand_counts_follow_the_selected_region_and_keep_series(): void
    {
        $this->seed(\Database\Seeders\CatalogSeeder::class);

        $bmw = VehicleBrand::query()->where('name', 'BMW')->firstOrFail();
        $series = VehicleModel::query()->where('brand_id', $bmw->id)->where('name', '3 Series')->firstOrFail();
        $model = VehicleModel::query()->where('parent_id', $series->id)->where('name', '320')->firstOrFail();
        $sofia = Region::query()->where('slug', 'sofia-grad')->firstOrFail();
        $varna = Region::query()->where('slug', 'varna')->firstOrFail();

        $this->createListing(ListingStatus::Published, 'Sofia BMW', [
            'brand_id' => $bmw->id,
            'model_id' => $model->id,
            'region_id' => $sofia->id,
        ]);
        $this->createListing(ListingStatus::Published, 'Varna BMW', [
            'brand_id' => $bmw->id,
            'model_id' => $model->id,
            'region_id' => $varna->id,
        ]);
        $this->createListing(ListingStatus::Published, 'German import', [
            'country_code' => 'DE',
            'region_id' => null,
            'city' => null,
        ]);

        $sofiaPayload = $this->getJson('/api/app/catalog/counts?place=bg&region_id='.$sofia->id)
            ->assertOk()
            ->json();
        $sofiaBmw = collect($sofiaPayload['brands'])->firstWhere('name', 'BMW');
        $sofiaSeries = collect($sofiaBmw['models'])->firstWhere('name', '3 Series');
        $sofia320 = collect($sofiaSeries['children'])->firstWhere('name', '320');

        $this->assertSame(1, $sofiaBmw['count']);
        $this->assertSame(1, $sofiaSeries['count']);
        $this->assertSame(1, $sofia320['count']);
        $this->assertSame('series', $sofiaSeries['type']);
        $this->assertSame(1, collect($sofiaPayload['regions'])->firstWhere('slug', 'sofia-grad')['count']);

        $countryPayload = $this->getJson('/api/app/catalog/counts?place=bg')->assertOk()->json();
        $this->assertSame(2, collect($countryPayload['brands'])->firstWhere('name', 'BMW')['count']);
        $this->assertSame(1, $countryPayload['abroad_count']);

        $this->getJson('/api/app/listings?location_type=abroad&per_page=5')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    #[Test]
    public function test_company_profile_lists_published_ads_and_dealer_info(): void
    {
        $this->seed(\Database\Seeders\CatalogSeeder::class);

        $dealer = User::factory()->create();
        $region = Region::query()->where('slug', 'sofia-grad')->firstOrFail();
        $company = Company::query()->create([
            'user_id' => $dealer->id,
            'name' => 'Ratola Motors',
            'slug' => 'ratola-profile',
            'description' => '<p>Семеен дилър</p>',
            'logo' => 'companies/ratola/logo.webp',
            'cover_image' => 'companies/ratola/cover.webp',
            'phone' => '0888000111',
            'email' => 'sales@ratola.test',
            'website' => 'https://ratola.example',
            'address' => 'бул. България 1',
            'city' => 'София',
            'region_id' => $region->id,
            'member_since_year' => 2014,
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        $brands = VehicleBrand::query()->whereHas('models')->orderBy('name')->take(2)->get();
        $firstModel = VehicleModel::query()->where('brand_id', $brands[0]->id)->firstOrFail();
        $secondModel = VehicleModel::query()->where('brand_id', $brands[1]->id)->firstOrFail();

        $expensive = $this->createListing(ListingStatus::Published, 'Expensive car', [
            'user_id' => $dealer->id,
            'company_id' => $company->id,
            'brand_id' => $brands[0]->id,
            'model_id' => $firstModel->id,
            'price' => 20000,
            'ad_number' => 1042,
        ]);
        $cheap = $this->createListing(ListingStatus::Published, 'Cheap car', [
            'user_id' => $dealer->id,
            'company_id' => $company->id,
            'brand_id' => $brands[1]->id,
            'model_id' => $secondModel->id,
            'price' => 9000,
            'ad_number' => 1043,
        ]);
        $this->createListing(ListingStatus::Draft, 'Hidden dealer car', [
            'user_id' => $dealer->id,
            'company_id' => $company->id,
            'ad_number' => 1044,
        ]);

        $payload = $this->getJson('/api/app/companies/'.$company->id)
            ->assertOk()
            ->assertJsonPath('company.name', 'Ratola Motors')
            ->assertJsonPath('company.description', 'Семеен дилър')
            ->assertJsonPath('company.email', 'sales@ratola.test')
            ->assertJsonPath('company.phone', '0888000111')
            ->assertJsonPath('company.city', 'София')
            ->assertJsonPath('company.region.id', $region->id)
            ->assertJsonPath('company.member_since_year', 2014)
            ->assertJsonPath('company.is_verified', true)
            ->assertJsonPath('company.listings_count', 2)
            ->assertJsonPath('listings.meta.total', 2)
            ->json();

        $this->assertStringContainsString('https://proekt-damqnovi.online/storage/', (string) $payload['company']['logo_url']);
        $this->assertStringContainsString('cover.webp', (string) $payload['company']['cover_url']);
        $this->assertStringStartsWith('https://proekt-damqnovi.online/dealers/', (string) $payload['company']['web_url']);
        $this->assertCount(2, $payload['brands']);

        $ids = collect($payload['listings']['data'])->pluck('id');
        $this->assertTrue($ids->contains($expensive->id));
        $this->assertTrue($ids->contains($cheap->id));

        $this->getJson('/api/app/companies/'.$company->id.'?brand_id='.$brands[1]->id)
            ->assertOk()
            ->assertJsonPath('listings.meta.total', 1)
            ->assertJsonPath('listings.data.0.id', $cheap->id)
            ->assertJsonPath('company.listings_count', 1);

        $this->getJson('/api/app/companies/'.$company->id.'?ad_number=1042')
            ->assertOk()
            ->assertJsonPath('listings.data.0.id', $expensive->id);

        $this->getJson('/api/app/companies/'.$company->id.'?sort=price_asc')
            ->assertOk()
            ->assertJsonPath('listings.data.0.id', $cheap->id);

        $this->getJson('/api/app/companies/999999')->assertNotFound();
    }

    /** @param  array<string, mixed>  $extra */
    private function createListing(ListingStatus $status, string $title, array $extra = []): Listing
    {
        $brand = VehicleBrand::query()->whereHas('models')->first();
        $model = VehicleModel::query()->where('brand_id', $brand->id)->first();
        $user = User::factory()->create();

        return Listing::query()->create([
            'user_id' => $user->id,
            'brand_id' => $brand->id,
            'model_id' => $model->id,
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
            'status' => $status,
            'price' => 15000,
            'year' => 2019,
            'city' => 'София',
            'published_at' => $status === ListingStatus::Published ? now() : null,
            ...$extra,
        ]);
    }
}
