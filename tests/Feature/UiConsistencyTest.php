<?php

namespace Tests\Feature;

use App\Models\CaseAssignment;
use App\Models\Complaint;
use App\Models\PoliceCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedRoutes(): array
    {
        return [
            'dashboard' => route('dashboard'),
            'complaints' => route('complaints.index'),
            'cases' => route('cases.index'),
            'staff' => route('staff.index'),
            'permissions' => route('permissions.index'),
            'audit' => route('audit-logs.index'),
            'security' => route('two-factor.show'),
        ];
    }

    public function test_every_authenticated_screen_uses_the_shared_shell(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach ($this->authenticatedRoutes() as $name => $url) {
            $response = $this->actingAs($admin)->get($url);

            $response->assertOk()
                ->assertSee('id="appSidebar"', false)
                ->assertSee('aria-label="Main navigation"', false)
                ->assertSee('class="topbar"', false)
                ->assertSee('aria-label="Breadcrumb"', false)
                ->assertSee('data-sidebar-toggle', false)
                ->assertSee('data-sidebar-close', false)
                ->assertSee('data-sidebar-overlay', false)
                ->assertSee('/css/app.css', false)
                ->assertDontSee('cdn.jsdelivr.net/npm/bootstrap', false)
                ->assertDontSee('class="btn btn-primary"', false)
                ->assertDontSee('class="card shadow-sm"', false);
        }
    }

    public function test_active_navigation_and_permissions_are_visible(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('nav-item active', false)
            ->assertSee('Dashboard')
            ->assertSee('Complaints')
            ->assertSee('Cases')
            ->assertSee('Reports')
            ->assertSee('Audit Log')
            ->assertSee('User Management')
            ->assertSee('Permissions')
            ->assertSee('System Settings');
    }

    public function test_limited_roles_only_see_their_navigation_items(): void
    {
        $investigator = User::factory()->create(['role' => 'investigator']);

        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-NAV1',
            'complainant_name' => 'Fictional Complainant',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'approved',
        ]);

        $case = PoliceCase::create([
            'complaint_id' => $complaint->id,
            'case_number' => 'CASE-20261124-NAV1',
            'title' => 'Fictional Navigation Case',
            'description' => 'Fictional case description.',
            'status' => 'open',
            'opened_at' => '2026-11-24',
        ]);

        CaseAssignment::create([
            'case_id' => $case->id,
            'user_id' => $investigator->id,
            'role' => 'investigator',
            'assigned_at' => '2026-11-24',
        ]);

        $this->actingAs($investigator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Cases')
            ->assertDontSee('User Management')
            ->assertDontSee('Audit Log')
            ->assertDontSee('Permissions');
    }

    public function test_authentication_screens_use_the_shared_design_language(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('auth-shell', false)
            ->assertSee('/css/app.css', false)
            ->assertSee('name="remember"', false)
            ->assertSee('Keep me signed in')
            ->assertSee('auth-eyebrow', false)
            ->assertSee('input--lg', false)
            ->assertSee('assets/ethiopia-police-logo.webp', false)
            ->assertSee('Ethiopian Police Management System')
            ->assertSee('Service with integrity. Records with accountability.')
            ->assertDontSee('Clear records. Coherent cases.')
            ->assertDontSee('Demonstration application', false)
            ->assertDontSee('id="appSidebar"', false);

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('auth-shell', false);

        $this->get(route('two-factor.challenge'))
            ->assertRedirect(route('login'));
    }

    public function test_validation_errors_are_rendered_globally(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->followingRedirects()
            ->from(route('complaints.index'))
            ->get(route('complaints.index', [
                'from' => '2026-11-20',
                'to' => '2026-11-01',
            ]))
            ->assertOk()
            ->assertSee('Please review the following', false);
    }

    public function test_detail_and_form_screens_also_use_the_shared_shell(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'investigator']);

        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-UI1',
            'complainant_name' => 'Fictional Complainant',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'approved',
            'registered_by' => $admin->id,
        ]);

        $case = PoliceCase::create([
            'complaint_id' => $complaint->id,
            'case_number' => 'CASE-20261124-UI1',
            'title' => 'Fictional UI Case',
            'description' => 'Fictional case description.',
            'status' => 'open',
            'opened_at' => '2026-11-24',
        ]);

        $screens = [
            route('complaints.show', $complaint),
            route('cases.show', $case),
            route('staff.create'),
            route('staff.edit', $target),
            route('staff.permissions.edit', $target),
        ];

        foreach ($screens as $url) {
            $this->actingAs($admin)
                ->get($url)
                ->assertOk()
                ->assertSee('id="appSidebar"', false)
                ->assertSee('class="topbar"', false)
                ->assertSee('aria-label="Breadcrumb"', false)
                ->assertDontSee('cdn.jsdelivr.net/npm/bootstrap', false);
        }

        $officer = User::factory()->create(['role' => 'reporting_officer']);

        $this->actingAs($officer)
            ->get(route('complaints.create'))
            ->assertOk()
            ->assertSee('id="appSidebar"', false)
            ->assertSee('class="topbar"', false)
            ->assertSee('aria-label="Breadcrumb"', false);
    }

    public function test_print_screens_keep_the_shared_shell_and_print_bar(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-UI2',
            'complainant_name' => 'Fictional Complainant',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'approved',
            'registered_by' => $admin->id,
        ]);

        $case = PoliceCase::create([
            'complaint_id' => $complaint->id,
            'case_number' => 'CASE-20261124-UI2',
            'title' => 'Fictional Print Case',
            'description' => 'Fictional case description.',
            'status' => 'open',
            'opened_at' => '2026-11-24',
        ]);

        $this->actingAs($admin)
            ->get(route('cases.print', $case))
            ->assertOk()
            ->assertSee('id="appSidebar"', false)
            ->assertSee('print-document', false)
            ->assertSee('print-bar', false)
            ->assertSee('Evidence Handling History');

        $this->actingAs($admin)
            ->get(route('reports.summary'))
            ->assertOk()
            ->assertSee('id="appSidebar"', false)
            ->assertSee('print-document', false)
            ->assertSee('Administrative Summary');
    }

    public function test_dashboard_shows_recent_records_and_pending_actions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-UI3',
            'complainant_name' => 'Fictional Complainant',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'pending',
            'registered_by' => $admin->id,
        ]);

        $case = PoliceCase::create([
            'complaint_id' => $complaint->id,
            'case_number' => 'CASE-20261124-UI3',
            'title' => 'Fictional Dashboard Case',
            'description' => 'Fictional case description.',
            'status' => 'open',
            'opened_at' => '2026-11-24',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Recent Complaints')
            ->assertSee('Pending Actions')
            ->assertSee('Activity History')
            ->assertSee($complaint->complaint_number)
            ->assertSee($case->case_number);
    }

    public function test_index_tables_display_actual_status_badge_labels(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $pendingComplaint = Complaint::create([
            'complaint_number' => 'CMP-20261125-BADGE1',
            'complainant_name' => 'Fictional Complainant',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional pending complaint.',
            'incident_date' => '2026-11-25',
            'status' => 'pending',
            'registered_by' => $admin->id,
        ]);

        $approvedComplaint = Complaint::create([
            'complaint_number' => 'CMP-20261125-BADGE2',
            'complainant_name' => 'Fictional Complainant Two',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional approved complaint.',
            'incident_date' => '2026-11-25',
            'status' => 'approved',
            'registered_by' => $admin->id,
        ]);

        PoliceCase::create([
            'complaint_id' => $approvedComplaint->id,
            'case_number' => 'CASE-20261125-BADGE1',
            'title' => 'Fictional Open Case',
            'description' => 'Fictional case description.',
            'status' => 'open',
            'opened_at' => '2026-11-25',
        ]);

        $this->actingAs($admin)
            ->get(route('complaints.index'))
            ->assertOk()
            ->assertSee('<span class="badge badge--warning">Pending</span>', false)
            ->assertSee('<span class="badge badge--success">Approved</span>', false);

        $this->actingAs($admin)
            ->get(route('cases.index'))
            ->assertOk()
            ->assertSee('<span class="badge badge--info">Open</span>', false);
    }
}
