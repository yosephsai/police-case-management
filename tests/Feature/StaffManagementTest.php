<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_deactivate_another_staff_member(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $investigator = User::factory()->create(['role' => 'investigator']);

        $response = $this->actingAs($admin)
            ->from(route('staff.index'))
            ->patch(route('staff.toggle', $investigator));

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $investigator->id,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'staff.deactivated',
        ]);
    }

    public function test_an_admin_cannot_deactivate_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->from(route('staff.index'))
            ->patch(route('staff.toggle', $admin));

        $response->assertSessionHasErrors('staff');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'is_active' => true,
        ]);
    }

    public function test_a_deactivated_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'role' => 'investigator',
            'is_active' => false,
            'password' => bcrypt('password'),
        ]);

        $response = $this->from(route('login'))->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_an_investigator_cannot_manage_staff(): void
    {
        $investigator = User::factory()->create(['role' => 'investigator']);

        $this->actingAs($investigator)
            ->get(route('staff.index'))
            ->assertForbidden();
    }

    public function test_staff_list_is_ordered_ascending_by_id(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'First Admin']);
        $second = User::factory()->create(['role' => 'investigator', 'name' => 'Second Staff']);
        $third = User::factory()->create(['role' => 'supervisor', 'name' => 'Third Staff']);

        $this->actingAs($admin)
            ->get(route('staff.index'))
            ->assertOk()
            ->assertSeeInOrder([
                $admin->name,
                $second->name,
                $third->name,
            ]);
    }
}
