<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\TicketActivity;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class WorkspaceDashboardData
{
    public function __construct(private readonly AuthorizedTicketQuery $authorizedTickets) {}

    /** @return array{metrics: array<string, int|float|null>, weeklyTickets: array<int, array{date: string, label: string, count: int}>, recentActivities: Collection<int, TicketActivity>} */
    public function for(User $user): array
    {
        $timezone = (string) config('app.timezone', 'UTC');
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $weekStart = $today->subDays(6);
        $authorized = $this->authorizedTickets->for($user);

        $activeWorkload = (clone $authorized)
            ->whereIn('status', [TicketStatus::Open, TicketStatus::Pending, TicketStatus::InProgress])
            ->count();

        $resolvedToday = (clone $authorized)
            ->whereNotNull('resolved_at')
            ->whereBetween('resolved_at', [$today, $today->endOfDay()])
            ->count();

        $dailyCounts = (clone $authorized)
            ->whereBetween('created_at', [$weekStart, $today->endOfDay()])
            ->get(['created_at'])
            ->countBy(fn ($ticket): string => $ticket->created_at->timezone($timezone)->toDateString());

        $weeklyTickets = collect(range(0, 6))
            ->map(function (int $offset) use ($dailyCounts, $weekStart): array {
                $day = $weekStart->addDays($offset);

                return [
                    'date' => $day->toDateString(),
                    'label' => $day->format('D'),
                    'count' => (int) $dailyCounts->get($day->toDateString(), 0),
                ];
            })
            ->all();

        $recentActivities = TicketActivity::query()
            ->whereIn('ticket_id', (clone $authorized)->select('tickets.id'))
            ->with(['ticket', 'user'])
            ->latest('created_at')
            ->latest('id')
            ->limit(10)
            ->get();

        return [
            'metrics' => [
                'active_workload' => $activeWorkload,
                'resolved_today' => $resolvedToday,
                'first_response_minutes' => null,
                'csat_average' => null,
                'csat_responses' => 0,
            ],
            'weeklyTickets' => $weeklyTickets,
            'recentActivities' => $recentActivities,
        ];
    }
}
