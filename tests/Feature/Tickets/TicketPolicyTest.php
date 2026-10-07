<?php

namespace Tests\Feature\Tickets;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Policies\TicketPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_only_view_and_close_their_own_eligible_tickets(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $resolved = Ticket::factory()->for($owner, 'creator')->create(['status' => TicketStatus::Resolved]);
        $open = Ticket::factory()->for($owner, 'creator')->create(['status' => TicketStatus::Open]);
        $someoneElses = Ticket::factory()->for($otherUser, 'creator')->create();
        $policy = app(TicketPolicy::class);

        $this->assertTrue($policy->view($owner, $resolved));
        $this->assertFalse($policy->view($owner, $someoneElses));
        $this->assertTrue($policy->updateStatus($owner, $resolved));
        $this->assertFalse($policy->updateStatus($owner, $open));
        $this->assertFalse($policy->assign($owner, $resolved));
        $this->assertFalse($policy->updatePriority($owner, $resolved));
    }

    public function test_staff_can_view_unassigned_and_self_assigned_tickets_only(): void
    {
        $staff = User::factory()->staff()->create();
        $otherStaff = User::factory()->staff()->create();
        $unassigned = Ticket::factory()->create();
        $own = Ticket::factory()->create(['assigned_to' => $staff]);
        $other = Ticket::factory()->create(['assigned_to' => $otherStaff]);
        $policy = app(TicketPolicy::class);

        $this->assertTrue($policy->view($staff, $unassigned));
        $this->assertTrue($policy->view($staff, $own));
        $this->assertFalse($policy->view($staff, $other));
        $this->assertTrue($policy->assign($staff, $unassigned));
        $this->assertFalse($policy->assign($staff, $own));
        $this->assertFalse($policy->assign($staff, $other));
        $this->assertTrue($policy->updateStatus($staff, $own));
        $this->assertFalse($policy->updatePriority($staff, $own));
    }

    public function test_admins_can_manage_every_ticket_but_closed_tickets_are_otherwise_read_only(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->staff()->create();
        $closed = Ticket::factory()->create(['status' => TicketStatus::Closed]);
        $policy = app(TicketPolicy::class);

        $this->assertTrue($policy->view($admin, $closed));
        $this->assertFalse($policy->assign($admin, $closed));
        $this->assertTrue($policy->updateStatus($admin, $closed));
        $this->assertFalse($policy->updatePriority($admin, $closed));
        $this->assertFalse($policy->comment($staff, $closed));
        $this->assertFalse($policy->attach($staff, $closed));
    }
}
