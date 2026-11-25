<header class="topbar">
    <div class="topbar-left">
        <button type="button" class="menu-toggle" data-sidebar-toggle
                aria-label="Toggle navigation" aria-controls="appSidebar" aria-expanded="false">
            @include('partials.icon', ['name' => 'menu'])
        </button>

        <div class="page-heading">
            <div class="page-title">@yield('heading', 'Workspace')</div>
            @include('partials.breadcrumbs')
        </div>
    </div>

    <div class="topbar-right">
        @include('partials.theme-toggle')

        <div class="user-menu">
            <button type="button" class="user-trigger" data-user-menu-toggle
                    aria-label="Open user menu" aria-haspopup="true" aria-expanded="false">
                <span class="user-avatar" aria-hidden="true">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                <span class="user-details">
                    <span class="user-name">{{ Auth::user()->name }}</span>
                    <span class="user-role">{{ Auth::user()->roleLabel() }}</span>
                </span>
                @include('partials.icon', ['name' => 'menu', 'class' => 'user-chevron'])
            </button>

            <div class="user-dropdown" role="menu">
                <a href="{{ route('two-factor.show') }}" role="menuitem">
                    @include('partials.icon', ['name' => 'shield'])
                    Security settings
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" role="menuitem" aria-label="Log out">
                        @include('partials.icon', ['name' => 'close'])
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
