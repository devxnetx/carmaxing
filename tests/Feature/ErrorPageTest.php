<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => false]);

        Route::middleware('web')->get('/__test/error/{code}', function (int $code) {
            abort($code);
        });
    }

    public function test_400_page_uses_branded_layout_in_bulgarian(): void
    {
        $this->withSession(['locale' => 'bg'])
            ->get('/__test/error/400')
            ->assertStatus(400)
            ->assertSee('Грешка 400', false)
            ->assertSee('Не успяхме да обработим заявката', false)
            ->assertSee('Към началната страница', false);
    }

    public function test_500_page_uses_branded_layout_in_english(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get('/__test/error/500')
            ->assertStatus(500)
            ->assertSee('Error 500', false)
            ->assertSee('An unexpected error occurred', false)
            ->assertSee('Back to homepage', false);
    }
}