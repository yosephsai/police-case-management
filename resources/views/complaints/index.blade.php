@extends('layouts.app')

@section('title', 'Complaints')
@section('heading', 'Complaints')

@section('content')

<div class="page-header">
    <div class="page-header-text">
        <h1>Complaint Management</h1>
        <p class="page-subtitle">View and manage registered complaints.</p>
    </div>
    @can('register-complaints')
        <div class="page-actions">
            <a href="{{ route('complaints.create') }}" class="btn btn--primary">
                @include('partials.icon', ['name' => 'plus'])
                Register Complaint
            </a>
        </div>
    @endcan
</div>

<form method="GET" action="{{ route('complaints.index') }}" class="filter-bar">
    <div class="form-field">
        <label class="form-label" for="complaint-search">Search</label>
        <input id="complaint-search" type="search" name="search" class="input"
               placeholder="Reference, complainant, or location"
               value="{{ request('search') }}">
    </div>
    <div class="form-field">
        <label class="form-label" for="complaint-status">Status</label>
        <select id="complaint-status" name="status" class="select">
            <option value="">All statuses</option>
            @foreach($statuses as $statusValue => $statusLabel)
                <option value="{{ $statusValue }}" @selected(request('status') === $statusValue)>{{ $statusLabel }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-field">
        <label class="form-label" for="complaint-from">Incident from</label>
        <input id="complaint-from" type="date" name="from" class="input" value="{{ request('from') }}">
        @include('partials.field-error', ['field' => 'from'])
    </div>
    <div class="form-field">
        <label class="form-label" for="complaint-to">Incident to</label>
        <input id="complaint-to" type="date" name="to" class="input" value="{{ request('to') }}">
        @include('partials.field-error', ['field' => 'to'])
    </div>
    <div class="filter-actions">
        <button type="submit" class="btn btn--primary">Filter</button>
        <a href="{{ route('complaints.index') }}" class="btn btn--outline">Reset</a>
    </div>
</form>

<section class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Reference Number</th>
                    <th scope="col">Complainant</th>
                    <th scope="col">Location</th>
                    <th scope="col">Incident Date</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($complaints as $complaint)
                    <tr>
                        <td class="nowrap">{{ $complaint->complaint_number }}</td>
                        <td>{{ $complaint->complainant_name }}</td>
                        <td>{{ $complaint->incident_location }}</td>
                        <td class="nowrap">{{ $complaint->incident_date }}</td>
                        <td>@include('partials.status-badge', ['status' => $complaint->status, 'label' => null])</td>
                        <td class="text-right">
                            <a href="{{ route('complaints.show', $complaint) }}" class="btn btn--outline btn--sm">
                                @include('partials.icon', ['name' => 'view'])
                                View Details
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            @if(auth()->user()->hasPermission('view-assigned-complaints') && ! auth()->user()->hasPermission('view-all-complaints'))
                                @include('partials.empty-state', [
                                    'title' => 'No complaints linked to your assigned cases.',
                                    'message' => 'Complaints appear here once a supervisor assigns you to their corresponding case.',
                                ])
                            @else
                                @include('partials.empty-state', [
                                    'title' => 'No complaints have been registered yet.',
                                    'message' => 'Register a complaint or adjust the filters above.',
                                ])
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@include('partials.pagination', ['paginator' => $complaints])

@endsection
