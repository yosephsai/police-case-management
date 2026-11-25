<?php

namespace Tests\Feature;

use App\Models\CaseAssignment;
use App\Models\Complaint;
use App\Models\PoliceCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function pendingComplaint(): Complaint
    {
        return Complaint::create([
            'complaint_number' => 'CMP-20261124-PERM1',
            'complainant_name' => 'Fictional Complainant',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'pending',
        ]);
    }

    private function approvedCase(): PoliceCase
    {
        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-PERM2',
            'complainant_name' => 'Fictional Complainant',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'approved',
        ]);

        return PoliceCase::create([
            'complaint_id' => $complaint->id,
            'case_number' => 'CASE-20261124-PERM2',
            'title' => 'Fictional Case',
            'description' => 'Fictional case description.',
            'status' => 'open',
            'opened_at' => '2026-11-24',
        ]);
    }

    public function test_the_system_administrator_cannot_approve_complaints(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $complaint = $this->pendingComplaint();

        $this->actingAs($admin)
            ->post(route('complaints.review', $complaint), [
                'decision' => 'approved',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'pending',
        ]);
    }

    public function test_a_reporting_officer_cannot_review_complaints(): void
    {
        $officer = User::factory()->create(['role' => 'reporting_officer']);
        $complaint = $this->pendingComplaint();

        $this->actingAs($officer)
            ->post(route('complaints.review', $complaint), [
                'decision' => 'approved',
            ])
            ->assertForbidden();
    }

    public function test_a_reviewing_officer_can_review_complaints(): void
    {
        $officer = User::factory()->create(['role' => 'reviewing_officer']);
        $complaint = $this->pendingComplaint();

        $this->actingAs($officer)
            ->post(route('complaints.review', $complaint), [
                'decision' => 'approved',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'approved',
        ]);
    }

    public function test_the_system_administrator_cannot_convert_complaints_to_cases(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-PERM3',
            'complainant_name' => 'Fictional Complainant',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'approved',
        ]);

        $this->actingAs($admin)
            ->post(route('complaints.convert-to-case', $complaint))
            ->assertForbidden();

        $this->assertDatabaseCount('cases', 0);
    }

    public function test_an_assigned_assistant_investigator_cannot_submit_investigation_reports(): void
    {
        $investigator = User::factory()->create(['role' => 'investigator']);
        $case = $this->approvedCase();

        CaseAssignment::create([
            'case_id' => $case->id,
            'user_id' => $investigator->id,
            'role' => 'investigator',
            'assigned_at' => '2026-11-24',
        ]);

        $this->actingAs($investigator)
            ->post(route('investigation-reports.store', $case), [
                'report_summary' => 'Fictional summary.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('investigation_reports', 0);
    }

    public function test_a_station_commander_can_view_the_audit_log(): void
    {
        $commander = User::factory()->create(['role' => 'station_commander']);

        $this->actingAs($commander)
            ->get(route('audit-logs.index'))
            ->assertOk();
    }

    public function test_a_records_officer_can_view_records_but_cannot_record_activities(): void
    {
        $records = User::factory()->create(['role' => 'records_officer']);
        $case = $this->approvedCase();

        $this->actingAs($records)
            ->get(route('cases.index'))
            ->assertOk();

        $this->actingAs($records)
            ->post(route('investigation-activities.store', $case), [
                'activity_type' => 'Interview',
                'description' => 'Fictional activity.',
                'activity_date' => '2026-11-24 10:00:00',
            ])
            ->assertForbidden();
    }
}
