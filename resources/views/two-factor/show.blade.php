@extends('layouts.app')

@section('title', 'Two-Factor Authentication')
@section('heading', 'System Settings')

@section('content')

<div class="page-header">
    <div class="page-header-text">
        <h1>Two-Factor Authentication</h1>
        <p class="page-subtitle">Add a second step to your staff sign-in.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('dashboard') }}" class="btn btn--outline">
            @include('partials.icon', ['name' => 'dashboard'])
            Dashboard
        </a>
    </div>
</div>

@if(session('two_factor_recovery_codes'))
    <div class="alert alert--warning" role="alert">
        @include('partials.icon', ['name' => 'warning'])
        <div class="w-100">
            <strong>Save your recovery codes now</strong>
            <p class="mb-2">Each code works once if you lose access to your authenticator. They will not be shown again.</p>
            <div class="recovery-grid">
                @foreach(session('two_factor_recovery_codes') as $code)
                    <code class="recovery-code">{{ $code }}</code>
                @endforeach
            </div>
        </div>
    </div>
@endif

@if($enabled)
    <div class="card">
        <div class="card-header">
            <span class="card-title">Status</span>
            @include('partials.status-badge', ['status' => 'active', 'label' => 'Enabled'])
        </div>
        <div class="card-body">
            <div class="detail-row">
                <span class="detail-label">Authentication method</span>
                <span class="detail-value">Authenticator app (TOTP)</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Recovery codes remaining</span>
                <span class="detail-value">{{ count($user->two_factor_recovery_codes ?? []) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Enabled on</span>
                <span class="detail-value">{{ $user->two_factor_confirmed_at?->format('d M Y, H:i') }}</span>
            </div>
        </div>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <div class="card-header"><span class="card-title">Regenerate recovery codes</span></div>
            <div class="card-body">
                <p class="muted">Old codes stop working immediately.</p>
                <form method="POST" action="{{ route('two-factor.recovery-codes') }}"
                      data-confirm="Regenerate recovery codes? Existing codes will stop working.">
                    @csrf
                    <div class="form-field">
                        <label class="form-label" for="regen-password">Current password <span class="required">*</span></label>
                        <input id="regen-password" type="password" name="password" class="input"
                               autocomplete="current-password" required>
                        @include('partials.field-error', ['field' => 'password'])
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn--outline">Regenerate codes</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><span class="card-title">Disable two-factor authentication</span></div>
            <div class="card-body">
                <p class="muted">You will only need your password to sign in after disabling.</p>
                <form method="POST" action="{{ route('two-factor.disable') }}"
                      data-confirm="Disable two-factor authentication for this account?">
                    @csrf
                    <div class="form-field">
                        <label class="form-label" for="disable-password">Current password <span class="required">*</span></label>
                        <input id="disable-password" type="password" name="password" class="input"
                               autocomplete="current-password" required>
                        @include('partials.field-error', ['field' => 'password'])
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn--danger">Disable two-factor authentication</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@else
    <div class="card">
        <div class="card-header"><span class="card-title">Set up your authenticator app</span></div>
        <div class="card-body">
            <ol class="setup-steps">
                <li>Open an authenticator app (Google Authenticator, Authy, 1Password, etc.).</li>
                <li>Add a new account using the setup key or setup link below.</li>
                <li>Enter the 6-digit code to confirm and enable protection.</li>
            </ol>

            <div class="form-row">
                <div class="form-field">
                    <label class="form-label" for="setup-key">Setup key</label>
                    <input id="setup-key" type="text" class="input" value="{{ $secret }}" readonly>
                </div>
                <div class="form-field">
                    <label class="form-label" for="setup-link">Setup link</label>
                    <input id="setup-link" type="text" class="input" value="{{ $otpauth }}" readonly>
                </div>
            </div>

            <form method="POST" action="{{ route('two-factor.confirm') }}">
                @csrf
                <div class="form-field">
                    <label class="form-label" for="confirm-code">6-digit code <span class="required">*</span></label>
                    <input id="confirm-code" type="text" name="code" class="input" inputmode="numeric"
                           autocomplete="one-time-code" required>
                    @include('partials.field-error', ['field' => 'code'])
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn--primary">Confirm &amp; Enable</button>
                </div>
            </form>
        </div>
    </div>
@endif

@endsection
