<?php

namespace Tests\Feature\Tickets;

use App\Enums\TicketActivityAction;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_claim_an_unassigned_ticket_but_cannot_take_another_staff_ticket(): void
    {
        $staff = User::factory()->staff()->create();
        $otherStaff = User::factory()->staff()->create();
        $available = Ticket::factory()->create();
        $taken = Ticket::factory()->create(['assigned_to' => $otherStaff]);

        $this->actingAs($staff)->patch(route('tickets.assignment.update', $available), [
            'assigned_to' => $staff->id,
        ])->assertRedirect(route('tickets.index', ['ticket' => $available->ticket_number]));
        $this->assertSame($staff->id, $available->fresh()->assigned_to);

        $this->actingAs($staff)->patch(route('tickets.assignment.update', $taken), [
            'assigned_to' => $staff->id,
        ])->assertForbidden();
        $this->assertSame($otherStaff->id, $taken->fresh()->assigned_to);
    }

    public function test_admin_can_assign_and_update_priority_with_activity(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->staff()->create();
        $ticket = Ticket::factory()->create(['priority' => TicketPriority::Low]);

        $this->actingAs($admin)->patch(route('tickets.assignment.update', $ticket), [
            'assigned_to' => $staff->id,
        ])->assertRedirect(route('tickets.index', ['ticket' => $ticket->ticket_number]));

        $this->actingAs($admin)->patch(route('tickets.priority.update', $ticket), [
            'priority' => TicketPriority::Urgent->value,
        ])->assertRedirect(route('tickets.index', ['ticket' => $ticket->ticket_number]));

        $this->assertSame($staff->id, $ticket->fresh()->assigned_to);
        $this->assertSame(TicketPriority::Urgent, $ticket->fresh()->priority);
        $this->assertDatabaseHas('ticket_activities', ['ticket_id' => $ticket->id, 'action' => TicketActivityAction::Assigned->value]);
        $this->assertDatabaseHas('ticket_activities', ['ticket_id' => $ticket->id, 'action' => TicketActivityAction::PriorityChanged->value]);
    }

    public function test_owner_can_close_a_resolved_ticket_but_cannot_change_operational_status(): void
    {
        $owner = User::factory()->create();
        $resolved = Ticket::factory()->for($owner, 'creator')->create(['status' => TicketStatus::Resolved]);
        $open = Ticket::factory()->for($owner, 'creator')->create(['status' => TicketStatus::Open]);

        $this->actingAs($owner)->patch(route('tickets.status.update', $resolved), [
            'status' => TicketStatus::Closed->value,
        ])->assertRedirect(route('tickets.show', $resolved));
        $this->assertSame(TicketStatus::Closed, $resolved->fresh()->status);

        $this->actingAs($owner)->patch(route('tickets.status.update', $open), [
            'status' => TicketStatus::Pending->value,
        ])->assertForbidden();
        $this->assertSame(TicketStatus::Open, $open->fresh()->status);
    }

    public function test_malformed_management_values_do_not_change_the_ticket(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($admin)->patch(route('tickets.status.update', $ticket), ['status' => 'deleted'])
            ->assertSessionHasErrors('status');
        $this->actingAs($admin)->patch(route('tickets.priority.update', $ticket), ['priority' => 'critical'])
            ->assertSessionHasErrors('priority');
        $this->actingAs($admin)->patch(route('tickets.assignment.update', $ticket), ['assigned_to' => $admin->id])
            ->assertSessionHasErrors('assigned_to');

        $ticket->refresh();
        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertSame(TicketPriority::Medium, $ticket->priority);
        $this->assertNull($ticket->assigned_to);
    }
}
