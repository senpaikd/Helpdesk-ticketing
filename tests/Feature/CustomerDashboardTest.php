<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CustomerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_root_redirects_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_customer_root_is_the_portal(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get('/')
            ->assertOk()
            ->assertSee('HelpDesk');
    }

    public function test_staff_and_admin_portal_requests_redirect_to_dashboard(): void
    {
        foreach ([User::factory()->staff()->create(), User::factory()->admin()->create()] as $operator) {
            $this->actingAs($operator)
                ->get('/')
                ->assertRedirect('/dashboard');
        }
    }

    public function test_customer_dashboard_counts_only_their_tickets_by_status(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();

        Ticket::factory()->for($customer, 'creator')->create(['status' => TicketStatus::Open]);
        Ticket::factory()->for($customer, 'creator')->create(['status' => TicketStatus::InProgress]);
        Ticket::factory()->for($customer, 'creator')->create(['status' => TicketStatus::Resolved]);
        Ticket::factory()->for($customer, 'creator')->create(['status' => TicketStatus::Closed]);
        Ticket::factory()->for($otherCustomer, 'creator')->count(3)->create(['status' => TicketStatus::Open]);

        $this->actingAs($customer)
            ->get('/')
            ->assertViewHas('stats', [
                'total' => 4,
                'open' => 1,
                'in_progress' => 1,
                'resolved' => 1,
            ]);
    }

    public function test_customer_dashboard_recent_tickets_are_owned_ordered_and_limited(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();

        $owned = collect(range(1, 6))->map(fn (int $index) => Ticket::factory()
            ->for($customer, 'creator')
            ->create([
                'subject' => "Owned {$index}",
                'created_at' => now()->subMinutes(7 - $index),
                'updated_at' => now()->subMinutes(7 - $index),
            ]));
        $foreign = Ticket::factory()->for($otherCustomer, 'creator')->create([
            'subject' => 'Foreign newest ticket',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($customer)
            ->get('/')
            ->assertViewHas('recentTickets', function (Collection $tickets) use ($owned, $foreign): bool {
                return $tickets->pluck('id')->all() === $owned->reverse()->take(5)->pluck('id')->values()->all()
                    && ! $tickets->contains('id', $foreign->id);
            });
    }

    public function test_customer_history_search_and_filters_remain_owner_scoped(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $category = Category::factory()->create();

        Ticket::factory()->for($customer, 'creator')->for($category)->count(11)->create([
            'subject' => 'Printer needs attention',
            'status' => TicketStatus::Open,
            'priority' => TicketPriority::High,
        ]);
        Ticket::factory()->for($customer, 'creator')->create([
            'subject' => 'Printer but wrong filters',
            'status' => TicketStatus::Resolved,
            'priority' => TicketPriority::Low,
        ]);
        Ticket::factory()->for($otherCustomer, 'creator')->for($category)->create([
            'subject' => 'Printer foreign private ticket',
            'status' => TicketStatus::Open,
            'priority' => TicketPriority::High,
        ]);

        $query = [
            'search' => 'printer',
            'status' => TicketStatus::Open->value,
            'priority' => TicketPriority::High->value,
            'category' => $category->id,
        ];

        $this->actingAs($customer)
            ->get(route('portal', $query))
            ->assertViewHas('tickets', function (LengthAwarePaginator $tickets): bool {
                return $tickets->total() === 11
                    && $tickets->count() === 10
                    && str_contains($tickets->url(2), 'status=open')
                    && str_contains($tickets->url(2), 'priority=high');
            });
    }

    public function test_malformed_filters_do_not_broaden_the_customer_query(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        Ticket::factory()->for($customer, 'creator')->count(2)->create();
        Ticket::factory()->for($otherCustomer, 'creator')->count(4)->create();

        $this->actingAs($customer)
            ->get('/?status=deleted&priority=critical&category=not-an-id')
            ->assertViewHas('tickets', fn (LengthAwarePaginator $tickets): bool => $tickets->total() === 2
                && $tickets->every(fn (Ticket $ticket): bool => $ticket->user_id === $customer->id));
    }

    public function test_inactive_user_cannot_render_the_portal_or_workspace_destination(): void
    {
        $inactiveCustomer = User::factory()->inactive()->create();
        $inactiveAdmin = User::factory()->admin()->inactive()->create();

        $this->actingAs($inactiveCustomer)->get('/')->assertRedirect('/login');
        $this->assertGuest();

        $this->actingAs($inactiveAdmin)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_authenticated_customer_sees_a_real_private_portal_without_guest_marketing(): void
    {
        $customer = User::factory()->create(['name' => 'Kent Rivera']);
        $ticket = Ticket::factory()->for($customer, 'creator')->create([
            'subject' => 'Cannot access my account',
            'status' => TicketStatus::Open,
        ]);

        $this->actingAs($customer)
            ->get('/')
            ->assertOk()
            ->assertSee('Welcome back, Kent Rivera')
            ->assertSee('data-stat="total">1<', false)
            ->assertSee('data-stat="open">1<', false)
            ->assertSee('Cannot access my account')
            ->assertSee($ticket->ticket_number)
            ->assertSee('name="search"', false)
            ->assertSee('name="status"', false)
            ->assertSee('href="'.route('tickets.create').'"', false)
            ->assertSee('href="'.route('profile.edit').'"', false)
            ->assertSee('Knowledge base')
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('action="'.route('logout').'"', false)
            ->assertDontSee('Create account')
            ->assertDontSee('Sign in')
            ->assertDontSee('Available after sign in')
            ->assertDontSee('Support that feels straightforward')
            ->assertDontSee('Deskly')
            ->assertDontSee('TKT-2026-00001');
    }

    public function test_customer_without_tickets_sees_a_truthful_empty_state(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get('/')
            ->assertOk()
            ->assertSee('No tickets yet')
            ->assertSee('Create your first ticket')
            ->assertSee('data-stat="total">0<', false);
    }

    public function test_customer_ticket_index_redirects_to_portal_history_while_ticket_workflows_remain_available(): void
    {
        $customer = User::factory()->create();
        $ticket = Ticket::factory()->for($customer, 'creator')->create();

        $this->actingAs($customer)->get('/tickets')->assertRedirect('/#my-tickets');
        $this->actingAs($customer)->get('/tickets/create')->assertOk();
        $this->actingAs($customer)->get(route('tickets.show', $ticket))->assertOk();
    }

    public function test_staff_and_admin_ticket_queue_remains_available(): void
    {
        $this->actingAs(User::factory()->staff()->create())->get('/tickets')->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get('/tickets')->assertOk();
    }
}
