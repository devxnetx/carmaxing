<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OnboardingFormTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_inactive_account_type_fields_are_disabled(): void
    {
        $user = User::factory()->create(['onboarding_completed_at' => null]);

        $this->actingAs($user)
            ->get(route('onboarding.show'))
            ->assertOk()
            ->assertSee('name="phone"', false)
            ->assertSee('x-bind:disabled="type !== \'private\'"', false)
            ->assertSee('name="company_phone"', false)
            ->assertSee('x-bind:disabled="type !== \'company\'"', false)
            ->assertSee(':disabled="type !== \'company\'"', false);
    }
}
