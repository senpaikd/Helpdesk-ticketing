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
                window.matchMedia('(prefers-color-scheme: dark)').matches
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
        class="mx-auto flex min-h-18 max-w-7xl items-center gap-3 px-4 py-3 sm:px-6 lg:px-8"
    >

        <a
            href="{{ route('dashboard') }}"
            class="flex items-center gap-3 font-bold tracking-tight"
        >

            <span
                class="grid size-10 place-items-center rounded-2xl bg-[var(--portal-primary)] text-white shadow-lg shadow-emerald-950/15"
            >
                <svg
                    viewBox="0 0 24 24"
                    class="size-5"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.9"
                    aria-hidden="true"
                >
                    <path
                        d="M4 13a8 8 0 0 1 16 0M4 13v4a2 2 0 0 0 2 2h1v-7H6a2 2 0 0 0-2 1Zm16 0v4a2 2 0 0 1-2 2h-1v-7h1a2 2 0 0 1 2 1Z"
                    />
                </svg>
            </span>

            <span class="text-lg">
                HelpDesk
            </span>

        </a>

        <nav
            class="ml-auto hidden items-center gap-1 md:flex"
        >

            <a
                class="portal-nav-link {{
                    request()->routeIs(
                        'tickets.index',
                        'tickets.show'
                    )
                        ? 'portal-nav-link-active'
                        : ''
                }}"
                href="{{ route('tickets.index') }}"
            >
                Tickets
            </a>

            <a
                class="portal-nav-link {{
                    request()->routeIs('tickets.create')
                        ? 'portal-nav-link-active'
                        : ''
                }}"
                href="{{ route('tickets.create') }}"
            >
                Create ticket
            </a>

            <a
                class="portal-nav-link {{
                    request()->routeIs('profile.*')
                        ? 'portal-nav-link-active'
                        : ''
                }}"
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

            <svg
                viewBox="0 0 24 24"
                class="size-5"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                aria-hidden="true"
            >
                <circle
                    cx="12"
                    cy="12"
                    r="4"
                />

                <path
                    d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2"
                />
            </svg>

        </button>

        <form
            method="POST"
            action="{{ route('logout') }}"
        >
            @csrf

            <button
                class="portal-button portal-button-secondary text-sm"
                type="submit"
            >
                Log out
            </button>
        </form>

    </div>
</header>

<main
    class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8"
>

    @if (session('success'))

        <div
            role="status"
            class="mb-6 rounded-2xl border border-emerald-300 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200"
        >
            {{ session('success') }}
        </div>

    @endif

    @if ($errors->any())

        <div
            role="alert"
            class="mb-6 rounded-2xl border border-red-300 bg-red-50 px-5 py-4 text-sm text-red-900 dark:border-red-900 dark:bg-red-950 dark:text-red-200"
        >
            Please review the highlighted fields.
        </div>

    @endif

    @yield('content')

</main>

</body>
</html>
