<?php

namespace Tests\Feature;

use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use App\Models\Role;
use App\Models\User;
use App\Support\ContactCaptcha;
use App\Support\WebsiteManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::query()->firstOrCreate(['slug' => Role::ADMIN], ['name' => 'Administrator']);
    }

    #[Test]
    public function test_contact_page_shows_form_without_email_block(): void
    {
        $this->get(route('pages.contact'))
            ->assertOk()
            ->assertSee(__('pages.contact.form_title'))
            ->assertDontSee(__('pages.contact.email_title'));
    }

    #[Test]
    public function test_contact_form_stores_message_and_emails_manager(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
        ]);

        config(['site.website_manager_email' => null]);

        $captcha = app(ContactCaptcha::class);
        $challenge = $captcha->generate();

        $response = $this->post(route('pages.contact.store'), [
            'name' => 'Ivan Petrov',
            'email' => 'ivan@example.com',
            'subject' => 'API question',
            'message' => 'Hello, I need help with the dealer API.',
            'captcha_answer' => $challenge['left'] + $challenge['right'],
        ]);

        $response->assertRedirect(route('pages.contact'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'ivan@example.com',
            'subject' => 'API question',
        ]);

        $message = ContactMessage::query()->first();
        $this->assertNotNull($message);

        Mail::assertSent(ContactMessageMail::class, function (ContactMessageMail $mail) use ($admin, $message) {
            return $mail->hasTo($admin->email) && $mail->contactMessage->is($message);
        });
    }

    #[Test]
    public function test_website_manager_email_env_is_used_when_set(): void
    {
        config(['site.website_manager_email' => 'manager@carmaxing.bg']);

        User::factory()->admin()->create(['email' => 'admin@example.com']);

        $this->assertSame('manager@carmaxing.bg', WebsiteManager::email());
    }

    #[Test]
    public function test_contact_form_rejects_invalid_captcha(): void
    {
        app(ContactCaptcha::class)->generate();

        $this->from(route('pages.contact'))
            ->post(route('pages.contact.store'), [
                'name' => 'Ivan Petrov',
                'email' => 'ivan@example.com',
                'message' => 'Hello',
                'captcha_answer' => 999,
            ])
            ->assertRedirect(route('pages.contact'))
            ->assertSessionHasErrors('captcha_answer');

        $this->assertDatabaseCount('contact_messages', 0);
    }

    #[Test]
    public function test_admin_can_view_contact_messages(): void
    {
        $admin = User::factory()->admin()->create();

        $message = ContactMessage::query()->create([
            'name' => 'Buyer',
            'email' => 'buyer@example.com',
            'message' => 'Need support',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.contact-messages.index'))
            ->assertOk()
            ->assertSee('buyer@example.com');

        $this->actingAs($admin)
            ->get(route('admin.contact-messages.show', $message))
            ->assertOk()
            ->assertSee('Need support');

        $this->assertNotNull($message->fresh()->read_at);
    }
}