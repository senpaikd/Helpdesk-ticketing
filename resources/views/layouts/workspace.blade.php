<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'HelpDesk Workspace')</title>
    <script>
        if (localStorage.getItem('helpdesk-theme') === 'dark' || (!localStorage.getItem('helpdesk-theme') && matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="workspace min-h-screen antialiased">
    <button type="button" class="workspace-backdrop" data-workspace-backdrop aria-label="Close navigation" hidden></button>

    <aside id="workspace-sidebar" class="workspace-sidebar" data-workspace-sidebar>
        <a href="{{ route('dashboard') }}" class="workspace-brand">
            <span class="workspace-brand-mark" aria-hidden="true"><i class="ph ph-headset"></i></span>
            <span>HelpDesk</span>
        </a>

        <p class="workspace-nav-label">Workspace</p>
        <nav class="workspace-nav" aria-label="Workspace navigation">
            <a href="{{ route('dashboard') }}" class="workspace-link {{ request()->routeIs('dashboard') ? 'workspace-link-active' : '' }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                <i class="ph ph-squares-four" aria-hidden="true"></i><span>Dashboard</span>
            </a>
            <a href="{{ route('tickets.index') }}" class="workspace-link {{ request()->routeIs('tickets.*') ? 'workspace-link-active' : '' }}" @if(request()->routeIs('tickets.*')) aria-current="page" @endif>
                <i class="ph ph-ticket" aria-hidden="true"></i><span>Tickets</span>
            </a>
            <a href="{{ route('profile.edit') }}" class="workspace-link {{ request()->routeIs('profile.*') ? 'workspace-link-active' : '' }}" @if(request()->routeIs('profile.*')) aria-current="page" @endif>
                <i class="ph ph-user-circle" aria-hidden="true"></i><span>Profile</span>
            </a>
        </nav>

        <div class="workspace-user-card">
            <span class="workspace-avatar workspace-avatar-large" aria-hidden="true">{{ str(auth()->user()->name)->substr(0, 2)->upper() }}</span>
            <span class="workspace-user-copy">
                <strong>{{ auth()->user()->name }}</strong>
                <small>{{ auth()->user()->role->label() }}</small>
            </span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="workspace-user-logout" aria-label="Log out"><i class="ph ph-sign-out" aria-hidden="true"></i></button>
            </form>
        </div>
    </aside>

    <div class="workspace-frame">
        <header class="workspace-topbar">
            <button type="button" class="workspace-icon-button workspace-menu-button" data-workspace-menu-toggle aria-controls="workspace-sidebar" aria-expanded="false" aria-label="Open navigation">
                <i class="ph ph-list" aria-hidden="true"></i>
            </button>

            <div class="workspace-topbar-title">
                <span class="workspace-mobile-brand">HelpDesk</span>
                <strong>
                    @if (request()->routeIs('dashboard')) Dashboard
                    @elseif (request()->routeIs('tickets.*')) Tickets
                    @elseif (request()->routeIs('profile.*')) Profile
                    @else Workspace
                    @endif
                </strong>
            </div>

            @unless (request()->routeIs('profile.*'))
                <form action="{{ route('tickets.index') }}" method="GET" class="workspace-global-search">
                    <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
                    <label class="sr-only" for="workspace-search">Search tickets</label>
                    <input id="workspace-search" type="search" name="search" value="{{ request('search') }}" placeholder="Search tickets or customers">
                </form>
            @endunless

            <button type="button" class="workspace-icon-button" data-theme-toggle aria-label="Toggle color theme">
                <i class="ph ph-moon-stars" aria-hidden="true"></i>
            </button>
        </header>

        <main class="workspace-content">
            <x-flash-message class="mb-6" :message="session('success')" />

            @if ($errors->any())
                <x-flash-message class="mb-6" type="error" message="Please review the highlighted fields." />
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
