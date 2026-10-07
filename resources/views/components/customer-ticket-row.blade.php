<a
    href="{{ route('tickets.show', $ticket) }}"
    {{ $attributes->class('grid gap-3 border-b border-[var(--portal-border)] p-5 transition hover:bg-[var(--portal-soft)] last:border-0 sm:grid-cols-[1fr_auto] sm:items-center') }}
>
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
            <span class="font-mono text-xs font-bold text-[var(--portal-primary)]">{{ $ticket->ticket_number }}</span>
            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $ticket->status->badgeClasses() }}">{{ $ticket->status->label() }}</span>
            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $ticket->priority->badgeClasses() }}">{{ $ticket->priority->label() }}</span>
        </div>

        <h3 class="mt-2 truncate font-bold">{{ $ticket->subject }}</h3>
        <p class="mt-1 text-sm text-[var(--portal-muted)]">
            {{ $ticket->category->name }} · Updated {{ $ticket->updated_at->diffForHumans() }}
        </p>
    </div>

    <div class="text-sm text-[var(--portal-muted)] sm:text-right">
        <p>{{ $ticket->assignee?->name ?? 'Not assigned yet' }}</p>
        <span class="mt-1 inline-block font-semibold text-[var(--portal-primary)]">View ticket →</span>
    </div>
</a>
