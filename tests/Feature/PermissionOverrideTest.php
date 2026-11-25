<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\PermissionOverride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionOverrideTest extends TestCase
{
    use RefreshDatabase;

    private function pendingComplaint(): Complaint
    {
        return Complaint::create([
            'complaint_number' => 'CMP-20261124-OVR1',
            'complainant_name' => 'Fictional Complainant',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'pending',
        ]);
    }

    private function payload(array $changes = []): array
    {
        $permissions = [];

        foreach (array_keys(config('permissions.roles')) as $role) {
            foreach (config('permissions.permissions') as $permission => $roles) {
                if ($permission === 'manage-permissions') {
                    continue;
                }

                $permissions[$role][$permission] = in_array($role, $roles, true) ? 1 : 0;
            }
        }

        foreach ($changes as $role => $capabilities) {
            foreach ($capabilities as $permission => $value) {
                $permissions[$role][$permission] = $value;
            }
        }

        return ['permissions' => $permissions];
    }

    public function test_the_system_administrator_can_view_permission_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('permissions.index'))
            ->assertOk()
            ->assertSee('Role Permissions')
            ->assertSee('Review complaints');
    }

    public function test_a_supervisor_cannot_view_permission_settings(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $this->actingAs($supervisor)
            ->get(route('permissions.index'))
            ->assertForbidden();
    }

    public function test_an_administrator_can_grant_a_capability_by_override(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $complaint = $this->pendingComplaint();

        $this->actingAs($admin)
            ->put(route('permissions.update'), $this->payload([
                'supervisor' => ['review-complaints' => 1],
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('permission_overrides', [
            'role' => 'supervisor',
            'permission' => 'review-complaints',
            'allowed' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'permissions.updated']);

        $this->actingAs($supervisor)
            ->post(route('complaints.review', $complaint), ['decision' => 'approved'])
            ->assertRedirect();

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'approved',
        ]);
    }

    public function test_an_administrator_can_revoke_a_capability_by_override(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $this->actingAs($admin)
            ->put(route('permissions.update'), $this->payload([
                'supervisor' => ['view-audit-logs' => 0],
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('permission_overrides', [
            'role' => 'supervisor',
            'permission' => 'view-audit-logs',
            'allowed' => false,
        ]);

        $this->actingAs($supervisor)
            ->get(route('audit-logs.index'))
            ->assertForbidden();
    }

    public function test_posting_default_values_removes_overrides(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        PermissionOverride::create([
            'role' => 'supervisor',
            'permission' => 'view-audit-logs',
            'allowed' => false,
        ]);

        $this->actingAs($admin)
            ->put(route('permissions.update'), $this->payload())
            ->assertRedirect();

        $this->assertDatabaseCount('permission_overrides', 0);
    }

    public function test_the_manage_permissions_capability_cannot_be_edited(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $payload = $this->payload(['supervisor' => ['manage-permissions' => 1]]);

        $this->actingAs($admin)
            ->put(route('permissions.update'), $payload)
            ->assertRedirect();

        $this->assertDatabaseMissing('permission_overrides', [
            'role' => 'supervisor',
            'permission' => 'manage-permissions',
        ]);

        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $this->actingAs($supervisor)
            ->get(route('permissions.index'))
            ->assertForbidden();
    }
}
