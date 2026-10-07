<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceTicketDrawerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_ticket_can_be_selected_in_the_queue_drawer(): void
    {
        $staff = User::factory()->staff()->create();
        $ticket = Ticket::factory()->create(['assigned_to' => $staff, 'subject' => 'Drawer detail subject']);

        $this->actingAs($staff)->get(route('tickets.index', ['ticket' => $ticket->ticket_number, 'status' => 'open']))
            ->assertOk()
            ->assertViewHas('selectedTicket', fn (?Ticket $selected): bool => $selected?->is($ticket) === true)
            ->assertSee('data-workspace-ticket-drawer', false)
            ->assertSee('Drawer detail subject')
            ->assertSee('status=open', false);
    }

    public function test_missing_or_unauthorized_drawer_selection_does_not_leak_ticket_data(): void
    {
        $staff = User::factory()->staff()->create();
        $otherStaff = User::factory()->staff()->create();
        $hidden = Ticket::factory()->create(['assigned_to' => $otherStaff, 'subject' => 'Private other staff subject']);

        $this->actingAs($staff)->get(route('tickets.index', ['ticket' => 'TKT-2099-99999']))->assertNotFound();
        $this->actingAs($staff)->get(route('tickets.index', ['ticket' => $hidden->ticket_number]))
            ->assertForbidden()
            ->assertDontSee('Private other staff subject');
    }

    public function test_staff_ticket_detail_url_canonicalizes_to_the_queue_drawer(): void
    {
        $staff = User::factory()->staff()->create();
        $ticket = Ticket::factory()->create(['assigned_to' => $staff]);

        $this->actingAs($staff)->get(route('tickets.show', $ticket))
            ->assertRedirect(route('tickets.index', ['ticket' => $ticket->ticket_number]));
    }

    public function test_invalid_drawer_mutation_returns_to_the_selected_queue_url(): void
    {
        $staff = User::factory()->staff()->create();
        $ticket = Ticket::factory()->create(['assigned_to' => $staff]);
        $drawerUrl = route('tickets.index', ['status' => 'open', 'ticket' => $ticket->ticket_number]);

        $this->actingAs($staff)
            ->from($drawerUrl)
            ->post(route('tickets.comments.store', $ticket), ['message' => ''])
            ->assertRedirect($drawerUrl)
            ->assertSessionHasErrors('message');
    }

    public function test_successful_drawer_mutation_preserves_the_queue_context(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::factory()->create();
        $query = ['status' => 'open', 'priority' => 'high', 'ticket' => $ticket->ticket_number];
        $drawerUrl = route('tickets.index', $query);

        $this->actingAs($admin)
            ->patch(route('tickets.priority.update', $ticket).'?'.http_build_query($query), ['priority' => TicketPriority::High->value])
            ->assertRedirect($drawerUrl);

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'priority' => TicketPriority::High->value]);
    }
}
