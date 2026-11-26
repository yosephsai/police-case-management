<?php

namespace Tests\Feature;

use App\Models\CaseAssignment;
use App\Models\Complaint;
use App\Models\Evidence;
use App\Models\PoliceCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EvidenceAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): PoliceCase
    {
        $complaint = Complaint::create([
            'complaint_number' => 'CMP-20261124-ATT1',
            'complainant_name' => 'Fictional Complainant',
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => '2026-11-24',
            'status' => 'approved',
        ]);

        return PoliceCase::create([
            'complaint_id' => $complaint->id,
            'case_number' => 'CASE-20261124-ATT1',
            'title' => 'Fictional Attachment Case',
            'description' => 'Fictional case description.',
            'status' => 'open',
            'opened_at' => '2026-11-24',
        ]);
    }

    public function test_an_authorized_user_can_upload_and_download_an_attachment(): void
    {
        Storage::fake('local');

        $lead = User::factory()->create(['role' => 'lead_detective']);
        $case = $this->makeCase();

        CaseAssignment::create([
            'case_id' => $case->id,
            'user_id' => $lead->id,
            'role' => 'lead',
            'assigned_at' => '2026-11-24',
        ]);

        $this->actingAs($lead)
            ->post(route('evidences.store', $case), [
                'evidence_number' => 'EVD-ATT-001',
                'evidence_type' => 'Document',
                'description' => 'Fictional evidence description.',
                'collected_at' => '2026-11-24',
                'attachment' => UploadedFile::fake()->create('statement.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect();

        $evidence = Evidence::where('evidence_number', 'EVD-ATT-001')->firstOrFail();

        $this->assertNotNull($evidence->attachment_path);
        Storage::disk('local')->assertExists($evidence->attachment_path);

        $this->actingAs($lead)
            ->get(route('evidences.attachment', $evidence))
            ->assertOk();
    }

    public function test_an_authorized_user_can_upload_and_preview_image_evidence(): void
    {
        Storage::fake('local');

        $lead = User::factory()->create(['role' => 'lead_detective']);
        $case = $this->makeCase();

        CaseAssignment::create([
            'case_id' => $case->id,
            'user_id' => $lead->id,
            'role' => 'lead',
            'assigned_at' => '2026-11-24',
        ]);

        $this->actingAs($lead)
            ->post(route('evidences.store', $case), [
                'evidence_number' => 'EVD-IMG-001',
                'evidence_type' => 'Photograph',
                'description' => 'Crime scene photograph.',
                'collected_at' => '2026-11-24',
                'attachment' => UploadedFile::fake()->create('scene.webp', 120, 'image/webp'),
            ])
            ->assertRedirect();

        $evidence = Evidence::where('evidence_number', 'EVD-IMG-001')->firstOrFail();

        Storage::disk('local')->assertExists($evidence->attachment_path);

        $response = $this->actingAs($lead)
            ->get(route('evidences.attachment', ['evidence' => $evidence, 'inline' => 1]))
            ->assertOk();

        $this->assertStringStartsNotWith("\n", $response->streamedContent());
    }

    public function test_an_unassigned_investigator_cannot_download_an_attachment(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('evidence/secret.pdf', 'fictional');

        $investigator = User::factory()->create(['role' => 'investigator']);
        $case = $this->makeCase();

        $evidence = Evidence::create([
            'case_id' => $case->id,
            'user_id' => $investigator->id,
            'evidence_number' => 'EVD-ATT-002',
            'evidence_type' => 'Document',
            'description' => 'Fictional evidence description.',
            'collected_at' => '2026-11-24',
            'attachment_path' => 'evidence/secret.pdf',
        ]);

        $this->actingAs($investigator)
            ->get(route('evidences.attachment', $evidence))
            ->assertForbidden();
    }

    public function test_invalid_attachment_types_are_rejected(): void
    {
        Storage::fake('local');

        $lead = User::factory()->create(['role' => 'lead_detective']);
        $case = $this->makeCase();

        CaseAssignment::create([
            'case_id' => $case->id,
            'user_id' => $lead->id,
            'role' => 'lead',
            'assigned_at' => '2026-11-24',
        ]);

        $this->actingAs($lead)
            ->post(route('evidences.store', $case), [
                'evidence_number' => 'EVD-ATT-003',
                'evidence_type' => 'Document',
                'description' => 'Fictional evidence description.',
                'collected_at' => '2026-11-24',
                'attachment' => UploadedFile::fake()->create('malware.exe', 10, 'application/x-msdownload'),
            ])
            ->assertSessionHasErrors('attachment');

        $this->assertDatabaseCount('evidences', 0);
    }
}
