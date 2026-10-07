<?php

namespace Tests\Feature;

use App\Enums\TicketActivityAction;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_admin_dashboard_uses_system_wide_truthful_metrics(): void
    {
        CarbonImmutable::setTestNow('2026-10-06 12:00:00');
        $admin = User::factory()->admin()->create();

        Ticket::factory()->create(['status' => TicketStatus::Open]);
        Ticket::factory()->create(['status' => TicketStatus::Pending]);
        Ticket::factory()->create(['status' => TicketStatus::InProgress]);
        Ticket::factory()->create(['status' => TicketStatus::Resolved, 'resolved_at' => now()]);
        Ticket::factory()->create(['status' => TicketStatus::Resolved, 'resolved_at' => now()->subDay()]);
        Ticket::factory()->create(['status' => TicketStatus::Closed, 'closed_at' => now()]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk()
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['active_workload'] === 3
                && $metrics['resolved_today'] === 1
                && $metrics['first_response_minutes'] === null
                && $metrics['csat_average'] === null
                && $metrics['csat_responses'] === 0)
            ->assertSee('Not available yet')
            ->assertSee('No ratings yet')
            ->assertDontSee('14m')
            ->assertDontSee('94%');
    }

    public function test_staff_dashboard_scopes_metrics_and_activity_to_authorized_work(): void
    {
        $staff = User::factory()->staff()->create();
        $otherStaff = User::factory()->staff()->create();
        $unassigned = Ticket::factory()->create(['status' => TicketStatus::Open]);
        $own = Ticket::factory()->create(['assigned_to' => $staff, 'status' => TicketStatus::Pending]);
        $hidden = Ticket::factory()->create(['assigned_to' => $otherStaff, 'status' => TicketStatus::InProgress]);

        $unassigned->activities()->create(['user_id' => $staff->id, 'action' => TicketActivityAction::CommentAdded]);
        $own->activities()->create(['user_id' => $staff->id, 'action' => TicketActivityAction::Claimed]);
        $hidden->activities()->create(['user_id' => $otherStaff->id, 'action' => TicketActivityAction::PriorityChanged]);

        $response = $this->actingAs($staff)->get(route('dashboard'));

        $response->assertOk()
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['active_workload'] === 2)
            ->assertViewHas('recentActivities', fn ($activities): bool => $activities->pluck('ticket_id')->sort()->values()->all() === [$unassigned->id, $own->id])
            ->assertSee($unassigned->ticket_number)
            ->assertSee($own->ticket_number)
            ->assertDontSee($hidden->ticket_number);
    }

    public function test_weekly_ticket_series_contains_the_last_seven_calendar_days(): void
    {
        CarbonImmutable::setTestNow('2026-10-06 12:00:00');
        $admin = User::factory()->admin()->create();

        Ticket::factory()->create(['created_at' => now()->subDays(6)]);
        Ticket::factory()->count(2)->create(['created_at' => now()->subDays(3)]);
        Ticket::factory()->create(['created_at' => now()]);
        Ticket::factory()->create(['created_at' => now()->subDays(7)]);

        $series = $this->actingAs($admin)->get(route('dashboard'))->viewData('weeklyTickets');

        $this->assertCount(7, $series);
        $this->assertSame('2026-09-30', $series[0]['date']);
        $this->assertSame(1, $series[0]['count']);
        $this->assertSame(2, $series[3]['count']);
        $this->assertSame('2026-10-06', $series[6]['date']);
        $this->assertSame(1, $series[6]['count']);
    }

    public function test_resolved_today_uses_the_configured_application_timezone(): void
    {
        config(['app.timezone' => 'Asia/Manila']);
        CarbonImmutable::setTestNow('2026-10-06 00:30:00', 'Asia/Manila');
        $admin = User::factory()->admin()->create();

        Ticket::factory()->create(['status' => TicketStatus::Resolved, 'resolved_at' => now('Asia/Manila')->startOfDay()->addMinutes(5)]);
        Ticket::factory()->create(['status' => TicketStatus::Resolved, 'resolved_at' => now('Asia/Manila')->subDay()->endOfDay()]);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['resolved_today'] === 1);
    }

    public function test_customer_cannot_render_the_workspace_dashboard(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get(route('dashboard'))->assertRedirect(route('portal'));
    }
}
