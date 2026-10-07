<?php

namespace App\Policies;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::Staff => $ticket->assigned_to === null || $ticket->assigned_to === $user->id,
            UserRole::User => $ticket->user_id === $user->id,
        };
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket) && $ticket->status !== TicketStatus::Closed;
    }

    public function attach(User $user, Ticket $ticket): bool
    {
        return $this->comment($user, $ticket);
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        if ($ticket->status === TicketStatus::Closed) {
            return false;
        }

        return $user->role === UserRole::Admin
            || ($user->role === UserRole::Staff && $ticket->assigned_to === null);
    }

    public function updateStatus(User $user, Ticket $ticket): bool
    {
        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::Staff => $ticket->assigned_to === $user->id && $ticket->status !== TicketStatus::Closed,
            UserRole::User => $ticket->user_id === $user->id && $ticket->status === TicketStatus::Resolved,
        };
    }

    public function updatePriority(User $user, Ticket $ticket): bool
    {
        return $user->role === UserRole::Admin && $ticket->status !== TicketStatus::Closed;
    }
}
