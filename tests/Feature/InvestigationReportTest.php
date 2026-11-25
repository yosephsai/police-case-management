<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CaseAssignment;
use App\Models\Complaint;
use App\Models\InvestigationReport;
use App\Models\PoliceCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestigationReportTest extends TestCase
{
    use RefreshDatabase;

    private function createCase(): PoliceCase
    {
        $user = User::factory()->create(['role' => 'lead_detective']);
        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-TEST14',
            'complainant_name' => 'Fictional Complainant',
            'complainant_phone' => '0910000000',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'approved',
        ]);

        return PoliceCase::create([
            'complaint_id' => $complaint->id,
            'case_number' => 'CASE-20261124-TEST14',
            'title' => 'Fictional Case',
            'description' => 'Fictional case description.',
            'status' => 'open',
            'opened_at' => now()->toDateString(),
        ]);
    }

    public function test_a_lead_investigator_can_submit_an_investigation_report(): void
    {
        $user = User::factory()->create(['role' => 'lead_detective']);
        $case = $this->createCase();
        CaseAssignment::create([
            'case_id' => $case->id,
            'user_id' => $user->id,
            'role' => 'lead',
            'assigned_at' => now()->toDateString(),
        ]);


        $response = $this->actingAs($user)
            ->post(route('investigation-reports.store', ['case' => $case->id]), [
                'report_summary' => 'Fictional investigation summary.',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('investigation_reports', [
            'case_id' => $case->id,
            'submitted_by' => $user->id,
            'review_status' => 'pending',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'investigation_report.submitted',
        ]);
    }

    public function test_a_supervisor_can_accept_an_investigation_report(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $case = $this->createCase();
        $submittedBy = User::factory()->create(['role' => 'lead_detective']);
        CaseAssignment::create([
            'case_id' => $case->id,
            'user_id' => $submittedBy->id,
            'role' => 'lead',
            'assigned_at' => now()->toDateString(),
        ]);


        $this->actingAs($submittedBy)
            ->post(route('investigation-reports.store', ['case' => $case->id]), [
                'report_summary' => 'Fictional investigation summary.',
            ]);

        $reportId = InvestigationReport::where('case_id', $case->id)->value('id');

        $response = $this->actingAs($supervisor)
            ->from(route('dashboard'))
            ->post(route('investigation-reports.review', $reportId), [
                'decision' => 'accepted',
                'notes' => 'Accepted for referral.',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('investigation_reports', [
            'id' => $reportId,
            'review_status' => 'accepted',
            'reviewed_by' => $supervisor->id,
        ]);
    }

    public function test_a_supervisor_can_return_an_investigation_report_for_further_investigation(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $case = $this->createCase();
        $submittedBy = User::factory()->create(['role' => 'lead_detective']);
        CaseAssignment::create([
            'case_id' => $case->id,
            'user_id' => $submittedBy->id,
            'role' => 'lead',
            'assigned_at' => now()->toDateString(),
        ]);


        $this->actingAs($submittedBy)
            ->post(route('investigation-reports.store', ['case' => $case->id]), [
                'report_summary' => 'Fictional investigation summary.',
            ]);

        $reportId = InvestigationReport::where('case_id', $case->id)->value('id');

        $response = $this->actingAs($supervisor)
            ->from(route('dashboard'))
            ->post(route('investigation-reports.review', $reportId), [
                'decision' => 'returned',
                'notes' => 'Return for further investigation.',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('investigation_reports', [
            'id' => $reportId,
            'review_status' => 'returned',
        ]);
    }

    public function test_returning_an_investigation_report_requires_notes(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $case = $this->createCase();
        $submittedBy = User::factory()->create(['role' => 'lead_detective']);
        CaseAssignment::create([
            'case_id' => $case->id,
            'user_id' => $submittedBy->id,
            'role' => 'lead',
            'assigned_at' => now()->toDateString(),
        ]);


        $this->actingAs($submittedBy)
            ->post(route('investigation-reports.store', ['case' => $case->id]), [
                'report_summary' => 'Fictional investigation summary.',
            ]);

        $reportId = InvestigationReport::where('case_id', $case->id)->value('id');

        $response = $this->actingAs($supervisor)
            ->from(route('dashboard'))
            ->post(route('investigation-reports.review', $reportId), [
                'decision' => 'returned',
                'notes' => null,
            ]);

        $response->assertSessionHasErrors('notes');
    }

    public function test_end_to_end_report_submission_return_and_acceptance_transitions_case_status(): void
    {
        $reportingOfficer = User::factory()->create(['role' => 'reporting_officer']);
        $reviewingOfficer = User::factory()->create(['role' => 'reviewing_officer']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $lead = User::factory()->create(['role' => 'lead_detective']);

        // 1. Reporting officer creates complaint and logs out
        $this->actingAs($reportingOfficer)
            ->post(route('complaints.store'), [
                'complainant_name' => 'Abebe Bikila',
                'complainant_phone' => '912345678',
                'incident_location' => 'Adama',
                'incident_description' => 'End-to-end test complaint description.',
                'incident_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('complaints.index'));
        $this->post(route('logout'))->assertRedirect(route('login'));

        $complaint = Complaint::where('registered_by', $reportingOfficer->id)->firstOrFail();

        // 2. Reviewing officer approves complaint and converts to case
        $this->actingAs($reviewingOfficer)
            ->post(route('complaints.review', $complaint), ['decision' => 'approved'])
            ->assertRedirect();
        $this->actingAs($reviewingOfficer)
            ->post(route('complaints.convert-to-case', $complaint))
            ->assertRedirect();
        $this->post(route('logout'))->assertRedirect(route('login'));

        $case = PoliceCase::where('complaint_id', $complaint->id)->firstOrFail();

        // 3. Supervisor assigns lead detective and moves case to under_investigation
        $this->actingAs($supervisor)
            ->post(route('case-assignments.store', $case), ['user_id' => $lead->id])
            ->assertRedirect();
        $this->actingAs($supervisor)
            ->patch(route('cases.status', $case), ['to_status' => 'under_investigation'])
            ->assertRedirect();
        $this->post(route('logout'))->assertRedirect(route('login'));

        // 4. Lead detective submits investigation report -> case moves to pending_review
        $this->actingAs($lead)
            ->post(route('investigation-reports.store', $case), [
                'report_summary' => 'Initial investigation findings ready for review.',
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('cases', ['id' => $case->id, 'status' => 'pending_review']);
        $firstReport = InvestigationReport::where('case_id', $case->id)->latest('id')->firstOrFail();

        // 5. Supervisor returns report -> case moves back to under_investigation
        $this->actingAs($supervisor)
            ->post(route('investigation-reports.review', $firstReport), [
                'decision' => 'returned',
                'notes' => 'Need witness statement.',
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('cases', ['id' => $case->id, 'status' => 'under_investigation']);

        // 6. Lead detective submits updated report -> case moves to pending_review
        $this->actingAs($lead)
            ->post(route('investigation-reports.store', $case), [
                'report_summary' => 'Updated investigation report with witness statement.',
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('cases', ['id' => $case->id, 'status' => 'pending_review']);
        $secondReport = InvestigationReport::where('case_id', $case->id)->latest('id')->firstOrFail();

        // 7. Supervisor accepts report -> case closes
        $this->actingAs($supervisor)
            ->post(route('investigation-reports.review', $secondReport), [
                'decision' => 'accepted',
                'notes' => 'Complete and accepted.',
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('cases', ['id' => $case->id, 'status' => 'closed']);
        $this->assertDatabaseHas('complaints', ['id' => $complaint->id, 'status' => 'approved']);

        // 8. Closed case cannot accept new investigation reports
        $this->actingAs($lead)
            ->from(route('cases.show', $case))
            ->post(route('investigation-reports.store', $case), [
                'report_summary' => 'Attempt after case closure.',
            ])
            ->assertSessionHasErrors('report_summary');
    }
}
