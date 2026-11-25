<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>@yield('title') - {{ config('app.name', 'Police Case Management') }}</title>
    <script>
        (function () {
            var stored = localStorage.getItem('pcms-theme');
            var theme = stored || 'light';
            document.documentElement.dataset.theme = theme;
        })();
    </script>
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>

<body>
    <a href="#main-content" class="skip-link">Skip to content</a>

    <div class="auth-theme-toggle">@include('partials.theme-toggle')</div>

    <div class="auth-shell">
        <aside class="auth-aside">
            <div class="auth-aside-brand">
                <img src="{{ asset('assets/ethiopia-police-logo.webp') }}"
                     alt="Ethiopian Federal Police emblem" class="auth-emblem-small">
                <span class="auth-aside-brand-text">
                    <span class="auth-aside-brand-name">Police Case Management</span>
                    <span class="auth-aside-brand-sub">Ethiopian Federal Police</span>
                </span>
            </div>

            <div class="auth-aside-content">
                <div class="auth-emblem-large">
                    <img src="{{ asset('assets/ethiopia-police-logo.webp') }}" alt="" aria-hidden="true">
                </div>
                <h1>Ethiopian Police Management System</h1>
                <p class="auth-aside-slogan">&ldquo;Service with integrity. Records with accountability.&rdquo;</p>
            </div>
        </aside>

        <main id="main-content" class="auth-panel">
            <div class="auth-card">
                @include('partials.alerts')
                @yield('content')
            </div>
        </main>
    </div>

    <script src="{{ asset('js/app.js') }}" defer></script>
</body>
</html>
