<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private array $recoveryCodes = ['AAAA-AAAA', 'BBBB-BBBB'];

    private function userWithTwoFactor(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['password' => 'password'], $attributes));

        $user->forceFill([
            'two_factor_secret' => Totp::generateSecret(),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => array_map(
                fn (string $code) => Hash::make($code),
                $this->recoveryCodes
            ),
        ])->save();

        return $user;
    }

    public function test_an_authenticated_user_can_enable_two_factor_authentication(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->actingAs($user)
            ->get(route('two-factor.show'))
            ->assertOk()
            ->assertSee('Setup key');

        $secret = $user->fresh()->two_factor_secret;
        $this->assertNotNull($secret);

        $this->actingAs($user)
            ->post(route('two-factor.confirm'), ['code' => Totp::code($secret, time())])
            ->assertRedirect(route('two-factor.show'))
            ->assertSessionHas('two_factor_recovery_codes');

        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.two_factor_enabled']);
    }

    public function test_an_invalid_confirmation_code_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->actingAs($user)
            ->get(route('two-factor.show'))
            ->assertOk();

        $this->actingAs($user)
            ->post(route('two-factor.confirm'), ['code' => 'not-a-code'])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->two_factor_confirmed_at);
    }

    public function test_login_with_two_factor_requires_a_challenge(): void
    {
        $user = $this->userWithTwoFactor();
        $secret = $user->two_factor_secret;

        $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.challenge'));

        $this->assertGuest();

        $this->post(route('two-factor.verify'), [
            'code' => Totp::code($secret, time()),
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.two_factor_login']);
    }

    public function test_a_recovery_code_can_be_used_only_once(): void
    {
        $user = $this->userWithTwoFactor();

        $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.challenge'));

        $this->post(route('two-factor.verify'), ['code' => $this->recoveryCodes[0]])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertCount(1, $user->fresh()->two_factor_recovery_codes);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.two_factor_recovery_code_used']);

        $this->post(route('logout'));

        $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.challenge'));

        $this->post(route('two-factor.verify'), ['code' => $this->recoveryCodes[0]])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_two_factor_can_be_disabled_with_the_current_password(): void
    {
        $user = $this->userWithTwoFactor();

        $this->actingAs($user)
            ->post(route('two-factor.disable'), ['password' => 'wrong-password'])
            ->assertSessionHasErrors('password');

        $this->actingAs($user)
            ->post(route('two-factor.disable'), ['password' => 'password'])
            ->assertRedirect(route('two-factor.show'));

        $fresh = $user->fresh();
        $this->assertNull($fresh->two_factor_confirmed_at);
        $this->assertNull($fresh->two_factor_secret);
        $this->assertNull($fresh->two_factor_recovery_codes);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.two_factor_disabled']);
    }

    public function test_recovery_codes_can_be_regenerated(): void
    {
        $user = $this->userWithTwoFactor();

        $this->actingAs($user)
            ->post(route('two-factor.recovery-codes'), ['password' => 'password'])
            ->assertRedirect(route('two-factor.show'))
            ->assertSessionHas('two_factor_recovery_codes');

        $this->assertCount(8, $user->fresh()->two_factor_recovery_codes);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.two_factor_recovery_codes_regenerated',
        ]);
    }

    public function test_a_user_without_two_factor_logs_in_directly(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_the_challenge_page_requires_a_pending_sign_in(): void
    {
        $this->get(route('two-factor.challenge'))
            ->assertRedirect(route('login'));
    }
}
