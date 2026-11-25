@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')

<h1 class="auth-title">Reset your password</h1>
<p class="auth-subtitle">Enter your staff email and we will send a reset link.</p>

<form method="POST" action="{{ route('password.email') }}">
    @csrf

    <div class="form-field">
        <label class="form-label" for="email">Email address <span class="required">*</span></label>
        <input id="email" type="email" name="email" class="input"
               value="{{ old('email') }}" autocomplete="email" required autofocus>
        @include('partials.field-error', ['field' => 'email'])
    </div>

    <button type="submit" class="btn btn--primary btn--block">Send Reset Link</button>
</form>

<div class="auth-links">
    <a href="{{ route('login') }}">Back to sign in</a>
</div>

@endsection
