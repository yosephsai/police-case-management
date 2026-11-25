@extends('layouts.app')

@section('title', 'Register Complaint')
@section('heading', 'Complaints')

@section('content')

<div class="page-header">
    <div class="page-header-text">
        <h1>Register Complaint</h1>
        <p class="page-subtitle">Record a new complaint. A reference number is generated automatically.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('complaints.index') }}" class="btn btn--outline">Back to Complaints</a>
    </div>
</div>

<div class="form-layout">
<div class="workflow-stepper workflow-stepper--3 mb-4" aria-label="Complaint workflow">
    <div class="workflow-step is-active">
        <span class="workflow-step-num">1</span>
        <div>
            <strong>Register Intake</strong>
            <span class="form-help">Starts in Pending status</span>
        </div>
    </div>
    <div class="workflow-step">
        <span class="workflow-step-num">2</span>
        <div>
            <strong>Officer Review</strong>
            <span class="form-help">Approved or rejected</span>
        </div>
    </div>
    <div class="workflow-step">
        <span class="workflow-step-num">3</span>
        <div>
            <strong>Case Creation</strong>
            <span class="form-help">Assigned for investigation</span>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('complaints.store') }}">
    @csrf

    <div class="card">
        <div class="card-header"><span class="card-title">Complainant information</span></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-field">
                    <label class="form-label" for="complainant_name">Full name <span class="required">*</span></label>
                    <input id="complainant_name" type="text" name="complainant_name" class="input"
                           placeholder="e.g. Abebe Bikila"
                           value="{{ old('complainant_name') }}" required>
                    @include('partials.field-error', ['field' => 'complainant_name'])
                </div>
                <div class="form-field">
                    <label class="form-label" for="complainant_phone">Phone number</label>
                    <div class="phone-field">
                        <span class="phone-prefix" aria-hidden="true">+251</span>
                        <input id="complainant_phone" type="text" name="complainant_phone" class="input"
                               inputmode="numeric" autocomplete="tel-national"
                               pattern="[0-9]{9}" minlength="9" maxlength="9"
                               placeholder="912345678" data-phone-digits
                               value="{{ old('complainant_phone') }}">
                        <span class="sr-only">Country code +251 is added automatically.</span>
                    </div>
                    <span class="form-help">Optional: enter 9 digits without leading zero (e.g. 912345678).</span>
                    @include('partials.field-error', ['field' => 'complainant_phone'])
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">Incident information</span></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-field">
                    <label class="form-label" for="incident_location">Location <span class="required">*</span></label>
                    <input id="incident_location" type="text" name="incident_location" class="input"
                           placeholder="Sub-city, kebele, or landmark"
                           value="{{ old('incident_location') }}" required>
                    @include('partials.field-error', ['field' => 'incident_location'])
                </div>
                <div class="form-field">
                    <label class="form-label" for="incident_date">Incident date <span class="required">*</span></label>
                    <input id="incident_date" type="date" name="incident_date" class="input"
                           max="{{ now()->toDateString() }}"
                           value="{{ old('incident_date', now()->toDateString()) }}" required>
                    @include('partials.field-error', ['field' => 'incident_date'])
                </div>
            </div>

            <div class="form-field">
                <label class="form-label" for="incident_description">Description <span class="required">*</span></label>
                <textarea id="incident_description" name="incident_description" class="textarea" rows="5"
                          placeholder="Describe what happened, when, who was involved, and any immediate details."
                          required>{{ old('incident_description') }}</textarea>
                @include('partials.field-error', ['field' => 'incident_description'])
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary">Register Complaint</button>
        <a href="{{ route('complaints.index') }}" class="btn btn--outline">Cancel</a>
    </div>
</form>
</div>

@endsection
