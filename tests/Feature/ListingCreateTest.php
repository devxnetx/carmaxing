<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\Region;
use App\Models\User;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_listings_create(): void
    {
        $response = $this->get('/listings/create');

        $response->assertRedirect();
    }

    public function test_authenticated_user_can_open_listing_create_form(): void
    {
        $this->seed(\Database\Seeders\CatalogSeeder::class);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/listings/create');

        $response->assertOk();
        $response->assertSee(__('messages.new_listing'), false);
        $response->assertSee(__('messages.form_section_general'), false);
        $response->assertSee(__('messages.form_next'), false);
        $response->assertSee('name="brand_id"', false);
        $response->assertSee(__('messages.car_variant'), false);
        $response->assertSee('name="year"', false);
        $response->assertSee('name="mileage"', false);
        $response->assertSee('name="city"', false);
        $response->assertSee('name="price_on_request"', false);
        $response->assertSee('value="0"', false);
        $response->assertSee('value="1"', false);
        $response->assertDontSee('name="price_negotiable"', false);
        $response->assertSee('София', false);
        $response->assertSee('listingPhotos', false);
        $response->assertSee('richTextEditor', false);
        $response->assertSee('listingFormWizard', false);
        $response->assertSee('data-step="0"', false);
        $response->assertSee('data-step="1"', false);
        $response->assertSee(__('messages.form_step_general'), false);
        $response->assertSee('data-step="3"', false);
        $response->assertDontSee('name="month"', false);
        $response->assertDontSee('name="ad_name"', false);
        $response->assertDontSee('type="date"', false);
        $response->assertSee('datePicker', false);
        $response->assertSee(__('messages.date_placeholder'), false);
        $response->assertSee('name="warranty_until"', false);
        $response->assertSee('name="first_registration_date"', false);
        $response->assertDontSee('dateSelect', false);
        $response->assertDontSee(__('messages.date_day'), false);
    }

    public function test_listing_form_saves_bulgarian_city_and_safe_description(): void
    {
        $this->seed(\Database\Seeders\CatalogSeeder::class);

        $user = User::factory()->create();
        $model = VehicleModel::query()->firstOrFail();
        $region = Region::query()->where('slug', 'sofia-grad')->firstOrFail();

        $response = $this->actingAs($user)->post('/listings', [
            'brand_id' => $model->brand_id,
            'model_id' => $model->id,
            'price' => 15000,
            'currency' => 'EUR',
            'year' => 2018,
            'month' => 6,
            'location_type' => 'bg',
            'city' => $region->id.'::София',
            'description' => '<p>Hello <strong>car</strong> <a href="https://example.com/car" onclick="alert(1)">site</a></p><script>alert(1)</script><div>Second line</div>',
            'warranty_until' => '2030-05-01',
            'first_registration_date' => '2016-07-15',
            'photo_order_submitted' => '1',
        ]);

        $response->assertRedirect();

        $listing = Listing::query()->first();
        $this->assertNotNull($listing);
        $this->assertSame($region->id, $listing->region_id);
        $this->assertSame('София', $listing->city);
        $this->assertNull($listing->country_code);
        $this->assertNull($listing->month);
        $this->assertSame(2018, (int) $listing->year);
        $this->assertSame('2030-05-01', $listing->warranty_until?->toDateString());
        $this->assertSame('2016-07-15', $listing->first_registration_date?->toDateString());
        $this->assertStringContainsString('<strong>car</strong>', (string) $listing->description);
        $this->assertStringContainsString('href="https://example.com/car"', (string) $listing->description);
        $this->assertStringContainsString('<p>Second line</p>', (string) $listing->description);
        $this->assertStringNotContainsString('alert', (string) $listing->description);
        $this->assertStringNotContainsString('onclick', (string) $listing->description);
    }

    public function test_price_on_request_stores_zero_price(): void
    {
        $this->seed(\Database\Seeders\CatalogSeeder::class);

        $user = User::factory()->create();
        $model = VehicleModel::query()->firstOrFail();
        $region = Region::query()->where('slug', 'sofia-grad')->firstOrFail();

        $this->actingAs($user)->post('/listings', [
            'brand_id' => $model->brand_id,
            'model_id' => $model->id,
            'price_on_request' => '1',
            'price' => 15000,
            'price_negotiable' => '1',
            'currency' => 'EUR',
            'year' => 2018,
            'location_type' => 'bg',
            'city' => $region->id.'::София',
            'photo_order_submitted' => '1',
        ])->assertRedirect();

        $listing = Listing::query()->first();
        $this->assertTrue($listing->price_on_request);
        $this->assertSame(0, (int) $listing->price);
        $this->assertFalse($listing->price_negotiable);
    }

    public function test_other_country_hides_bulgarian_city(): void
    {
        $this->seed(\Database\Seeders\CatalogSeeder::class);

        $user = User::factory()->create();
        $model = VehicleModel::query()->firstOrFail();

        $this->actingAs($user)->post('/listings', [
            'brand_id' => $model->brand_id,
            'model_id' => $model->id,
            'price' => 9000,
            'currency' => 'EUR',
            'year' => 2020,
            'location_type' => 'abroad',
            'city' => '1::София',
            'country_code' => 'DE',
            'photo_order_submitted' => '1',
        ])->assertRedirect();

        $listing = Listing::query()->first();
        $this->assertSame('DE', $listing->country_code);
        $this->assertNull($listing->city);
        $this->assertNull($listing->region_id);
    }

    public function test_photo_order_reorders_and_clear_removes_images(): void
    {
        $this->seed(\Database\Seeders\CatalogSeeder::class);

        $user = User::factory()->create();
        $model = VehicleModel::query()->firstOrFail();
        $region = Region::query()->where('slug', 'sofia-grad')->firstOrFail();
        $listing = Listing::query()->create([
            'user_id' => $user->id,
            'brand_id' => $model->brand_id,
            'model_id' => $model->id,
            'region_id' => $region->id,
            'city' => 'София',
            'title' => 'Photo order car',
            'slug' => 'photo-order-car',
            'status' => 'published',
            'price' => 10000,
            'currency' => 'EUR',
            'year' => 2019,
        ]);
        $first = ListingImage::query()->create([
            'listing_id' => $listing->id,
            'path' => 'https://example.com/first.jpg',
            'sort_order' => 1,
            'is_primary' => true,
        ]);
        $second = ListingImage::query()->create([
            'listing_id' => $listing->id,
            'path' => 'https://example.com/second.jpg',
            'sort_order' => 2,
            'is_primary' => false,
        ]);

        $payload = [
            'brand_id' => $model->brand_id,
            'model_id' => $model->id,
            'price' => 10000,
            'currency' => 'EUR',
            'year' => 2019,
            'location_type' => 'bg',
            'city' => $region->id.'::София',
            'photo_order_submitted' => '1',
            'photo_order' => ['e-'.$second->id, 'e-'.$first->id],
        ];

        $this->actingAs($user)
            ->put(route('listings.update', $listing), $payload)
            ->assertRedirect();

        $first->refresh();
        $second->refresh();
        $this->assertSame(1, (int) $second->sort_order);
        $this->assertTrue($second->is_primary);
        $this->assertSame(2, (int) $first->sort_order);
        $this->assertFalse($first->is_primary);

        $payload['photo_order'] = [];
        $payload['photos_cleared'] = '1';

        $this->actingAs($user)
            ->put(route('listings.update', $listing), $payload)
            ->assertRedirect();

        $this->assertSame(0, $listing->images()->count());
    }

    public function test_listings_create_does_not_resolve_as_listing_slug(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/listings/create');

        $response->assertOk();
        $response->assertDontSee('404', false);
    }
}