<?php

namespace Tests\Feature;

use App\Models\User;
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
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/listings/create');

        $response->assertOk();
        $response->assertSee(__('messages.new_listing'), false);
        $response->assertSee(__('messages.form_section_basic'), false);
        $response->assertSee('name="brand_id"', false);
    }

    public function test_listings_create_does_not_resolve_as_listing_slug(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/listings/create');

        $response->assertOk();
        $response->assertDontSee('404', false);
    }
}