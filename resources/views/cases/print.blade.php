@extends('layouts.app')

@section('title', 'Case ' . $case->case_number)
@section('heading', 'Cases')

@section('content')

@include('partials.print-bar')

<article class="print-document">

<h1>Case {{ $case->case_number }}</h1>
<p class="print-muted">{{ $case->title }}</p>
<p><strong>Status:</strong> {{ $statuses[$case->status] ?? ucfirst($case->status) }}</p>

<section>
    <h2>Case Information</h2>
    <table>
        <tr>
            <th style="width: 25%">Source Complaint</th>
            <td>{{ $case->complaint?->complaint_number ?? 'Not linked' }}</td>
        </tr>
        <tr>
            <th>Opened At</th>
            <td>{{ $case->opened_at ?? 'Not recorded' }}</td>
        </tr>
        <tr>
            <th>Lead Investigator</th>
            <td>{{ $case->leadInvestigator()?->name ?? 'Not assigned' }}</td>
        </tr>
        <tr>
            <th>Description</th>
            <td>{{ $case->description ?? 'No description provided.' }}</td>
        </tr>
    </table>
</section>

<section>
    <h2>Status History</h2>
    @if($case->statusHistories->isEmpty())
        <p class="print-muted">No status history recorded.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Changed At</th>
                    <th>From</th>
                    <th>To</th>
                    <th>By</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($case->statusHistories as $history)
                    <tr>
                        <td>{{ $history->changed_at }}</td>
                        <td>{{ $statuses[$history->from_status] ?? ($history->from_status ?? '—') }}</td>
                        <td>{{ $statuses[$history->to_status] ?? $history->to_status }}</td>
                        <td>{{ $history->user?->name ?? 'System' }}</td>
                        <td>{{ $history->notes ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</section>

<section>
    <h2>Assigned Investigators</h2>
    @if($case->assignments->isEmpty())
        <p class="print-muted">No investigators assigned.</p>
    @else
        <table>
            <thead>
                <tr><th>Investigator</th><th>Role</th><th>Assigned At</th></tr>
            </thead>
            <tbody>
                @foreach($case->assignments as $assignment)
                    <tr>
                        <td>{{ $assignment->user?->name ?? 'Unknown' }}</td>
                        <td>{{ ucfirst($assignment->role) }}</td>
                        <td>{{ $assignment->assigned_at }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</section>

<section>
    <h2>Investigation Activities</h2>
    @if($case->activities->isEmpty())
        <p class="print-muted">No activities recorded.</p>
    @else
        <table>
            <thead>
                <tr><th>Date</th><th>Type</th><th>Description</th><th>Recorded By</th></tr>
            </thead>
            <tbody>
                @foreach($case->activities->sortByDesc('activity_date') as $activity)
                    <tr>
                        <td>{{ $activity->activity_date }}</td>
                        <td>{{ $activity->activity_type }}</td>
                        <td>{{ $activity->description }}</td>
                        <td>{{ $activity->user?->name ?? 'Unknown' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</section>

<section>
    <h2>Evidence</h2>
    @if($case->evidences->isEmpty())
        <p class="print-muted">No evidence recorded.</p>
    @else
        <table>
            <thead>
                <tr><th>Number</th><th>Type</th><th>Description</th><th>Storage</th><th>Collected</th></tr>
            </thead>
            <tbody>
                @foreach($case->evidences as $evidence)
                    <tr>
                        <td>{{ $evidence->evidence_number }}</td>
                        <td>{{ $evidence->evidence_type }}</td>
                        <td>{{ $evidence->description }}</td>
                        <td>{{ $evidence->storage_location ?? '—' }}</td>
                        <td>{{ $evidence->collected_at }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</section>

<section>
    <h2>Evidence Handling History</h2>
    @php
        $handling = [];

        foreach ($case->evidences as $evidence) {
            foreach ($evidence->histories as $history) {
                $handling[] = [
                    'evidence' => $evidence->evidence_number,
                    'handled_at' => $history->handled_at,
                    'action' => $history->action,
                    'notes' => $history->notes,
                    'user' => $history->user?->name ?? 'Unknown',
                ];
            }
        }

        $handling = collect($handling)->sortByDesc('handled_at');
    @endphp

    @if($handling->isEmpty())
        <p class="print-muted">No evidence handling history recorded.</p>
    @else
        <table>
            <thead>
                <tr><th>Evidence</th><th>Handled At</th><th>Action</th><th>Notes</th><th>By</th></tr>
            </thead>
            <tbody>
                @foreach($handling as $entry)
                    <tr>
                        <td>{{ $entry['evidence'] }}</td>
                        <td>{{ $entry['handled_at'] }}</td>
                        <td>{{ $entry['action'] }}</td>
                        <td>{{ $entry['notes'] ?? '—' }}</td>
                        <td>{{ $entry['user'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</section>

<section>
    <h2>Victims, Suspects and Witnesses</h2>
    @if($case->people->isEmpty())
        <p class="print-muted">No people associated.</p>
    @else
        <table>
            <thead>
                <tr><th>Name</th><th>Role</th><th>Phone</th><th>Notes</th></tr>
            </thead>
            <tbody>
                @foreach($case->people as $person)
                    <tr>
                        <td>{{ $person->full_name }}</td>
                        <td>{{ ucfirst($person->role) }}</td>
                        <td>{{ $person->phone ?? '—' }}</td>
                        <td>{{ $person->notes ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</section>

<section>
    <h2>Investigation Reports</h2>
    @if($case->reports->isEmpty())
        <p class="print-muted">No investigation reports submitted.</p>
    @else
        <table>
            <thead>
                <tr><th>Submitted At</th><th>Submitted By</th><th>Status</th><th>Review Notes</th><th>Reviewed By</th></tr>
            </thead>
            <tbody>
                @foreach($case->reports as $report)
                    <tr>
                        <td>{{ $report->submitted_at }}</td>
                        <td>{{ $report->submittedBy?->name ?? 'Unknown' }}</td>
                        <td>{{ ucfirst($report->review_status) }}</td>
                        <td>{{ $report->review_notes ?? '—' }}</td>
                        <td>{{ $report->reviewedBy?->name ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</section>

<p class="print-muted">
    Generated {{ now()->format('d M Y, H:i') }} · This document records application data and is not a legal judgment.
</p>

</article>

@endsection
