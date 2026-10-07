<?php

namespace App\Actions\Tickets;

use App\Enums\TicketActivityAction;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketActivityRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateTicketStatusAction
{
    public function __construct(private readonly TicketActivityRecorder $activityRecorder) {}

    public function execute(Ticket $ticket, TicketStatus $next, User $actor): Ticket
    {
        $current = $ticket->status;

        if (! $current->canTransitionTo($next, $actor->role)) {
            throw ValidationException::withMessages(['status' => "The ticket cannot move from {$current->label()} to {$next->label()}."]);
        }

        return DB::transaction(function () use ($ticket, $current, $next, $actor): Ticket {
            $timestamps = match ($next) {
                TicketStatus::Resolved => ['resolved_at' => now(), 'closed_at' => null],
                TicketStatus::Closed => ['closed_at' => now()],
                default => $current === TicketStatus::Resolved || $current === TicketStatus::Closed
                    ? ['resolved_at' => null, 'closed_at' => null]
                    : [],
            };

            $ticket->update(['status' => $next, ...$timestamps]);

            $action = match (true) {
                $current === TicketStatus::Closed => TicketActivityAction::Reopened,
                $next === TicketStatus::Resolved => TicketActivityAction::Resolved,
                $next === TicketStatus::Closed => TicketActivityAction::Closed,
                default => TicketActivityAction::StatusChanged,
            };

            $this->activityRecorder->record($ticket, $actor, $action, $current->value, $next->value);

            return $ticket->refresh();
        });
    }
}
