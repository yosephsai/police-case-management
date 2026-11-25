<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_user_can_approve_a_complaint(): void
    {
        $reviewer = User::factory()->create(['role' => 'reviewing_officer']);
        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-TEST1',
            'complainant_name' => 'Fictional Complainant',
            'complainant_phone' => '0910000000',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($reviewer)
            ->post(route('complaints.review', $complaint), [
                'decision' => 'approved',
                'reason' => null,
            ]);

        $response->assertRedirect(route('complaints.show', $complaint));

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('complaint_reviews', [
            'complaint_id' => $complaint->id,
            'user_id' => $reviewer->id,
            'decision' => 'approved',
        ]);
    }

    public function test_a_complaint_can_be_rejected_with_a_reason(): void
    {
        $reviewer = User::factory()->create(['role' => 'reviewing_officer']);
        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-TEST2',
            'complainant_name' => 'Fictional Complainant',
            'complainant_phone' => '0910000000',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($reviewer)
            ->post(route('complaints.review', $complaint), [
                'decision' => 'rejected',
                'reason' => 'Fictional rejection reason.',
            ]);

        $response->assertRedirect(route('complaints.show', $complaint));

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'rejected',
        ]);

        $this->assertDatabaseHas('complaint_reviews', [
            'complaint_id' => $complaint->id,
            'user_id' => $reviewer->id,
            'decision' => 'rejected',
        ]);
    }

    public function test_a_rejection_requires_a_reason(): void
    {
        $reviewer = User::factory()->create(['role' => 'reviewing_officer']);
        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-TEST3',
            'complainant_name' => 'Fictional Complainant',
            'complainant_phone' => '0910000000',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($reviewer)
            ->from(route('complaints.show', $complaint))
            ->post(route('complaints.review', $complaint), [
                'decision' => 'rejected',
                'reason' => null,
            ]);

        $response->assertSessionHasErrors('reason');

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'pending',
        ]);
    }

    public function test_a_complaint_cannot_be_reviewed_twice(): void
    {
        $reviewer = User::factory()->create(['role' => 'reviewing_officer']);
        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-TEST4',
            'complainant_name' => 'Fictional Complainant',
            'complainant_phone' => '0910000000',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($reviewer)
            ->from(route('complaints.show', $complaint))
            ->post(route('complaints.review', $complaint), [
                'decision' => 'approved',
                'reason' => null,
            ]);

        $response->assertSessionHasErrors('review');

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'approved',
        ]);
    }
}
