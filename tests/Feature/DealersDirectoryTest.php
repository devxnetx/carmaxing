<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\ListingStatus;
use App\Models\Company;
use App\Models\Listing;
use App\Models\Region;
use App\Models\User;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DealersDirectoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_dealers_page_is_available(): void
    {
        $this->get(route('dealers.index'))
            ->assertOk()
            ->assertSee(__('messages.dealers_page_heading'))
            ->assertSee(__('messages.dealers_nav'))
            ->assertSee('id="dealers-map"', false);
    }

    #[Test]
    public function test_dealers_page_filters_by_region_and_city(): void
    {
        $region = Region::query()->create([
            'slug' => 'sofia-grad',
            'name_bg' => 'София-град',
            'name_en' => 'Sofia City',
            'sort_order' => 1,
        ]);

        $ownerA = User::factory()->create(['account_type' => AccountType::Company]);
        $ownerB = User::factory()->create(['account_type' => AccountType::Company]);

        Company::query()->create([
            'user_id' => $ownerA->id,
            'name' => 'Sofia Motors',
            'slug' => 'sofia-motors',
            'region_id' => $region->id,
            'city' => 'София',
        ]);

        Company::query()->create([
            'user_id' => $ownerB->id,
            'name' => 'Plovdiv Cars',
            'slug' => 'plovdiv-cars',
            'region_id' => $region->id,
            'city' => 'Пловдив',
        ]);

        $this->get(route('dealers.index', ['region_id' => $region->id, 'city' => 'София']))
            ->assertOk()
            ->assertSee('Sofia Motors')
            ->assertDontSee('Plovdiv Cars');
    }

    #[Test]
    public function test_dealer_cities_api_returns_counts(): void
    {
        $region = Region::query()->create([
            'slug' => 'varna',
            'name_bg' => 'Варна',
            'name_en' => 'Varna',
            'sort_order' => 2,
        ]);

        $owner = User::factory()->create(['account_type' => AccountType::Company]);
        Company::query()->create([
            'user_id' => $owner->id,
            'name' => 'Varna Dealer',
            'slug' => 'varna-dealer',
            'region_id' => $region->id,
            'city' => 'Варна',
        ]);

        $this->getJson(route('regions.dealer-cities', $region))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Варна', 'count' => 1]);
    }

    #[Test]
    public function test_individual_dealer_profile_route_still_works(): void
    {
        $owner = User::factory()->create(['account_type' => AccountType::Company]);
        $company = Company::query()->create([
            'user_id' => $owner->id,
            'name' => 'Profile Dealer',
            'slug' => 'profile-dealer',
        ]);

        $this->get(route('company.show', $company))
            ->assertOk()
            ->assertSee('Profile Dealer');
    }

    #[Test]
    public function test_header_uses_short_newest_label(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(__('messages.newest_nav'))
            ->assertSee(__('messages.dealers_nav'));
    }
}