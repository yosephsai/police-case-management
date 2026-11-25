<?php

namespace Tests\Feature;

use App\Models\CaseAssignment;
use App\Models\Complaint;
use App\Models\PoliceCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintAccessTest extends TestCase
{
    use RefreshDatabase;

    private function complaint(string $number, ?int $registeredBy = null, string $status = 'pending'): Complaint
    {
        return Complaint::create([
            'complaint_number' => $number,
            'complainant_name' => 'Fictional Complainant',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => $status,
            'registered_by' => $registeredBy,
        ]);
    }

    public function test_a_reporting_officer_only_lists_their_own_complaints(): void
    {
        $officer = User::factory()->create(['role' => 'reporting_officer']);
        $other = User::factory()->create(['role' => 'reporting_officer']);

        $own = $this->complaint('CMP-20261124-ACC1', $officer->id);
        $foreign = $this->complaint('CMP-20261124-ACC2', $other->id);

        $this->actingAs($officer)
            ->get(route('complaints.index'))
            ->assertOk()
            ->assertSee($own->complaint_number)
            ->assertDontSee($foreign->complaint_number);
    }

    public function test_a_reporting_officer_cannot_open_another_officers_complaint(): void
    {
        $officer = User::factory()->create(['role' => 'reporting_officer']);
        $other = User::factory()->create(['role' => 'reporting_officer']);

        $foreign = $this->complaint('CMP-20261124-ACC3', $other->id);

        $this->actingAs($officer)
            ->get(route('complaints.show', $foreign))
            ->assertForbidden();
    }

    public function test_a_supervisor_can_see_all_complaints(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $officer = User::factory()->create(['role' => 'reporting_officer']);

        $complaint = $this->complaint('CMP-20261124-ACC4', $officer->id);

        $this->actingAs($supervisor)
            ->get(route('complaints.index'))
            ->assertOk()
            ->assertSee($complaint->complaint_number);

        $this->actingAs($supervisor)
            ->get(route('complaints.show', $complaint))
            ->assertOk();
    }

    public function test_an_investigator_can_see_complaints_for_assigned_cases_only(): void
    {
        $investigator = User::factory()->create(['role' => 'investigator']);

        $assignedComplaint = $this->complaint('CMP-20261124-ACC5', null, 'approved');
        $assignedCase = PoliceCase::create([
            'complaint_id' => $assignedComplaint->id,
            'case_number' => 'CASE-20261124-ACC5',
            'title' => 'Fictional Assigned Case',
            'description' => 'Fictional case description.',
            'status' => 'open',
            'opened_at' => '2026-11-24',
        ]);
        CaseAssignment::create([
            'case_id' => $assignedCase->id,
            'user_id' => $investigator->id,
            'role' => 'investigator',
            'assigned_at' => '2026-11-24',
        ]);

        $unassignedComplaint = $this->complaint('CMP-20261124-ACC6', null, 'approved');

        $this->actingAs($investigator)
            ->get(route('complaints.show', $assignedComplaint))
            ->assertOk();

        $this->actingAs($investigator)
            ->get(route('complaints.show', $unassignedComplaint))
            ->assertForbidden();
    }

    public function test_registered_complaints_record_the_creating_officer(): void
    {
        $officer = User::factory()->create(['role' => 'reporting_officer']);

        $this->actingAs($officer)
            ->post(route('complaints.store'), [
                'complainant_name' => 'Fictional Complainant',
                'incident_location' => 'Adama',
                'incident_description' => 'Fictional incident description.',
                'incident_date' => '2026-11-24',
            ])
            ->assertRedirect(route('complaints.index'));

        $this->assertDatabaseHas('complaints', [
            'registered_by' => $officer->id,
            'status' => 'pending',
        ]);
    }
}
