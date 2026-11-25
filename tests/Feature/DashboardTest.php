<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\PoliceCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_dashboard_renders_administrative_summaries(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-DASH1',
            'complainant_name' => 'Fictional Complainant',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'approved',
        ]);

        PoliceCase::create([
            'complaint_id' => $complaint->id,
            'case_number' => 'CASE-20261124-DASH1',
            'title' => 'Fictional Dashboard Case',
            'description' => 'Fictional case description.',
            'status' => 'open',
            'opened_at' => '2026-11-24',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Complaints by Status')
            ->assertSee('Cases by Status')
            ->assertSee('Administrative Report Exports')
            ->assertSee('CASE-20261124-DASH1');
    }

    public function test_each_role_has_a_dedicated_dashboard_path_and_cannot_open_others(): void
    {
        $detective = User::factory()->create(['role' => 'lead_detective']);

        $this->actingAs($detective)
            ->get(route('role.dashboard', ['rolePath' => 'detective']))
            ->assertOk()
            ->assertSee('Lead Detective');

        $this->actingAs($detective)
            ->get(route('role.dashboard', ['rolePath' => 'admin']))
            ->assertForbidden();
    }
}
