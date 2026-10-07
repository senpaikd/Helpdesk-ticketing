<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'active', 'role:admin'])
            ->get('/testing/admin-only', fn () => 'admin area');
        Route::middleware(['web', 'auth', 'active', 'role:admin,staff'])
            ->get('/testing/staff-area', fn () => 'staff area');
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/testing/admin-only')->assertOk()->assertSee('admin area');
    }

    public function test_staff_can_access_staff_routes_but_not_admin_routes(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get('/testing/staff-area')->assertOk();
        $this->actingAs($staff)->get('/testing/admin-only')->assertForbidden();
    }

    public function test_normal_user_cannot_access_staff_or_admin_routes(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);

        $this->actingAs($user)->get('/testing/staff-area')->assertForbidden();
        $this->actingAs($user)->get('/testing/admin-only')->assertForbidden();
    }
}
