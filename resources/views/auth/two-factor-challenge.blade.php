@extends('layouts.auth')

@section('title', 'Two-Factor Challenge')

@section('content')

<h1 class="auth-title">Two-factor verification</h1>
<p class="auth-subtitle">Enter the 6-digit code from your authenticator, or a recovery code.</p>

<form method="POST" action="{{ route('two-factor.verify') }}">
    @csrf

    <div class="form-field">
        <label class="form-label" for="code">Authentication code <span class="required">*</span></label>
        <input id="code" type="text" name="code" class="input" inputmode="numeric"
               autocomplete="one-time-code" required autofocus>
        @include('partials.field-error', ['field' => 'code'])
    </div>

    <button type="submit" class="btn btn--primary btn--block">Verify</button>
</form>

<form method="POST" action="{{ route('two-factor.cancel') }}" class="mt-3">
    @csrf
    <button type="submit" class="btn btn--ghost btn--block">Cancel and return to sign in</button>
</form>

@endsection
