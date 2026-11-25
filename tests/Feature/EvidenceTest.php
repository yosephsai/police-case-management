<?php

namespace Tests\Feature;

use App\Models\CaseAssignment;
use App\Models\Complaint;
use App\Models\Evidence;
use App\Models\PoliceCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvidenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_user_can_create_evidence_for_a_case(): void
    {
        $user = User::factory()->create(['role' => 'investigator']);
        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-TEST11',
            'complainant_name' => 'Fictional Complainant',
            'complainant_phone' => '0910000000',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'approved',
        ]);
        $case = PoliceCase::create([
            'complaint_id' => $complaint->id,
            'case_number' => 'CASE-20261124-TEST11',
            'title' => 'Fictional Case',
            'description' => 'Fictional case description.',
            'status' => 'open',
            'opened_at' => now()->toDateString(),
        ]);
        CaseAssignment::create([
            'case_id' => $case->id,
            'user_id' => $user->id,
            'role' => 'lead',
            'assigned_at' => now()->toDateString(),
        ]);


        $response = $this->actingAs($user)
            ->post(route('evidences.store', ['case' => $case->id]), [
                'evidence_number' => 'EV-20261124-001',
                'evidence_type' => 'Photograph',
                'description' => 'Fictional evidence description.',
                'storage_location' => 'Adama station storage',
                'collected_at' => '2026-11-24',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('evidences', [
            'case_id' => $case->id,
            'user_id' => $user->id,
            'evidence_number' => 'EV-20261124-001',
        ]);
    }

    public function test_an_authorized_user_can_record_evidence_handling(): void
    {
        $user = User::factory()->create(['role' => 'investigator']);
        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-TEST12',
            'complainant_name' => 'Fictional Complainant',
            'complainant_phone' => '0910000000',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'approved',
        ]);
        $case = PoliceCase::create([
            'complaint_id' => $complaint->id,
            'case_number' => 'CASE-20261124-TEST12',
            'title' => 'Fictional Case',
            'description' => 'Fictional case description.',
            'status' => 'open',
            'opened_at' => now()->toDateString(),
        ]);
        CaseAssignment::create([
            'case_id' => $case->id,
            'user_id' => $user->id,
            'role' => 'lead',
            'assigned_at' => now()->toDateString(),
        ]);

        $evidence = Evidence::create([
            'case_id' => $case->id,
            'user_id' => $user->id,
            'evidence_number' => 'EV-20261124-002',
            'evidence_type' => 'Document',
            'description' => 'Fictional evidence description.',
            'collected_at' => '2026-11-24',
        ]);

        $response = $this->actingAs($user)
            ->post(route('evidence-histories.store', ['evidence' => $evidence->id]), [
                'action' => 'Transferred',
                'notes' => 'Fictional transfer note.',
                'handled_at' => '2026-11-24 11:00:00',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('evidence_histories', [
            'evidence_id' => $evidence->id,
            'user_id' => $user->id,
            'action' => 'Transferred',
        ]);
    }
}
