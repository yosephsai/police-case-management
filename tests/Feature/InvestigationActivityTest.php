<?php

namespace Tests\Feature;

use App\Models\CaseAssignment;
use App\Models\Complaint;
use App\Models\PoliceCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestigationActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_assigned_investigator_can_record_an_activity(): void
    {
        $investigator = User::factory()->create(['role' => 'investigator']);
        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-TEST7',
            'complainant_name' => 'Fictional Complainant',
            'complainant_phone' => '0910000000',
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
        CaseAssignment::create([
            'case_id' => $case->id,
            'user_id' => $investigator->id,
            'role' => 'lead',
            'assigned_at' => now()->toDateString(),
        ]);


        $response = $this->actingAs($investigator)
            ->post(route('investigation-activities.store', ['case' => $case->id]), [
                'activity_type' => 'Witness statement',
                'description' => 'Fictional witness statement recorded.',
                'activity_date' => '2026-11-24 10:00:00',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('investigation_activities', [
            'case_id' => $case->id,
            'user_id' => $investigator->id,
            'activity_type' => 'Witness statement',
        ]);
    }

    public function test_activity_requires_description_and_date(): void
    {
        $investigator = User::factory()->create(['role' => 'investigator']);
        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-TEST8',
            'complainant_name' => 'Fictional Complainant',
            'complainant_phone' => '0910000000',
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
        CaseAssignment::create([
            'case_id' => $case->id,
            'user_id' => $investigator->id,
            'role' => 'lead',
            'assigned_at' => now()->toDateString(),
        ]);


        $response = $this->actingAs($investigator)
            ->from(route('dashboard'))
            ->post(route('investigation-activities.store', ['case' => $case->id]), [
                'activity_type' => 'Witness statement',
                'description' => '',
                'activity_date' => '',
            ]);

        $response->assertSessionHasErrors(['description', 'activity_date']);
    }
}
