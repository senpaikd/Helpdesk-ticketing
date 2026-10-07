<?php

namespace App\Http\Controllers;

use App\Actions\Tickets\UpdateTicketPriorityAction;
use App\Enums\TicketPriority;
use App\Http\Requests\UpdateTicketPriorityRequest;
use App\Models\Ticket;
use App\Services\TicketRedirector;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TicketPriorityController extends Controller
{
    public function update(UpdateTicketPriorityRequest $request, Ticket $ticket, UpdateTicketPriorityAction $action, TicketRedirector $redirector): RedirectResponse
    {
        $action->execute($ticket, TicketPriority::from($request->validated('priority')), $request->user());

        return $redirector->afterMutation($request, $ticket, 'Ticket priority updated.');
    }
}
