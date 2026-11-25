<?php

namespace Tests\Feature;

use App\Models\CasePerson;
use App\Models\CaseAssignment;
use App\Models\Complaint;
use App\Models\PoliceCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CasePersonTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_associated_investigator_can_add_a_person_to_a_case(): void
    {
        $user = User::factory()->create(['role' => 'investigator']);
        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-TEST13',
            'complainant_name' => 'Fictional Complainant',
            'complainant_phone' => '0910000000',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'approved',
        ]);
        $case = PoliceCase::create([
            'complaint_id' => $complaint->id,
            'case_number' => 'CASE-20261124-TEST13',
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
            ->post(route('case-people.store', ['case' => $case->id]), [
                'full_name' => 'Fictional Witness',
                'role' => 'witness',
                'phone' => '0911111111',
                'notes' => 'Fictional witness note.',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('case_people', [
            'case_id' => $case->id,
            'full_name' => 'Fictional Witness',
            'role' => 'witness',
        ]);
    }
}
