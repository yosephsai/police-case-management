<?php

namespace Tests\Feature;

use App\Models\CaseAssignment;
use App\Models\CasePerson;
use App\Models\CaseStatusHistory;
use App\Models\Complaint;
use App\Models\Evidence;
use App\Models\InvestigationActivity;
use App\Models\InvestigationReport;
use App\Models\PoliceCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CasesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_cases_index_and_show_pages_render_with_related_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $investigator = User::factory()->create(['role' => 'investigator']);

        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-PAGE1',
            'complainant_name' => 'Fictional Complainant',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'approved',
        ]);

        $case = PoliceCase::create([
            'complaint_id' => $complaint->id,
            'case_number' => 'CASE-20261124-PAGE1',
            'title' => 'Fictional Case Title',
            'description' => 'Fictional case description.',
            'status' => 'under_investigation',
            'opened_at' => '2026-11-24',
        ]);

        CaseAssignment::create([
            'case_id' => $case->id,
            'user_id' => $investigator->id,
            'role' => 'lead',
            'assigned_at' => '2026-11-24',
        ]);

        InvestigationActivity::create([
            'case_id' => $case->id,
            'user_id' => $investigator->id,
            'activity_type' => 'Interview',
            'description' => 'Fictional interview activity.',
            'activity_date' => '2026-11-24 10:00:00',
        ]);

        $evidence = Evidence::create([
            'case_id' => $case->id,
            'user_id' => $investigator->id,
            'evidence_number' => 'EVD-20261124-1',
            'evidence_type' => 'Document',
            'description' => 'Fictional evidence description.',
            'storage_location' => 'Fictional store room',
            'collected_at' => '2026-11-24',
        ]);

        CasePerson::create([
            'case_id' => $case->id,
            'full_name' => 'Fictional Witness',
            'role' => 'witness',
        ]);

        InvestigationReport::create([
            'case_id' => $case->id,
            'submitted_by' => $investigator->id,
            'report_summary' => 'Fictional report summary.',
            'submitted_at' => now(),
            'review_status' => 'pending',
        ]);

        CaseStatusHistory::create([
            'case_id' => $case->id,
            'user_id' => $admin->id,
            'from_status' => 'open',
            'to_status' => 'under_investigation',
            'changed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('cases.index'))
            ->assertOk()
            ->assertSee('CASE-20261124-PAGE1');

        $this->actingAs($admin)
            ->get(route('cases.show', $case))
            ->assertOk()
            ->assertSee('CASE-20261124-PAGE1')
            ->assertSee('Fictional Case Title')
            ->assertSee('Interview')
            ->assertSee('EVD-20261124-1')
            ->assertSee('Fictional Witness')
            ->assertSee($investigator->name);
    }
}
