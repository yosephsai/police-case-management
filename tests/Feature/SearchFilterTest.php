<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\PoliceCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchFilterTest extends TestCase
{
    use RefreshDatabase;

    private function complaint(string $number, array $attributes = []): Complaint
    {
        return Complaint::create(array_merge([
            'complaint_number' => $number,
            'complainant_name' => 'Fictional Complainant',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-01',
            'status' => 'pending',
        ], $attributes));
    }

    private function caseFor(Complaint $complaint, string $number, array $attributes = []): PoliceCase
    {
        return PoliceCase::create(array_merge([
            'complaint_id' => $complaint->id,
            'case_number' => $number,
            'title' => 'Fictional Case',
            'description' => 'Fictional case description.',
            'status' => 'open',
            'opened_at' => '2026-11-01',
        ], $attributes));
    }

    public function test_complaint_search_ranks_reference_matches_first(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $exact = $this->complaint('CMP-20261124-SRCH');
        $prefix = $this->complaint('CMP-20261124-SRCH-2');
        $unrelated = $this->complaint('CMP-20261124-OTHER');

        $this->actingAs($supervisor)
            ->get(route('complaints.index', ['search' => 'CMP-20261124-SRCH']))
            ->assertOk()
            ->assertSeeInOrder([$exact->complaint_number, $prefix->complaint_number])
            ->assertDontSee($unrelated->complaint_number);
    }

    public function test_complaint_status_and_date_filters_apply(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $pending = $this->complaint('CMP-20261124-FILT1', [
            'status' => 'pending',
            'incident_date' => '2026-11-05',
        ]);
        $approved = $this->complaint('CMP-20261124-FILT2', [
            'status' => 'approved',
            'incident_date' => '2026-11-25',
        ]);

        $this->actingAs($supervisor)
            ->get(route('complaints.index', [
                'status' => 'pending',
                'from' => '2026-11-01',
                'to' => '2026-11-10',
            ]))
            ->assertOk()
            ->assertSee($pending->complaint_number)
            ->assertDontSee($approved->complaint_number);
    }

    public function test_case_search_ranks_reference_matches_first(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $exact = $this->caseFor($this->complaint('CMP-20261124-CSRCH1'), 'CASE-20261124-SRCH');
        $prefix = $this->caseFor($this->complaint('CMP-20261124-CSRCH2'), 'CASE-20261124-SRCH-2');

        $this->actingAs($supervisor)
            ->get(route('cases.index', ['search' => 'CASE-20261124-SRCH']))
            ->assertOk()
            ->assertSeeInOrder([$exact->case_number, $prefix->case_number]);
    }

    public function test_case_status_and_date_filters_apply(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $open = $this->caseFor($this->complaint('CMP-20261124-CFILT1'), 'CASE-20261124-CFILT1', [
            'status' => 'open',
            'opened_at' => '2026-11-05',
        ]);
        $closed = $this->caseFor($this->complaint('CMP-20261124-CFILT2'), 'CASE-20261124-CFILT2', [
            'status' => 'closed',
            'opened_at' => '2026-11-25',
        ]);

        $this->actingAs($supervisor)
            ->get(route('cases.index', [
                'status' => 'open',
                'from' => '2026-11-01',
                'to' => '2026-11-10',
            ]))
            ->assertOk()
            ->assertSee($open->case_number)
            ->assertDontSee($closed->case_number);
    }

    public function test_an_invalid_date_range_is_rejected(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $this->actingAs($supervisor)
            ->from(route('complaints.index'))
            ->get(route('complaints.index', [
                'from' => '2026-11-20',
                'to' => '2026-11-01',
            ]))
            ->assertRedirect(route('complaints.index'))
            ->assertSessionHasErrors('to');
    }
}
