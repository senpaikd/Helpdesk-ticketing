<?php

namespace Tests\Feature\Tickets;

use App\Enums\TicketActivityAction;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_upload_is_private_safely_named_and_audited(): void
    {
        Storage::fake('ticket_attachments');
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'creator')->create();
        $file = UploadedFile::fake()->create('../../report.pdf', 120, 'application/pdf');

        $this->actingAs($owner)->post(route('tickets.attachments.store', $ticket), ['attachment' => $file])
            ->assertRedirect(route('tickets.show', $ticket));

        $attachment = TicketAttachment::query()->sole();
        $this->assertSame('report.pdf', $attachment->original_name);
        $this->assertStringNotContainsString('..', $attachment->file_path);
        $this->assertStringNotContainsString('report', $attachment->file_path);
        Storage::disk('ticket_attachments')->assertExists($attachment->file_path);
        $this->assertDatabaseHas('ticket_activities', ['ticket_id' => $ticket->id, 'action' => TicketActivityAction::AttachmentAdded->value]);
    }

    public function test_disallowed_mismatched_and_oversized_files_are_rejected(): void
    {
        Storage::fake('ticket_attachments');
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'creator')->create();

        foreach ([
            UploadedFile::fake()->create('payload.php', 10, 'application/x-php'),
            UploadedFile::fake()->create('malware.exe', 10, 'application/x-msdownload'),
            UploadedFile::fake()->create('fake.pdf', 10, 'application/x-php'),
            UploadedFile::fake()->create('large.pdf', 5121, 'application/pdf'),
        ] as $file) {
            $this->actingAs($owner)->post(route('tickets.attachments.store', $ticket), ['attachment' => $file])
                ->assertSessionHasErrors('attachment');
        }

        $this->assertDatabaseCount('ticket_attachments', 0);
        $this->assertSame([], Storage::disk('ticket_attachments')->allFiles());
    }

    public function test_download_requires_ticket_access_and_existing_private_file(): void
    {
        Storage::fake('ticket_attachments');
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'creator')->create();
        $attachment = TicketAttachment::query()->create([
            'ticket_id' => $ticket->id,
            'uploaded_by' => $owner->id,
            'original_name' => 'notes.txt',
            'file_path' => $ticket->id.'/generated.txt',
            'mime_type' => 'text/plain',
            'file_size' => 5,
        ]);
        Storage::disk('ticket_attachments')->put($attachment->file_path, 'hello');

        $this->actingAs($owner)->get(route('tickets.attachments.download', [$ticket, $attachment]))
            ->assertOk()
            ->assertDownload('notes.txt');
        $this->actingAs($stranger)->get(route('tickets.attachments.download', [$ticket, $attachment]))->assertForbidden();

        Storage::disk('ticket_attachments')->delete($attachment->file_path);
        $this->actingAs($owner)->get(route('tickets.attachments.download', [$ticket, $attachment]))->assertNotFound();
    }

    public function test_closed_ticket_rejects_new_attachments(): void
    {
        Storage::fake('ticket_attachments');
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'creator')->create(['status' => TicketStatus::Closed]);

        $this->actingAs($owner)->post(route('tickets.attachments.store', $ticket), [
            'attachment' => UploadedFile::fake()->create('notes.txt', 2, 'text/plain'),
        ])->assertForbidden();
    }
}
