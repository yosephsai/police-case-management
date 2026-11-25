@extends('layouts.app')

@section('title', 'Permissions')
@section('heading', 'Permissions')

@section('content')

<div class="page-header">
    <div class="page-header-text">
        <h1>Role Permissions</h1>
        <p class="page-subtitle">
            Configure which capabilities each role holds. Checked values reflect effective permissions,
            including any overrides. The permission editor itself is locked to the System Administrator.
        </p>
    </div>
</div>

<form method="POST" action="{{ route('permissions.update') }}">
    @csrf
    @method('PUT')

    <section class="card">
        <div class="table-wrap">
            <table class="table table--compact">
                <thead>
                    <tr>
                        <th scope="col">Permission</th>
                        @foreach($roles as $role => $roleLabel)
                            <th scope="col" class="text-center">{{ $roleLabel }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($permissionMap as $permission => $defaultRoles)
                        <tr>
                            <td>
                                <strong>{{ $labels[$permission] ?? $permission }}</strong>
                                <div class="form-help">{{ $permission }}</div>
                            </td>

                            @foreach($roles as $role => $roleLabel)
                                @php
                                    $key = $role . '|' . $permission;
                                    $override = $overrides->get($key);
                                    $checked = $override ? $override->allowed : in_array($role, $defaultRoles, true);
                                @endphp

                                <td class="text-center">
                                    @if($permission === 'manage-permissions')
                                        <span class="muted">Locked</span>
                                    @else
                                        <input type="checkbox" class="input-checkbox"
                                               name="permissions[{{ $role }}][{{ $permission }}]"
                                               value="1"
                                               aria-label="{{ $roleLabel }}: {{ $labels[$permission] ?? $permission }}"
                                               @checked($checked)>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save Permissions</button>
    </div>
</form>

@endsection
