<?php

namespace Database\Seeders;

use App\Models\CaseAssignment;
use App\Models\CasePerson;
use App\Models\CaseStatusHistory;
use App\Models\Complaint;
use App\Models\Evidence;
use App\Models\EvidenceHistory;
use App\Models\InvestigationActivity;
use App\Models\InvestigationReport;
use App\Models\PoliceCase;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        $admin = User::firstOrCreate(
            ['email' => 'admin@demo.test'],
            ['name' => 'Demo Administrator', 'password' => $password, 'role' => 'admin', 'is_active' => true]
        );

        $supervisor = User::firstOrCreate(
            ['email' => 'supervisor@demo.test'],
            ['name' => 'Demo Supervisor', 'password' => $password, 'role' => 'supervisor', 'is_active' => true]
        );

        $commander = User::firstOrCreate(
            ['email' => 'commander@demo.test'],
            ['name' => 'Demo Station Commander', 'password' => $password, 'role' => 'station_commander', 'is_active' => true]
        );

        $reporting = User::firstOrCreate(
            ['email' => 'reporting@demo.test'],
            ['name' => 'Demo Reporting Officer', 'password' => $password, 'role' => 'reporting_officer', 'is_active' => true]
        );

        $reviewing = User::firstOrCreate(
            ['email' => 'reviewing@demo.test'],
            ['name' => 'Demo Reviewing Officer', 'password' => $password, 'role' => 'reviewing_officer', 'is_active' => true]
        );

        $lead = User::firstOrCreate(
            ['email' => 'lead.investigator@demo.test'],
            ['name' => 'Demo Lead Detective', 'password' => $password, 'role' => 'lead_detective', 'is_active' => true]
        );

        $investigator = User::firstOrCreate(
            ['email' => 'investigator@demo.test'],
            ['name' => 'Demo Assistant Investigator', 'password' => $password, 'role' => 'investigator', 'is_active' => true]
        );

        $records = User::firstOrCreate(
            ['email' => 'records@demo.test'],
            ['name' => 'Demo Records Officer', 'password' => $password, 'role' => 'records_officer', 'is_active' => true]
        );

        $complaint = Complaint::firstOrCreate(
            ['complaint_number' => 'CMP-DEMO-0001'],
            [
                'complainant_name' => 'Fictional Complainant',
                'complainant_phone' => '0900-000-000',
                'incident_location' => 'Fictional District, Adama',
                'incident_description' => 'This is fictional demonstration data describing a sample incident.',
                'incident_date' => '2026-11-20',
                'status' => 'approved',
                'registered_by' => $reporting->id,
            ]
        );

        $case = PoliceCase::firstOrCreate(
            ['case_number' => 'CASE-DEMO-0001'],
            [
                'complaint_id' => $complaint->id,
                'title' => 'Demo Case from Complaint ' . $complaint->complaint_number,
                'description' => $complaint->incident_description,
                'status' => 'under_investigation',
                'opened_at' => '2026-11-21',
            ]
        );

        CaseStatusHistory::firstOrCreate(
            ['case_id' => $case->id, 'to_status' => 'open'],
            [
                'user_id' => $admin->id,
                'from_status' => null,
                'notes' => 'Case created from complaint ' . $complaint->complaint_number,
                'changed_at' => '2026-11-21 09:00:00',
            ]
        );

        CaseStatusHistory::firstOrCreate(
            ['case_id' => $case->id, 'to_status' => 'under_investigation'],
            [
                'user_id' => $supervisor->id,
                'from_status' => 'open',
                'notes' => 'Investigation started.',
                'changed_at' => '2026-11-21 10:00:00',
            ]
        );

        CaseAssignment::firstOrCreate(
            ['case_id' => $case->id, 'user_id' => $lead->id],
            ['role' => 'lead', 'assigned_at' => '2026-11-21']
        );

        CaseAssignment::firstOrCreate(
            ['case_id' => $case->id, 'user_id' => $investigator->id],
            ['role' => 'investigator', 'assigned_at' => '2026-11-22']
        );

        InvestigationActivity::firstOrCreate(
            ['case_id' => $case->id, 'activity_type' => 'Initial scene visit'],
            [
                'user_id' => $lead->id,
                'description' => 'Fictional record of an initial scene visit.',
                'activity_date' => '2026-11-22 09:30:00',
            ]
        );

        $evidence = Evidence::firstOrCreate(
            ['evidence_number' => 'EVD-DEMO-0001'],
            [
                'case_id' => $case->id,
                'user_id' => $lead->id,
                'evidence_type' => 'Document',
                'description' => 'Fictional sample document evidence.',
                'storage_location' => 'Fictional evidence store',
                'collected_at' => '2026-11-22',
            ]
        );

        EvidenceHistory::firstOrCreate(
            ['evidence_id' => $evidence->id, 'action' => 'Collected'],
            [
                'user_id' => $lead->id,
                'notes' => 'Fictional collection note.',
                'handled_at' => '2026-11-22 10:00:00',
            ]
        );

        CasePerson::firstOrCreate(
            ['case_id' => $case->id, 'full_name' => 'Fictional Witness'],
            [
                'role' => 'witness',
                'phone' => '0900-111-111',
                'notes' => 'Fictional witness statement summary.',
            ]
        );

        InvestigationReport::firstOrCreate(
            ['case_id' => $case->id, 'review_status' => 'accepted'],
            [
                'submitted_by' => $lead->id,
                'report_summary' => 'Fictional investigation summary submitted for demonstration.',
                'submitted_at' => '2026-11-23 15:00:00',
                'review_notes' => 'Accepted for demonstration purposes.',
                'reviewed_by' => $supervisor->id,
                'reviewed_at' => '2026-11-23 16:00:00',
            ]
        );
    }
}
