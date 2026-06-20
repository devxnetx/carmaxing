<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\User;
use App\Services\MobileBg\MobileBgProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileBgProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_extract_and_apply_ratola_profile(): void
    {
        if (! env('RUN_MOBILE_BG_INTEGRATION')) {
            $this->markTestSkipped('Set RUN_MOBILE_BG_INTEGRATION=1 to run live Mobile.bg profile apply test.');
        }

        $user = User::factory()->create();
        $company = Company::query()->create([
            'user_id' => $user->id,
            'name' => 'Before',
            'slug' => 'before',
            'phone' => '+359888000000',
        ]);

        $profile = app(MobileBgProfileService::class)->extractAndApply($company, 'https://ratola.mobile.bg/');

        $company->refresh();

        $this->assertSame('RATOLA', $profile->name);
        $this->assertSame('RATOLA', $company->name);
        $this->assertSame('https://ratola.mobile.bg', $company->mobile_bg_url);
        $this->assertNotNull($company->description);
        $this->assertNotNull($company->phone);
    }
}