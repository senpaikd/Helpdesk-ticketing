<?php

namespace App\Actions\Tickets;

use App\Enums\TicketActivityAction;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketActivityRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;

class CreateTicketAction
{
    public function __construct(
        private readonly GenerateTicketNumberAction $generateNumber,
        private readonly TicketActivityRecorder $activityRecorder,
        private readonly StoreTicketAttachmentAction $storeAttachment,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(User $creator, array $attributes, ?UploadedFile $attachment = null): Ticket
    {
        return DB::transaction(function () use ($creator, $attributes, $attachment): Ticket {
            $ticket = Ticket::query()->create([
                'user_id' => $creator->id,
                'category_id' => $attributes['category_id'],
                'subject' => $attributes['subject'],
                'description' => $attributes['description'],
                'priority' => $attributes['priority'],
                'status' => TicketStatus::Open,
            ]);

            $this->generateNumber->execute($ticket);
            $this->activityRecorder->record($ticket, $creator, TicketActivityAction::Created);

            if ($attachment !== null) {
                $this->storeAttachment->execute($ticket, $creator, $attachment);
            }

            return $ticket->refresh();
        });
    }
}
