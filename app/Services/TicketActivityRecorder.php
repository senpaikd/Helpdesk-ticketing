<?php

namespace App\Services;

use App\Enums\TicketActivityAction;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;

class TicketActivityRecorder
{
    public function record(
        Ticket $ticket,
        ?User $actor,
        TicketActivityAction $action,
        ?string $oldValue = null,
        ?string $newValue = null,
    ): TicketActivity {
        return $ticket->activities()->create([
            'user_id' => $actor?->id,
            'action' => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);
    }
}
