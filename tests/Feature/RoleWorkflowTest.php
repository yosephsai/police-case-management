<?php

namespace Tests\Feature;

use App\Models\CaseAssignment;
use App\Models\Complaint;
use App\Models\Evidence;
use App\Models\InvestigationReport;
use App\Models\PoliceCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function complaint(string $suffix, string $status = 'pending', ?User $registeredBy = null): Complaint
    {
        return Complaint::create([
            'complaint_number' => 'CMP-20261124-ROLE' . $suffix,
            'complainant_name' => 'Fictional Complainant',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => now()->toDateString(),
            'status' => $status,
            'registered_by' => $registeredBy?->id,
        ]);
    }

    private function caseFor(Complaint $complaint, string $suffix): PoliceCase
    {
        return PoliceCase::create([
            'complaint_id' => $complaint->id,
            'case_number' => 'CASE-20261124-ROLE' . $suffix,
            'title' => 'Fictional Role Case',
            'description' => 'Fictional case description.',
            'status' => 'open',
            'opened_at' => now()->toDateString(),
        ]);
    }

    private function assign(User $user, PoliceCase $case, string $role = 'investigator'): void
    {
        CaseAssignment::create([
            'case_id' => $case->id,
            'user_id' => $user->id,
            'role' => $role,
            'assigned_at' => now()->toDateString(),
        ]);
    }

    private function evidenceFor(PoliceCase $case, User $user): Evidence
    {
        return Evidence::create([
            'case_id' => $case->id,
            'user_id' => $user->id,
            'evidence_number' => 'EVD-ROLE-' . $case->id,
            'evidence_type' => 'Document',
            'description' => 'Fictional evidence.',
            'collected_at' => now()->toDateString(),
        ]);
    }

    private function reportFor(PoliceCase $case, User $submitter): InvestigationReport
    {
        return InvestigationReport::create([
            'case_id' => $case->id,
            'submitted_by' => $submitter->id,
            'report_summary' => 'Fictional report summary.',
            'submitted_at' => now(),
            'review_status' => 'pending',
        ]);
    }

    public function test_reporting_officer_registers_a_complaint_and_sees_only_their_own(): void
    {
        $officer = User::factory()->create(['role' => 'reporting_officer']);
        $other = User::factory()->create(['role' => 'reporting_officer']);

        $this->actingAs($officer)
            ->get(route('complaints.create'))
            ->assertOk();

        $this->actingAs($officer)
            ->post(route('complaints.store'), [
                'complainant_name' => 'Fictional Complainant',
                'complainant_phone' => '912345678',
                'incident_location' => 'Adama',
                'incident_description' => 'Fictional incident description.',
                'incident_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('complaints.index'));

        $own = Complaint::where('registered_by', $officer->id)->firstOrFail();
        $foreign = $this->complaint('RPT2', 'pending', $other);

        $this->assertDatabaseHas('complaints', [
            'id' => $own->id,
            'complainant_phone' => '+251912345678',
        ]);

        $this->actingAs($officer)
            ->get(route('complaints.index'))
            ->assertOk()
            ->assertSee($own->complaint_number)
            ->assertDontSee($foreign->complaint_number);

        $this->actingAs($officer)
            ->get(route('complaints.show', $foreign))
            ->assertForbidden();

        $this->actingAs($officer)
            ->post(route('complaints.review', $own), ['decision' => 'approved'])
            ->assertForbidden();
    }

    public function test_reviewing_officer_reviews_and_converts_a_complaint(): void
    {
        $reviewer = User::factory()->create(['role' => 'reviewing_officer']);
        $complaint = $this->complaint('REV1');

        $this->actingAs($reviewer)
            ->post(route('complaints.review', $complaint), ['decision' => 'approved'])
            ->assertRedirect();

        $this->assertDatabaseHas('complaints', ['id' => $complaint->id, 'status' => 'approved']);

        $this->actingAs($reviewer)
            ->post(route('complaints.convert-to-case', $complaint))
            ->assertRedirect();

        $case = PoliceCase::where('complaint_id', $complaint->id)->firstOrFail();

        $this->actingAs($reviewer)
            ->patch(route('cases.status', $case), ['to_status' => 'under_investigation'])
            ->assertForbidden();

        $this->actingAs($reviewer)
            ->get(route('audit-logs.index'))
            ->assertForbidden();
    }

    public function test_supervisor_manages_assignment_status_and_report_review(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $investigator = User::factory()->create(['role' => 'investigator']);
        $case = $this->caseFor($this->complaint('SUP1', 'approved'), 'SUP1');
        $report = $this->reportFor($case, $investigator);
        $pending = $this->complaint('SUP2');

        $this->actingAs($supervisor)
            ->post(route('case-assignments.store', $case), [
                'user_id' => $investigator->id,
                'role' => 'investigator',
            ])
            ->assertRedirect();

        $this->actingAs($supervisor)
            ->patch(route('cases.status', $case), ['to_status' => 'under_investigation'])
            ->assertRedirect();

        $this->actingAs($supervisor)
            ->post(route('investigation-reports.review', $report), ['decision' => 'accepted'])
            ->assertRedirect();

        $this->actingAs($supervisor)
            ->get(route('audit-logs.index'))
            ->assertOk();

        $this->actingAs($supervisor)
            ->get(route('reports.summary'))
            ->assertOk();

        $this->actingAs($supervisor)
            ->post(route('complaints.review', $pending), ['decision' => 'approved'])
            ->assertForbidden();

        $this->actingAs($supervisor)
            ->post(route('investigation-activities.store', $case), [
                'activity_type' => 'Interview',
                'description' => 'Fictional activity.',
                'activity_date' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertForbidden();

        $this->actingAs($supervisor)
            ->post(route('investigation-reports.store', $case), [
                'report_summary' => 'Fictional report.',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('cases', ['id' => $case->id, 'status' => 'under_investigation']);
        $this->assertDatabaseHas('investigation_reports', [
            'id' => $report->id,
            'review_status' => 'accepted',
        ]);
    }

    public function test_station_commander_oversees_cases_but_not_complaint_decisions(): void
    {
        $commander = User::factory()->create(['role' => 'station_commander']);
        $investigator = User::factory()->create(['role' => 'investigator']);
        $case = $this->caseFor($this->complaint('CMD1', 'approved'), 'CMD1');
        $pending = $this->complaint('CMD2');

        $this->actingAs($commander)
            ->post(route('case-assignments.store', $case), [
                'user_id' => $investigator->id,
                'role' => 'investigator',
            ])
            ->assertRedirect();

        $this->actingAs($commander)
            ->patch(route('cases.status', $case), ['to_status' => 'under_investigation'])
            ->assertRedirect();

        $this->actingAs($commander)
            ->get(route('audit-logs.index'))
            ->assertOk();

        $this->actingAs($commander)
            ->get(route('reports.complaints'))
            ->assertOk();

        $this->actingAs($commander)
            ->post(route('complaints.review', $pending), ['decision' => 'approved'])
            ->assertForbidden();

        $this->actingAs($commander)
            ->post(route('investigation-activities.store', $case), [
                'activity_type' => 'Interview',
                'description' => 'Fictional activity.',
                'activity_date' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertForbidden();
    }

    public function test_lead_detective_records_all_case_work_and_submits_a_report(): void
    {
        $lead = User::factory()->create(['role' => 'lead_detective']);
        $case = $this->caseFor($this->complaint('LEAD1', 'approved'), 'LEAD1');
        $this->assign($lead, $case, 'lead');
        $evidence = $this->evidenceFor($case, $lead);

        $this->actingAs($lead)
            ->post(route('investigation-activities.store', $case), [
                'activity_type' => 'Interview',
                'description' => 'Fictional activity.',
                'activity_date' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect();

        $this->actingAs($lead)
            ->post(route('evidences.store', $case), [
                'evidence_number' => 'EVD-LEAD-1',
                'evidence_type' => 'Document',
                'description' => 'Fictional evidence.',
                'collected_at' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->actingAs($lead)
            ->post(route('evidence-histories.store', $evidence), [
                'action' => 'Received',
                'handled_at' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect();

        $this->actingAs($lead)
            ->post(route('case-people.store', $case), [
                'full_name' => 'Fictional Witness',
                'role' => 'witness',
            ])
            ->assertRedirect();

        $this->actingAs($lead)
            ->post(route('investigation-reports.store', $case), [
                'report_summary' => 'Fictional lead report.',
            ])
            ->assertRedirect();

        $report = InvestigationReport::where('case_id', $case->id)->firstOrFail();

        $this->actingAs($lead)
            ->post(route('investigation-reports.review', $report), ['decision' => 'accepted'])
            ->assertForbidden();

        $this->actingAs($lead)
            ->patch(route('cases.status', $case), ['to_status' => 'under_investigation'])
            ->assertForbidden();

        $this->assertDatabaseHas('investigation_reports', ['id' => $report->id]);
    }

    public function test_investigator_records_case_work_but_cannot_submit_or_review_reports(): void
    {
        $investigator = User::factory()->create(['role' => 'investigator']);
        $case = $this->caseFor($this->complaint('INV1', 'approved'), 'INV1');
        $this->assign($investigator, $case);
        $evidence = $this->evidenceFor($case, $investigator);

        $this->actingAs($investigator)
            ->post(route('investigation-activities.store', $case), [
                'activity_type' => 'Scene visit',
                'description' => 'Fictional activity.',
                'activity_date' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect();

        $this->actingAs($investigator)
            ->post(route('evidence-histories.store', $evidence), [
                'action' => 'Logged',
                'handled_at' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect();

        $this->actingAs($investigator)
            ->post(route('case-people.store', $case), [
                'full_name' => 'Fictional Witness',
                'role' => 'witness',
            ])
            ->assertRedirect();

        $this->actingAs($investigator)
            ->post(route('investigation-reports.store', $case), [
                'report_summary' => 'Fictional report.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('investigation_reports', 0);
    }

    public function test_records_officer_handles_evidence_but_cannot_add_case_records(): void
    {
        $records = User::factory()->create(['role' => 'records_officer']);
        $case = $this->caseFor($this->complaint('REC1', 'approved'), 'REC1');
        $evidence = $this->evidenceFor($case, $records);

        foreach ([
            route('cases.index'),
            route('audit-logs.index'),
            route('reports.summary'),
        ] as $url) {
            $this->actingAs($records)->get($url)->assertOk();
        }

        $this->actingAs($records)
            ->post(route('evidence-histories.store', $evidence), [
                'action' => 'Archived',
                'handled_at' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect();

        $this->actingAs($records)
            ->post(route('evidences.store', $case), [
                'evidence_number' => 'EVD-REC-1',
                'evidence_type' => 'Document',
                'description' => 'Fictional evidence.',
                'collected_at' => now()->toDateString(),
            ])
            ->assertForbidden();

        $this->actingAs($records)
            ->post(route('investigation-activities.store', $case), [
                'activity_type' => 'Interview',
                'description' => 'Fictional activity.',
                'activity_date' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertForbidden();
    }

    public function test_admin_manages_users_and_reports_but_has_no_case_decisions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'investigator']);
        $case = $this->caseFor($this->complaint('ADM1', 'approved'), 'ADM1');
        $pending = $this->complaint('ADM2');

        $this->actingAs($admin)
            ->post(route('staff.store'), [
                'name' => 'Fictional New Staff',
                'email' => 'new.staff@demo.test',
                'password' => 'password',
                'role' => 'reporting_officer',
            ])
            ->assertRedirect(route('staff.index'));

        foreach ([
            route('permissions.index'),
            route('staff.permissions.edit', $target),
            route('audit-logs.index'),
            route('reports.summary'),
            route('cases.print', $case),
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        $this->actingAs($admin)
            ->post(route('complaints.review', $pending), ['decision' => 'approved'])
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('complaints.convert-to-case', $this->complaint('ADM3', 'approved')))
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('cases.status', $case), ['to_status' => 'under_investigation'])
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('investigation-activities.store', $case), [
                'activity_type' => 'Interview',
                'description' => 'Fictional activity.',
                'activity_date' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['email' => 'new.staff@demo.test']);
    }
}
