<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\InvestigationReport;
use App\Models\PoliceCase;
use App\Models\User;
use App\Models\UserPermissionOverride;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordLevelSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function caseWithReport(): array
    {
        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-SEC1',
            'complainant_name' => 'Fictional Complainant',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'approved',
        ]);

        $case = PoliceCase::create([
            'complaint_id' => $complaint->id,
            'case_number' => 'CASE-20261124-SEC1',
            'title' => 'Fictional Secure Case',
            'description' => 'Fictional case description.',
            'status' => 'open',
            'opened_at' => '2026-11-24',
        ]);

        $report = InvestigationReport::create([
            'case_id' => $case->id,
            'submitted_by' => User::factory()->create(['role' => 'investigator'])->id,
            'report_summary' => 'Fictional report summary.',
            'submitted_at' => now(),
            'review_status' => 'pending',
        ]);

        return [$case, $report];
    }

    private function supervisorWithoutCaseVisibility(): User
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        UserPermissionOverride::create([
            'user_id' => $supervisor->id,
            'permission' => 'view-all-cases',
            'allowed' => false,
        ]);

        return $supervisor;
    }

    public function test_a_user_cannot_change_the_status_of_an_invisible_case(): void
    {
        [$case] = $this->caseWithReport();
        $supervisor = $this->supervisorWithoutCaseVisibility();

        $this->actingAs($supervisor)
            ->patch(route('cases.status', $case), ['to_status' => 'under_investigation'])
            ->assertForbidden();

        $this->assertDatabaseHas('cases', [
            'id' => $case->id,
            'status' => 'open',
        ]);
    }

    public function test_a_user_cannot_review_a_report_on_an_invisible_case(): void
    {
        [$case, $report] = $this->caseWithReport();
        $supervisor = $this->supervisorWithoutCaseVisibility();

        $this->actingAs($supervisor)
            ->post(route('investigation-reports.review', $report), [
                'decision' => 'accepted',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('investigation_reports', [
            'id' => $report->id,
            'review_status' => 'pending',
        ]);
    }

    public function test_a_user_cannot_assign_investigators_to_an_invisible_case(): void
    {
        [$case] = $this->caseWithReport();
        $supervisor = $this->supervisorWithoutCaseVisibility();
        $investigator = User::factory()->create(['role' => 'investigator']);

        $this->actingAs($supervisor)
            ->post(route('case-assignments.store', $case), [
                'user_id' => $investigator->id,
                'role' => 'investigator',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('case_assignments', 0);
    }
}
