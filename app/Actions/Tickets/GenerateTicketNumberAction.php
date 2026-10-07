<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;
use LogicException;

class GenerateTicketNumberAction
{
    public function execute(Ticket $ticket): string
    {
        if (! $ticket->exists || $ticket->id === null) {
            throw new LogicException('A ticket must be persisted before its public number can be generated.');
        }

        $number = sprintf(
            'TKT-%s-%s',
            $ticket->created_at->format('Y'),
            str_pad((string) $ticket->id, 5, '0', STR_PAD_LEFT),
        );

        $ticket->forceFill(['ticket_number' => $number])->save();

        return $number;
    }
}
