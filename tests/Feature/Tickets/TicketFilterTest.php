<?php

namespace Tests\Feature\Tickets;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_search_and_filters_never_expose_another_users_ticket(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = Category::factory()->create();
        $own = Ticket::factory()->for($user, 'creator')->create([
            'category_id' => $category,
            'subject' => 'Shared search phrase own',
            'status' => TicketStatus::Open,
            'priority' => TicketPriority::High,
        ]);
        $hidden = Ticket::factory()->for($other, 'creator')->create([
            'category_id' => $category,
            'subject' => 'Shared search phrase hidden',
            'status' => TicketStatus::Open,
            'priority' => TicketPriority::High,
        ]);

        $this->actingAs($user)->get(route('portal', [
            'search' => 'Shared search phrase',
            'status' => TicketStatus::Open->value,
            'priority' => TicketPriority::High->value,
            'category' => $category->id,
        ]))->assertOk()->assertSee($own->subject)->assertDontSee($hidden->subject);

        $this->actingAs($user)->get(route('portal', [
            'search' => 'Shared search phrase',
            'status' => 'forged',
            'priority' => '../urgent',
            'category' => 'not-an-id',
            'assignee' => $hidden->assigned_to ?? 99999,
        ]))->assertOk()->assertSee($own->subject)->assertDontSee($hidden->subject);
    }

    public function test_staff_scope_contains_unassigned_and_self_assigned_but_not_other_staff_work(): void
    {
        $staff = User::factory()->staff()->create();
        $otherStaff = User::factory()->staff()->create();
        $unassigned = Ticket::factory()->create(['subject' => 'Visible unassigned issue']);
        $own = Ticket::factory()->create(['assigned_to' => $staff, 'subject' => 'Visible assigned issue']);
        $hidden = Ticket::factory()->create(['assigned_to' => $otherStaff, 'subject' => 'Hidden assigned issue']);

        $this->actingAs($staff)->get(route('tickets.index', ['assignee' => $otherStaff->id]))
            ->assertOk()
            ->assertSee($unassigned->subject)
            ->assertSee($own->subject)
            ->assertDontSee($hidden->subject);
    }

    public function test_admin_can_filter_by_assignee_and_pagination_preserves_query_string(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->staff()->create();
        Ticket::factory()->count(17)->create([
            'assigned_to' => $staff,
            'status' => TicketStatus::Open,
            'subject' => 'Paged network issue',
        ]);
        Ticket::factory()->create(['subject' => 'Different hidden result']);

        $this->actingAs($admin)->get(route('tickets.index', [
            'search' => 'Paged',
            'status' => TicketStatus::Open->value,
            'assignee' => $staff->id,
        ]))->assertOk()
            ->assertSee('Paged network issue')
            ->assertDontSee('Different hidden result')
            ->assertSee('search=Paged', false)
            ->assertSee('status=open', false)
            ->assertSee('assignee='.$staff->id, false);
    }

    public function test_workspace_search_matches_requester_name_and_composes_with_filters(): void
    {
        $admin = User::factory()->admin()->create();
        $requester = User::factory()->create(['name' => 'Unique Requester']);
        $other = User::factory()->create(['name' => 'Different Person']);
        $category = Category::factory()->create();
        $match = Ticket::factory()->for($requester, 'creator')->create([
            'category_id' => $category,
            'status' => TicketStatus::Pending,
            'priority' => TicketPriority::Urgent,
        ]);
        $hidden = Ticket::factory()->for($other, 'creator')->create([
            'category_id' => $category,
            'status' => TicketStatus::Pending,
            'priority' => TicketPriority::Urgent,
        ]);

        $this->actingAs($admin)->get(route('tickets.index', [
            'search' => 'Unique Requester',
            'status' => TicketStatus::Pending->value,
            'priority' => TicketPriority::Urgent->value,
            'category' => $category->id,
        ]))->assertOk()
            ->assertSee($match->ticket_number)
            ->assertDontSee($hidden->ticket_number)
            ->assertSee('search=Unique%20Requester', false)
            ->assertSee('status=pending', false);
    }
}
