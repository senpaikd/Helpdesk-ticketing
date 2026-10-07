<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_is_available(): void
    {
        $this->get('/login')->assertOk()->assertSee('Welcome back');
    }

    public function test_authenticated_users_are_redirected_from_login_by_role(): void
    {
        $customer = User::factory()->create();
        $staff = User::factory()->staff()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($customer)->get('/login')->assertRedirect('/');
        $this->actingAs($staff)->get('/login')->assertRedirect('/dashboard');
        $this->actingAs($admin)->get('/login')->assertRedirect('/dashboard');
    }

    public function test_active_user_can_authenticate_and_log_out(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')
            ->assertRedirect('/login')
            ->assertSessionHas('status', 'You have been signed out safely.');
        $this->assertGuest();
    }

    public function test_staff_and_admin_are_sent_to_the_workspace_after_login(): void
    {
        foreach ([User::factory()->staff()->create(), User::factory()->admin()->create()] as $operator) {
            $this->post('/login', ['email' => $operator->email, 'password' => 'password'])
                ->assertRedirect('/dashboard');

            $this->post('/logout');
        }
    }

    public function test_safe_local_intended_ticket_url_is_honored_for_a_customer(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->withSession(['url.intended' => '/tickets/TKT-2026-00006'])
            ->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/tickets/TKT-2026-00006');
    }

    public function test_unsafe_or_forbidden_intended_urls_fall_back_to_the_role_destination(): void
    {
        foreach (['https://evil.example/steal', '//evil.example/steal', '/tickets'] as $intended) {
            $user = User::factory()->create(['password' => 'password']);

            $this->withSession(['url.intended' => $intended])
                ->post('/login', ['email' => $user->email, 'password' => 'password'])
                ->assertRedirect('/');

            $this->post('/logout');
        }
    }

    public function test_successful_login_rotates_the_session_identifier(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        Session::start();
        $before = Session::getId();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertNotSame($before, Session::getId());
    }

    public function test_remembered_login_uses_a_thirty_day_cookie(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
            'remember_token' => null,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => true,
        ]);

        $cookie = $response->getCookie(Auth::guard('web')->getRecallerName());

        $this->assertNotNull($cookie);
        $this->assertEqualsWithDelta(now()->addDays(30)->timestamp, $cookie->getExpiresTime(), 5);
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_logout_invalidates_session_data_and_rotates_the_identifier(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        Session::put('sentinel', 'private');
        $before = Session::getId();

        $this->post('/logout')
            ->assertRedirect('/login')
            ->assertSessionMissing('sentinel');

        $this->assertNotSame($before, Session::getId());
        $this->assertGuest();
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_start_or_keep_a_session(): void
    {
        $user = User::factory()->inactive()->create(['password' => 'password']);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => true,
        ])
            ->assertSessionHasErrors('email');
        $response->assertCookieMissing(Auth::guard('web')->getRecallerName());
        $this->assertGuest();

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }
}
