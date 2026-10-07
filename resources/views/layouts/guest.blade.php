<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        @yield('title', 'Account') — HelpDesk
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

<main
    class="grid min-h-screen lg:grid-cols-[.9fr_1.1fr]"
>

    {{-- Left Branding --}}
    <section
        class="relative hidden overflow-hidden bg-[var(--portal-primary-deep)] p-12 text-white lg:flex lg:flex-col lg:justify-between"
    >

        <div
            class="portal-grid-pattern absolute inset-0 opacity-25"
        ></div>

        <a
            href="/"
            class="relative flex items-center gap-3 text-lg font-bold"
        >
            <span
                class="grid size-10 place-items-center rounded-2xl bg-white/15"
            >
                H
            </span>

            HelpDesk
        </a>

        <div class="relative max-w-lg">

            <p
                class="text-sm font-bold uppercase tracking-[0.18em] text-emerald-100/80"
            >
                Clear support for everyday work
            </p>

            <h1
                class="mt-5 text-4xl font-bold leading-tight tracking-[-0.04em]"
            >
                One calm place for every request, reply,
                and resolution.
            </h1>

            <p
                class="mt-5 leading-7 text-emerald-50/75"
            >
                Secure access keeps your support history
                available to the right people.
            </p>

        </div>

        <p
            class="relative text-sm text-emerald-100/65"
        >
            Laravel HelpDesk portfolio project
        </p>

    </section>

    {{-- Form Area --}}
    <section
        class="flex min-h-screen items-center justify-center px-4 py-12 sm:px-8"
    >

        <div class="w-full max-w-md">

            <div
                class="mb-8 flex items-center justify-between lg:justify-end"
            >

                <a
                    href="/"
                    class="flex items-center gap-2 font-bold lg:hidden"
                >
                    <span
                        class="grid size-9 place-items-center rounded-xl bg-[var(--portal-primary)] text-white"
                    >
                        H
                    </span>

                    HelpDesk
                </a>

                <button
                    type="button"
                    class="portal-icon-button inline-grid"
                    data-theme-toggle
                    aria-label="Toggle color theme"
                >
                    <span class="dark:hidden">
                        ☀
                    </span>

                    <span class="hidden dark:inline">
                        ☾
                    </span>
                </button>

            </div>

            @if (session('status'))

                <div
                    class="mb-6 rounded-2xl bg-[var(--portal-soft)] px-4 py-3 text-sm font-medium text-[var(--portal-primary)]"
                    role="status"
                    aria-live="polite"
                >
                    {{ session('status') }}
                </div>

            @endif

            @if ($errors->any())

                <div
                    class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300"
                    role="alert"
                >
                    <ul class="list-disc space-y-1 pl-5">

                        @foreach ($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>
                </div>

            @endif

            @yield('content')

        </div>
    </section>

</main>

</body>
</html>
