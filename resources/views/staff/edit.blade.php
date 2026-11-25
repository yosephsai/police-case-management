@extends('layouts.app')

@section('title', 'Edit Staff')
@section('heading', 'User Management')

@section('content')

<div class="page-header">
    <div class="page-header-text">
        <h1>Edit Staff Member</h1>
        <p class="page-subtitle">Update staff account information and role.</p>
    </div>
    <div class="page-actions">
        @include('partials.status-badge', ['status' => $staff->is_active ? 'active' : 'inactive', 'label' => null])
        <a href="{{ route('staff.index') }}" class="btn btn--outline">Back to Staff</a>
    </div>
</div>

<div class="form-layout">
<form method="POST" action="{{ route('staff.update', $staff) }}">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-header"><span class="card-title">Account details</span></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-field">
                    <label class="form-label" for="staff-name">Full name <span class="required">*</span></label>
                    <input id="staff-name" type="text" name="name" class="input"
                           placeholder="Full name"
                           value="{{ old('name', $staff->name) }}" required>
                    @include('partials.field-error', ['field' => 'name'])
                </div>
                <div class="form-field">
                    <label class="form-label" for="staff-email">Email address <span class="required">*</span></label>
                    <input id="staff-email" type="email" name="email" class="input"
                           placeholder="officer@police.gov.et"
                           value="{{ old('email', $staff->email) }}" required>
                    @include('partials.field-error', ['field' => 'email'])
                </div>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label class="form-label" for="staff-role">Staff role <span class="required">*</span></label>
                    <select id="staff-role" name="role" class="select" required>
                        @foreach($roles as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', $staff->role) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @include('partials.field-error', ['field' => 'role'])
                </div>
                <div class="form-field">
                    <label class="form-label" for="staff-password">New password</label>
                    <input id="staff-password" type="password" name="password" class="input"
                           placeholder="Leave blank to keep current password">
                    @include('partials.field-error', ['field' => 'password'])
                    <span class="form-help">Leave blank to keep the current password.</span>
                </div>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save Changes</button>
        <a href="{{ route('staff.index') }}" class="btn btn--outline">Cancel</a>
    </div>
</form>
</div>

@endsection
