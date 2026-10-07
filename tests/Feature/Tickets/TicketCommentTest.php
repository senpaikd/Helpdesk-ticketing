<?php

namespace Tests\Feature\Tickets;

use App\Enums\TicketActivityAction;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketCommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_people_can_reply_and_create_activity(): void
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'creator')->create();

        $this->actingAs($owner)->post(route('tickets.comments.store', $ticket), [
            'message' => 'I also see the error on my phone.',
        ])->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('ticket_comments', ['ticket_id' => $ticket->id, 'user_id' => $owner->id]);
        $this->assertDatabaseHas('ticket_activities', ['ticket_id' => $ticket->id, 'action' => TicketActivityAction::CommentAdded->value]);
    }

    public function test_closed_and_unauthorized_tickets_reject_replies(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $closed = Ticket::factory()->for($owner, 'creator')->create(['status' => TicketStatus::Closed]);

        $this->actingAs($owner)->post(route('tickets.comments.store', $closed), ['message' => 'Reopen please'])->assertForbidden();
        $this->actingAs($stranger)->post(route('tickets.comments.store', $closed), ['message' => 'Not mine'])->assertForbidden();
        $this->assertDatabaseCount('ticket_comments', 0);
    }
}
