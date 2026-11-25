@extends('layouts.app')

@section('title', 'Add Staff')
@section('heading', 'User Management')

@section('content')

<div class="page-header">
    <div class="page-header-text">
        <h1>Add New Staff Member</h1>
        <p class="page-subtitle">Create an account for a police staff member.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('staff.index') }}" class="btn btn--outline">Back to Staff</a>
    </div>
</div>

<div class="form-layout">
<form method="POST" action="{{ route('staff.store') }}">
    @csrf

    <div class="card">
        <div class="card-header"><span class="card-title">Account details</span></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-field">
                    <label class="form-label" for="staff-name">Full name <span class="required">*</span></label>
                    <input id="staff-name" type="text" name="name" class="input"
                           placeholder="e.g. Abebe Kebede"
                           value="{{ old('name') }}" required>
                    @include('partials.field-error', ['field' => 'name'])
                </div>
                <div class="form-field">
                    <label class="form-label" for="staff-email">Email address <span class="required">*</span></label>
                    <input id="staff-email" type="email" name="email" class="input"
                           placeholder="officer@police.gov.et"
                           value="{{ old('email') }}" required>
                    @include('partials.field-error', ['field' => 'email'])
                </div>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label class="form-label" for="staff-password">Password <span class="required">*</span></label>
                    <input id="staff-password" type="password" name="password" class="input"
                           placeholder="Minimum 8 characters" required>
                    @include('partials.field-error', ['field' => 'password'])
                    <span class="form-help">At least 8 characters. Share securely with the staff member.</span>
                </div>
                <div class="form-field">
                    <label class="form-label" for="staff-role">Staff role <span class="required">*</span></label>
                    <select id="staff-role" name="role" class="select" required>
                        <option value="">Select a role</option>
                        @foreach($roles as $value => $label)
                            <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @include('partials.field-error', ['field' => 'role'])
                    <span class="form-help">Determines default dashboard, queues, and case permissions.</span>
                </div>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary">Create Staff Account</button>
        <a href="{{ route('staff.index') }}" class="btn btn--outline">Cancel</a>
    </div>
</form>
</div>

@endsection
