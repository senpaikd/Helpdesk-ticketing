@extends('layouts.workspace')

@section('title', 'Tickets — HelpDesk')

@section('content')
    <div class="workspace-page-heading">
        <div>
            <p class="workspace-eyebrow">Support workspace</p>
            <h1>Ticket queue</h1>
            <p>Search, prioritize, and resolve requests in your authorized workload.</p>
        </div>
    </div>

    <form method="GET" class="workspace-filter-card">
        <label class="workspace-filter-search">
            <span class="sr-only">Search tickets</span>
            <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
            <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Search number, subject, or requester">
        </label>
        <select name="status" class="portal-field" aria-label="Filter by status">
            <option value="">All statuses</option>
            @foreach(\App\Enums\TicketStatus::cases() as $status)<option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>@endforeach
        </select>
        <select name="priority" class="portal-field" aria-label="Filter by priority">
            <option value="">All priorities</option>
            @foreach(\App\Enums\TicketPriority::cases() as $priority)<option value="{{ $priority->value }}" @selected($filters['priority'] === $priority->value)>{{ $priority->label() }}</option>@endforeach
        </select>
        <select name="category" class="portal-field" aria-label="Filter by category">
            <option value="">All categories</option>
            @foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) $filters['category'] === (string) $category->id)>{{ $category->name }}</option>@endforeach
        </select>
        @if(auth()->user()->role === \App\Enums\UserRole::Admin)
            <select name="assignee" class="portal-field" aria-label="Filter by assignee">
                <option value="">All assignees</option>
                @foreach($staff as $member)<option value="{{ $member->id }}" @selected((string) $filters['assignee'] === (string) $member->id)>{{ $member->name }}</option>@endforeach
            </select>
        @endif
        <button type="submit" class="workspace-button workspace-button-primary">Filter</button>
        @if(collect($filters)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty())
            <a href="{{ route('tickets.index') }}" class="workspace-button workspace-button-secondary">Clear</a>
        @endif
    </form>

    <div class="workspace-table-card">
        <div class="workspace-table-scroll">
            <table class="workspace-table">
                <thead><tr><th>ID</th><th>Subject</th><th>Requester</th><th>Priority</th><th>Status</th><th>Assignee</th><th>Updated</th></tr></thead>
                <tbody>
                    @forelse($tickets as $ticket)
                        <x-workspace-ticket-row :ticket="$ticket" />
                    @empty
                        <tr><td colspan="7"><div class="workspace-empty-compact"><strong>No tickets match your filters</strong><span>Try clearing one or more filters.</span></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $tickets->links() }}</div>

    @if($selectedTicket)
        <x-workspace-ticket-drawer :ticket="$selectedTicket" :staff="$staff" />
    @endif
@endsection
