<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Mail\FavoriteListingArchivedMail;
use App\Mail\FavoriteListingPriceChangeMail;
use App\Mail\NewListingsDigestMail;
use App\Mail\PriceDigestMail;
use App\Mail\SiteNewsMail;
use App\Models\Listing;
use App\Models\ListingPriceChange;
use App\Models\SiteNewsPost;
use App\Models\User;
use App\Services\NewListingsDigestService;
use App\Services\PriceDigestService;
use App\Services\SiteNewsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\CatalogSeeder::class);
    }

    #[Test]
    public function test_listing_show_displays_price_history_when_changes_exist(): void
    {
        $listing = $this->createListing(ListingStatus::Published);

        ListingPriceChange::query()->create([
            'listing_id' => $listing->id,
            'old_price' => 12000,
            'new_price' => 11500,
            'created_at' => now()->subDay(),
        ]);

        $this->get(route('listings.show', $listing))
            ->assertOk()
            ->assertSee(__('messages.price_history_title'))
            ->assertSee('12,000')
            ->assertSee('11,500');
    }

    #[Test]
    public function test_archived_listing_is_public_with_noindex(): void
    {
        $listing = $this->createListing(ListingStatus::Archived);

        $this->get(route('listings.show', $listing))
            ->assertOk()
            ->assertSee(__('messages.listing_archived_banner'))
            ->assertSee('noindex', false);
    }

    #[Test]
    public function test_favorite_users_are_notified_when_listing_is_archived(): void
    {
        Mail::fake();

        $owner = User::factory()->create();
        $favoriter = User::factory()->create(['email' => 'favoriter@example.com']);
        $listing = $this->createListing(ListingStatus::Published, $owner);

        $favoriter->favorites()->create(['listing_id' => $listing->id]);

        $listing->archive();

        Mail::assertSent(FavoriteListingArchivedMail::class, function (FavoriteListingArchivedMail $mail) use ($favoriter, $listing) {
            return $mail->hasTo($favoriter->email) && $mail->listing->is($listing);
        });

        Mail::assertNotSent(FavoriteListingArchivedMail::class, function (FavoriteListingArchivedMail $mail) use ($owner) {
            return $mail->hasTo($owner->email);
        });
    }

    #[Test]
    public function test_favorite_users_are_notified_on_price_change(): void
    {
        Mail::fake();

        $owner = User::factory()->create();
        $favoriter = User::factory()->create(['email' => 'favoriter@example.com']);
        $listing = $this->createListing(ListingStatus::Published, $owner);

        $favoriter->favorites()->create(['listing_id' => $listing->id]);

        ListingPriceChange::query()->create([
            'listing_id' => $listing->id,
            'old_price' => 10000,
            'new_price' => 9500,
            'created_at' => now(),
        ]);

        Mail::assertSent(FavoriteListingPriceChangeMail::class, function (FavoriteListingPriceChangeMail $mail) use ($favoriter) {
            return $mail->hasTo($favoriter->email);
        });
    }

    #[Test]
    public function test_price_digest_is_skipped_when_there_are_no_changes(): void
    {
        Mail::fake();

        User::factory()->create([
            'email' => 'digest@example.com',
            'subscribe_price_digest' => true,
        ]);

        $sent = app(PriceDigestService::class)->sendDue();

        $this->assertSame(0, $sent);
        Mail::assertNothingSent();
    }

    #[Test]
    public function test_price_digest_is_sent_when_there_are_yesterdays_changes(): void
    {
        Mail::fake();

        $subscriber = User::factory()->create([
            'email' => 'digest@example.com',
            'subscribe_price_digest' => true,
        ]);

        $listing = $this->createListing(ListingStatus::Published);

        ListingPriceChange::query()->create([
            'listing_id' => $listing->id,
            'old_price' => 12000,
            'new_price' => 11500,
            'created_at' => now()->subDay()->setTime(12, 0),
        ]);

        $sent = app(PriceDigestService::class)->sendDue();

        $this->assertSame(1, $sent);
        Mail::assertSent(PriceDigestMail::class, fn (PriceDigestMail $mail) => $mail->hasTo($subscriber->email));
    }

    #[Test]
    public function test_new_listings_digest_is_skipped_when_empty(): void
    {
        Mail::fake();

        User::factory()->create([
            'email' => 'new@example.com',
            'subscribe_new_listings_digest' => true,
        ]);

        $sent = app(NewListingsDigestService::class)->sendDue();

        $this->assertSame(0, $sent);
        Mail::assertNothingSent();
    }

    #[Test]
    public function test_subscription_toggle_updates_user_setting(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => now(),
            'subscribe_price_digest' => false,
        ]);

        $this->actingAs($user)
            ->patchJson(route('subscriptions.update'), [
                'key' => 'subscribe_price_digest',
                'enabled' => true,
            ])
            ->assertOk()
            ->assertJson([
                'enabled' => true,
                'has_any_subscription' => true,
            ]);

        $this->assertTrue($user->fresh()->subscribe_price_digest);
    }

    #[Test]
    public function test_dashboard_archived_tab_lists_inactive_listings(): void
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $active = $this->createListing(ListingStatus::Published, $user, 'Active unique car');
        $archived = $this->createListing(ListingStatus::Archived, $user, 'Archived unique car');

        $this->actingAs($user)
            ->get(route('dashboard', ['tab' => 'archived']))
            ->assertOk()
            ->assertSee('Archived unique car')
            ->assertDontSee('Active unique car');
    }

    #[Test]
    public function test_owner_can_unarchive_listing(): void
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $listing = $this->createListing(ListingStatus::Archived, $user);

        $this->actingAs($user)
            ->post(route('listings.unarchive', $listing))
            ->assertRedirect(route('dashboard', ['tab' => 'archived']));

        $this->assertSame(ListingStatus::Published, $listing->fresh()->status);
    }

    #[Test]
    public function test_site_news_is_sent_only_to_news_subscribers(): void
    {
        Mail::fake();

        $subscriber = User::factory()->create([
            'email' => 'news@example.com',
            'subscribe_news' => true,
        ]);

        User::factory()->create([
            'email' => 'other@example.com',
            'subscribe_news' => false,
        ]);

        $admin = User::factory()->admin()->create();

        $post = SiteNewsPost::query()->create([
            'title' => 'Platform update',
            'body' => 'We launched a new feature.',
            'recipient_target' => SiteNewsService::TARGET_SUBSCRIBERS,
            'sent_by_user_id' => $admin->id,
        ]);

        $sent = app(SiteNewsService::class)->send($post, SiteNewsService::TARGET_SUBSCRIBERS);

        $this->assertSame(1, $sent);
        Mail::assertSent(SiteNewsMail::class, fn (SiteNewsMail $mail) => $mail->hasTo($subscriber->email));
        Mail::assertSent(SiteNewsMail::class, 1);
    }

    #[Test]
    public function test_site_news_can_be_sent_to_non_subscribers(): void
    {
        Mail::fake();

        User::factory()->create([
            'email' => 'news@example.com',
            'subscribe_news' => true,
        ]);

        $nonSubscriber = User::factory()->create([
            'email' => 'other@example.com',
            'subscribe_news' => false,
        ]);

        $admin = User::factory()->admin()->create(['subscribe_news' => true]);

        $post = SiteNewsPost::query()->create([
            'title' => 'Invite to subscribe',
            'body' => 'Try our news updates.',
            'recipient_target' => SiteNewsService::TARGET_NON_SUBSCRIBERS,
            'sent_by_user_id' => $admin->id,
        ]);

        $sent = app(SiteNewsService::class)->send($post, SiteNewsService::TARGET_NON_SUBSCRIBERS);

        $this->assertSame(1, $sent);
        Mail::assertSent(SiteNewsMail::class, fn (SiteNewsMail $mail) => $mail->hasTo($nonSubscriber->email));
        Mail::assertNotSent(SiteNewsMail::class, fn (SiteNewsMail $mail) => $mail->hasTo('news@example.com'));
        Mail::assertSent(SiteNewsMail::class, 1);
    }

    private function createListing(ListingStatus $status, ?User $user = null, ?string $adName = null): Listing
    {
        $user ??= User::factory()->create();

        return Listing::query()->create([
            'user_id' => $user->id,
            'brand_id' => (int) \App\Models\VehicleBrand::query()->value('id'),
            'model_id' => (int) \App\Models\VehicleModel::query()->value('id'),
            'region_id' => (int) \App\Models\Region::query()->value('id'),
            'ad_name' => $adName,
            'title' => 'Test listing '.Str::random(4),
            'slug' => 'test-listing-'.Str::lower(Str::random(8)),
            'status' => $status,
            'price' => 10000,
            'year' => 2020,
            'published_at' => $status === ListingStatus::Published ? now() : now()->subMonth(),
            'archived_at' => $status->isInactive() ? now() : null,
        ]);
    }
}