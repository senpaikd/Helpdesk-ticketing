<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Services\AuthorizedTicketQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_ticket_query_scopes_each_role(): void
    {
        $this->assertTrue(class_exists(AuthorizedTicketQuery::class), 'The shared authorized ticket query service must exist.');

        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $staff = User::factory()->staff()->create();
        $otherStaff = User::factory()->staff()->create();
        $admin = User::factory()->admin()->create();

        $customerTicket = Ticket::factory()->for($customer, 'creator')->create();
        $otherCustomerTicket = Ticket::factory()->for($otherCustomer, 'creator')->create();
        $unassigned = Ticket::factory()->create();
        $staffTicket = Ticket::factory()->create(['assigned_to' => $staff]);
        $otherStaffTicket = Ticket::factory()->create(['assigned_to' => $otherStaff]);

        $query = app(AuthorizedTicketQuery::class);

        $this->assertEqualsCanonicalizing(
            [$customerTicket->id],
            $query->for($customer)->pluck('id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$customerTicket->id, $otherCustomerTicket->id, $unassigned->id, $staffTicket->id],
            $query->for($staff)->pluck('id')->all(),
        );
        $this->assertNotContains($otherStaffTicket->id, $query->for($staff)->pluck('id')->all());
        $this->assertCount(5, $query->for($admin)->get());
    }

    public function test_customers_are_redirected_away_from_workspace_routes(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get(route('dashboard'))->assertRedirect(route('portal'));
        $this->actingAs($customer)->get(route('tickets.index'))->assertRedirect('/#my-tickets');
    }
}
