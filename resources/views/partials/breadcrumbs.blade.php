@php
    $routeName = request()->route()?->getName() ?? '';
    $dashboardUrl = route('role.dashboard', ['rolePath' => auth()->user()->roleSlug()]);
    $trail = [['label' => 'Dashboard', 'url' => $dashboardUrl]];

    if (str_starts_with($routeName, 'complaints.')) {
        $trail[] = ['label' => 'Complaints', 'url' => route('complaints.index')];
        $trail[] = ['label' => match ($routeName) {
            'complaints.index' => 'All complaints',
            'complaints.create' => 'Register complaint',
            'complaints.show' => 'Complaint details',
            default => 'Complaints',
        }, 'url' => null];
    } elseif (str_starts_with($routeName, 'cases.')) {
        $trail[] = ['label' => 'Cases', 'url' => route('cases.index')];
        $trail[] = ['label' => match ($routeName) {
            'cases.index' => 'All cases',
            'cases.show' => 'Case details',
            'cases.print' => 'Printable record',
            default => 'Cases',
        }, 'url' => null];
    } elseif (str_starts_with($routeName, 'staff.permissions.')) {
        if (auth()->user()->can('manage-staff')) {
            $trail[] = ['label' => 'User Management', 'url' => route('staff.index')];
        }
        $trail[] = ['label' => 'User permissions', 'url' => null];
    } elseif (str_starts_with($routeName, 'staff.')) {
        $trail[] = ['label' => 'User Management', 'url' => route('staff.index')];
        $trail[] = ['label' => match ($routeName) {
            'staff.index' => 'All staff',
            'staff.create' => 'Add staff',
            'staff.edit' => 'Edit staff',
            default => 'Staff',
        }, 'url' => null];
    } elseif (str_starts_with($routeName, 'permissions.')) {
        $trail[] = ['label' => 'Permissions', 'url' => null];
    } elseif (str_starts_with($routeName, 'audit-logs.')) {
        $trail[] = ['label' => 'Audit Log', 'url' => null];
    } elseif (str_starts_with($routeName, 'reports.')) {
        $trail[] = ['label' => 'Reports', 'url' => route('reports.summary')];
        $trail[] = ['label' => 'Administrative summary', 'url' => null];
    } elseif (str_starts_with($routeName, 'two-factor.')) {
        $trail[] = ['label' => 'System Settings', 'url' => route('two-factor.show')];
        $trail[] = ['label' => 'Security', 'url' => null];
    } elseif (in_array($routeName, ['dashboard', 'role.dashboard'], true)) {
        $trail = [['label' => 'Dashboard', 'url' => null]];
    }
@endphp

<nav class="breadcrumbs" aria-label="Breadcrumb">
    @foreach($trail as $index => $crumb)
        @if($index > 0)
            <span class="breadcrumb-sep" aria-hidden="true">/</span>
        @endif

        @if($crumb['url'])
            <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
        @else
            <span class="breadcrumb-current" aria-current="page">{{ $crumb['label'] }}</span>
        @endif
    @endforeach
</nav>
