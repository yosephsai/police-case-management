<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\PoliceCase;
use App\Models\User;
use App\Support\SearchIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FullTextSearchTest extends TestCase
{
    use RefreshDatabase;

    private function complaint(string $description): Complaint
    {
        return Complaint::create([
            'complaint_number' => 'CMP-20261124-FTS' . random_int(100, 999),
            'complainant_name' => 'Fictional Complainant',
            'incident_location' => 'Adama',
            'incident_description' => $description,
            'incident_date' => '2026-11-24',
            'status' => 'pending',
        ]);
    }

    public function test_full_text_index_tables_exist(): void
    {
        $this->assertTrue(SearchIndex::available('complaints_fts'));
        $this->assertTrue(SearchIndex::available('cases_fts'));
    }

    public function test_complaint_search_matches_description_text(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $complaint = $this->complaint('Fictional description with Tokenuniquexyz present.');

        $this->actingAs($supervisor)
            ->get(route('complaints.index', ['search' => 'Tokenuniquexyz']))
            ->assertOk()
            ->assertSee($complaint->complaint_number);
    }

    public function test_full_text_index_is_updated_when_a_complaint_changes(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $complaint = $this->complaint('Oldtokenuniquexyz in description.');

        $complaint->update([
            'incident_description' => 'Newtokenuniquexyz in description.',
        ]);

        $this->actingAs($supervisor)
            ->get(route('complaints.index', ['search' => 'Newtokenuniquexyz']))
            ->assertOk()
            ->assertSee($complaint->complaint_number);

        $this->actingAs($supervisor)
            ->get(route('complaints.index', ['search' => 'Oldtokenuniquexyz']))
            ->assertOk()
            ->assertDontSee($complaint->complaint_number);
    }

    public function test_case_search_matches_description_text(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $complaint = $this->complaint('Fictional complaint description.');
        $case = PoliceCase::create([
            'complaint_id' => $complaint->id,
            'case_number' => 'CASE-20261124-FTS1',
            'title' => 'Fictional Case',
            'description' => 'Case detail with Casetokenuniquexyz present.',
            'status' => 'open',
            'opened_at' => '2026-11-24',
        ]);

        $this->actingAs($supervisor)
            ->get(route('cases.index', ['search' => 'Casetokenuniquexyz']))
            ->assertOk()
            ->assertSee($case->case_number);
    }

    public function test_a_punctuation_only_search_falls_back_without_an_error(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $this->complaint('Fictional complaint description.');

        $this->actingAs($supervisor)
            ->get(route('complaints.index', ['search' => '!!!']))
            ->assertOk();
    }
}
