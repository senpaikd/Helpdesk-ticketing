<?php

namespace App\Actions\Tickets;

use App\Enums\TicketActivityAction;
use App\Enums\TicketPriority;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketActivityRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateTicketPriorityAction
{
    public function __construct(private readonly TicketActivityRecorder $activityRecorder) {}

    public function execute(Ticket $ticket, TicketPriority $next, User $actor): Ticket
    {
        $current = $ticket->priority;

        if ($current === $next) {
            throw ValidationException::withMessages(['priority' => 'The ticket already has this priority.']);
        }

        return DB::transaction(function () use ($ticket, $current, $next, $actor): Ticket {
            $ticket->update(['priority' => $next]);
            $this->activityRecorder->record(
                $ticket,
                $actor,
                TicketActivityAction::PriorityChanged,
                $current->value,
                $next->value,
            );

            return $ticket->refresh();
        });
    }
}
