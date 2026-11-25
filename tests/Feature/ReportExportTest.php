<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\PoliceCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PoliceCase
    {
        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-EXP1',
            'complainant_name' => 'Fictional Complainant',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'approved',
        ]);

        return PoliceCase::create([
            'complaint_id' => $complaint->id,
            'case_number' => 'CASE-20261124-EXP1',
            'title' => 'Fictional Export Case',
            'description' => 'Fictional case description.',
            'status' => 'open',
            'opened_at' => '2026-11-24',
        ]);
    }

    public function test_a_supervisor_can_export_cases_as_csv(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $this->makeCase();

        $response = $this->actingAs($supervisor)->get(route('reports.cases'));

        $response->assertOk();
        $this->assertStringContainsString('CASE-20261124-EXP1', $response->streamedContent());

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $supervisor->id,
            'action' => 'reports.cases_exported',
        ]);
    }

    public function test_a_supervisor_can_export_complaints_as_csv(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $this->makeCase();

        $response = $this->actingAs($supervisor)->get(route('reports.complaints'));

        $response->assertOk();
        $this->assertStringContainsString('CMP-20261124-EXP1', $response->streamedContent());
    }

    public function test_an_investigator_cannot_export_reports(): void
    {
        $investigator = User::factory()->create(['role' => 'investigator']);
        $this->makeCase();

        $this->actingAs($investigator)
            ->get(route('reports.cases'))
            ->assertForbidden();

        $this->actingAs($investigator)
            ->get(route('reports.complaints'))
            ->assertForbidden();
    }
}
