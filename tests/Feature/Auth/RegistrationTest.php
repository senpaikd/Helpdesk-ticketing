<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_is_available(): void
    {
        $this->get('/register')->assertOk()->assertSee('Create your account');
    }

    public function test_registration_always_creates_an_active_normal_user(): void
    {
        $response = $this->post('/register', [
            'name' => 'Alex Rivera',
            'email' => 'alex@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'role' => UserRole::Admin->value,
            'is_active' => false,
        ]);

        $user = User::where('email', 'alex@example.com')->firstOrFail();

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->assertSame(UserRole::User, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('SecurePass123!', $user->password));
    }
}
