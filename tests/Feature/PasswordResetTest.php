<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_reset_link_is_sent_for_a_known_email(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'reset@demo.test']);

        $this->post(route('password.email'), ['email' => 'reset@demo.test'])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'reset@demo.test']);
    }

    public function test_an_unknown_email_does_not_reveal_account_existence(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'nobody@demo.test'])
            ->assertRedirect()
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_a_valid_token_resets_the_password_and_is_audited(): void
    {
        $user = User::factory()->create([
            'email' => 'reset2@demo.test',
            'password' => 'old-password',
        ]);

        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'reset2@demo.test',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.password_reset',
            'record_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'reset2@demo.test']);
    }

    public function test_an_invalid_token_is_rejected(): void
    {
        $user = User::factory()->create([
            'email' => 'reset3@demo.test',
            'password' => 'old-password',
        ]);

        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => 'reset3@demo.test',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_the_reset_form_renders_with_the_token(): void
    {
        $this->get(route('password.reset', ['token' => 'sample-token', 'email' => 'reset4@demo.test']))
            ->assertOk()
            ->assertSee('sample-token')
            ->assertSee('reset4@demo.test');
    }

    public function test_the_forgot_password_form_renders(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Send Reset Link');
    }
}
