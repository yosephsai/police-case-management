<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_failed_logins_are_rate_limited(): void
    {
        $user = User::factory()->create([
            'role' => 'investigator',
            'password' => bcrypt('password'),
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->from(route('login'))->post(route('login.submit'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $this->from(route('login'))->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(429);

        $this->assertGuest();
    }
}
