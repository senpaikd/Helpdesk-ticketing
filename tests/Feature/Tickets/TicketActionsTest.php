<?php

namespace Tests\Feature\Tickets;

use App\Actions\Tickets\AssignTicketAction;
use App\Actions\Tickets\CreateTicketAction;
use App\Actions\Tickets\UpdateTicketPriorityAction;
use App\Actions\Tickets\UpdateTicketStatusAction;
use App\Enums\TicketActivityAction;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TicketActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_creation_generates_a_persisted_public_number_and_activity(): void
    {
        $creator = User::factory()->create();
        $category = Category::factory()->create();

        $ticket = app(CreateTicketAction::class)->execute($creator, [
            'category_id' => $category->id,
            'subject' => 'Cannot access the VPN',
            'description' => 'The VPN client rejects my company credentials.',
            'priority' => TicketPriority::High,
        ]);

        $this->assertTrue($ticket->exists);
        $this->assertSame('TKT-'.now()->format('Y').'-'.str_pad((string) $ticket->id, 5, '0', STR_PAD_LEFT), $ticket->ticket_number);
        $this->assertSame($creator->id, $ticket->user_id);
        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'user_id' => $creator->id,
            'action' => TicketActivityAction::Created->value,
        ]);
    }

    public function test_rapid_creation_uses_the_persisted_id_for_unique_public_numbers(): void
    {
        $creator = User::factory()->create();
        $category = Category::factory()->create();
        $action = app(CreateTicketAction::class);
        $numbers = [];

        foreach (range(1, 12) as $position) {
            $ticket = $action->execute($creator, [
                'category_id' => $category->id,
                'subject' => "Issue {$position}",
                'description' => 'A reproducible support issue.',
                'priority' => TicketPriority::Medium,
            ]);
            $numbers[] = $ticket->ticket_number;
            $this->assertSame('TKT-'.now()->format('Y').'-'.str_pad((string) $ticket->id, 5, '0', STR_PAD_LEFT), $ticket->ticket_number);
        }

        $this->assertCount(count($numbers), array_unique($numbers));
    }

    public function test_assignment_requires_an_active_staff_assignee_and_records_claims(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->staff()->create();
        $inactiveStaff = User::factory()->staff()->inactive()->create();
        $customer = User::factory()->create();
        $ticket = Ticket::factory()->create();
        $action = app(AssignTicketAction::class);

        $claimed = $action->execute($ticket, $staff, $staff);
        $this->assertSame($staff->id, $claimed->assigned_to);
        $this->assertDatabaseHas('ticket_activities', ['ticket_id' => $ticket->id, 'action' => TicketActivityAction::Claimed->value]);

        $reassigned = $action->execute($claimed, null, $admin);
        $this->assertNull($reassigned->assigned_to);
        $this->assertDatabaseHas('ticket_activities', ['ticket_id' => $ticket->id, 'action' => TicketActivityAction::Assigned->value]);

        foreach ([$inactiveStaff, $customer] as $invalidAssignee) {
            try {
                $action->execute($reassigned, $invalidAssignee, $admin);
                $this->fail('Invalid assignee was accepted.');
            } catch (ValidationException) {
                $this->assertNull($ticket->fresh()->assigned_to);
            }
        }
    }

    public function test_status_updates_synchronize_timestamps_and_activity(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::factory()->create(['status' => TicketStatus::InProgress]);
        $action = app(UpdateTicketStatusAction::class);

        $resolved = $action->execute($ticket, TicketStatus::Resolved, $admin);
        $this->assertNotNull($resolved->resolved_at);
        $this->assertNull($resolved->closed_at);
        $this->assertDatabaseHas('ticket_activities', ['ticket_id' => $ticket->id, 'action' => TicketActivityAction::Resolved->value]);

        $closed = $action->execute($resolved, TicketStatus::Closed, $admin);
        $this->assertNotNull($closed->closed_at);
        $this->assertDatabaseHas('ticket_activities', ['ticket_id' => $ticket->id, 'action' => TicketActivityAction::Closed->value]);

        $reopened = $action->execute($closed, TicketStatus::Open, $admin);
        $this->assertNull($reopened->resolved_at);
        $this->assertNull($reopened->closed_at);
        $this->assertDatabaseHas('ticket_activities', ['ticket_id' => $ticket->id, 'action' => TicketActivityAction::Reopened->value]);
    }

    public function test_invalid_status_change_is_atomic(): void
    {
        $staff = User::factory()->staff()->create();
        $ticket = Ticket::factory()->create([
            'assigned_to' => $staff,
            'status' => TicketStatus::Closed,
            'resolved_at' => now()->subHour(),
            'closed_at' => now(),
        ]);
        $before = $ticket->only(['status', 'resolved_at', 'closed_at']);
        $activityCount = $ticket->activities()->count();

        try {
            app(UpdateTicketStatusAction::class)->execute($ticket, TicketStatus::Open, $staff);
            $this->fail('Invalid transition was accepted.');
        } catch (ValidationException) {
            $ticket->refresh();
            $this->assertSame($before['status'], $ticket->status);
            $this->assertTrue($before['resolved_at']->equalTo($ticket->resolved_at));
            $this->assertTrue($before['closed_at']->equalTo($ticket->closed_at));
            $this->assertSame($activityCount, $ticket->activities()->count());
        }
    }

    public function test_priority_updates_record_old_and_new_values(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::factory()->create(['priority' => TicketPriority::Low]);

        $updated = app(UpdateTicketPriorityAction::class)->execute($ticket, TicketPriority::Urgent, $admin);

        $this->assertSame(TicketPriority::Urgent, $updated->priority);
        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'action' => TicketActivityAction::PriorityChanged->value,
            'old_value' => TicketPriority::Low->value,
            'new_value' => TicketPriority::Urgent->value,
        ]);
    }
}
