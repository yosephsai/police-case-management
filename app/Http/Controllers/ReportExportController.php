<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Complaint;
use App\Models\Evidence;
use App\Models\InvestigationReport;
use App\Models\PoliceCase;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function complaints(): StreamedResponse
    {
        $this->recordAudit('reports.complaints_exported', 'Complaints report exported as CSV.');

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Complaint Number',
                'Complainant',
                'Phone',
                'Location',
                'Incident Date',
                'Status',
                'Created At',
            ]);

            Complaint::query()->orderBy('id')->chunk(500, function ($complaints) use ($handle) {
                foreach ($complaints as $complaint) {
                    fputcsv($handle, [
                        $complaint->complaint_number,
                        $complaint->complainant_name,
                        $complaint->complainant_phone,
                        $complaint->incident_location,
                        $complaint->incident_date,
                        $complaint->status,
                        $complaint->created_at,
                    ]);
                }
            });

            fclose($handle);
        }, 'complaints-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv']);
    }

    public function cases(): StreamedResponse
    {
        $this->recordAudit('reports.cases_exported', 'Cases report exported as CSV.');

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Case Number',
                'Title',
                'Status',
                'Source Complaint',
                'Lead Investigator',
                'Opened At',
                'Created At',
            ]);

            PoliceCase::query()
                ->with(['complaint', 'assignments.user'])
                ->orderBy('id')
                ->chunk(500, function ($cases) use ($handle) {
                    foreach ($cases as $case) {
                        $lead = $case->assignments->firstWhere('role', 'lead');

                        fputcsv($handle, [
                            $case->case_number,
                            $case->title,
                            $case->status,
                            $case->complaint?->complaint_number,
                            $lead?->user?->name,
                            $case->opened_at,
                            $case->created_at,
                        ]);
                    }
                });

            fclose($handle);
        }, 'cases-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv']);
    }

    public function summary()
    {
        $this->recordAudit('reports.summary_viewed', 'Printable administrative summary viewed.');

        $complaintStatuses = Complaint::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $caseStatuses = PoliceCase::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('reports.summary', [
            'complaintStatuses' => $complaintStatuses,
            'caseStatuses' => $caseStatuses,
            'totals' => [
                'complaints' => Complaint::count(),
                'cases' => PoliceCase::count(),
                'evidences' => Evidence::count(),
                'staff' => User::count(),
            ],
            'pendingReports' => InvestigationReport::where('review_status', 'pending')->count(),
            'recentCases' => PoliceCase::with('complaint')->latest()->limit(10)->get(),
        ]);
    }

    private function recordAudit(string $action, string $details): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'table_name' => null,
            'record_id' => null,
            'details' => $details,
        ]);
    }
}
