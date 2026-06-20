<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\ListingStatus;
use App\Models\Company;
use App\Models\CompanyApiKey;
use App\Models\Listing;
use App\Models\User;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocsApiPlaygroundTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_run_playground_request(): void
    {
        $this->postJson(route('docs.api.try'), [
            'api_key' => 'ac_test',
            'method' => 'GET',
            'path' => '/catalog',
        ])->assertRedirect(route('login'));
    }

    public function test_company_user_without_api_key_cannot_run_playground_request(): void
    {
        [$user] = $this->createCompanyUser();

        $this->actingAs($user)
            ->postJson(route('docs.api.try'), [
                'api_key' => 'ac_test',
                'method' => 'GET',
                'path' => '/catalog',
            ])
            ->assertForbidden();
    }

    public function test_company_user_can_run_catalog_with_own_api_key(): void
    {
        [$user, $company] = $this->createCompanyUser();
        $generated = CompanyApiKey::generate('API', $company);

        $response = $this->actingAs($user)
            ->postJson(route('docs.api.try'), [
                'api_key' => $generated['plain_key'],
                'method' => 'GET',
                'path' => '/catalog',
            ])
            ->assertOk()
            ->json('body');

        $this->assertArrayHasKey('brands', $response);
    }

    public function test_playground_rejects_foreign_api_key(): void
    {
        [$user, $company] = $this->createCompanyUser();
        CompanyApiKey::generate('API', $company);

        [$otherUser, $otherCompany] = $this->createCompanyUser('other-dealer');
        $otherKey = CompanyApiKey::generate('API', $otherCompany);

        $this->actingAs($user)
            ->postJson(route('docs.api.try'), [
                'api_key' => $otherKey['plain_key'],
                'method' => 'GET',
                'path' => '/catalog',
            ])
            ->assertForbidden();
    }

    public function test_playground_returns_sample_listing_id(): void
    {
        [$user, $company] = $this->createCompanyUser();
        CompanyApiKey::generate('API', $company);
        $this->createListing($user, $company, 1042);

        $this->actingAs($user)
            ->getJson(route('docs.api.sample-listing'))
            ->assertOk()
            ->assertJson(['listing_id' => '1042']);
    }

    /**
     * @return array{0: User, 1: Company}
     */
    private function createCompanyUser(string $slug = 'test-dealer'): array
    {
        $user = User::factory()->create([
            'account_type' => AccountType::Company,
        ]);

        $company = Company::query()->create([
            'user_id' => $user->id,
            'name' => 'Test Dealer',
            'slug' => $slug,
            'city' => 'София',
        ]);

        return [$user, $company];
    }

    private function createListing(User $user, Company $company, int $adNumber): Listing
    {
        $brand = VehicleBrand::query()->create(['name' => 'Test Brand', 'slug' => 'test-brand-'.$adNumber]);
        $model = VehicleModel::query()->create([
            'brand_id' => $brand->id,
            'name' => 'Test Model',
            'slug' => 'test-model-'.$adNumber,
        ]);

        return Listing::query()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'brand_id' => $brand->id,
            'model_id' => $model->id,
            'title' => 'Test Brand Test Model',
            'slug' => 'test-brand-test-model-'.$adNumber,
            'status' => ListingStatus::Published,
            'price' => 10000,
            'currency' => 'EUR',
            'year' => 2020,
            'ad_number' => $adNumber,
            'published_at' => now(),
        ]);
    }
}