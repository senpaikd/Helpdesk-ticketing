@extends('layouts.portal')

@section('title', 'My support dashboard — HelpDesk')

@section('content')
    <section id="overview" class="portal-hero overflow-hidden rounded-[2rem] px-6 py-10 text-white sm:px-10 lg:px-12">
        <div class="portal-grid-pattern absolute inset-0 opacity-25" aria-hidden="true"></div>
        <div class="portal-orb portal-orb-one" aria-hidden="true"></div>

        <div class="relative grid gap-8 lg:grid-cols-[1fr_auto] lg:items-end">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.16em] text-emerald-100/80">Your private support space</p>
                <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-5xl">Welcome back, {{ auth()->user()->name }}</h1>
                <p class="mt-4 max-w-2xl text-emerald-50/80">Track every request, reply, and resolution from one calm place.</p>
            </div>

            <a href="{{ route('tickets.create') }}" class="portal-button portal-button-light w-fit">Create ticket</a>
        </div>
    </section>

    <section class="mt-7" aria-labelledby="ticket-summary-heading">
        <h2 id="ticket-summary-heading" class="sr-only">Ticket summary</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['total', 'Total tickets', 'All requests you have submitted'],
                ['open', 'Open', 'Waiting for support'],
                ['in_progress', 'In progress', 'Currently being handled'],
                ['resolved', 'Resolved', 'Ready for your review'],
            ] as [$key, $label, $description])
                <article class="portal-stat-card">
                    <p class="text-sm font-medium text-[var(--portal-muted)]">{{ $label }}</p>
                    <p class="mt-3 text-3xl font-bold tracking-tight" data-stat="{{ $key }}">{{ $stats[$key] }}</p>
                    <p class="mt-2 text-xs text-[var(--portal-muted)]">{{ $description }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="mt-7 overflow-hidden rounded-3xl border border-[var(--portal-border)] bg-[var(--portal-surface)] shadow-[var(--portal-shadow)]" aria-labelledby="recent-tickets-heading">
        <div class="flex flex-col gap-4 border-b border-[var(--portal-border)] p-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 id="recent-tickets-heading" class="font-semibold">Recent tickets</h2>
                <p class="mt-1 text-sm text-[var(--portal-muted)]">Your five most recently created requests.</p>
            </div>
            <a href="#my-tickets" class="text-sm font-semibold text-[var(--portal-primary)] hover:underline">View all tickets</a>
        </div>

        @forelse ($recentTickets as $ticket)
            <x-customer-ticket-row :ticket="$ticket" />
        @empty
            <div class="grid min-h-56 place-items-center px-6 py-12 text-center">
                <div class="max-w-sm">
                    <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-[var(--portal-soft)] text-2xl text-[var(--portal-primary)]" aria-hidden="true">?</span>
                    <h3 class="mt-4 font-semibold">No tickets yet</h3>
                    <p class="mt-2 text-sm leading-6 text-[var(--portal-muted)]">When you need help, send your first request and its progress will appear here.</p>
                    <a href="{{ route('tickets.create') }}" class="portal-button portal-button-primary mt-5">Create your first ticket</a>
                </div>
            </div>
        @endforelse
    </section>

    <section id="my-tickets" class="mt-7 scroll-mt-24" aria-labelledby="my-tickets-heading">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="portal-eyebrow">Complete history</p>
                <h2 id="my-tickets-heading" class="mt-2 text-2xl font-bold tracking-tight">My tickets</h2>
            </div>
            <p class="text-sm text-[var(--portal-muted)]">{{ $tickets->total() }} {{ Str::plural('ticket', $tickets->total()) }}</p>
        </div>

        <form method="GET" action="{{ route('portal') }}#my-tickets" class="mt-5 grid gap-3 rounded-3xl border border-[var(--portal-border)] bg-[var(--portal-surface)] p-4 sm:grid-cols-2 lg:grid-cols-[1.4fr_repeat(3,1fr)_auto]">
            <label class="grid gap-1 text-xs font-semibold text-[var(--portal-muted)]">
                Search
                <input class="portal-field" name="search" value="{{ $filters['search'] }}" placeholder="Ticket number or subject">
            </label>

            <label class="grid gap-1 text-xs font-semibold text-[var(--portal-muted)]">
                Status
                <select class="portal-field" name="status">
                    <option value="">All statuses</option>
                    @foreach (App\Enums\TicketStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected($filters['status'] === $status)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </label>

            <label class="grid gap-1 text-xs font-semibold text-[var(--portal-muted)]">
                Priority
                <select class="portal-field" name="priority">
                    <option value="">All priorities</option>
                    @foreach (App\Enums\TicketPriority::cases() as $priority)
                        <option value="{{ $priority->value }}" @selected($filters['priority'] === $priority)>{{ $priority->label() }}</option>
                    @endforeach
                </select>
            </label>

            <label class="grid gap-1 text-xs font-semibold text-[var(--portal-muted)]">
                Category
                <select class="portal-field" name="category">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected($filters['category'] === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>

            <div class="flex items-end gap-2">
                <button class="portal-button portal-button-primary" type="submit">Filter</button>
                <a class="portal-button portal-button-secondary" href="{{ route('portal') }}#my-tickets">Clear</a>
            </div>
        </form>

        <div class="mt-4 overflow-hidden rounded-3xl border border-[var(--portal-border)] bg-[var(--portal-surface)] shadow-[var(--portal-shadow)]">
            @forelse ($tickets as $ticket)
                <x-customer-ticket-row :ticket="$ticket" />
            @empty
                <div class="p-10 text-center">
                    <h3 class="font-semibold">No tickets match these filters</h3>
                    <p class="mt-2 text-sm text-[var(--portal-muted)]">Clear the filters or create a new request.</p>
                </div>
            @endforelse
        </div>

        @if ($tickets->hasPages())
            <div class="mt-5">{{ $tickets->links() }}</div>
        @endif
    </section>
@endsection
