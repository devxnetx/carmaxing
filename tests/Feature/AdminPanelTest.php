<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\ListingStatus;
use App\Models\Company;
use App\Models\CompanyApiKey;
use App\Models\Listing;
use App\Models\ListingReport;
use App\Models\Role;
use App\Models\User;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::query()->firstOrCreate(['slug' => Role::ADMIN], ['name' => 'Administrator']);
        Role::query()->firstOrCreate(['slug' => Role::MEMBER], ['name' => 'Member']);
    }

    public function test_guest_cannot_access_admin(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_non_admin_user_is_forbidden(): void
    {
        $user = User::factory()->create([
            'account_type' => AccountType::Private,
            'onboarding_completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_can_view_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('admin.dashboard_heading'));
    }

    public function test_admin_can_update_company_profile(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create([
            'account_type' => AccountType::Company,
            'onboarding_completed_at' => now(),
        ]);
        $company = Company::query()->create([
            'user_id' => $owner->id,
            'name' => 'Old Dealer',
            'slug' => 'old-dealer',
            'phone' => '+359888123456',
            'email' => 'old@dealer.test',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.companies.update', $company), [
                'name' => 'Updated Dealer',
                'phone' => '888654321',
                'email' => 'new@dealer.test',
            ])
            ->assertRedirect();

        $company->refresh();
        $this->assertSame('Updated Dealer', $company->name);
        $this->assertSame('+359888654321', $company->phone);
        $this->assertSame('new@dealer.test', $company->email);
    }

    public function test_admin_can_generate_company_api_key(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create([
            'account_type' => AccountType::Company,
            'onboarding_completed_at' => now(),
        ]);
        $company = Company::query()->create([
            'user_id' => $owner->id,
            'name' => 'Key Dealer',
            'slug' => 'key-dealer',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.companies.api-keys.generate', $company))
            ->assertRedirect()
            ->assertSessionHas('new_api_key');

        $this->assertTrue($company->apiKeys()->where('is_active', true)->exists());
    }

    public function test_admin_can_toggle_company_verification(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create([
            'account_type' => AccountType::Company,
            'onboarding_completed_at' => now(),
        ]);
        $company = Company::query()->create([
            'user_id' => $owner->id,
            'name' => 'Test Dealer',
            'slug' => 'test-dealer',
            'is_verified' => false,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.companies.verification', $company), ['is_verified' => true])
            ->assertRedirect();

        $company->refresh();
        $this->assertTrue($company->isVerifiedDealer());
    }

    public function test_admin_can_revoke_api_key(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create([
            'account_type' => AccountType::Company,
            'onboarding_completed_at' => now(),
        ]);
        $company = Company::query()->create([
            'user_id' => $owner->id,
            'name' => 'API Dealer',
            'slug' => 'api-dealer',
        ]);
        $apiKey = CompanyApiKey::query()->create([
            'company_id' => $company->id,
            'name' => 'Test key',
            'key_prefix' => 'ac_testprefix',
            'key_hash' => hash('sha256', 'ac_test'),
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.api-keys.revoke', $apiKey))
            ->assertRedirect();

        $this->assertFalse($apiKey->fresh()->is_active);
    }

    public function test_admin_can_archive_listing(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create(['onboarding_completed_at' => now()]);
        $brand = VehicleBrand::query()->create(['name' => 'Test', 'slug' => 'test']);
        $model = VehicleModel::query()->create(['brand_id' => $brand->id, 'name' => 'Model', 'slug' => 'model']);
        $listing = Listing::query()->create([
            'user_id' => $owner->id,
            'brand_id' => $brand->id,
            'model_id' => $model->id,
            'title' => 'Test listing',
            'slug' => 'test-listing-admin',
            'status' => ListingStatus::Published,
            'price' => 10000,
            'currency' => 'EUR',
            'year' => 2020,
            'published_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.listings.archive', $listing))
            ->assertRedirect();

        $this->assertSame(ListingStatus::Archived, $listing->fresh()->status);
    }

    public function test_admin_can_resolve_report(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create(['onboarding_completed_at' => now()]);
        $brand = VehicleBrand::query()->create(['name' => 'Report', 'slug' => 'report']);
        $model = VehicleModel::query()->create(['brand_id' => $brand->id, 'name' => 'Car', 'slug' => 'car']);
        $listing = Listing::query()->create([
            'user_id' => $owner->id,
            'brand_id' => $brand->id,
            'model_id' => $model->id,
            'title' => 'Reported listing',
            'slug' => 'reported-listing',
            'status' => ListingStatus::Published,
            'price' => 5000,
            'currency' => 'EUR',
            'year' => 2018,
            'published_at' => now(),
        ]);
        $report = ListingReport::query()->create([
            'listing_id' => $listing->id,
            'reason' => 'scam',
            'details' => 'Suspicious offer',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.reports.resolve', $report), [
                'status' => 'resolved',
                'admin_notes' => 'Checked and archived.',
            ])
            ->assertRedirect();

        $report->refresh();
        $this->assertSame('resolved', $report->status);
        $this->assertSame($admin->id, $report->reviewed_by);
    }
}