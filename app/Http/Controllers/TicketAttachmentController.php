<?php

namespace App\Http\Controllers;

use App\Actions\Tickets\StoreTicketAttachmentAction;
use App\Http\Requests\StoreTicketAttachmentRequest;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Services\TicketRedirector;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentController extends Controller
{
    public function store(StoreTicketAttachmentRequest $request, Ticket $ticket, StoreTicketAttachmentAction $action, TicketRedirector $redirector): RedirectResponse
    {
        $action->execute($ticket, $request->user(), $request->file('attachment'));

        return $redirector->afterMutation($request, $ticket, 'Attachment uploaded.');
    }

    public function download(Ticket $ticket, TicketAttachment $attachment): StreamedResponse
    {
        $this->authorize('view', $ticket);
        abort_unless($attachment->ticket_id === $ticket->id, 404);

        $disk = Storage::disk('ticket_attachments');
        abort_unless($disk->exists($attachment->file_path), 404);

        return $disk->download($attachment->file_path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
