<?php

namespace App\Actions\Tickets;

use App\Enums\TicketActivityAction;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Services\TicketActivityRecorder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class StoreTicketAttachmentAction
{
    public function __construct(private readonly TicketActivityRecorder $activityRecorder) {}

    public function execute(Ticket $ticket, User $uploader, UploadedFile $file): TicketAttachment
    {
        $originalName = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $path = null;

        try {
            return DB::transaction(function () use ($ticket, $uploader, $file, $originalName, $extension, &$path): TicketAttachment {
                $generatedName = Str::uuid()->toString().($extension === '' ? '' : '.'.$extension);
                $path = $file->storeAs((string) $ticket->id, $generatedName, 'ticket_attachments');

                if ($path === false) {
                    throw new RuntimeException('The attachment could not be stored.');
                }

                $attachment = $ticket->attachments()->create([
                    'uploaded_by' => $uploader->id,
                    'original_name' => $originalName,
                    'file_path' => $path,
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'file_size' => $file->getSize(),
                ]);

                $this->activityRecorder->record(
                    $ticket,
                    $uploader,
                    TicketActivityAction::AttachmentAdded,
                    null,
                    $originalName,
                );

                return $attachment;
            });
        } catch (Throwable $exception) {
            if (is_string($path)) {
                Storage::disk('ticket_attachments')->delete($path);
            }

            throw $exception;
        }
    }
}
