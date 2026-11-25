<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_supervisor_can_view_the_audit_log(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        AuditLog::create([
            'user_id' => $supervisor->id,
            'action' => 'complaint.created',
            'table_name' => 'complaints',
            'record_id' => 1,
            'details' => 'Fictional audit detail.',
        ]);

        $this->actingAs($supervisor)
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('complaint.created')
            ->assertSee('Fictional audit detail.');
    }

    public function test_an_investigator_cannot_view_the_audit_log(): void
    {
        $investigator = User::factory()->create(['role' => 'investigator']);

        $this->actingAs($investigator)
            ->get(route('audit-logs.index'))
            ->assertForbidden();
    }
}
