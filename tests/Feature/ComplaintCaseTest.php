<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Complaint;
use App\Models\PoliceCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_approved_complaint_can_be_converted_to_a_case(): void
    {
        $reviewer = User::factory()->create(['role' => 'reviewing_officer']);
        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-TEST9',
            'complainant_name' => 'Fictional Complainant',
            'complainant_phone' => '0910000000',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($reviewer)
            ->from(route('complaints.show', $complaint))
            ->post(route('complaints.convert-to-case', $complaint));

        $response->assertRedirect();

        $this->assertDatabaseHas('cases', [
            'complaint_id' => $complaint->id,
            'status' => 'open',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $reviewer->id,
            'action' => 'complaint.convert_to_case',
        ]);
    }

    public function test_a_non_approved_complaint_cannot_be_converted_to_a_case(): void
    {
        $reviewer = User::factory()->create(['role' => 'reviewing_officer']);
        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-TEST10',
            'complainant_name' => 'Fictional Complainant',
            'complainant_phone' => '0910000000',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'rejected',
        ]);

        $response = $this->actingAs($reviewer)
            ->from(route('complaints.show', $complaint))
            ->post(route('complaints.convert-to-case', $complaint));

        $response->assertSessionHasErrors('case');

        $this->assertDatabaseCount('cases', 0);
    }
}
