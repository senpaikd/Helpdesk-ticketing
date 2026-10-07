<?php

namespace Tests\Feature\Views;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_ticket_pages_use_the_nature_portal_navigation(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->for($user, 'creator')->create();

        $this->actingAs($user)->withSession(['success' => '<script>alert("x")</script>'])
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('HelpDesk')
            ->assertSee('My tickets')
            ->assertSee('Create ticket')
            ->assertDontSee('User management')
            ->assertSee('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert("x")</script>', false);
    }

    public function test_staff_and_admin_pages_use_the_responsive_workspace_shell(): void
    {
        $staff = User::factory()->staff()->create(['name' => 'Support Agent']);
        $admin = User::factory()->admin()->create(['name' => 'HelpDesk Admin']);

        $this->actingAs($staff)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-workspace-sidebar', false)
            ->assertSee('data-workspace-menu-toggle', false)
            ->assertSee('HelpDesk')
            ->assertSee('Dashboard')
            ->assertSee('Tickets')
            ->assertSee('Profile')
            ->assertSee('Support Agent')
            ->assertSee('Staff')
            ->assertSee('aria-current="page"', false)
            ->assertSee('ph ph-squares-four', false)
            ->assertSee('action="'.route('logout').'"', false)
            ->assertDontSee('Deskly')
            ->assertDontSee('User management')
            ->assertDontSee('Categories')
            ->assertDontSee('🎧');

        $this->actingAs($admin)->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('HelpDesk Admin')
            ->assertSee('Administrator')
            ->assertSee('aria-current="page"', false)
            ->assertDontSee('/admin/users', false)
            ->assertDontSee('/admin/categories', false);
    }
}
