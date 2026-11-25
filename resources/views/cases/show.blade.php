@extends('layouts.app')

@section('title', 'Case Details')
@section('heading', 'Cases')

@section('content')

<div class="page-header">
    <div class="page-header-text">
        <h1>Case {{ $case->case_number }}</h1>
        <p class="page-subtitle">{{ $case->title }}</p>
    </div>
    <div class="page-actions">
        @include('partials.status-badge', [
            'status' => $case->status,
            'label' => $statuses[$case->status] ?? ucfirst($case->status),
        ])
        <a href="{{ route('cases.print', $case) }}" class="btn btn--outline">
            @include('partials.icon', ['name' => 'print'])
            Print / Save PDF
        </a>
        <a href="{{ route('cases.index') }}" class="btn btn--outline">Back to Cases</a>
    </div>
</div>

<div class="grid grid-2">
<section class="card">
    <div class="card-header"><span class="card-title">Case information</span></div>
    <div class="card-body">
        <div class="detail-row">
            <span class="detail-label">Description</span>
            <span class="detail-value"><span class="prose-box">{{ $case->description ?? 'No description provided.' }}</span></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Source complaint</span>
            <span class="detail-value">
                @if($case->complaint && $case->complaint->isVisibleTo(auth()->user()))
                    <a href="{{ route('complaints.show', $case->complaint) }}">{{ $case->complaint->complaint_number }}</a>
                @elseif($case->complaint)
                    Complaint record not available
                @else
                    Not linked
                @endif
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Opened at</span>
            <span class="detail-value">{{ $case->opened_at ?? 'Not recorded' }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Lead investigator</span>
            <span class="detail-value">{{ $case->leadInvestigator()?->name ?? 'Not assigned' }}</span>
        </div>
    </div>
</section>

<section class="card">
    <div class="card-header"><span class="card-title">Case status</span></div>
    <div class="card-body">
        @can('update-case-status')
            @if(count($allowedTransitions) > 0)
                <form method="POST" action="{{ route('cases.status', $case) }}"
                      data-confirm="Update the status of this case?">
                    @csrf
                    @method('PATCH')

                    <div class="form-row form-row--inline">
                        <div class="form-field">
                            <label class="form-label" for="to_status">New status <span class="required">*</span></label>
                            <select id="to_status" name="to_status" class="select" required>
                                <option value="">Select new status</option>
                                @foreach($allowedTransitions as $status)
                                    <option value="{{ $status }}">{{ $statuses[$status] }}</option>
                                @endforeach
                            </select>
                            @include('partials.field-error', ['field' => 'to_status'])
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="status-notes">Notes</label>
                            <input id="status-notes" type="text" name="notes" class="input"
                                   placeholder="Optional notes for this change" value="{{ old('notes') }}">
                            @include('partials.field-error', ['field' => 'notes'])
                        </div>
                        <button type="submit" class="btn btn--primary">Update Status</button>
                    </div>
                </form>
            @else
                <p class="muted">No further status changes are permitted from the current status.</p>
            @endif
        @endcan

        @if($case->statusHistories->isEmpty())
            @include('partials.empty-state', ['title' => 'No status history recorded'])
        @else
            <div class="table-wrap">
                <table class="table table--compact">
                    <thead>
                        <tr>
                            <th scope="col">Changed At</th>
                            <th scope="col">From</th>
                            <th scope="col">To</th>
                            <th scope="col">By</th>
                            <th scope="col">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($case->statusHistories as $history)
                            <tr>
                                <td class="nowrap">{{ $history->changed_at }}</td>
                                <td>{{ $statuses[$history->from_status] ?? ($history->from_status ?? '—') }}</td>
                                <td>{{ $statuses[$history->to_status] ?? $history->to_status }}</td>
                                <td>{{ $history->user?->name ?? 'System' }}</td>
                                <td>{{ $history->notes ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
</div>

<section id="section-team" class="card">
    <div class="card-header">
        <span class="card-title">Assigned investigators <span class="card-count">{{ $case->assignments->count() }}</span></span>
    </div>
    <div class="card-body">
        @if($case->assignments->isEmpty())
            @include('partials.empty-state', ['title' => 'No investigators assigned yet'])
        @else
            <div class="table-wrap mb-3">
                <table class="table table--compact">
                    <thead>
                        <tr>
                            <th scope="col">Investigator</th>
                            <th scope="col">Role</th>
                            <th scope="col">Assigned At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($case->assignments as $assignment)
                            <tr>
                                <td>{{ $assignment->user?->name ?? 'Unknown' }}</td>
                                <td>{{ ucfirst($assignment->role) }}</td>
                                <td class="nowrap">{{ $assignment->assigned_at }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @can('assign-cases')
            <form method="POST" action="{{ route('case-assignments.store', $case) }}">
                @csrf
                <div class="form-row form-row--inline">
                        <div class="form-field">
                            <label class="form-label" for="assign-user">Staff member <span class="required">*</span></label>
                            <select id="assign-user" name="user_id" class="select" data-assign-user required>
                                <option value="">Select lead detective or investigator</option>
                                @foreach($investigators as $investigator)
                                    @php
                                        $assignmentRole = $investigator->role === 'lead_detective' ? 'lead' : 'investigator';
                                        $assignmentRoleLabel = $assignmentRole === 'lead' ? 'Lead Investigator' : 'Investigator';
                                    @endphp
                                    <option value="{{ $investigator->id }}"
                                            data-assignment-role="{{ $assignmentRole }}"
                                            data-assignment-role-label="{{ $assignmentRoleLabel }}"
                                            @selected((string) old('user_id') === (string) $investigator->id)>
                                        {{ $investigator->name }} ({{ $investigator->roleLabel() }})
                                    </option>
                                @endforeach
                            </select>
                            @include('partials.field-error', ['field' => 'user_id'])
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="assign-role-display">Assignment role</label>
                            <input id="assign-role-display" type="text" class="input"
                                   value="" placeholder="Picked automatically from staff role"
                                   data-assign-role-display readonly>
                            <input id="assign-role" type="hidden" name="role" value="{{ old('role') }}" data-assign-role-input>
                            @include('partials.field-error', ['field' => 'role'])
                        </div>
                    <button type="submit" class="btn btn--primary">Assign</button>
                </div>
            </form>
        @endcan
    </div>
</section>

<section id="section-activities" class="card">
    <div class="card-header">
        <span class="card-title">Investigation activities <span class="card-count">{{ $case->activities->count() }}</span></span>
    </div>
    <div class="card-body">
        @if($case->activities->isEmpty())
            @include('partials.empty-state', ['title' => 'No investigation activities recorded'])
        @else
            <div class="table-wrap mb-3">
                <table class="table table--compact">
                    <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Type</th>
                            <th scope="col">Description</th>
                            <th scope="col">Recorded By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($case->activities->sortByDesc('activity_date') as $activity)
                            <tr>
                                <td class="nowrap">{{ $activity->activity_date }}</td>
                                <td>{{ $activity->activity_type }}</td>
                                <td>{{ $activity->description }}</td>
                                <td>{{ $activity->user?->name ?? 'Unknown' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @can('record-activities')
            <form method="POST" action="{{ route('investigation-activities.store', $case) }}">
                @csrf
                <div class="form-row">
                    <div class="form-field">
                        <label class="form-label" for="activity_type">Activity type <span class="required">*</span></label>
                        <input id="activity_type" type="text" name="activity_type" class="input"
                               placeholder="Interview, scene visit, etc." value="{{ old('activity_type') }}" required>
                        @include('partials.field-error', ['field' => 'activity_type'])
                    </div>
                    <div class="form-field">
                        <label class="form-label" for="activity_date">Activity date <span class="required">*</span></label>
                        <input id="activity_date" type="datetime-local" name="activity_date" class="input"
                               value="{{ old('activity_date', now()->format('Y-m-d\TH:i')) }}" required>
                        @include('partials.field-error', ['field' => 'activity_date'])
                    </div>
                </div>
                <div class="form-field">
                    <label class="form-label" for="activity-description">Description <span class="required">*</span></label>
                    <textarea id="activity-description" name="description" class="textarea" rows="2"
                              required>{{ old('description') }}</textarea>
                    @include('partials.field-error', ['field' => 'description'])
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn--primary">Record Activity</button>
                </div>
            </form>
        @endcan
    </div>
</section>

<section id="section-evidence" class="card">
    <div class="card-header">
        <span class="card-title">Evidence <span class="card-count">{{ $case->evidences->count() }}</span></span>
    </div>
    <div class="card-body">
        @forelse($case->evidences as $evidence)
            @php
                $ext = $evidence->attachment_path ? strtolower(pathinfo($evidence->attachment_path, PATHINFO_EXTENSION)) : '';
                $isImageAttachment = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
            @endphp
            <div class="evidence-item">
                <div class="evidence-item-header">
                    <div>
                        <strong>{{ $evidence->evidence_number }}</strong>
                        <span class="badge badge--neutral">{{ $evidence->evidence_type }}</span>
                    </div>
                    <span class="muted">Collected {{ $evidence->collected_at }}</span>
                </div>
                <div class="evidence-item-body">
                    <p>{{ $evidence->description }}</p>
                    <div class="evidence-meta-grid">
                        <span><strong>Storage:</strong> {{ $evidence->storage_location ?? 'Not recorded' }}</span>
                        <span><strong>Recorded by:</strong> {{ $evidence->user?->name ?? 'Unknown' }}</span>
                        @if($evidence->attachment_path && ! $isImageAttachment)
                            <a href="{{ route('evidences.attachment', $evidence) }}" class="btn btn--outline btn--sm">
                                @include('partials.icon', ['name' => 'download'])
                                Download attachment
                            </a>
                        @endif
                    </div>

                    @if($evidence->attachment_path && $isImageAttachment)
                        <div class="evidence-preview">
                            <img src="{{ route('evidences.attachment', ['evidence' => $evidence, 'inline' => 1]) }}"
                                 alt="Attachment for {{ $evidence->evidence_number }}"
                                 class="evidence-preview-img">
                            <div>
                                <div><strong>Image attachment</strong> ({{ strtoupper($ext) }})</div>
                                <div class="mt-2">
                                    <a href="{{ route('evidences.attachment', $evidence) }}" class="btn btn--outline btn--sm">
                                        @include('partials.icon', ['name' => 'download'])
                                        Download attachment
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($evidence->histories->isEmpty())
                        <p class="muted">No handling history recorded.</p>
                    @else
                        <div class="table-wrap">
                            <table class="table table--compact">
                                <thead>
                                    <tr>
                                        <th scope="col">Handled At</th>
                                        <th scope="col">Action</th>
                                        <th scope="col">Notes</th>
                                        <th scope="col">By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($evidence->histories->sortByDesc('handled_at') as $history)
                                        <tr>
                                            <td class="nowrap">{{ $history->handled_at }}</td>
                                            <td>{{ $history->action }}</td>
                                            <td>{{ $history->notes ?? '—' }}</td>
                                            <td>{{ $history->user?->name ?? 'Unknown' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @can('handle-evidence')
                        <form method="POST" action="{{ route('evidence-histories.store', $evidence) }}">
                            @csrf
                            <div class="form-row form-row--inline">
                                <div class="form-field">
                                    <label class="form-label" for="handling-action-{{ $evidence->id }}">Action <span class="required">*</span></label>
                                    <input id="handling-action-{{ $evidence->id }}" type="text" name="action" class="input"
                                           placeholder="Transfer, review, etc." required>
                                    @include('partials.field-error', ['field' => 'action'])
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="handled-at-{{ $evidence->id }}">Handled at <span class="required">*</span></label>
                                    <input id="handled-at-{{ $evidence->id }}" type="datetime-local" name="handled_at" class="input"
                                           value="{{ now()->format('Y-m-d\TH:i') }}" required>
                                    @include('partials.field-error', ['field' => 'handled_at'])
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="handling-notes-{{ $evidence->id }}">Notes</label>
                                    <input id="handling-notes-{{ $evidence->id }}" type="text" name="notes" class="input"
                                           placeholder="Optional notes">
                                    @include('partials.field-error', ['field' => 'notes'])
                                </div>
                                <button type="submit" class="btn btn--outline btn--sm">Record Handling</button>
                            </div>
                        </form>
                    @endcan
                </div>
            </div>
        @empty
            @include('partials.empty-state', ['title' => 'No evidence recorded for this case'])
        @endforelse

        @can('add-evidence')
            @php
                $uploadIni = trim((string) ini_get('upload_max_filesize'));
                $uploadUnit = strtolower(substr($uploadIni, -1));
                $uploadNum = (int) $uploadIni;
                $serverMaxBytes = match ($uploadUnit) {
                    'g' => $uploadNum * 1024 * 1024 * 1024,
                    'm' => $uploadNum * 1024 * 1024,
                    'k' => $uploadNum * 1024,
                    default => $uploadNum > 0 ? $uploadNum : 2 * 1024 * 1024,
                };
                $effectiveMaxBytes = min($serverMaxBytes, 10 * 1024 * 1024);
                $effectiveMaxMb = rtrim(rtrim(number_format($effectiveMaxBytes / (1024 * 1024), 1), '0'), '.');
            @endphp
            <div class="evidence-composer">
                <div class="evidence-composer-header">
                    <h3>Add New Evidence</h3>
                    <p class="form-help">Log physical items, documents, or photographs into the case chain of custody.</p>
                </div>
                <form method="POST" action="{{ route('evidences.store', $case) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="form-row">
                        <div class="form-field">
                            <label class="form-label" for="evidence_number">Evidence number <span class="required">*</span></label>
                            <input id="evidence_number" type="text" name="evidence_number" class="input"
                                   value="{{ old('evidence_number', 'EVD-' . now()->format('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(4))) }}"
                                   placeholder="e.g. EVD-20261125-001" required>
                            @include('partials.field-error', ['field' => 'evidence_number'])
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="evidence_type">Evidence type <span class="required">*</span></label>
                            <input id="evidence_type" type="text" name="evidence_type" class="input"
                                   list="evidence-types"
                                   value="{{ old('evidence_type') }}"
                                   placeholder="Photograph, Document, Physical Item…" required>
                            <datalist id="evidence-types">
                                <option value="Photograph"></option>
                                <option value="Document"></option>
                                <option value="Digital File"></option>
                                <option value="Physical Item"></option>
                                <option value="CCTV Footage"></option>
                                <option value="Forensic Sample"></option>
                            </datalist>
                            @include('partials.field-error', ['field' => 'evidence_type'])
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="storage_location">Storage location</label>
                            <input id="storage_location" type="text" name="storage_location" class="input"
                                   value="{{ old('storage_location') }}"
                                   placeholder="e.g. Evidence Locker B-12">
                            @include('partials.field-error', ['field' => 'storage_location'])
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="collected_at">Collected at <span class="required">*</span></label>
                            <input id="collected_at" type="date" name="collected_at" class="input"
                                   max="{{ now()->toDateString() }}"
                                   value="{{ old('collected_at', now()->toDateString()) }}" required>
                            @include('partials.field-error', ['field' => 'collected_at'])
                        </div>
                    </div>
                    <div class="form-field">
                        <label class="form-label" for="evidence-description">Description <span class="required">*</span></label>
                        <textarea id="evidence-description" name="description" class="textarea" rows="3"
                                  placeholder="Describe the item, condition, and where/how it was found."
                                  required>{{ old('description') }}</textarea>
                        @include('partials.field-error', ['field' => 'description'])
                    </div>
                    <div class="form-field">
                        <label class="form-label" for="attachment">Image or Document Attachment</label>
                        <div class="file-dropzone" data-file-dropzone data-max-bytes="{{ $effectiveMaxBytes }}">
                            <input id="attachment" type="file" name="attachment" class="file-input"
                                   accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.doc,.docx,.txt">
                            <span class="file-dropzone-meta" data-file-meta
                                  data-default-text="Optional: JPG, PNG, WEBP, GIF, PDF, DOC, DOCX, or TXT (up to {{ $effectiveMaxMb }} MB).">
                                Optional: JPG, PNG, WEBP, GIF, PDF, DOC, DOCX, or TXT (up to {{ $effectiveMaxMb }} MB).
                            </span>
                        </div>
                        @include('partials.field-error', ['field' => 'attachment'])
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn--primary">
                            @include('partials.icon', ['name' => 'plus'])
                            Add Evidence
                        </button>
                    </div>
                </form>
            </div>
        @endcan
    </div>
</section>

<section id="section-people" class="card">
    <div class="card-header">
        <span class="card-title">Victims, suspects and witnesses <span class="card-count">{{ $case->people->count() }}</span></span>
    </div>
    <div class="card-body">
        @if($case->people->isEmpty())
            @include('partials.empty-state', ['title' => 'No people associated with this case yet'])
        @else
            <div class="table-wrap mb-3">
                <table class="table table--compact">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Role</th>
                            <th scope="col">Phone</th>
                            <th scope="col">Notes</th>
                        </tr>
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
            </div>
        @endif

        @can('manage-people')
            <form method="POST" action="{{ route('case-people.store', $case) }}">
                @csrf
                <div class="form-row form-row--inline">
                    <div class="form-field">
                        <label class="form-label" for="person-name">Full name <span class="required">*</span></label>
                        <input id="person-name" type="text" name="full_name" class="input" placeholder="Full name" required>
                        @include('partials.field-error', ['field' => 'full_name'])
                    </div>
                    <div class="form-field">
                        <label class="form-label" for="person-role">Role <span class="required">*</span></label>
                        <select id="person-role" name="role" class="select" required>
                            <option value="">Select role</option>
                            <option value="victim">Victim</option>
                            <option value="suspect">Suspect</option>
                            <option value="witness">Witness</option>
                            <option value="other">Other</option>
                        </select>
                        @include('partials.field-error', ['field' => 'role'])
                    </div>
                    <div class="form-field">
                        <label class="form-label" for="person-phone">Phone</label>
                        <input id="person-phone" type="text" name="phone" class="input" placeholder="Optional phone">
                        @include('partials.field-error', ['field' => 'phone'])
                    </div>
                    <div class="form-field">
                        <label class="form-label" for="person-notes">Notes</label>
                        <input id="person-notes" type="text" name="notes" class="input" placeholder="Statement or address">
                        @include('partials.field-error', ['field' => 'notes'])
                    </div>
                    <button type="submit" class="btn btn--primary">Add Person</button>
                </div>
            </form>
        @endcan
    </div>
</section>

<section id="section-reports" class="card">
    <div class="card-header">
        <span class="card-title">Investigation reports <span class="card-count">{{ $case->reports->count() }}</span></span>
    </div>
    <div class="card-body">
        @if($case->reports->isEmpty())
            @include('partials.empty-state', ['title' => 'No investigation reports submitted'])
        @else
            <div class="table-wrap mb-3">
                <table class="table table--compact">
                    <thead>
                        <tr>
                            <th scope="col">Submitted At</th>
                            <th scope="col">Submitted By</th>
                            <th scope="col">Report Summary</th>
                            <th scope="col">Status</th>
                            <th scope="col">Review Notes</th>
                            <th scope="col">Reviewed By</th>
                            <th scope="col">Review</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($case->reports as $report)
                            <tr>
                                <td class="nowrap">{{ $report->submitted_at }}</td>
                                <td>{{ $report->submittedBy?->name ?? 'Unknown' }}</td>
                                <td>{{ $report->report_summary ?: '—' }}</td>
                                <td>@include('partials.status-badge', ['status' => $report->review_status, 'label' => null])</td>
                                <td>{{ $report->review_notes ?? '—' }}</td>
                                <td>{{ $report->reviewedBy?->name ?? '—' }}</td>
                                <td>
                                    @can('review-report')
                                        @if($report->review_status === 'pending')
                                            <form method="POST"
                                                  action="{{ route('investigation-reports.review', $report) }}"
                                                  data-confirm="Record this supervisory review decision?"
                                                  data-confirm-title="Confirm Report Review"
                                                  data-confirm-select="#decision-{{ $report->id }}"
                                                  data-confirm-accepted="Accept this investigation report? If the case is pending review, it will be closed."
                                                  data-confirm-returned="Return this investigation report for further investigation?">
                                                @csrf
                                                <div class="form-inline-group">
                                                    <label class="sr-only" for="decision-{{ $report->id }}">Decision</label>
                                                    <select id="decision-{{ $report->id }}" name="decision" class="select" required>
                                                        <option value="">Decision</option>
                                                        <option value="accepted">Accept</option>
                                                        <option value="returned">Return</option>
                                                    </select>
                                                    @include('partials.field-error', ['field' => 'decision'])
                                                    <label class="sr-only" for="review-notes-{{ $report->id }}">Notes</label>
                                                    <input id="review-notes-{{ $report->id }}" type="text" name="notes" class="input" placeholder="Notes">
                                                    @include('partials.field-error', ['field' => 'notes'])
                                                    <button type="submit" class="btn btn--outline btn--sm">Save</button>
                                                </div>
                                            </form>
                                        @else
                                            <span class="muted">Reviewed</span>
                                        @endif
                                    @else
                                        <span class="muted">—</span>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @can('submit-report')
            <div class="section">
                <h2 class="section-title">Submit investigation report</h2>
                @if($case->status === 'closed')
                    <p class="note">This case is closed. No further investigation reports can be submitted.</p>
                @else
                    <form method="POST" action="{{ route('investigation-reports.store', $case) }}">
                        @csrf
                        <div class="form-field">
                            <label class="form-label" for="report_summary">Report summary <span class="required">*</span></label>
                            <textarea id="report_summary" name="report_summary" class="textarea" rows="3"
                                      placeholder="Summary of the investigation findings for supervisory review (minimum 10 characters)"
                                      required>{{ old('report_summary') }}</textarea>
                            <span class="form-help">Submitting this report moves the case to Pending Review for supervisory decision.</span>
                            @include('partials.field-error', ['field' => 'report_summary'])
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn--primary">Submit Investigation Report</button>
                        </div>
                    </form>
                @endif
            </div>
        @else
            <p class="note">Only an assigned Lead Detective can submit an investigation report for supervisory review.</p>
        @endcan
    </div>
</section>

@endsection
