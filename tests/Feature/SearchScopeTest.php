<?php

namespace Tests\Feature;

use App\Models\VehicleBrand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SearchScopeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_search_form_page_is_available(): void
    {
        $this->get(route('search.form'))
            ->assertOk()
            ->assertSee(__('messages.search_form_heading'));
    }

    #[Test]
    public function test_brand_models_api_resolves_vehicle_brand_model(): void
    {
        $brand = VehicleBrand::query()->create([
            'name' => 'BMW',
            'slug' => 'bmw',
        ]);

        $this->getJson(route('brands.models', ['brand' => $brand, 'scope' => 'listings']))
            ->assertOk()
            ->assertJsonPath('brand.id', $brand->id);
    }

    #[Test]
    public function test_import_search_results_page_is_available(): void
    {
        $this->get(route('search.imports'))
            ->assertOk()
            ->assertSee(__('messages.search_imports_subtitle'))
            ->assertSee(__('messages.view_grid'))
            ->assertSee(__('messages.sort_ending_soon'))
            ->assertDontSee('search-scope-tabs', false);
    }

    #[Test]
    public function test_auction_search_results_page_is_available(): void
    {
        $this->get(route('search.auctions'))
            ->assertOk()
            ->assertSee(__('messages.search_scope_auctions'));
    }

    #[Test]
    public function test_search_form_preserves_selected_brand_from_correct_search(): void
    {
        $brand = VehicleBrand::query()->create([
            'name' => 'Audi',
            'slug' => 'audi',
        ]);

        $this->get(route('search.form', [
            'scope' => 'listings',
            'brand_id' => $brand->id,
        ]))
            ->assertOk()
            ->assertSee('value="'.$brand->id.'" selected', false);
    }

    #[Test]
    public function test_auction_search_form_shows_tender_period_filters(): void
    {
        $this->get(route('search.form', ['scope' => 'auctions']))
            ->assertOk()
            ->assertSee(__('messages.tender_period_upcoming'))
            ->assertDontSee('name="brand_id"', false);
    }
}