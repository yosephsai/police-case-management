<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_offers_the_theme_toggle(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-theme-toggle', false)
            ->assertSee('content="light"', false)
            ->assertDontSee('light dark', false)
            ->assertSee("stored || 'light'", false);
    }

    public function test_authenticated_screens_offer_the_theme_toggle(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-theme-toggle', false)
            ->assertSee('Switch theme', false);
    }

    public function test_design_system_defines_a_dark_palette_and_print_overrides(): void
    {
        $css = file_get_contents(public_path('css/app.css'));

        $this->assertStringContainsString('[data-theme="dark"]', $css);
        $this->assertStringContainsString('--bg: #0b1220;', $css);
        $this->assertStringContainsString('--surface: #111d2f;', $css);
        $this->assertStringContainsString('@media print', $css);

        $javascript = file_get_contents(public_path('js/app.js'));

        $this->assertStringContainsString('pcms-theme', $javascript);
        $this->assertStringContainsString('localStorage.setItem', $javascript);
        $this->assertStringContainsString('.input-checkbox:checked::after', $css);
        $this->assertStringContainsString('minmax(min(100%, 260px), 1fr)', $css);
        $this->assertStringContainsString('minmax(min(100%, 240px), 1fr)', $css);
    }

    public function test_box_sizing_applies_globally_and_form_focus_avoids_double_outline(): void
    {
        $css = file_get_contents(public_path('css/app.css'));

        $this->assertStringContainsString("\n*,\n*::before,\n*::after { box-sizing: border-box; }", $css);
        $this->assertStringNotContainsString("[data-theme=\"dark\"] \n*,", $css);
        $this->assertStringContainsString('outline: 2px solid transparent;', $css);
    }
}
