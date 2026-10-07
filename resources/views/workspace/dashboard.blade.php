@extends('layouts.workspace')

@section('title', 'Dashboard — HelpDesk')

@section('content')
    <div class="workspace-page-heading">
        <div>
            <p class="workspace-eyebrow">Support overview</p>
            <h1>Dashboard</h1>
            <p>Live workload and ticket activity for your authorized queue.</p>
        </div>
        <a href="{{ route('tickets.index') }}" class="workspace-button workspace-button-primary">Open ticket queue</a>
    </div>

    <section class="workspace-stats" aria-label="Support metrics">
        <article class="workspace-card workspace-stat-card">
            <span>Active workload</span>
            <strong data-stat="active-workload">{{ number_format($metrics['active_workload']) }}</strong>
            <small>Open, pending, and in-progress</small>
        </article>
        <article class="workspace-card workspace-stat-card">
            <span>Average first response</span>
            <strong>—</strong>
            <small>Not available yet</small>
        </article>
        <article class="workspace-card workspace-stat-card">
            <span>Resolved today</span>
            <strong data-stat="resolved-today">{{ number_format($metrics['resolved_today']) }}</strong>
            <small>{{ now(config('app.timezone'))->format('M j, Y') }}</small>
        </article>
        <article class="workspace-card workspace-stat-card">
            <span>Satisfaction (CSAT)</span>
            <strong>—</strong>
            <small>No ratings yet</small>
        </article>
    </section>

    <div class="workspace-dashboard-grid">
        <section class="workspace-card" aria-labelledby="weekly-tickets-heading">
            <div class="workspace-card-heading">
                <div>
                    <p class="workspace-eyebrow">Last 7 days</p>
                    <h2 id="weekly-tickets-heading">Tickets this week</h2>
                </div>
            </div>
            <div class="workspace-chart-wrap">
                <canvas
                    data-workspace-chart
                    data-chart='@json(["labels" => collect($weeklyTickets)->pluck("label"), "counts" => collect($weeklyTickets)->pluck("count")])'
                    aria-label="Ticket creation counts for the last seven days"
                    role="img"
                ></canvas>
            </div>
        </section>

        <section class="workspace-card" aria-labelledby="recent-activity-heading">
            <div class="workspace-card-heading">
                <div>
                    <p class="workspace-eyebrow">Latest changes</p>
                    <h2 id="recent-activity-heading">Recent activity</h2>
                </div>
            </div>

            <div class="workspace-activity-list">
                @forelse ($recentActivities as $activity)
                    <a href="{{ route('tickets.index', ['ticket' => $activity->ticket->ticket_number]) }}" class="workspace-activity-item">
                        <span class="workspace-avatar">{{ str($activity->user?->name ?? 'System')->substr(0, 1)->upper() }}</span>
                        <span>
                            <strong>{{ $activity->user?->name ?? 'System' }}</strong>
                            {{ strtolower($activity->action->label()) }}
                            <b>{{ $activity->ticket->ticket_number }}</b>
                            <small>{{ $activity->created_at->diffForHumans() }}</small>
                        </span>
                    </a>
                @empty
                    <div class="workspace-empty-compact">
                        <strong>No recent activity</strong>
                        <span>Authorized ticket changes will appear here.</span>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
