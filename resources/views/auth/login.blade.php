@extends('layouts.auth')

@section('title', 'Staff Login')

@section('content')

<div class="auth-eyebrow">
    @include('partials.icon', ['name' => 'shield'])
    Secure staff access
</div>

<h1 class="auth-title">Sign in to your workspace</h1>
<p class="auth-subtitle">Use your assigned staff account to review complaints, cases, and records.</p>

<form method="POST" action="{{ route('login.submit') }}">
    @csrf

    <div class="form-field">
        <label class="form-label" for="email">Email address <span class="required">*</span></label>
        <input id="email" type="email" name="email" class="input input--lg"
               value="{{ old('email') }}" autocomplete="username" placeholder="name@station.test" required autofocus>
        @include('partials.field-error', ['field' => 'email'])
    </div>

    <div class="form-field">
        <label class="form-label" for="password">Password <span class="required">*</span></label>
        <input id="password" type="password" name="password" class="input input--lg"
               autocomplete="current-password" placeholder="Enter your password" required>
        @include('partials.field-error', ['field' => 'password'])
    </div>

    <div class="login-options">
        <div class="checkbox-row">
            <input id="remember" type="checkbox" name="remember" value="1" class="input-checkbox"
                   @checked(old('remember'))>
            <label for="remember">Keep me signed in</label>
        </div>
        <a href="{{ route('password.request') }}">Forgot password?</a>
    </div>

    <button type="submit" class="btn btn--primary btn--block btn--lg">
        Login
        @include('partials.icon', ['name' => 'dashboard'])
    </button>
</form>

<div class="auth-security-note">
    @include('partials.icon', ['name' => 'info'])
    <span>Authorized staff only. This demonstration contains fictional records and is not an official system.</span>
</div>

@endsection
