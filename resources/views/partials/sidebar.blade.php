<aside class="sidebar" id="appSidebar" aria-label="Main navigation">
    <a class="sidebar-brand" href="{{ route('role.dashboard', ['rolePath' => auth()->user()->roleSlug()]) }}">
        <span class="sidebar-brand-mark">@include('partials.icon', ['name' => 'shield'])</span>
        <span class="sidebar-brand-text">
            <span class="sidebar-brand-name">Police Case Management</span>
            <span class="sidebar-brand-sub">Case administration</span>
        </span>
    </a>

    <button type="button" class="sidebar-close" data-sidebar-close aria-label="Close navigation">
        @include('partials.icon', ['name' => 'close'])
    </button>

    <nav class="sidebar-nav" aria-label="Primary">
        <div class="sidebar-section">
            <p class="sidebar-section-title">Overview</p>
            <a class="nav-item {{ request()->routeIs('dashboard', 'role.dashboard') ? 'active' : '' }}"
               href="{{ route('role.dashboard', ['rolePath' => auth()->user()->roleSlug()]) }}">
                @include('partials.icon', ['name' => 'dashboard'])
                <span class="nav-label">Dashboard</span>
            </a>
        </div>

        <div class="sidebar-section">
            <p class="sidebar-section-title">Records</p>
            <a class="nav-item {{ request()->routeIs('complaints.*') ? 'active' : '' }}"
               href="{{ route('complaints.index') }}">
                @include('partials.icon', ['name' => 'complaints'])
                <span class="nav-label">Complaints</span>
            </a>
            @if(auth()->user()->hasCaseAccess())
                <a class="nav-item {{ request()->routeIs('cases.*') && request('status') !== 'pending_review' ? 'active' : '' }}"
                   href="{{ route('cases.index') }}">
                    @include('partials.icon', ['name' => 'cases'])
                    <span class="nav-label">Cases</span>
                </a>
            @endif
            @can('review-report')
                <a class="nav-item {{ request()->routeIs('cases.index') && request('status') === 'pending_review' ? 'active' : '' }}"
                   href="{{ route('cases.index', ['status' => 'pending_review']) }}">
                    @include('partials.icon', ['name' => 'reports'])
                    <span class="nav-label">Pending Reviews</span>
                </a>
            @endcan
        </div>

        @can('view-reports')
            <div class="sidebar-section">
                <p class="sidebar-section-title">Reporting</p>
                <a class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}"
                   href="{{ route('reports.summary') }}">
                    @include('partials.icon', ['name' => 'print'])
                    <span class="nav-label">Summary Reports</span>
                </a>
            </div>
        @endcan

        @can('view-audit-logs')
            <div class="sidebar-section">
                <p class="sidebar-section-title">Oversight</p>
                <a class="nav-item {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}"
                   href="{{ route('audit-logs.index') }}">
                    @include('partials.icon', ['name' => 'audit'])
                    <span class="nav-label">Audit Log</span>
                </a>
            </div>
        @endcan

        <div class="sidebar-section">
            <p class="sidebar-section-title">Administration</p>
            @can('manage-staff')
                <a class="nav-item {{ request()->routeIs('staff.index', 'staff.create', 'staff.edit', 'staff.destroy', 'staff.toggle') ? 'active' : '' }}"
                   href="{{ route('staff.index') }}">
                    @include('partials.icon', ['name' => 'users'])
                    <span class="nav-label">User Management</span>
                </a>
            @endcan
            @can('manage-permissions')
                <a class="nav-item {{ request()->routeIs('permissions.*') ? 'active' : '' }}"
                   href="{{ route('permissions.index') }}">
                    @include('partials.icon', ['name' => 'shield'])
                    <span class="nav-label">Permissions</span>
                </a>
            @endcan
            <a class="nav-item {{ request()->routeIs('two-factor.*') ? 'active' : '' }}"
               href="{{ route('two-factor.show') }}">
                @include('partials.icon', ['name' => 'shield'])
                <span class="nav-label">System Settings</span>
            </a>
        </div>
    </nav>

</aside>

<div class="sidebar-overlay" data-sidebar-overlay aria-hidden="true"></div>
