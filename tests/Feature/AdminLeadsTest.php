<?php

namespace Tests\Feature;

use App\Enums\LeadContactedStatus;
use App\Mail\LeadInviteMail;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use App\Services\Leads\LeadExtractionService;
use App\Services\MobileBg\MobileBgProfileData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminLeadsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::query()->firstOrCreate(['slug' => Role::ADMIN], ['name' => 'Administrator']);
        Role::query()->firstOrCreate(['slug' => Role::MEMBER], ['name' => 'Member']);
    }

    public function test_guest_cannot_access_leads(): void
    {
        $this->get(route('admin.leads.index'))->assertRedirect(route('login'));
    }

    public function test_leads_default_sort_is_most_cars_first(): void
    {
        $admin = User::factory()->admin()->create();

        Lead::query()->create([
            'mobile_bg_url' => 'https://small.mobile.bg',
            'name' => 'Small Dealer',
            'slug' => 'small-dealer',
            'listings_count' => 5,
        ]);
        Lead::query()->create([
            'mobile_bg_url' => 'https://big.mobile.bg',
            'name' => 'Big Dealer',
            'slug' => 'big-dealer',
            'listings_count' => 120,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.leads.index'));

        $response->assertOk();
        $this->assertTrue(
            strpos($response->getContent(), 'Big Dealer') < strpos($response->getContent(), 'Small Dealer')
        );
    }

    public function test_admin_can_view_leads_index(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.leads.index'))
            ->assertOk()
            ->assertSee(__('admin.nav_leads'));
    }

    public function test_admin_can_update_lead_email(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = Lead::query()->create([
            'mobile_bg_url' => 'https://demo-dealer.mobile.bg',
            'name' => 'Demo Dealer',
            'slug' => 'demo-dealer',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.leads.update', $lead), [
                'email' => 'dealer@example.com',
            ])
            ->assertRedirect();

        $this->assertSame('dealer@example.com', $lead->fresh()->email);
    }

    public function test_admin_can_send_lead_invite_email(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();
        $lead = Lead::query()->create([
            'mobile_bg_url' => 'https://demo-dealer.mobile.bg',
            'name' => 'Demo Dealer',
            'slug' => 'demo-dealer',
            'email' => 'dealer@example.com',
            'contacted_status' => LeadContactedStatus::PendingInvite,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.leads.send-invite', $lead))
            ->assertRedirect();

        Mail::assertSent(LeadInviteMail::class, function (LeadInviteMail $mail) use ($lead) {
            return $mail->hasTo('dealer@example.com') && $mail->lead->is($lead);
        });

        $lead->refresh();
        $this->assertSame(LeadContactedStatus::EmailSent, $lead->contacted_status);
        $this->assertNotNull($lead->contacted_at);
    }

    public function test_admin_can_trigger_car_count_refresh(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.leads.refresh-counts'))
            ->assertRedirect();

        Queue::assertPushed(\App\Jobs\SyncLeadListingCounts::class);
    }

    public function test_send_invite_requires_email(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();
        $lead = Lead::query()->create([
            'mobile_bg_url' => 'https://no-email.mobile.bg',
            'name' => 'No Email Dealer',
            'slug' => 'no-email-dealer',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.leads.send-invite', $lead))
            ->assertRedirect()
            ->assertSessionHas('error');

        Mail::assertNothingSent();
    }

    public function test_lead_matches_existing_company_as_onboarded(): void
    {
        $owner = User::factory()->create(['onboarding_completed_at' => now()]);
        $company = Company::query()->create([
            'user_id' => $owner->id,
            'name' => 'Existing Dealer',
            'slug' => 'existing-dealer',
            'mobile_bg_url' => 'https://existing-dealer.mobile.bg',
        ]);

        $run = \App\Models\LeadExtractionRun::query()->create([
            'source_url' => 'https://www.mobile.bg/dealers/location-grad-sofiya',
            'city_slug' => 'grad-sofiya',
            'city_label' => 'Sofiya',
            'status' => 'running',
        ]);

        $service = app(LeadExtractionService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('upsertLead');
        $method->setAccessible(true);

        $profile = new MobileBgProfileData(
            sourceUrl: 'https://existing-dealer.mobile.bg',
            name: 'Existing Dealer',
            phone: '0888123456',
            city: 'София',
        );

        $method->invoke($service, $run, $profile);

        $lead = Lead::query()->where('mobile_bg_url', 'https://existing-dealer.mobile.bg')->first();

        $this->assertNotNull($lead);
        $this->assertSame($company->id, $lead->company_id);
        $this->assertTrue($lead->isOnboarded());
    }
}