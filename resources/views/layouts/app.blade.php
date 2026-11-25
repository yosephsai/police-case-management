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

    <div class="app-shell">
        @include('partials.sidebar')

        <div class="app-main">
            @include('partials.topbar')

            <main id="main-content" class="content">
                @include('partials.alerts')
                @yield('content')
            </main>
        </div>
    </div>

    <script src="{{ asset('js/app.js') }}" defer></script>
</body>
</html>
