@extends('layouts.app')

@section('title', 'Audit Log')
@section('heading', 'Audit Log')

@section('content')

<div class="page-header">
    <div class="page-header-text">
        <h1>Audit Log</h1>
        <p class="page-subtitle">Traceable record of important actions performed in the system.</p>
    </div>
</div>

<form method="GET" action="{{ route('audit-logs.index') }}" class="filter-bar">
    <div class="form-field">
        <label class="form-label" for="audit-search">Search</label>
        <input id="audit-search" type="search" name="search" class="input"
               placeholder="Action or details" value="{{ request('search') }}">
    </div>
    <div class="form-field">
        <label class="form-label" for="audit-user">User</label>
        <select id="audit-user" name="user_id" class="select">
            <option value="">All users</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>
                    {{ $user->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="form-field">
        <label class="form-label" for="audit-table">Table</label>
        <select id="audit-table" name="table_name" class="select">
            <option value="">All tables</option>
            @foreach($tables as $table)
                <option value="{{ $table }}" @selected(request('table_name') === $table)>{{ $table }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="btn btn--primary">Filter</button>
    <a href="{{ route('audit-logs.index') }}" class="btn btn--outline">Reset</a>
</form>

<section class="card">
    <div class="table-wrap">
        <table class="table table--compact">
            <thead>
                <tr>
                    <th scope="col">When</th>
                    <th scope="col">User</th>
                    <th scope="col">Action</th>
                    <th scope="col">Table</th>
                    <th scope="col">Record</th>
                    <th scope="col">Details</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="nowrap">{{ $log->created_at?->format('d M Y, H:i') }}</td>
                        <td>{{ $log->user?->name ?? 'System' }}</td>
                        <td><code>{{ $log->action }}</code></td>
                        <td>{{ $log->table_name ?? '—' }}</td>
                        <td>{{ $log->record_id ?? '—' }}</td>
                        <td>{{ $log->details ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            @include('partials.empty-state', ['title' => 'No audit entries match the current filters'])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@include('partials.pagination', ['paginator' => $logs])

@endsection
