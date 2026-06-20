<?php

namespace Tests\Feature;

use App\Enums\TenderBidStatus;
use App\Enums\TenderStatus;
use App\Models\Role;
use App\Models\Tender;
use App\Models\User;
use App\Models\Region;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Services\PlatformSettings;
use App\Services\Tenders\TenderLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::query()->firstOrCreate(['slug' => Role::ADMIN], ['name' => 'Administrator']);
        Role::query()->firstOrCreate(['slug' => Role::MEMBER], ['name' => 'Member']);

        $this->seedCatalog();
    }

    public function test_tenders_are_hidden_when_feature_disabled(): void
    {
        app(PlatformSettings::class)->setTendersEnabled(false);

        $this->get(route('tenders.index'))->assertNotFound();
    }

    public function test_admin_can_enable_tenders(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), ['tenders_enabled' => '1'])
            ->assertRedirect(route('admin.settings.index'));

        $this->assertTrue(app(PlatformSettings::class)->tendersEnabled());
    }

    public function test_seller_can_create_tender(): void
    {
        $this->enableTenders();
        $seller = User::factory()->create();

        $response = $this->actingAs($seller)->post(route('my.tenders.store'), [
            'brand_id' => $this->brandId(),
            'model_id' => $this->modelId(),
            'year' => 2020,
            'location_type' => 'bg',
            'region_id' => $this->regionId(),
            'city' => 'Sofia',
            'starting_price' => 5000,
            'bid_increment' => 100,
            'duration_days' => 7,
        ]);

        $tender = Tender::query()->first();
        $this->assertNotNull($tender);
        $response->assertRedirect(route('my.tenders.manage', $tender));
        $this->assertSame(TenderStatus::Active, $tender->status);
        $this->assertSame(7, $tender->duration_days);
    }

    public function test_bidder_can_place_and_revoke_bid(): void
    {
        $this->enableTenders();

        $seller = User::factory()->create();
        $bidder = User::factory()->create();
        $tender = $this->createTenderFor($seller);

        $this->actingAs($bidder)
            ->post(route('tenders.accept-rules'))
            ->assertOk();

        $this->actingAs($bidder)
            ->postJson(route('tenders.bids.store', $tender), ['amount' => 5000])
            ->assertOk()
            ->assertJsonPath('state.current_high_bid', 5000);

        $bid = $tender->bids()->first();
        $this->assertSame(TenderBidStatus::Active, $bid->status);

        $this->actingAs($bidder)
            ->deleteJson(route('tenders.bids.destroy', [$tender, $bid]))
            ->assertOk()
            ->assertJsonPath('state.current_high_bid', null);
    }

    public function test_ranking_uses_active_bid_and_marks_leader_after_lower_rebid(): void
    {
        $this->enableTenders();

        $seller = User::factory()->create();
        $bidderA = User::factory()->create();
        $bidderB = User::factory()->create();
        $tender = $this->createTenderFor($seller);

        foreach ([$bidderA, $bidderB] as $bidder) {
            $this->actingAs($bidder)->post(route('tenders.accept-rules'))->assertOk();
        }

        $this->actingAs($bidderA)
            ->postJson(route('tenders.bids.store', $tender), ['amount' => 5100])
            ->assertOk();

        $this->actingAs($bidderB)
            ->postJson(route('tenders.bids.store', $tender), ['amount' => 5200])
            ->assertOk();

        $leadingBid = $tender->fresh()->bids()->where('status', TenderBidStatus::Active)->first();
        $this->actingAs($bidderB)
            ->deleteJson(route('tenders.bids.destroy', [$tender, $leadingBid]))
            ->assertOk();

        $response = $this->actingAs($bidderA)
            ->postJson(route('tenders.bids.store', $tender), ['amount' => 5000])
            ->assertOk();

        $state = $response->json('state');

        $this->assertSame(5000, $state['current_high_bid']);
        $this->assertTrue(collect($state['bid_ranking'])->firstWhere('is_yours', true)['is_leader']);
        $this->assertSame(5000, collect($state['bid_ranking'])->firstWhere('is_yours', true)['amount']);
        $this->assertTrue(collect($state['bid_history'])->firstWhere('is_leader', true)['is_leader']);
        $this->assertSame(5000, collect($state['bid_history'])->firstWhere('is_leader', true)['amount']);
        $this->assertFalse(collect($state['bid_history'])->firstWhere('amount', 5100)['is_leader']);
    }

    public function test_ranking_includes_outbid_runners_up(): void
    {
        $this->enableTenders();

        $seller = User::factory()->create();
        $leader = User::factory()->create();
        $challenger = User::factory()->create();
        $tender = $this->createTenderFor($seller);

        foreach ([$leader, $challenger] as $bidder) {
            $this->actingAs($bidder)->post(route('tenders.accept-rules'))->assertOk();
        }

        $this->actingAs($challenger)
            ->postJson(route('tenders.bids.store', $tender), ['amount' => 5200])
            ->assertOk();

        $this->actingAs($leader)
            ->postJson(route('tenders.bids.store', $tender), ['amount' => 5300])
            ->assertOk();

        $highBid = $tender->fresh()->bids()->where('status', TenderBidStatus::Active)->first();
        $this->actingAs($leader)
            ->deleteJson(route('tenders.bids.destroy', [$tender, $highBid]))
            ->assertOk();

        $this->actingAs($leader)
            ->postJson(route('tenders.bids.store', $tender), ['amount' => 5000])
            ->assertOk();

        $response = $this->getJson(route('tenders.state', $tender));

        $response->assertOk()
            ->assertJsonCount(2, 'bid_ranking')
            ->assertJsonPath('bid_ranking.0.amount', 5000)
            ->assertJsonPath('bid_ranking.0.is_leader', true)
            ->assertJsonPath('bid_ranking.1.amount', 5200)
            ->assertJsonPath('bid_ranking.1.is_leader', false);
    }

    public function test_public_state_includes_bid_ranking_by_amount(): void
    {
        $this->enableTenders();

        $seller = User::factory()->create();
        $bidderA = User::factory()->create();
        $bidderB = User::factory()->create();
        $tender = $this->createTenderFor($seller);

        foreach ([$bidderA, $bidderB] as $bidder) {
            $this->actingAs($bidder)->post(route('tenders.accept-rules'))->assertOk();
        }

        $this->actingAs($bidderA)
            ->postJson(route('tenders.bids.store', $tender), ['amount' => 5000])
            ->assertOk();

        $this->actingAs($bidderB)
            ->postJson(route('tenders.bids.store', $tender), ['amount' => 5100])
            ->assertOk();

        $response = $this->getJson(route('tenders.state', $tender));

        $response->assertOk()
            ->assertJsonCount(2, 'bid_ranking')
            ->assertJsonPath('bid_ranking.0.amount', 5100)
            ->assertJsonPath('bid_ranking.0.is_leader', true)
            ->assertJsonPath('bid_ranking.1.amount', 5000)
            ->assertJsonPath('bid_ranking.1.is_leader', false);
    }

    public function test_public_state_hides_bidder_identity(): void
    {
        $this->enableTenders();

        $seller = User::factory()->create(['name' => 'Secret Seller']);
        $bidder = User::factory()->create(['name' => 'Visible Bidder']);
        $tender = $this->createTenderFor($seller);

        $this->actingAs($bidder)->post(route('tenders.accept-rules'))->assertOk();

        $this->actingAs($bidder)
            ->postJson(route('tenders.bids.store', $tender), ['amount' => 5000])
            ->assertOk();

        $response = $this->getJson(route('tenders.state', $tender));

        $response->assertOk()
            ->assertJsonMissing(['name' => 'Visible Bidder'])
            ->assertJsonPath('current_high_bid', 5000)
            ->assertJsonPath('my_bid.amount', 5000)
            ->assertJsonPath('bid_history.0.amount', 5000)
            ->assertJsonPath('bid_history.0.anonymous_label', __('tenders.anonymous_you'));
    }

    public function test_bid_must_meet_minimum_and_increment(): void
    {
        $this->enableTenders();

        $seller = User::factory()->create();
        $bidder = User::factory()->create();
        $tender = $this->createTenderFor($seller);

        $this->actingAs($bidder)->post(route('tenders.accept-rules'))->assertOk();

        $this->actingAs($bidder)
            ->postJson(route('tenders.bids.store', $tender), ['amount' => 5000])
            ->assertOk();

        $another = User::factory()->create();
        $another->forceFill(['tender_rules_accepted_at' => now(), 'tender_rules_version' => '1'])->save();

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(\App\Services\Tenders\TenderBidService::class)->place($tender->fresh(), $another, 5050);
    }

    public function test_bid_requires_accepted_rules(): void
    {
        $this->enableTenders();

        $seller = User::factory()->create();
        $bidder = User::factory()->create();
        $tender = $this->createTenderFor($seller);

        $bidder->forceFill([
            'tender_rules_accepted_at' => null,
            'tender_rules_version' => null,
        ])->save();

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(\App\Services\Tenders\TenderBidService::class)->place($tender, $bidder, 5000);
    }

    public function test_seller_sees_bidder_details_on_manage_page(): void
    {
        $this->enableTenders();

        $seller = User::factory()->create();
        $bidder = User::factory()->create([
            'name' => 'Bidder Name',
            'email' => 'bidder@example.com',
        ]);
        $tender = $this->createTenderFor($seller);

        $this->actingAs($bidder)->post(route('tenders.accept-rules'))->assertOk();
        $this->actingAs($bidder)
            ->postJson(route('tenders.bids.store', $tender), ['amount' => 5000])
            ->assertOk();

        $this->actingAs($seller)
            ->get(route('my.tenders.manage', $tender))
            ->assertOk()
            ->assertSee('Bidder Name')
            ->assertSee('bidder@example.com')
            ->assertSee((string) $bidder->email);
    }

    public function test_expired_tender_closes_and_can_be_awarded(): void
    {
        $this->enableTenders();

        $seller = User::factory()->create();
        $bidder = User::factory()->create();
        $tender = $this->createTenderFor($seller);

        $this->actingAs($bidder)->post(route('tenders.accept-rules'))->assertOk();
        $this->actingAs($bidder)
            ->postJson(route('tenders.bids.store', $tender), ['amount' => 5000])
            ->assertOk();

        $tender->update(['ends_at' => now()->subMinute()]);
        app(TenderLifecycleService::class)->closeExpired();
        $tender->refresh();

        $this->assertSame(TenderStatus::Ended, $tender->status);

        $this->assertFalse($tender->fresh()->isBiddable());

        $bid = $tender->bids()->first();

        $this->actingAs($seller)
            ->post(route('my.tenders.award', [$tender, $bid]))
            ->assertRedirect(route('my.tenders.manage', $tender));

        $this->assertSame(TenderStatus::Awarded, $tender->fresh()->status);
    }

    private function enableTenders(): void
    {
        app(PlatformSettings::class)->setTendersEnabled(true);
    }

    private function seedCatalog(): void
    {
        Region::query()->firstOrCreate(
            ['slug' => 'sofia'],
            ['name_bg' => 'София', 'name_en' => 'Sofia', 'sort_order' => 1],
        );

        if (VehicleBrand::query()->exists()) {
            return;
        }

        $brand = VehicleBrand::query()->create([
            'name' => 'BMW',
            'slug' => 'bmw',
            'sort_order' => 1,
        ]);

        VehicleModel::query()->create([
            'brand_id' => $brand->id,
            'name' => '320d',
            'slug' => '320d',
            'type' => 'model',
            'sort_order' => 1,
        ]);
    }

    private function brandId(): int
    {
        return (int) VehicleBrand::query()->value('id');
    }

    private function modelId(): int
    {
        return (int) VehicleModel::query()->value('id');
    }

    private function regionId(): int
    {
        return (int) Region::query()->value('id');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createTenderFor(User $seller, array $overrides = []): Tender
    {
        return Tender::query()->create([
            'reference_number' => Tender::nextReferenceNumber(),
            'slug' => 'test-tender-'.uniqid(),
            'user_id' => $seller->id,
            'status' => TenderStatus::Active,
            'brand_id' => $this->brandId(),
            'model_id' => $this->modelId(),
            'year' => 2020,
            'region_id' => null,
            'city' => 'Sofia',
            'starting_price' => 5000,
            'bid_increment' => 100,
            'duration_days' => 7,
            'starts_at' => now(),
            'ends_at' => now()->addDays(7),
            ...$overrides,
        ]);
    }
}