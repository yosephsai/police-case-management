<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - {{ config('app.name', 'Police Case Management') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>

<body>
    <div class="print-shell">
        <div class="print-bar">
            <span class="muted">Print or save this document as a PDF.</span>
            <div class="page-actions">
                <button type="button" class="btn btn--primary btn--sm" onclick="window.print()">
                    @include('partials.icon', ['name' => 'print'])
                    Print / Save PDF
                </button>
                <a href="{{ url()->previous() }}" class="btn btn--outline btn--sm">Back</a>
            </div>
        </div>

        <article class="print-document">
            @yield('content')
        </article>
    </div>
</body>
</html>
