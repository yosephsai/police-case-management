@extends('layouts.app')

@section('title', 'Cases')
@section('heading', 'Cases')

@section('content')

<div class="page-header">
    <div class="page-header-text">
        <h1>Case Management</h1>
        <p class="page-subtitle">Cases created from approved complaints.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('complaints.index') }}" class="btn btn--outline">View Complaints</a>
    </div>
</div>

<form method="GET" action="{{ route('cases.index') }}" class="filter-bar">
    <div class="form-field">
        <label class="form-label" for="case-search">Search</label>
        <input id="case-search" type="search" name="search" class="input"
               placeholder="Case number or title" value="{{ request('search') }}">
    </div>
    <div class="form-field">
        <label class="form-label" for="case-status">Status</label>
        <select id="case-status" name="status" class="select">
            <option value="">All statuses</option>
            @foreach($statuses as $statusValue => $statusLabel)
                <option value="{{ $statusValue }}" @selected(request('status') === $statusValue)>{{ $statusLabel }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-field">
        <label class="form-label" for="case-from">Opened from</label>
        <input id="case-from" type="date" name="from" class="input" value="{{ request('from') }}">
        @include('partials.field-error', ['field' => 'from'])
    </div>
    <div class="form-field">
        <label class="form-label" for="case-to">Opened to</label>
        <input id="case-to" type="date" name="to" class="input" value="{{ request('to') }}">
        @include('partials.field-error', ['field' => 'to'])
    </div>
    <div class="filter-actions">
        <button type="submit" class="btn btn--primary">Filter</button>
        <a href="{{ route('cases.index') }}" class="btn btn--outline">Reset</a>
    </div>
</form>

<section class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Case Number</th>
                    <th scope="col">Title</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-center">Assignments</th>
                    <th scope="col" class="text-center">Activities</th>
                    <th scope="col" class="text-center">Evidence</th>
                    <th scope="col" class="text-center">People</th>
                    <th scope="col" class="text-center">Reports</th>
                    <th scope="col" class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cases as $case)
                    <tr>
                        <td class="nowrap">{{ $case->case_number }}</td>
                        <td>{{ $case->title }}</td>
                        <td>@include('partials.status-badge', ['status' => $case->status, 'label' => null])</td>
                        <td class="text-center">{{ $case->assignments_count }}</td>
                        <td class="text-center">{{ $case->activities_count }}</td>
                        <td class="text-center">{{ $case->evidences_count }}</td>
                        <td class="text-center">{{ $case->people_count }}</td>
                        <td class="text-center">{{ $case->reports_count }}</td>
                        <td class="text-right">
                            <a href="{{ route('cases.show', $case) }}" class="btn btn--outline btn--sm">
                                @include('partials.icon', ['name' => 'view'])
                                View Case
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            @include('partials.empty-state', [
                                'title' => 'No cases have been created yet.',
                                'message' => 'Approve a complaint and convert it into a case.',
                            ])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@include('partials.pagination', ['paginator' => $cases])

@endsection
