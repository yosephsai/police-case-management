@extends('layouts.app')

@section('title', 'Administrative Summary')
@section('heading', 'Reports')

@section('content')

@include('partials.print-bar')

<article class="print-document">

<header class="print-header">
    <div>
        <span class="print-eyebrow">Ethiopian Federal Police · Official Summary</span>
        <h1>Administrative Summary</h1>
        <p class="print-muted">Operational overview of complaints, active investigations, evidence, and staff records.</p>
    </div>
    <div class="print-meta-box">
        <div><strong>Generated:</strong> {{ now()->format('d M Y, H:i') }}</div>
        <div><strong>Prepared by:</strong> {{ auth()->user()->name }} ({{ auth()->user()->roleLabel() }})</div>
    </div>
</header>

<section aria-label="Totals">
    <h2>System Totals</h2>
    <div class="print-kpi-grid">
        <div class="print-kpi">
            <div class="print-kpi-label">Complaints</div>
            <div class="print-kpi-value">{{ $totals['complaints'] }}</div>
        </div>
        <div class="print-kpi">
            <div class="print-kpi-label">Cases</div>
            <div class="print-kpi-value">{{ $totals['cases'] }}</div>
        </div>
        <div class="print-kpi">
            <div class="print-kpi-label">Evidence Records</div>
            <div class="print-kpi-value">{{ $totals['evidences'] }}</div>
        </div>
        <div class="print-kpi">
            <div class="print-kpi-label">Staff Accounts</div>
            <div class="print-kpi-value">{{ $totals['staff'] }}</div>
        </div>
        <div class="print-kpi">
            <div class="print-kpi-label">Reports Awaiting Review</div>
            <div class="print-kpi-value">{{ $pendingReports }}</div>
        </div>
    </div>
</section>

<div class="print-grid-2">
    <section class="print-panel">
        <h2>Complaints by Status</h2>
        @if($complaintStatuses->isEmpty())
            <p class="print-muted">No complaints registered.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Status</th><th class="text-right">Count</th></tr></thead>
                    <tbody>
                        @foreach($complaintStatuses as $status => $total)
                            <tr>
                                <td>@include('partials.status-badge', ['status' => $status, 'label' => null])</td>
                                <td class="text-right"><strong>{{ $total }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="print-panel">
        <h2>Cases by Status</h2>
        @if($caseStatuses->isEmpty())
            <p class="print-muted">No cases created.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Status</th><th class="text-right">Count</th></tr></thead>
                    <tbody>
                        @foreach($caseStatuses as $status => $total)
                            <tr>
                                <td>@include('partials.status-badge', ['status' => $status, 'label' => null])</td>
                                <td class="text-right"><strong>{{ $total }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>

<section class="print-panel">
    <h2>Recent Cases</h2>
    @if($recentCases->isEmpty())
        <p class="print-muted">No cases created.</p>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Case Number</th><th>Title</th><th>Status</th><th>Opened At</th></tr>
                </thead>
                <tbody>
                    @foreach($recentCases as $case)
                        <tr>
                            <td class="nowrap"><strong>{{ $case->case_number }}</strong></td>
                            <td>{{ $case->title }}</td>
                            <td>@include('partials.status-badge', ['status' => $case->status, 'label' => null])</td>
                            <td class="nowrap">{{ $case->opened_at ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

<p class="print-footer-note">
    This summary is generated from application data for administrative review and is not a legal document.
</p>

</article>

@endsection
