<?php

namespace App\Actions\Tickets;

use App\Enums\TicketActivityAction;
use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketActivityRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignTicketAction
{
    public function __construct(private readonly TicketActivityRecorder $activityRecorder) {}

    public function execute(Ticket $ticket, ?User $assignee, User $actor): Ticket
    {
        $this->validateAssignment($ticket, $assignee, $actor);

        return DB::transaction(function () use ($ticket, $assignee, $actor): Ticket {
            $oldAssignee = $ticket->assigned_to;
            $ticket->update(['assigned_to' => $assignee?->id]);

            $action = $oldAssignee === null && $assignee?->id === $actor->id && $actor->role === UserRole::Staff
                ? TicketActivityAction::Claimed
                : TicketActivityAction::Assigned;

            $this->activityRecorder->record(
                $ticket,
                $actor,
                $action,
                $oldAssignee === null ? null : (string) $oldAssignee,
                $assignee === null ? null : (string) $assignee->id,
            );

            return $ticket->refresh();
        });
    }

    private function validateAssignment(Ticket $ticket, ?User $assignee, User $actor): void
    {
        if ($assignee !== null && ($assignee->role !== UserRole::Staff || ! $assignee->is_active)) {
            throw ValidationException::withMessages(['assigned_to' => 'Tickets may only be assigned to active support staff.']);
        }

        $isAdminAssignment = $actor->role === UserRole::Admin;
        $isStaffClaim = $actor->role === UserRole::Staff
            && $ticket->assigned_to === null
            && $assignee?->id === $actor->id;

        if (! $isAdminAssignment && ! $isStaffClaim) {
            throw ValidationException::withMessages(['assigned_to' => 'This assignment is not permitted.']);
        }

        if ($ticket->assigned_to === $assignee?->id) {
            throw ValidationException::withMessages(['assigned_to' => 'The ticket already has this assignment.']);
        }
    }
}
