<?php

namespace Tests\Feature\Database;

use App\Enums\TicketActivityAction;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_default_to_an_active_normal_user(): void
    {
        $user = User::factory()->create();

        $this->assertSame(UserRole::User, $user->role);
        $this->assertTrue($user->is_active);
    }

    public function test_ticket_casts_and_nullable_assignment_are_preserved(): void
    {
        $ticket = Ticket::factory()->create([
            'assigned_to' => null,
            'priority' => TicketPriority::Urgent,
            'status' => TicketStatus::Pending,
        ]);

        $this->assertSame(TicketPriority::Urgent, $ticket->priority);
        $this->assertSame(TicketStatus::Pending, $ticket->status);
        $this->assertNull($ticket->assignee);
    }

    public function test_models_expose_the_named_helpdesk_relationships(): void
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create(['role' => UserRole::Staff]);
        $category = Category::factory()->create();
        $ticket = Ticket::factory()->for($creator, 'creator')->for($category)->create([
            'assigned_to' => $assignee->id,
        ]);

        $comment = $ticket->comments()->create([
            'user_id' => $creator->id,
            'message' => 'The issue started this morning.',
        ]);
        $attachment = $ticket->attachments()->create([
            'uploaded_by' => $creator->id,
            'original_name' => 'error.png',
            'file_path' => 'tickets/example/error.png',
            'mime_type' => 'image/png',
            'file_size' => 1024,
        ]);
        $activity = $ticket->activities()->create([
            'user_id' => $creator->id,
            'action' => TicketActivityAction::Created,
        ]);

        $this->assertTrue($ticket->creator->is($creator));
        $this->assertTrue($ticket->assignee->is($assignee));
        $this->assertTrue($ticket->category->is($category));
        $this->assertTrue($creator->createdTickets->contains($ticket));
        $this->assertTrue($assignee->assignedTickets->contains($ticket));
        $this->assertTrue($creator->comments->contains($comment));
        $this->assertTrue($creator->attachments->contains($attachment));
        $this->assertTrue($creator->activities->contains($activity));
    }

    public function test_ticket_children_are_deleted_without_deleting_referenced_records(): void
    {
        $creator = User::factory()->create();
        $category = Category::factory()->create();
        $ticket = Ticket::factory()->for($creator, 'creator')->for($category)->create();

        $comment = $ticket->comments()->create([
            'user_id' => $creator->id,
            'message' => 'Please help.',
        ]);
        $attachment = $ticket->attachments()->create([
            'uploaded_by' => $creator->id,
            'original_name' => 'details.txt',
            'file_path' => 'tickets/example/details.txt',
            'mime_type' => 'text/plain',
            'file_size' => 24,
        ]);
        $activity = $ticket->activities()->create([
            'user_id' => $creator->id,
            'action' => TicketActivityAction::Created,
        ]);

        $ticket->delete();

        $this->assertDatabaseMissing('ticket_comments', ['id' => $comment->id]);
        $this->assertDatabaseMissing('ticket_attachments', ['id' => $attachment->id]);
        $this->assertDatabaseMissing('ticket_activities', ['id' => $activity->id]);
        $this->assertDatabaseHas('users', ['id' => $creator->id]);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
