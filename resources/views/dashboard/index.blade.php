@extends('layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')

@php
    $user = auth()->user();
    $hasCaseAccess = $user->hasCaseAccess();
    $hasAdminStats = $user->canAny(['manage-staff', 'view-reports']);
    $hasPendingActions = $user->can('review-complaints') || $user->can('review-report') || $hasCaseAccess;

    $roleSubtitles = [
        'admin' => 'Manage staff accounts, role permissions, system security, and audit oversight.',
        'station_commander' => 'Oversee station complaints, case assignments, report approvals, and operational metrics.',
        'supervisor' => 'Assign detectives, track active investigations, and review submitted reports.',
        'reviewing_officer' => 'Review incoming complaints, record decisions, and convert approved complaints into cases.',
        'reporting_officer' => 'Register new public complaints and monitor their review outcomes.',
        'lead_detective' => 'Lead your assigned investigations, document evidence, and submit final reports.',
        'investigator' => 'Record investigation activities, evidence, and witness/suspect details for assigned cases.',
        'records_officer' => 'Maintain evidence chain-of-custody records, audit logs, and official summary exports.',
    ];
    $workspaceSubtitle = $roleSubtitles[$user->role] ?? 'Here is your current workspace overview.';
@endphp

<div class="page-header">
    <div class="page-header-text">
        <span class="role-pill">{{ $user->roleLabel() }}</span>
        <h1>Dashboard</h1>
        <p class="page-subtitle">Welcome, {{ $user->name }}. {{ $workspaceSubtitle }}</p>
    </div>
    <div class="page-actions">
        @can('register-complaints')
            <a href="{{ route('complaints.create') }}" class="btn btn--primary">
                @include('partials.icon', ['name' => 'plus'])
                Register Complaint
            </a>
        @endcan
        @can('review-complaints')
            <a href="{{ route('complaints.index', ['status' => 'pending']) }}" class="btn btn--primary">
                Review Pending ({{ $complaintStatuses['pending'] ?? 0 }})
            </a>
        @endcan
        @can('assign-cases')
            <a href="{{ route('cases.index', ['status' => 'open']) }}" class="btn btn--outline">
                Open Cases ({{ $caseStatuses['open'] ?? 0 }})
            </a>
        @endcan
        @can('manage-staff')
            <a href="{{ route('staff.create') }}" class="btn btn--primary">
                @include('partials.icon', ['name' => 'plus'])
                Add Staff
            </a>
        @endcan
        <a href="{{ route('complaints.index') }}" class="btn btn--outline">View complaints</a>
        @if($hasCaseAccess)
            <a href="{{ route('cases.index') }}" class="btn btn--primary">View cases</a>
        @endif
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Complaints</div>
        <div class="stat-value">{{ $complaints }}</div>
        <div class="stat-hint">{{ $hasCaseAccess ? 'Visible complaints' : 'Registered by you' }}</div>
    </div>

    @if(! $hasCaseAccess && ! $hasAdminStats)
        <div class="stat-card">
            <div class="stat-label">Pending Review</div>
            <div class="stat-value">{{ $complaintStatuses['pending'] ?? 0 }}</div>
            <div class="stat-hint">Waiting for review decision</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Approved</div>
            <div class="stat-value">{{ $complaintStatuses['approved'] ?? 0 }}</div>
            <div class="stat-hint">Accepted complaints</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Rejected</div>
            <div class="stat-value">{{ $complaintStatuses['rejected'] ?? 0 }}</div>
            <div class="stat-hint">Declined at review</div>
        </div>
    @endif

    @if($hasCaseAccess)
        <div class="stat-card">
            <div class="stat-label">Total Cases</div>
            <div class="stat-value">{{ $cases }}</div>
            <div class="stat-hint">{{ $user->can('view-all-cases') ? 'All station cases' : 'Assigned to you' }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Evidence Records</div>
            <div class="stat-value">{{ $evidences }}</div>
            <div class="stat-hint">On visible cases</div>
        </div>
    @endif

    @canany(['manage-staff', 'view-reports'])
        <div class="stat-card">
            <div class="stat-label">Staff Members</div>
            <div class="stat-value">{{ $staff }}</div>
            <div class="stat-hint">
                @can('manage-staff')
                    {{ $activeStaffCount }} active accounts
                @else
                    Registered accounts
                @endcan
            </div>
        </div>
    @endcanany
</div>

@if($user->can('review-complaints') || $user->can('create-cases') || $user->can('assign-cases') || $user->can('review-report'))
    <div class="grid grid-2">
        @can('review-complaints')
            <section class="card">
                <div class="card-header">
                    <span class="card-title">Review Queue</span>
                    <a href="{{ route('complaints.index', ['status' => 'pending']) }}" class="btn btn--ghost btn--sm">All pending</a>
                </div>
                <div class="card-body">
                    @forelse($pendingReviewComplaints as $item)
                        <div class="queue-item">
                            <div class="queue-meta">
                                <a href="{{ route('complaints.show', $item) }}" class="queue-title">{{ $item->complaint_number }}</a>
                                <span class="form-help">{{ $item->complainant_name }} · {{ $item->incident_location }} · {{ $item->incident_date }}</span>
                            </div>
                            <a href="{{ route('complaints.show', $item) }}" class="btn btn--primary btn--sm">Review</a>
                        </div>
                    @empty
                        @include('partials.empty-state', ['title' => 'No pending complaints to review'])
                    @endforelse
                </div>
            </section>
        @endcan

        @can('create-cases')
            <section class="card">
                <div class="card-header">
                    <span class="card-title">Ready to Convert to Case</span>
                    <a href="{{ route('complaints.index', ['status' => 'approved']) }}" class="btn btn--ghost btn--sm">Approved list</a>
                </div>
                <div class="card-body">
                    @forelse($readyToConvertComplaints as $item)
                        <div class="queue-item">
                            <div class="queue-meta">
                                <a href="{{ route('complaints.show', $item) }}" class="queue-title">{{ $item->complaint_number }}</a>
                                <span class="form-help">{{ $item->complainant_name }} · {{ $item->incident_date }}</span>
                            </div>
                            <a href="{{ route('complaints.show', $item) }}" class="btn btn--outline btn--sm">Convert</a>
                        </div>
                    @empty
                        @include('partials.empty-state', ['title' => 'No approved complaints waiting for case creation'])
                    @endforelse
                </div>
            </section>
        @endcan

        @can('assign-cases')
            <section class="card">
                <div class="card-header">
                    <span class="card-title">Cases Needing Investigator Assignment</span>
                    <a href="{{ route('cases.index', ['status' => 'open']) }}" class="btn btn--ghost btn--sm">Open cases</a>
                </div>
                <div class="card-body">
                    @forelse($unassignedCases as $item)
                        <div class="queue-item">
                            <div class="queue-meta">
                                <a href="{{ route('cases.show', $item) }}" class="queue-title">{{ $item->case_number }}</a>
                                <span class="form-help">{{ $item->title }}</span>
                            </div>
                            <a href="{{ route('cases.show', $item) }}#section-team" class="btn btn--primary btn--sm">Assign Team</a>
                        </div>
                    @empty
                        @include('partials.empty-state', ['title' => 'All visible cases have assigned investigators'])
                    @endforelse
                </div>
            </section>
        @endcan

        @can('review-report')
            <section class="card">
                <div class="card-header">
                    <span class="card-title">Submitted Reports Queue</span>
                    <a href="{{ route('cases.index', ['status' => 'pending_review']) }}" class="btn btn--ghost btn--sm">Pending review</a>
                </div>
                <div class="card-body">
                    @forelse($pendingReportList as $report)
                        <div class="queue-item">
                            <div class="queue-meta">
                                <a href="{{ route('cases.show', $report->case_id) }}#section-reports" class="queue-title">
                                    {{ $report->case?->case_number ?? 'Case #' . $report->case_id }}
                                </a>
                                <span class="form-help">
                                    Submitted by {{ $report->submittedBy?->name ?? 'Unknown' }} · {{ $report->submitted_at }}
                                </span>
                            </div>
                            <a href="{{ route('cases.show', $report->case_id) }}#section-reports" class="btn btn--outline btn--sm">Review</a>
                        </div>
                    @empty
                        @include('partials.empty-state', ['title' => 'No investigation reports awaiting review'])
                    @endforelse
                </div>
            </section>
        @endcan
    </div>
@endif

@if($hasCaseAccess)
    <div class="grid grid-3">
        <section class="card">
            <div class="card-header"><span class="card-title">Complaints by Status</span></div>
            <div class="card-body">
                @forelse($complaintStatuses as $status => $total)
                    <div class="meter-row">
                        <div class="meter-top">
                            @include('partials.status-badge', ['status' => $status, 'label' => null])
                            <strong>{{ $total }}</strong>
                        </div>
                        <div class="meter-track">
                            <div class="meter-fill" style="width: {{ $complaints > 0 ? round(($total / $complaints) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                @empty
                    @include('partials.empty-state', ['title' => 'No complaints registered'])
                @endforelse
            </div>
        </section>

        <section class="card">
            <div class="card-header"><span class="card-title">Cases by Status</span></div>
            <div class="card-body">
                @forelse($caseStatuses as $status => $total)
                    <div class="meter-row">
                        <div class="meter-top">
                            @include('partials.status-badge', ['status' => $status, 'label' => null])
                            <strong>{{ $total }}</strong>
                        </div>
                        <div class="meter-track">
                            <div class="meter-fill" style="width: {{ $cases > 0 ? round(($total / $cases) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                @empty
                    @include('partials.empty-state', ['title' => 'No cases created'])
                @endforelse

                @can('review-report')
                    <div class="detail-row">
                        <span class="detail-label">Reports Awaiting Review</span>
                        <span class="detail-value text-right">{{ $pendingReports }}</span>
                    </div>
                @endcan
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <span class="card-title">Recent Cases</span>
                <a href="{{ route('cases.index') }}" class="btn btn--ghost btn--sm">View all</a>
            </div>
            <div class="card-body">
                @forelse($recentCases as $case)
                    <div class="queue-item">
                        <div class="queue-meta">
                            <a href="{{ route('cases.show', $case) }}" class="queue-title">{{ $case->case_number }}</a>
                            <span class="form-help">{{ $case->activities_count }} activities · {{ $case->evidences_count }} evidence</span>
                        </div>
                        @include('partials.status-badge', ['status' => $case->status, 'label' => null])
                    </div>
                @empty
                    @include('partials.empty-state', ['title' => 'No cases created yet'])
                @endforelse
            </div>
        </section>
    </div>

    <div class="grid {{ $user->can('view-audit-logs') ? 'grid-3' : 'grid-2' }}">
        <section class="card">
            <div class="card-header">
                <span class="card-title">Recent Complaints</span>
                <a href="{{ route('complaints.index') }}" class="btn btn--ghost btn--sm">View all</a>
            </div>
            <div class="card-body">
                @forelse($recentComplaints as $complaint)
                    <div class="queue-item">
                        <div class="queue-meta">
                            <a href="{{ route('complaints.show', $complaint) }}" class="queue-title">{{ $complaint->complaint_number }}</a>
                            <span class="form-help">{{ $complaint->complainant_name }}</span>
                        </div>
                        @include('partials.status-badge', ['status' => $complaint->status, 'label' => null])
                    </div>
                @empty
                    @include('partials.empty-state', ['title' => 'No complaints registered'])
                @endforelse
            </div>
        </section>

        @if($hasPendingActions)
            <section class="card">
                <div class="card-header"><span class="card-title">Pending Actions</span></div>
                <div class="card-body">
                    @can('review-complaints')
                        <div class="detail-row">
                            <span class="detail-label">Complaints awaiting review</span>
                            <span class="detail-value text-right">
                                <a href="{{ route('complaints.index', ['status' => 'pending']) }}">
                                    {{ $complaintStatuses['pending'] ?? 0 }}
                                </a>
                            </span>
                        </div>
                    @endcan

                    @can('review-report')
                        <div class="detail-row">
                            <span class="detail-label">Reports awaiting review</span>
                            <span class="detail-value text-right">{{ $pendingReports }}</span>
                        </div>
                    @endcan

                    <div class="detail-row">
                        <span class="detail-label">Cases under investigation</span>
                        <span class="detail-value text-right">
                            <a href="{{ route('cases.index', ['status' => 'under_investigation']) }}">
                                {{ $caseStatuses['under_investigation'] ?? 0 }}
                            </a>
                        </span>
                    </div>
                </div>
            </section>
        @endif

        @can('view-audit-logs')
            <section class="card">
                <div class="card-header">
                    <span class="card-title">Activity History</span>
                    <a href="{{ route('audit-logs.index') }}" class="btn btn--ghost btn--sm">Open log</a>
                </div>
                <div class="card-body">
                    @forelse($recentActivity as $entry)
                        <div class="detail-row">
                            <span class="detail-value">
                                <code>{{ $entry->action }}</code>
                                <span class="form-help">{{ $entry->created_at?->format('d M Y, H:i') }}</span>
                            </span>
                            <span class="detail-value text-right">{{ $entry->user?->name ?? 'System' }}</span>
                        </div>
                    @empty
                        @include('partials.empty-state', ['title' => 'No recent activity recorded'])
                    @endforelse
                </div>
            </section>
        @endcan
    </div>
@else
    <div class="grid {{ ($hasPendingActions || $user->can('view-audit-logs')) ? 'grid-3' : 'grid-2' }}">
        <section class="card">
            <div class="card-header"><span class="card-title">Complaints by Status</span></div>
            <div class="card-body">
                @forelse($complaintStatuses as $status => $total)
                    <div class="meter-row">
                        <div class="meter-top">
                            @include('partials.status-badge', ['status' => $status, 'label' => null])
                            <strong>{{ $total }}</strong>
                        </div>
                        <div class="meter-track">
                            <div class="meter-fill" style="width: {{ $complaints > 0 ? round(($total / $complaints) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                @empty
                    @include('partials.empty-state', ['title' => 'No complaints registered'])
                @endforelse
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <span class="card-title">Recent Complaints</span>
                <a href="{{ route('complaints.index') }}" class="btn btn--ghost btn--sm">View all</a>
            </div>
            <div class="card-body">
                @forelse($recentComplaints as $complaint)
                    <div class="queue-item">
                        <div class="queue-meta">
                            <a href="{{ route('complaints.show', $complaint) }}" class="queue-title">{{ $complaint->complaint_number }}</a>
                            <span class="form-help">{{ $complaint->complainant_name }} · {{ $complaint->incident_date }}</span>
                        </div>
                        @include('partials.status-badge', ['status' => $complaint->status, 'label' => null])
                    </div>
                @empty
                    @include('partials.empty-state', ['title' => 'No complaints registered'])
                @endforelse
            </div>
        </section>

        @if($hasPendingActions)
            <section class="card">
                <div class="card-header"><span class="card-title">Pending Actions</span></div>
                <div class="card-body">
                    @can('review-complaints')
                        <div class="detail-row">
                            <span class="detail-label">Complaints awaiting review</span>
                            <span class="detail-value text-right">
                                <a href="{{ route('complaints.index', ['status' => 'pending']) }}">
                                    {{ $complaintStatuses['pending'] ?? 0 }}
                                </a>
                            </span>
                        </div>
                    @endcan

                    @can('review-report')
                        <div class="detail-row">
                            <span class="detail-label">Reports awaiting review</span>
                            <span class="detail-value text-right">{{ $pendingReports }}</span>
                        </div>
                    @endcan
                </div>
            </section>
        @endif

        @can('view-audit-logs')
            <section class="card">
                <div class="card-header">
                    <span class="card-title">Activity History</span>
                    <a href="{{ route('audit-logs.index') }}" class="btn btn--ghost btn--sm">Open log</a>
                </div>
                <div class="card-body">
                    @forelse($recentActivity as $entry)
                        <div class="detail-row">
                            <span class="detail-value">
                                <code>{{ $entry->action }}</code>
                                <span class="form-help">{{ $entry->created_at?->format('d M Y, H:i') }}</span>
                            </span>
                            <span class="detail-value text-right">{{ $entry->user?->name ?? 'System' }}</span>
                        </div>
                    @empty
                        @include('partials.empty-state', ['title' => 'No recent activity recorded'])
                    @endforelse
                </div>
            </section>
        @endcan
    </div>
@endif

@can('view-reports')
    <section class="card">
        <div class="card-header"><span class="card-title">Administrative Report Exports</span></div>
        <div class="card-body">
            <p class="muted">
                Download fictional/local reporting data as CSV. Exports are recorded in the audit log.
            </p>
            <div class="page-actions">
                <a href="{{ route('reports.complaints') }}" class="btn btn--outline btn--sm">
                    @include('partials.icon', ['name' => 'download'])
                    Download Complaints CSV
                </a>
                <a href="{{ route('reports.cases') }}" class="btn btn--outline btn--sm">
                    @include('partials.icon', ['name' => 'download'])
                    Download Cases CSV
                </a>
                <a href="{{ route('reports.summary') }}" class="btn btn--outline btn--sm">
                    @include('partials.icon', ['name' => 'print'])
                    Printable Summary
                </a>
            </div>
        </div>
    </section>
@endcan

@endsection
