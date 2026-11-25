<?php

namespace Database\Seeders;

use App\Models\CaseAssignment;
use App\Models\CasePerson;
use App\Models\CaseStatusHistory;
use App\Models\Complaint;
use App\Models\Evidence;
use App\Models\InvestigationActivity;
use App\Models\InvestigationReport;
use App\Models\PoliceCase;
use App\Models\User;
use Illuminate\Database\Seeder;

class BulkDemoDataSeeder extends Seeder
{
    private const TOTAL = 100;

    public function run(): void
    {
        $password = bcrypt('password');

        $reporting = User::firstOrCreate(
            ['email' => 'bulk.reporting@demo.test'],
            ['name' => 'Bulk Demo Reporting Officer', 'password' => $password,
                'role' => 'reporting_officer', 'is_active' => true]
        );

        $lead = User::firstOrCreate(
            ['email' => 'bulk.lead@demo.test'],
            ['name' => 'Bulk Demo Lead Detective', 'password' => $password,
                'role' => 'lead_detective', 'is_active' => true]
        );

        for ($i = 1; $i <= self::TOTAL; $i++) {
            $number = 'CMP-BULK-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT);

            $complaint = Complaint::firstOrCreate(
                ['complaint_number' => $number],
                [
                    'complainant_name' => 'Fictional Complainant ' . $i,
                    'incident_location' => 'Fictional District ' . ($i % 10),
                    'incident_description' => 'Fictional bulk incident description ' . $i . ' with searchable token.',
                    'incident_date' => '2026-11-' . str_pad((string) (($i % 28) + 1), 2, '0', STR_PAD_LEFT),
                    'status' => 'approved',
                    'registered_by' => $reporting->id,
                ]
            );

            $caseNumber = 'CASE-BULK-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT);

            $case = PoliceCase::firstOrCreate(
                ['case_number' => $caseNumber],
                [
                    'complaint_id' => $complaint->id,
                    'title' => 'Fictional Bulk Case ' . $i,
                    'description' => 'Fictional bulk case description ' . $i . '.',
                    'status' => 'open',
                    'opened_at' => '2026-11-24',
                ]
            );

            if (! $case->wasRecentlyCreated) {
                continue;
            }

            CaseStatusHistory::create([
                'case_id' => $case->id,
                'user_id' => $reporting->id,
                'from_status' => null,
                'to_status' => 'open',
                'notes' => 'Bulk demo case created.',
                'changed_at' => now(),
            ]);

            CaseAssignment::create([
                'case_id' => $case->id,
                'user_id' => $lead->id,
                'role' => 'lead',
                'assigned_at' => '2026-11-24',
            ]);

            InvestigationActivity::create([
                'case_id' => $case->id,
                'user_id' => $lead->id,
                'activity_type' => 'Interview',
                'description' => 'Fictional bulk activity.',
                'activity_date' => '2026-11-24 10:00:00',
            ]);

            Evidence::create([
                'case_id' => $case->id,
                'user_id' => $lead->id,
                'evidence_number' => 'EVD-BULK-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'evidence_type' => 'Document',
                'description' => 'Fictional bulk evidence.',
                'storage_location' => null,
                'collected_at' => '2026-11-24',
            ]);

            CasePerson::create([
                'case_id' => $case->id,
                'role' => 'witness',
                'full_name' => 'Fictional Witness ' . $i,
                'phone' => null,
                'notes' => null,
            ]);

            InvestigationReport::create([
                'case_id' => $case->id,
                'submitted_by' => $lead->id,
                'report_summary' => 'Fictional bulk report summary.',
                'submitted_at' => now(),
                'review_status' => 'pending',
            ]);
        }
    }
}
