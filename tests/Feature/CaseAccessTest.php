<?php

namespace Tests\Feature;

use App\Models\CaseAssignment;
use App\Models\Complaint;
use App\Models\PoliceCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseAccessTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(string $suffix): PoliceCase
    {
        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-ACC' . $suffix,
            'complainant_name' => 'Fictional Complainant',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'approved',
        ]);

        return PoliceCase::create([
            'complaint_id' => $complaint->id,
            'case_number' => 'CASE-20261124-ACC' . $suffix,
            'title' => 'Fictional Access Case ' . $suffix,
            'description' => 'Fictional case description.',
            'status' => 'open',
            'opened_at' => '2026-11-24',
        ]);
    }

    public function test_an_assigned_investigator_can_view_the_case(): void
    {
        $investigator = User::factory()->create(['role' => 'investigator']);
        $case = $this->makeCase('1');

        CaseAssignment::create([
            'case_id' => $case->id,
            'user_id' => $investigator->id,
            'role' => 'lead',
            'assigned_at' => '2026-11-24',
        ]);

        $this->actingAs($investigator)
            ->get(route('cases.show', $case))
            ->assertOk()
            ->assertSee($case->case_number);
    }

    public function test_an_unassigned_investigator_cannot_view_the_case(): void
    {
        $investigator = User::factory()->create(['role' => 'investigator']);
        $case = $this->makeCase('2');

        $this->actingAs($investigator)
            ->get(route('cases.show', $case))
            ->assertForbidden();
    }

    public function test_the_case_index_only_lists_cases_visible_to_the_investigator(): void
    {
        $investigator = User::factory()->create(['role' => 'investigator']);

        $assigned = $this->makeCase('3');
        $unassigned = $this->makeCase('4');

        CaseAssignment::create([
            'case_id' => $assigned->id,
            'user_id' => $investigator->id,
            'role' => 'lead',
            'assigned_at' => '2026-11-24',
        ]);

        $this->actingAs($investigator)
            ->get(route('cases.index'))
            ->assertOk()
            ->assertSee($assigned->case_number)
            ->assertDontSee($unassigned->case_number);
    }

    public function test_a_supervisor_can_view_any_case(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $case = $this->makeCase('5');

        $this->actingAs($supervisor)
            ->get(route('cases.show', $case))
            ->assertOk();
    }

    public function test_an_unassigned_investigator_cannot_write_to_the_case(): void
    {
        $investigator = User::factory()->create(['role' => 'investigator']);
        $case = $this->makeCase('6');

        $this->actingAs($investigator)
            ->post(route('investigation-activities.store', $case), [
                'activity_type' => 'Interview',
                'description' => 'Fictional activity.',
                'activity_date' => '2026-11-24 10:00:00',
            ])
            ->assertForbidden();

        $this->actingAs($investigator)
            ->post(route('evidences.store', $case), [
                'evidence_number' => 'EVD-ACC-6',
                'evidence_type' => 'Document',
                'description' => 'Fictional evidence.',
                'collected_at' => '2026-11-24',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('investigation_activities', 0);
        $this->assertDatabaseCount('evidences', 0);
    }
}
