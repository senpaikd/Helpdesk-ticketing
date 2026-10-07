<?php

namespace Tests\Feature\Tickets;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_detail_shows_conversation_attachments_and_activity_only_to_authorized_viewers(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'creator')->create();
        $ticket->comments()->create(['user_id' => $owner->id, 'message' => 'Visible conversation message']);
        $ticket->attachments()->create(['uploaded_by' => $owner->id, 'original_name' => 'evidence.txt', 'file_path' => 'safe/path.txt', 'mime_type' => 'text/plain', 'file_size' => 12]);

        $this->actingAs($owner)->get(route('tickets.show', $ticket))
            ->assertOk()->assertSee('Visible conversation message')->assertSee('evidence.txt');
        $this->actingAs($stranger)->get(route('tickets.show', $ticket))->assertForbidden();
    }
}
