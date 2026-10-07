<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        @yield('title', 'HelpDesk')
    </title>

    <script>
        if (
            localStorage.getItem('helpdesk-theme') === 'dark' ||
            (
                !localStorage.getItem('helpdesk-theme') &&
                matchMedia('(prefers-color-scheme: dark)').matches
            )
        ) {
            document.documentElement.classList.add('dark');
        }
    </script>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])
</head>

<body
    class="min-h-screen bg-[var(--portal-bg)] text-[var(--portal-text)] antialiased"
>

<header
    class="sticky top-0 z-40 border-b border-[var(--portal-border)] bg-[color:var(--portal-surface)]/94 backdrop-blur-xl"
>

    <div
        class="mx-auto flex min-h-18 max-w-7xl items-center gap-2 px-3 py-3 sm:px-6 lg:px-8"
    >

        <a
            href="{{ route('portal') }}"
            class="flex min-w-0 items-center gap-2 font-bold"
        >
            <span
                class="grid size-9 shrink-0 place-items-center rounded-xl bg-[var(--portal-primary)] text-white"
            >
                H
            </span>

            <span>
                HelpDesk
            </span>
        </a>

        <nav
            class="ml-auto hidden items-center gap-1 md:flex"
            aria-label="Portal navigation"
        >
            <a
                class="portal-nav-link {{ request()->routeIs('portal') ? 'portal-nav-link-active' : '' }}"
                href="{{ route('portal') }}#overview"
            >
                Overview
            </a>

            <a
                class="portal-nav-link"
                href="{{ route('portal') }}#my-tickets"
            >
                My tickets
            </a>

            <a
                class="portal-nav-link {{ request()->routeIs('tickets.create') ? 'portal-nav-link-active' : '' }}"
                href="{{ route('tickets.create') }}"
            >
                Create ticket
            </a>

            <span class="portal-nav-link cursor-not-allowed opacity-60" aria-disabled="true" title="Coming in the next HelpDesk stage">
                Knowledge base
            </span>

            <a
                class="portal-nav-link {{ request()->routeIs('profile.*') ? 'portal-nav-link-active' : '' }}"
                href="{{ route('profile.edit') }}"
            >
                Profile
            </a>
        </nav>

        <button
            type="button"
            class="portal-icon-button inline-grid"
            data-theme-toggle
            aria-label="Toggle color theme"
        >
            ◐
        </button>

        <button
            type="button"
            class="portal-icon-button inline-grid md:hidden"
            data-menu-toggle
            aria-controls="portal-mobile-navigation"
            aria-label="Open navigation"
            aria-expanded="false"
        >
            ☰
        </button>

        <form
            class="hidden sm:block"
            method="POST"
            action="{{ route('logout') }}"
        >
            @csrf

            <button
                type="submit"
                class="portal-button portal-button-secondary text-sm"
            >
                Log out
            </button>
        </form>
    </div>

    <nav
        id="portal-mobile-navigation"
        class="hidden border-t border-[var(--portal-border)] px-3 py-3 md:hidden"
        data-mobile-menu
        aria-label="Mobile navigation"
    >
        <div class="grid gap-1">

            <a
                class="portal-nav-link"
                href="{{ route('portal') }}#overview"
            >
                Overview
            </a>

            <a
                class="portal-nav-link"
                href="{{ route('portal') }}#my-tickets"
            >
                My tickets
            </a>

            <a
                class="portal-nav-link"
                href="{{ route('tickets.create') }}"
            >
                Create ticket
            </a>

            <span class="portal-nav-link cursor-not-allowed opacity-60" aria-disabled="true">
                Knowledge base — Coming soon
            </span>

            <a
                class="portal-nav-link"
                href="{{ route('profile.edit') }}"
            >
                Profile
            </a>

            <form
                method="POST"
                action="{{ route('logout') }}"
            >
                @csrf

                <button
                    type="submit"
                    class="portal-nav-link w-full text-left"
                >
                    Log out
                </button>
            </form>

        </div>
    </nav>
</header>

<main
    class="mx-auto max-w-7xl px-3 py-7 sm:px-6 lg:px-8"
>

    <x-flash-message
        class="mb-6"
        :message="session('success')"
    />

    @if ($errors->any())

        <x-flash-message
            class="mb-6"
            type="error"
            message="Please review the highlighted fields."
        />

    @endif

    @yield('content')

</main>

</body>
</html>
