@extends('layouts.auth')

@section('title', 'Set New Password')

@section('content')

<h1 class="auth-title">Choose a new password</h1>
<p class="auth-subtitle">Set a new password for your staff account.</p>

<form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="form-field">
        <label class="form-label" for="email">Email address <span class="required">*</span></label>
        <input id="email" type="email" name="email" class="input"
               value="{{ old('email', $email) }}" autocomplete="email" required>
        @include('partials.field-error', ['field' => 'email'])
    </div>

    <div class="form-field">
        <label class="form-label" for="password">New password <span class="required">*</span></label>
        <input id="password" type="password" name="password" class="input"
               autocomplete="new-password" required>
        @include('partials.field-error', ['field' => 'password'])
        <span class="form-help">At least 8 characters.</span>
    </div>

    <div class="form-field">
        <label class="form-label" for="password_confirmation">Confirm new password <span class="required">*</span></label>
        <input id="password_confirmation" type="password" name="password_confirmation" class="input"
               autocomplete="new-password" required>
        @include('partials.field-error', ['field' => 'password_confirmation'])
    </div>

    <button type="submit" class="btn btn--primary btn--block">Reset Password</button>
</form>

<div class="auth-links">
    <a href="{{ route('login') }}">Back to sign in</a>
</div>

@endsection
