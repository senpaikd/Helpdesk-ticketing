<?php

namespace App\Http\Controllers;

use App\Actions\Tickets\UpdateTicketStatusAction;
use App\Enums\TicketStatus;
use App\Http\Requests\UpdateTicketStatusRequest;
use App\Models\Ticket;
use App\Services\TicketRedirector;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TicketStatusController extends Controller
{
    public function update(UpdateTicketStatusRequest $request, Ticket $ticket, UpdateTicketStatusAction $action, TicketRedirector $redirector): RedirectResponse
    {
        $action->execute($ticket, TicketStatus::from($request->validated('status')), $request->user());

        return $redirector->afterMutation($request, $ticket, 'Ticket status updated.');
    }
}
