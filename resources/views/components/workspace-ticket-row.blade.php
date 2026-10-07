@props(['ticket'])

@php($drawerUrl = request()->fullUrlWithQuery(['ticket' => $ticket->ticket_number]))

<tr class="workspace-ticket-row">
    <td><a href="{{ $drawerUrl }}" class="workspace-ticket-link"><span class="workspace-ticket-id">{{ $ticket->ticket_number }}</span></a></td>
    <td><a href="{{ $drawerUrl }}" class="workspace-ticket-link"><strong>{{ $ticket->subject }}</strong><small>{{ $ticket->category->name }}</small></a></td>
    <td><span class="workspace-person"><span class="workspace-avatar">{{ str($ticket->creator->name)->substr(0, 1)->upper() }}</span>{{ $ticket->creator->name }}</span></td>
    <td><span class="workspace-priority workspace-priority-{{ $ticket->priority->value }}">{{ $ticket->priority->label() }}</span></td>
    <td><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $ticket->status->badgeClasses() }}">{{ $ticket->status->label() }}</span></td>
    <td>{{ $ticket->assignee?->name ?? 'Unassigned' }}</td>
    <td class="workspace-table-muted">{{ $ticket->updated_at->diffForHumans() }}</td>
</tr>
