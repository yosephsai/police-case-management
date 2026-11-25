<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintRegistrationRoleTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        return [
            'complainant_name' => 'Fictional Complainant',
            'complainant_phone' => '912345678',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => now()->toDateString(),
        ];
    }

    public function test_a_reporting_officer_can_register_complaints(): void
    {
        $officer = User::factory()->create(['role' => 'reporting_officer']);

        $this->actingAs($officer)
            ->get(route('complaints.create'))
            ->assertOk()
            ->assertSee('Register Complaint');

        $this->actingAs($officer)
            ->get(route('complaints.index'))
            ->assertOk()
            ->assertSee('Register Complaint');

        $this->actingAs($officer)
            ->post(route('complaints.store'), $this->payload())
            ->assertRedirect(route('complaints.index'));

        $this->assertDatabaseHas('complaints', [
            'registered_by' => $officer->id,
            'complainant_phone' => '+251912345678',
        ]);
    }

    public function test_all_other_roles_cannot_register_complaints(): void
    {
        foreach ([
            'admin',
            'station_commander',
            'reviewing_officer',
            'supervisor',
            'lead_detective',
            'investigator',
            'records_officer',
        ] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('complaints.create'))
                ->assertForbidden();

            $this->actingAs($user)
                ->post(route('complaints.store'), $this->payload())
                ->assertForbidden();
        }

        $this->assertDatabaseCount('complaints', 0);
    }

    public function test_the_registration_button_is_hidden_from_other_roles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('complaints.index'))
            ->assertOk()
            ->assertDontSee('Register Complaint');
    }
}
