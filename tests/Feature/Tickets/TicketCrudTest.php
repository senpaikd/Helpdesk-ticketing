<?php

namespace Tests\Feature\Tickets;

use App\Enums\TicketPriority;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_pages_require_authentication(): void
    {
        $this->get('/tickets')->assertRedirect('/login');
        $this->get('/tickets/create')->assertRedirect('/login');
    }

    public function test_user_can_create_a_ticket_with_an_active_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->post(route('tickets.store'), [
            'category_id' => $category->id,
            'subject' => 'Email stopped synchronizing',
            'description' => 'Messages have not synchronized since this morning.',
            'priority' => TicketPriority::High->value,
        ]);

        $ticket = Ticket::query()->sole();
        $response->assertRedirect(route('tickets.show', $ticket))->assertSessionHas('success');
        $this->assertSame($user->id, $ticket->user_id);
        $this->assertNotNull($ticket->ticket_number);
    }

    public function test_inactive_category_and_forged_fields_are_rejected(): void
    {
        $user = User::factory()->create();
        $inactiveCategory = Category::factory()->create(['is_active' => false]);

        $this->actingAs($user)->post(route('tickets.store'), [
            'category_id' => $inactiveCategory->id,
            'subject' => 'Issue',
            'description' => 'Enough detail to describe the issue.',
            'priority' => 'critical',
            'status' => 'closed',
            'assigned_to' => User::factory()->staff()->create()->id,
        ])->assertSessionHasErrors(['category_id', 'priority']);

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_show_uses_public_ticket_number_and_enforces_visibility(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'creator')->create(['subject' => 'Private printer issue']);

        $this->actingAs($owner)->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee($ticket->ticket_number)
            ->assertSee('Private printer issue');

        $this->actingAs($other)->get(route('tickets.show', $ticket))->assertForbidden();
        $this->actingAs($owner)->get('/tickets/'.$ticket->id)->assertNotFound();
    }
}
