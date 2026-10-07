<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TicketRedirector
{
    public function afterMutation(Request $request, Ticket $ticket, string $message): RedirectResponse
    {
        if ($request->user()->role === UserRole::User) {
            return redirect()->route('tickets.show', $ticket)->with('success', $message);
        }

        $query = collect($request->query())->only(['search', 'status', 'priority', 'category', 'assignee'])
            ->filter(fn (mixed $value): bool => is_scalar($value) && (string) $value !== '')
            ->map(fn (mixed $value): string => (string) $value)
            ->all();

        $query['ticket'] = $ticket->ticket_number;

        return redirect()->route('tickets.index', $query)->with('success', $message);
    }
}
