<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\UserSessionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class ProfileSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_lists_only_the_current_users_database_sessions(): void
    {
        $user = User::factory()->create(['remember_token' => 'private-remember-token']);
        $other = User::factory()->create();
        $this->actingAs($user);
        $currentId = Session::getId();

        $this->createSession($currentId, $user, 'Chrome on Windows', '10.0.0.1', 'private-current-payload');
        $this->createSession('own-other', $user, 'Firefox on Linux', '10.0.0.2', 'private-other-payload');
        $this->createSession('foreign', $other, 'Safari on macOS', '10.0.0.3', 'foreign-private-payload');

        $this->get('/profile')
            ->assertOk()
            ->assertSee('Chrome on Windows')
            ->assertSee('Firefox on Linux')
            ->assertDontSee('Safari on macOS')
            ->assertDontSee('private-current-payload')
            ->assertDontSee('private-other-payload')
            ->assertDontSee('private-remember-token');
    }

    public function test_current_session_is_identified_without_exposing_payload(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->createSession('current-session', $user, 'Edge on Windows', '127.0.0.1', '<script>secret</script>');
        $this->createSession('own-other', $user);
        $this->createSession('foreign', $other);

        $manager = app(UserSessionManager::class);
        $sessions = $manager->for($user, 'current-session');
        $current = $sessions->firstWhere('id', 'current-session');

        $this->assertTrue($current->is_current);
        $this->assertSame('Edge on Windows', $current->device);
        $this->assertObjectNotHasProperty('payload', $current);

        $manager->revokeOthers($user, 'current-session');

        $this->assertDatabaseHas('sessions', ['id' => 'current-session', 'user_id' => $user->id]);
        $this->assertDatabaseMissing('sessions', ['id' => 'own-other']);
        $this->assertDatabaseHas('sessions', ['id' => 'foreign', 'user_id' => $other->id]);
    }

    public function test_correct_password_revokes_only_other_sessions(): void
    {
        $user = User::factory()->create(['password' => 'CurrentPass123!']);
        $other = User::factory()->create();
        $this->actingAs($user);
        $currentId = Session::getId();
        $this->createSession($currentId, $user);
        $this->createSession('own-other', $user);
        $this->createSession('foreign', $other);

        $this->delete('/profile/sessions', ['current_password' => 'CurrentPass123!'])
            ->assertRedirect('/profile')
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('sessions', ['id' => 'own-other']);
        $this->assertDatabaseHas('sessions', ['id' => 'foreign', 'user_id' => $other->id]);
    }

    public function test_wrong_password_does_not_revoke_sessions(): void
    {
        $user = User::factory()->create(['password' => 'CurrentPass123!']);
        $this->actingAs($user);
        $this->createSession(Session::getId(), $user);
        $this->createSession('own-other', $user);

        $this->delete('/profile/sessions', ['current_password' => 'wrong-password'])
            ->assertSessionHasErrors('current_password');

        $this->assertDatabaseHas('sessions', ['id' => 'own-other', 'user_id' => $user->id]);
    }

    public function test_another_users_sessions_cannot_be_revoked(): void
    {
        $user = User::factory()->create(['password' => 'CurrentPass123!']);
        $other = User::factory()->create();
        $this->actingAs($user);
        $this->createSession(Session::getId(), $user);
        $this->createSession('own-other', $user);
        $this->createSession('foreign', $other);

        $this->delete('/profile/sessions', ['current_password' => 'CurrentPass123!'])
            ->assertRedirect('/profile');

        $this->assertDatabaseMissing('sessions', ['id' => 'own-other']);
        $this->assertDatabaseHas('sessions', ['id' => 'foreign', 'user_id' => $other->id]);
    }

    public function test_password_change_requires_current_password_and_confirmation(): void
    {
        $user = User::factory()->create(['password' => 'CurrentPass123!']);

        $this->actingAs($user)->patch('/profile/password', [
            'current_password' => 'wrong-password',
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'different-password',
        ])->assertSessionHasErrors(['current_password', 'password']);

        $this->assertTrue(Hash::check('CurrentPass123!', $user->fresh()->password));
    }

    public function test_password_change_rotates_current_session_and_revokes_other_sessions(): void
    {
        $user = User::factory()->create([
            'password' => 'CurrentPass123!',
            'remember_token' => 'old-token',
        ]);
        $other = User::factory()->create();
        $this->actingAs($user);
        $currentId = Session::getId();
        $this->createSession($currentId, $user);
        $this->createSession('own-other', $user);
        $this->createSession('foreign', $other);

        $this->patch('/profile/password', [
            'current_password' => 'CurrentPass123!',
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ])->assertRedirect('/profile')->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('NewSecurePass123!', $user->password));
        $this->assertNotSame('old-token', $user->remember_token);
        $this->assertNotSame($currentId, Session::getId());
        $this->assertDatabaseMissing('sessions', ['id' => 'own-other']);
        $this->assertDatabaseHas('sessions', ['id' => 'foreign', 'user_id' => $other->id]);
        $this->assertAuthenticatedAs($user);
    }

    private function createSession(
        string $id,
        User $user,
        string $userAgent = 'Chrome on Windows',
        string $ipAddress = '127.0.0.1',
        string $payload = 'private-payload',
    ): void {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'payload' => $payload,
            'last_activity' => now()->timestamp,
        ]);
    }
}
