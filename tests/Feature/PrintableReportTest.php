<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\PoliceCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrintableReportTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PoliceCase
    {
        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-PRT1',
            'complainant_name' => 'Fictional Complainant',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'approved',
        ]);

        return PoliceCase::create([
            'complaint_id' => $complaint->id,
            'case_number' => 'CASE-20261124-PRT1',
            'title' => 'Fictional Printable Case',
            'description' => 'Fictional case description.',
            'status' => 'open',
            'opened_at' => '2026-11-24',
        ]);
    }

    public function test_a_supervisor_can_open_the_printable_case_view(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $case = $this->makeCase();

        $this->actingAs($supervisor)
            ->get(route('cases.print', $case))
            ->assertOk()
            ->assertSee('CASE-20261124-PRT1')
            ->assertSee('not a legal judgment');
    }

    public function test_an_unassigned_investigator_cannot_open_the_printable_case_view(): void
    {
        $investigator = User::factory()->create(['role' => 'investigator']);
        $case = $this->makeCase();

        $this->actingAs($investigator)
            ->get(route('cases.print', $case))
            ->assertForbidden();
    }

    public function test_a_supervisor_can_open_the_printable_summary(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $this->makeCase();

        $this->actingAs($supervisor)
            ->get(route('reports.summary'))
            ->assertOk()
            ->assertSee('Administrative Summary')
            ->assertSee('CASE-20261124-PRT1');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $supervisor->id,
            'action' => 'reports.summary_viewed',
        ]);
    }

    public function test_an_investigator_cannot_open_the_printable_summary(): void
    {
        $investigator = User::factory()->create(['role' => 'investigator']);

        $this->actingAs($investigator)
            ->get(route('reports.summary'))
            ->assertForbidden();
    }
}
