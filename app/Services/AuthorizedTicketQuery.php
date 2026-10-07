<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AuthorizedTicketQuery
{
    /** @return Builder<Ticket> */
    public function for(User $user): Builder
    {
        return match ($user->role) {
            UserRole::Admin => Ticket::query(),
            UserRole::Staff => Ticket::query()->where(function (Builder $query) use ($user): void {
                $query->whereNull('assigned_to')->orWhere('assigned_to', $user->id);
            }),
            UserRole::User => Ticket::query()->where('user_id', $user->id),
        };
    }
}
