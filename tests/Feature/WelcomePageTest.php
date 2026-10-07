<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_is_a_private_helpdesk_portal(): void
    {
        $this->get('/')->assertRedirect('/login');

        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertSee('HelpDesk')
            ->assertSee('Toggle color theme')
            ->assertSee('data-menu-toggle', false)
            ->assertSee('data-mobile-menu', false)
            ->assertDontSee('Deskly');
    }
}
