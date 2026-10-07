<?php

namespace App\Http\Controllers;

use App\Actions\Tickets\AssignTicketAction;
use App\Http\Requests\AssignTicketRequest;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketRedirector;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TicketAssignmentController extends Controller
{
    public function update(AssignTicketRequest $request, Ticket $ticket, AssignTicketAction $action, TicketRedirector $redirector): RedirectResponse
    {
        $assignee = $request->validated('assigned_to') === null
            ? null
            : User::query()->findOrFail($request->integer('assigned_to'));

        $action->execute($ticket, $assignee, $request->user());

        return $redirector->afterMutation($request, $ticket, 'Ticket assignment updated.');
    }
}
