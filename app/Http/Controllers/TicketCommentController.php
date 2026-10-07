<?php

namespace App\Http\Controllers;

use App\Enums\TicketActivityAction;
use App\Http\Requests\StoreTicketCommentRequest;
use App\Models\Ticket;
use App\Services\TicketActivityRecorder;
use App\Services\TicketRedirector;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TicketCommentController extends Controller
{
    public function store(StoreTicketCommentRequest $request, Ticket $ticket, TicketActivityRecorder $recorder, TicketRedirector $redirector): RedirectResponse
    {
        DB::transaction(function () use ($request, $ticket, $recorder): void {
            $ticket->comments()->create([
                'user_id' => $request->user()->id,
                'message' => $request->validated('message'),
            ]);
            $recorder->record($ticket, $request->user(), TicketActivityAction::CommentAdded);
        });

        return $redirector->afterMutation($request, $ticket, 'Reply added.');
    }
}
