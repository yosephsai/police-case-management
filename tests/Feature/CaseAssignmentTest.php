<?php

namespace Tests\Feature;

use App\Models\CaseAssignment;
use App\Models\Complaint;
use App\Models\PoliceCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_supervisor_can_assign_an_investigator_to_a_case(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $investigator = User::factory()->create(['role' => 'investigator']);
        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-TEST5',
            'complainant_name' => 'Fictional Complainant',
            'complainant_phone' => '0910000000',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'approved',
        ]);
        $case = PoliceCase::create([
            'complaint_id' => $complaint->id,
            'case_number' => 'CASE-20261124-TEST5',
            'title' => 'Fictional Case',
            'description' => 'Fictional case description.',
            'status' => 'open',
            'opened_at' => now()->toDateString(),
        ]);

        $response = $this->actingAs($supervisor)
            ->post(route('case-assignments.store', ['case' => $case->id]), [
                'user_id' => $investigator->id,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('case_assignments', [
            'case_id' => $case->id,
            'user_id' => $investigator->id,
            'role' => 'investigator',
        ]);
    }

    public function test_non_investigator_roles_cannot_be_assigned_and_are_excluded_from_case_dropdown(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor', 'name' => 'Acting Supervisor']);
        $otherSupervisor = User::factory()->create(['role' => 'supervisor', 'name' => 'Supervisor Excluded']);
        $lead = User::factory()->create(['role' => 'lead_detective', 'name' => 'Lead Included']);
        $investigator = User::factory()->create(['role' => 'investigator', 'name' => 'Investigator Included']);

        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-TEST8',
            'complainant_name' => 'Fictional Complainant',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'approved',
        ]);

        $case = PoliceCase::create([
            'complaint_id' => $complaint->id,
            'case_number' => 'CASE-20261124-TEST8',
            'title' => 'Fictional Case',
            'description' => 'Fictional case description.',
            'status' => 'open',
            'opened_at' => now()->toDateString(),
        ]);

        $this->actingAs($supervisor)
            ->get(route('cases.show', $case))
            ->assertOk()
            ->assertSee($lead->name)
            ->assertSee($investigator->name)
            ->assertDontSee($otherSupervisor->name);

        $this->actingAs($supervisor)
            ->from(route('cases.show', $case))
            ->post(route('case-assignments.store', $case), [
                'user_id' => $otherSupervisor->id,
            ])
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseCount('case_assignments', 0);
    }

    public function test_assigning_a_lead_detective_automatically_sets_the_lead_role(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $lead = User::factory()->create(['role' => 'lead_detective']);

        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-TEST9',
            'complainant_name' => 'Fictional Complainant',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'approved',
        ]);

        $case = PoliceCase::create([
            'complaint_id' => $complaint->id,
            'case_number' => 'CASE-20261124-TEST9',
            'title' => 'Fictional Case',
            'description' => 'Fictional case description.',
            'status' => 'open',
            'opened_at' => now()->toDateString(),
        ]);

        $this->actingAs($supervisor)
            ->post(route('case-assignments.store', $case), [
                'user_id' => $lead->id,
                'role' => 'investigator',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('case_assignments', [
            'case_id' => $case->id,
            'user_id' => $lead->id,
            'role' => 'lead',
        ]);
    }

    public function test_an_investigator_cannot_be_assigned_twice_to_the_same_case(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $investigator = User::factory()->create(['role' => 'investigator']);
        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-TEST6',
            'complainant_name' => 'Fictional Complainant',
            'complainant_phone' => '0910000000',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'approved',
        ]);
        $case = PoliceCase::create([
            'complaint_id' => $complaint->id,
            'case_number' => 'CASE-20261124-TEST6',
            'title' => 'Fictional Case',
            'description' => 'Fictional case description.',
            'status' => 'open',
            'opened_at' => now()->toDateString(),
        ]);

        CaseAssignment::create([
            'case_id' => $case->id,
            'user_id' => $investigator->id,
            'role' => 'investigator',
            'assigned_at' => now()->toDateString(),
        ]);

        $response = $this->actingAs($supervisor)
            ->post(route('case-assignments.store', ['case' => $case->id]), [
                'user_id' => $investigator->id,
                'role' => 'investigator',
            ]);

        $response->assertSessionHasErrors('user_id');

        $this->assertDatabaseCount('case_assignments', 1);
    }

    public function test_a_lead_detective_can_be_assigned_as_lead_investigator(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $lead = User::factory()->create(['role' => 'lead_detective']);

        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-TEST7',
            'complainant_name' => 'Fictional Complainant',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'approved',
        ]);

        $case = PoliceCase::create([
            'complaint_id' => $complaint->id,
            'case_number' => 'CASE-20261124-TEST7',
            'title' => 'Fictional Case',
            'description' => 'Fictional case description.',
            'status' => 'open',
            'opened_at' => now()->toDateString(),
        ]);

        $this->actingAs($supervisor)
            ->post(route('case-assignments.store', ['case' => $case->id]), [
                'user_id' => $lead->id,
                'role' => 'lead',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('case_assignments', [
            'case_id' => $case->id,
            'user_id' => $lead->id,
            'role' => 'lead',
        ]);

        $this->actingAs($lead)
            ->post(route('investigation-activities.store', $case), [
                'activity_type' => 'Interview',
                'description' => 'Fictional activity by lead detective.',
                'activity_date' => '2026-11-24 10:00:00',
            ])
            ->assertRedirect();
    }
}
