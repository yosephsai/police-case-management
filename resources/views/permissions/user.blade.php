@extends('layouts.app')

@section('title', 'User Permissions')
@section('heading', 'User Management')

@section('content')

<div class="page-header">
    <div class="page-header-text">
        <h1>Permissions for {{ $staff->name }}</h1>
        <p class="page-subtitle">{{ $staff->email }} · {{ $staff->roleLabel() }}</p>
    </div>
    <div class="page-actions">
        @can('manage-staff')
            <a href="{{ route('staff.index') }}" class="btn btn--outline">Back to Staff</a>
        @else
            <a href="{{ route('dashboard') }}" class="btn btn--outline">Back to Dashboard</a>
        @endcan
    </div>
</div>

<p class="muted">
    Overrides apply to this user only and take precedence over role defaults.
    Choose <strong>Inherit</strong> to remove an override.
</p>

<form method="POST" action="{{ route('staff.permissions.update', $staff) }}">
    @csrf
    @method('PUT')

    <section class="card">
        <div class="table-wrap">
            <table class="table table--compact">
                <thead>
                    <tr>
                        <th scope="col">Permission</th>
                        <th scope="col">Role default</th>
                        <th scope="col">Override</th>
                        <th scope="col">Effective</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($permissionMap as $permission => $defaultRoles)
                        @php
                            $permissionLabel = $labels[$permission] ?? $permission;
                            $hasOverride = $overrides->has($permission);
                            $current = $hasOverride ? ($overrides->get($permission) ? '1' : '0') : '';
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $permissionLabel }}</strong>
                                <div class="form-help">{{ $permission }}</div>
                            </td>
                            <td>
                                @include('partials.status-badge', [
                                    'status' => in_array($staff->role, $defaultRoles, true) ? 'active' : 'inactive',
                                    'label' => in_array($staff->role, $defaultRoles, true) ? 'Allowed' : 'Not allowed',
                                ])
                            </td>
                            <td>
                                @if($permission === 'manage-permissions')
                                    <span class="muted">Locked to administrators</span>
                                @else
                                    <select name="overrides[{{ $permission }}]" class="select"
                                            aria-label="Override for {{ $permissionLabel }}">
                                        <option value="" @selected($current === '')>Inherit</option>
                                        <option value="1" @selected($current === '1')>Allow</option>
                                        <option value="0" @selected($current === '0')>Deny</option>
                                    </select>
                                @endif
                            </td>
                            <td>
                                @include('partials.status-badge', [
                                    'status' => ($effective[$permission] ?? false) ? 'active' : 'inactive',
                                    'label' => ($effective[$permission] ?? false) ? 'Allowed' : 'Denied',
                                ])
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save User Permissions</button>
    </div>
</form>

@endsection
