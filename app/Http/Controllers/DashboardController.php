<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Complaint;
use App\Models\Evidence;
use App\Models\InvestigationReport;
use App\Models\PoliceCase;
use App\Models\User;

class DashboardController extends Controller
{
    public function roleDashboard(string $rolePath)
    {
        abort_unless(auth()->user()->roleSlug() === $rolePath, 403);

        return $this->index();
    }

    public function index()
    {
        $user = auth()->user();

        $complaintStatuses = Complaint::query()
            ->visibleTo($user)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $caseStatuses = PoliceCase::query()
            ->visibleTo($user)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentComplaints = Complaint::query()
            ->visibleTo($user)
            ->latest()
            ->limit(5)
            ->get(['id', 'complaint_number', 'complainant_name', 'status', 'incident_date', 'created_at']);

        $recentActivity = $user->hasPermission('view-audit-logs')
            ? AuditLog::query()->with('user')->latest()->limit(5)->get()
            : collect();

        $pendingReviewComplaints = $user->hasPermission('review-complaints')
            ? Complaint::query()
                ->visibleTo($user)
                ->where('status', 'pending')
                ->latest()
                ->limit(5)
                ->get(['id', 'complaint_number', 'complainant_name', 'incident_location', 'incident_date', 'status'])
            : collect();

        $readyToConvertComplaints = $user->hasPermission('create-cases')
            ? Complaint::query()
                ->visibleTo($user)
                ->where('status', 'approved')
                ->doesntHave('case')
                ->latest()
                ->limit(5)
                ->get(['id', 'complaint_number', 'complainant_name', 'incident_date', 'status'])
            : collect();

        $unassignedCases = $user->hasPermission('assign-cases')
            ? PoliceCase::query()
                ->visibleTo($user)
                ->whereDoesntHave('assignments')
                ->latest()
                ->limit(5)
                ->get(['id', 'case_number', 'title', 'status', 'opened_at'])
            : collect();

        $pendingReportList = $user->hasPermission('review-report')
            ? InvestigationReport::query()
                ->with(['case:id,case_number,title', 'submittedBy:id,name'])
                ->where('review_status', 'pending')
                ->whereHas('case', fn ($query) => $query->visibleTo($user))
                ->latest('submitted_at')
                ->limit(5)
                ->get()
            : collect();

        $activeStaffCount = $user->hasPermission('manage-staff')
            ? User::where('is_active', true)->count()
            : 0;

        return view('dashboard.index', [
            'complaints' => Complaint::query()->visibleTo($user)->count(),
            'cases' => PoliceCase::query()->visibleTo($user)->count(),
            'evidences' => Evidence::query()
                ->whereHas('case', fn ($query) => $query->visibleTo($user))
                ->count(),
            'staff' => User::count(),
            'activeStaffCount' => $activeStaffCount,
            'complaintStatuses' => $complaintStatuses,
            'caseStatuses' => $caseStatuses,
            'pendingReports' => InvestigationReport::query()
                ->where('review_status', 'pending')
                ->whereHas('case', fn ($query) => $query->visibleTo($user))
                ->count(),
            'recentCases' => PoliceCase::query()
                ->visibleTo($user)
                ->with('complaint')
                ->withCount(['activities', 'evidences'])
                ->latest()
                ->limit(5)
                ->get(),
            'recentComplaints' => $recentComplaints,
            'recentActivity' => $recentActivity,
            'pendingReviewComplaints' => $pendingReviewComplaints,
            'readyToConvertComplaints' => $readyToConvertComplaints,
            'unassignedCases' => $unassignedCases,
            'pendingReportList' => $pendingReportList,
        ]);
    }
}
