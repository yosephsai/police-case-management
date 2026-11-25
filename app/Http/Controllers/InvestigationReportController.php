<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CaseStatusHistory;
use App\Models\InvestigationReport;
use App\Models\PoliceCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class InvestigationReportController extends Controller
{
    public function store(Request $request, PoliceCase $case)
    {
        Gate::authorize('work-on-case', $case);

        if ($case->status === 'closed') {
            return back()->withErrors([
                'report_summary' => 'Cannot submit an investigation report for a closed case.',
            ]);
        }

        $validated = $request->validate([
            'report_summary' => ['required', 'string', 'min:10'],
        ], [
            'report_summary.required' => 'Please provide a summary of the investigation findings.',
            'report_summary.min' => 'The investigation report summary must be at least 10 characters.',
        ]);

        DB::transaction(function () use ($case, $validated) {
            $report = InvestigationReport::create([
                'case_id' => $case->id,
                'submitted_by' => auth()->id(),
                'report_summary' => $validated['report_summary'],
                'submitted_at' => now(),
                'review_status' => 'pending',
            ]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'investigation_report.submitted',
                'table_name' => 'investigation_reports',
                'record_id' => $report->id,
                'details' => 'Investigation report submitted for case ' . $case->case_number,
            ]);

            if (in_array($case->status, ['open', 'under_investigation'], true)) {
                $fromStatus = $case->status;
                $case->update(['status' => 'pending_review']);

                CaseStatusHistory::create([
                    'case_id' => $case->id,
                    'user_id' => auth()->id(),
                    'from_status' => $fromStatus,
                    'to_status' => 'pending_review',
                    'notes' => 'Investigation report submitted for supervisory review.',
                    'changed_at' => now(),
                ]);
            }
        });

        return back()->with('success', 'Investigation report submitted successfully.');
    }

    public function review(Request $request, InvestigationReport $report)
    {
        Gate::authorize('view-case', $report->case);

        if ($report->review_status !== 'pending') {
            return back()->withErrors([
                'report' => 'This investigation report has already been reviewed.',
            ]);
        }

        $validated = $request->validate([
            'decision' => ['required', 'in:accepted,returned'],
            'notes' => ['required_if:decision,returned', 'nullable', 'string'],
        ]);

        DB::transaction(function () use ($report, $validated) {
            $report->update([
                'review_status' => $validated['decision'],
                'review_notes' => $validated['notes'] ?? null,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'investigation_report.reviewed',
                'table_name' => 'investigation_reports',
                'record_id' => $report->id,
                'details' => 'Investigation report review decision: ' . $validated['decision'],
            ]);

            $case = $report->case;
            if ($case && $case->status === 'pending_review') {
                $nextStatus = $validated['decision'] === 'accepted' ? 'closed' : 'under_investigation';
                $case->update(['status' => $nextStatus]);

                CaseStatusHistory::create([
                    'case_id' => $case->id,
                    'user_id' => auth()->id(),
                    'from_status' => 'pending_review',
                    'to_status' => $nextStatus,
                    'notes' => $validated['notes'] ?? ('Investigation report ' . $validated['decision'] . '.'),
                    'changed_at' => now(),
                ]);
            }
        });

        return back()->with('success', 'Investigation report review saved successfully.');
    }
}
