@extends('layouts.app')

@section('title', 'Complaint Details')
@section('heading', 'Complaints')

@section('content')

<div class="page-header">
    <div class="page-header-text">
        <h1>Complaint Details</h1>
        <p class="page-subtitle">Reference: <strong>{{ $complaint->complaint_number }}</strong></p>
    </div>
    <div class="page-actions">
        @include('partials.status-badge', ['status' => $complaint->status])
        @if($complaint->case && auth()->user()->can('view-case', $complaint->case))
            <a href="{{ route('cases.show', $complaint->case) }}" class="btn btn--primary">
                @include('partials.icon', ['name' => 'cases'])
                Open Case {{ $complaint->case->case_number }}
            </a>
        @endif
        <a href="{{ route('complaints.index') }}" class="btn btn--outline">
            @include('partials.icon', ['name' => 'inbox'])
            Back to Complaints
        </a>
    </div>
</div>

@php
    $step1Done = true;
    $step2Done = $complaint->status !== 'pending';
    $step3Done = $complaint->case !== null;
@endphp
<div class="workflow-stepper workflow-stepper--3 mb-4" aria-label="Complaint lifecycle">
    <div class="workflow-step is-complete">
        <span class="workflow-step-num">✓</span>
        <div>
            <strong>Registered</strong>
            <span class="form-help">{{ $complaint->created_at->format('d M Y') }}</span>
        </div>
    </div>
    <div class="workflow-step {{ $step2Done ? 'is-complete' : 'is-active' }}">
        <span class="workflow-step-num">{{ $step2Done ? '✓' : '2' }}</span>
        <div>
            <strong>Review Decision</strong>
            <span class="form-help">{{ ucfirst($complaint->status) }}</span>
        </div>
    </div>
    <div class="workflow-step {{ $step3Done ? 'is-complete' : ($complaint->status === 'approved' ? 'is-active' : '') }}">
        <span class="workflow-step-num">{{ $step3Done ? '✓' : '3' }}</span>
        <div>
            <strong>Case Creation</strong>
            <span class="form-help">
                @if($complaint->case)
                    {{ $complaint->case->case_number }}
                @elseif($complaint->status === 'rejected')
                    Closed (rejected)
                @else
                    {{ $complaint->status === 'approved' ? 'Ready to convert' : 'Waiting for approval' }}
                @endif
            </span>
        </div>
    </div>
</div>

<div class="grid grid-2">
    <section class="card">
        <div class="card-header"><span class="card-title">Complainant information</span></div>
        <div class="card-body">
            <div class="detail-row">
                <span class="detail-label">Full name</span>
                <span class="detail-value">{{ $complaint->complainant_name }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Phone</span>
                <span class="detail-value">{{ $complaint->complainant_phone ?? 'Not provided' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Registered by</span>
                <span class="detail-value">{{ $complaint->registeredBy?->name ?? 'System' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Registered on</span>
                <span class="detail-value">{{ $complaint->created_at->format('d M Y, h:i A') }}</span>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="card-header"><span class="card-title">Incident information</span></div>
        <div class="card-body">
            <div class="detail-row">
                <span class="detail-label">Location</span>
                <span class="detail-value">{{ $complaint->incident_location }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Incident date</span>
                <span class="detail-value">{{ $complaint->incident_date }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value">@include('partials.status-badge', ['status' => $complaint->status])</span>
            </div>
        </div>
    </section>
</div>

<section class="card">
    <div class="card-header"><span class="card-title">Incident description</span></div>
    <div class="card-body">
        <div class="prose-box">{{ $complaint->incident_description }}</div>
    </div>
</section>

@if($complaint->reviews->isNotEmpty())
    <section class="card">
        <div class="card-header"><span class="card-title">Review History</span></div>
        <div class="card-body">
            <div class="table-wrap">
                <table class="table table--compact">
                    <thead>
                        <tr>
                            <th scope="col">Reviewed At</th>
                            <th scope="col">Reviewed By</th>
                            <th scope="col">Decision</th>
                            <th scope="col">Reason / Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($complaint->reviews as $review)
                            <tr>
                                <td class="nowrap">{{ $review->reviewed_at }}</td>
                                <td>{{ $review->user?->name ?? 'Unknown' }}</td>
                                <td>@include('partials.status-badge', ['status' => $review->decision, 'label' => null])</td>
                                <td>{{ $review->reason ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endif

@if($complaint->status === 'pending' && auth()->user()->can('review-complaints'))
    <section class="card">
        <div class="card-header"><span class="card-title">Review Complaint</span></div>
        <div class="card-body">
            <form method="POST" action="{{ route('complaints.review', $complaint) }}"
                      data-confirm="Record this complaint review decision?"
                      data-confirm-title="Confirm Complaint Review"
                      data-confirm-select="#decision"
                      data-confirm-approved="Approve this complaint? Once approved, it can be converted into a formal case."
                      data-confirm-rejected="Reject this complaint? A rejected complaint cannot be converted into a case.">
                @csrf
                <div class="form-row">
                    <div class="form-field">
                        <label class="form-label" for="decision">Decision <span class="required">*</span></label>
                        <select id="decision" name="decision" class="select" data-review-decision required>
                            <option value="">Select decision</option>
                            <option value="approved" @selected(old('decision') === 'approved')>Approve</option>
                            <option value="rejected" @selected(old('decision') === 'rejected')>Reject</option>
                        </select>
                        @include('partials.field-error', ['field' => 'decision'])
                    </div>
                    <div class="form-field">
                        <label class="form-label" for="reason">
                            Reason
                            <span class="form-help" data-reason-hint>(required for rejection)</span>
                        </label>
                        <textarea id="reason" name="reason" class="textarea" rows="3"
                                  data-review-reason
                                  placeholder="Optional notes for approval, required explanation if rejecting.">{{ old('reason') }}</textarea>
                        @include('partials.field-error', ['field' => 'reason'])
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn--primary">Submit Review</button>
                </div>
            </form>
        </div>
    </section>
@endif

@if($complaint->status === 'approved' && ! $complaint->case && auth()->user()->can('create-cases'))
    <section class="card">
        <div class="card-header"><span class="card-title">Case Creation</span></div>
        <div class="card-body">
            <p class="muted">This complaint has been approved. Convert it into a formal police case to assign investigators.</p>
            <form method="POST" action="{{ route('complaints.convert-to-case', $complaint) }}"
                  data-confirm="Create a formal case from this approved complaint?"
                  data-confirm-title="Convert Complaint to Case"
                  data-confirm-ok="Convert to Case">
                @csrf
                <div class="form-actions">
                    <button type="submit" class="btn btn--primary">Convert to Case</button>
                </div>
            </form>
        </div>
    </section>
@endif

@endsection
