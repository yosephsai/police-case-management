@extends('layouts.auth')

@section('title', 'Welcome')

@section('content')

<h1 class="auth-title">Police Case Management</h1>
<p class="auth-subtitle">A coherent workspace for records, cases, and administrative review.</p>

<p class="muted">
    This is a fictional demonstration application. Records shown here do not represent real complaints,
    cases, people, or official decisions.
</p>

<div class="form-actions">
    <a href="{{ route('login') }}" class="btn btn--primary">Sign in</a>
</div>

@endsection
