<?php

namespace Tests\Feature;

use App\Models\CaseAssignment;
use App\Models\Complaint;
use App\Models\PoliceCase;
use App\Models\User;
use App\Models\UserPermissionOverride;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiRoleVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_reporting_officer_dashboard_hides_cases_and_review_metrics(): void
    {
        $officer = User::factory()->create(['role' => 'reporting_officer']);

        $this->actingAs($officer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Total Complaints')
            ->assertSee('Register Complaint')
            ->assertSee('Pending Review')
            ->assertDontSee('Pending Actions')
            ->assertDontSee('Total Cases')
            ->assertDontSee('Cases by Status')
            ->assertDontSee('Recent Cases')
            ->assertDontSee('View cases')
            ->assertDontSee('Staff Members')
            ->assertDontSee('Reports Awaiting Review')
            ->assertDontSee('Complaints awaiting review')
            ->assertDontSee('<span class="nav-label">Cases</span>', false);
    }

    public function test_an_assigned_investigator_sees_case_metrics(): void
    {
        $investigator = User::factory()->create(['role' => 'investigator']);

        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-UIV1',
            'complainant_name' => 'Fictional Complainant',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'approved',
        ]);

        $case = PoliceCase::create([
            'complaint_id' => $complaint->id,
            'case_number' => 'CASE-20261124-UIV1',
            'title' => 'Fictional Case',
            'description' => 'Fictional case description.',
            'status' => 'open',
            'opened_at' => '2026-11-24',
        ]);

        CaseAssignment::create([
            'case_id' => $case->id,
            'user_id' => $investigator->id,
            'role' => 'investigator',
            'assigned_at' => '2026-11-24',
        ]);

        $this->actingAs($investigator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Total Cases')
            ->assertSee('Cases by Status')
            ->assertSee('Recent Cases')
            ->assertDontSee('Staff Members')
            ->assertDontSee('Reports Awaiting Review');
    }

    public function test_admin_sees_oversight_metrics_but_not_review_actions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Staff Members')
            ->assertDontSee('Reports Awaiting Review')
            ->assertDontSee('Complaints awaiting review');
    }

    public function test_a_permission_manager_without_staff_access_gets_no_staff_link(): void
    {
        $manager = User::factory()->create(['role' => 'reporting_officer']);
        $target = User::factory()->create(['role' => 'investigator']);

        UserPermissionOverride::create([
            'user_id' => $manager->id,
            'permission' => 'manage-permissions',
            'allowed' => true,
        ]);

        $this->actingAs($manager)
            ->get(route('staff.permissions.edit', $target))
            ->assertOk()
            ->assertDontSee('Back to Staff')
            ->assertDontSee('href="' . route('staff.index') . '"', false)
            ->assertSee('Back to Dashboard');
    }

    public function test_a_case_link_to_an_invisible_complaint_is_hidden(): void
    {
        $owner = User::factory()->create(['role' => 'reporting_officer']);
        $detective = User::factory()->create(['role' => 'lead_detective']);

        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-UIV2',
            'complainant_name' => 'Fictional Complainant',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'approved',
            'registered_by' => $owner->id,
        ]);

        $case = PoliceCase::create([
            'complaint_id' => $complaint->id,
            'case_number' => 'CASE-20261124-UIV2',
            'title' => 'Fictional Case',
            'description' => 'Fictional case description.',
            'status' => 'open',
            'opened_at' => '2026-11-24',
        ]);

        UserPermissionOverride::create([
            'user_id' => $detective->id,
            'permission' => 'view-all-cases',
            'allowed' => true,
        ]);

        $this->actingAs($detective)
            ->get(route('cases.show', $case))
            ->assertOk()
            ->assertSee($case->case_number)
            ->assertDontSee($complaint->complaint_number)
            ->assertSee('Complaint record not available');
    }
}
