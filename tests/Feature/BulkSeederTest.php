<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\BulkDemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_seeder_creates_a_consistent_fictional_dataset(): void
    {
        $this->seed(BulkDemoDataSeeder::class);

        $this->assertDatabaseCount('complaints', 100);
        $this->assertDatabaseCount('cases', 100);
        $this->assertDatabaseCount('case_assignments', 100);
        $this->assertDatabaseCount('investigation_activities', 100);
        $this->assertDatabaseCount('evidences', 100);
        $this->assertDatabaseCount('case_people', 100);
        $this->assertDatabaseCount('investigation_reports', 100);
    }

    public function test_lists_and_dashboard_work_with_the_bulk_dataset(): void
    {
        $this->seed(BulkDemoDataSeeder::class);

        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $this->actingAs($supervisor)
            ->get(route('complaints.index', ['search' => 'CMP-BULK-0050']))
            ->assertOk()
            ->assertSee('CMP-BULK-0050');

        $this->actingAs($supervisor)
            ->get(route('cases.index', ['search' => 'CASE-BULK-0050']))
            ->assertOk()
            ->assertSee('CASE-BULK-0050');

        $this->actingAs($supervisor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Complaints by Status');
    }

    public function test_bulk_seeder_can_be_run_twice_without_duplicating_records(): void
    {
        $this->seed(BulkDemoDataSeeder::class);
        $this->seed(BulkDemoDataSeeder::class);

        $this->assertDatabaseCount('complaints', 100);
        $this->assertDatabaseCount('cases', 100);
        $this->assertDatabaseCount('case_assignments', 100);
    }
}
