<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\PoliceCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseStatusTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PoliceCase
    {
        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-STAT1',
            'complainant_name' => 'Fictional Complainant',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'approved',
        ]);

        return PoliceCase::create([
            'complaint_id' => $complaint->id,
            'case_number' => 'CASE-20261124-STAT1',
            'title' => 'Fictional Case',
            'description' => 'Fictional case description.',
            'status' => 'open',
            'opened_at' => '2026-11-24',
        ]);
    }

    public function test_a_supervisor_can_move_a_case_from_open_to_under_investigation(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $case = $this->makeCase();

        $response = $this->actingAs($supervisor)
            ->from(route('cases.show', $case))
            ->patch(route('cases.status', $case), [
                'to_status' => 'under_investigation',
                'notes' => 'Investigation started.',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('cases', [
            'id' => $case->id,
            'status' => 'under_investigation',
        ]);

        $this->assertDatabaseHas('case_status_histories', [
            'case_id' => $case->id,
            'user_id' => $supervisor->id,
            'from_status' => 'open',
            'to_status' => 'under_investigation',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $supervisor->id,
            'action' => 'case.status_changed',
        ]);
    }

    public function test_a_disallowed_status_transition_is_rejected(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $case = $this->makeCase();

        $response = $this->actingAs($supervisor)
            ->from(route('cases.show', $case))
            ->patch(route('cases.status', $case), [
                'to_status' => 'closed',
            ]);

        $response->assertSessionHasErrors('to_status');

        $this->assertDatabaseHas('cases', [
            'id' => $case->id,
            'status' => 'open',
        ]);

        $this->assertDatabaseCount('case_status_histories', 0);
    }

    public function test_an_investigator_cannot_change_case_status(): void
    {
        $investigator = User::factory()->create(['role' => 'investigator']);
        $case = $this->makeCase();

        $this->actingAs($investigator)
            ->patch(route('cases.status', $case), [
                'to_status' => 'under_investigation',
            ])
            ->assertForbidden();
    }
}
