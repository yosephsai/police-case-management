<?php

namespace Tests\Feature;

use App\Models\CaseAssignment;
use App\Models\Complaint;
use App\Models\PoliceCase;
use App\Models\User;
use App\Models\UserPermissionOverride;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPermissionDelegationTest extends TestCase
{
    use RefreshDatabase;

    private function assignedCase(User $user): PoliceCase
    {
        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-DEL1',
            'complainant_name' => 'Fictional Complainant',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'approved',
        ]);

        $case = PoliceCase::create([
            'complaint_id' => $complaint->id,
            'case_number' => 'CASE-20261124-DEL1',
            'title' => 'Fictional Delegation Case',
            'description' => 'Fictional case description.',
            'status' => 'open',
            'opened_at' => '2026-11-24',
        ]);

        CaseAssignment::create([
            'case_id' => $case->id,
            'user_id' => $user->id,
            'role' => 'investigator',
            'assigned_at' => '2026-11-24',
        ]);

        return $case;
    }

    public function test_an_administrator_can_grant_a_capability_to_an_individual(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $investigator = User::factory()->create(['role' => 'investigator']);
        $case = $this->assignedCase($investigator);

        $this->actingAs($admin)
            ->put(route('staff.permissions.update', $investigator), [
                'overrides' => ['submit-report' => '1'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('user_permission_overrides', [
            'user_id' => $investigator->id,
            'permission' => 'submit-report',
            'allowed' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'user_permissions.updated']);

        $this->actingAs($investigator)
            ->post(route('investigation-reports.store', $case), [
                'report_summary' => 'Fictional delegated report.',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('investigation_reports', 1);
    }

    public function test_an_administrator_can_deny_a_role_capability_for_an_individual(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $target = User::factory()->create(['role' => 'investigator']);
        $case = $this->assignedCase($supervisor);

        $this->actingAs($admin)
            ->put(route('staff.permissions.update', $supervisor), [
                'overrides' => ['assign-cases' => '0'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('user_permission_overrides', [
            'user_id' => $supervisor->id,
            'permission' => 'assign-cases',
            'allowed' => false,
        ]);

        $this->actingAs($supervisor)
            ->post(route('case-assignments.store', $case), [
                'user_id' => $target->id,
                'role' => 'investigator',
            ])
            ->assertForbidden();
    }

    public function test_inheriting_removes_existing_overrides(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $investigator = User::factory()->create(['role' => 'investigator']);

        UserPermissionOverride::create([
            'user_id' => $investigator->id,
            'permission' => 'submit-report',
            'allowed' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('staff.permissions.update', $investigator), [
                'overrides' => ['submit-report' => ''],
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('user_permission_overrides', 0);
    }

    public function test_only_permission_administrators_can_manage_user_permissions(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $target = User::factory()->create(['role' => 'investigator']);

        $this->actingAs($supervisor)
            ->get(route('staff.permissions.edit', $target))
            ->assertForbidden();
    }

    public function test_the_manage_permissions_capability_cannot_be_delegated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $this->actingAs($admin)
            ->put(route('staff.permissions.update', $supervisor), [
                'overrides' => ['manage-permissions' => '1'],
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('user_permission_overrides', [
            'user_id' => $supervisor->id,
            'permission' => 'manage-permissions',
        ]);

        $this->actingAs($supervisor)
            ->get(route('permissions.index'))
            ->assertForbidden();
    }

    public function test_the_user_permission_page_renders_effective_permissions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $investigator = User::factory()->create(['role' => 'investigator']);

        $this->actingAs($admin)
            ->get(route('staff.permissions.edit', $investigator))
            ->assertOk()
            ->assertSee($investigator->name)
            ->assertSee('Record investigation activities')
            ->assertSee('Inherit');
    }
}
