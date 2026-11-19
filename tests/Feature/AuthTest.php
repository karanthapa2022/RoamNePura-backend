<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ─── ADMIN LOGIN ───────────────────────────────────────────────────────

    public function test_admin_can_login_with_valid_credentials(): void
    {
        User::factory()->create([
            'email'    => 'admin@roamnepura.com',
            'password' => bcrypt('secret123'),
            'is_admin' => true,
        ]);

        $response = $this->postJson('/api/admin/login', [
            'email'    => 'admin@roamnepura.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token']);
    }

    public function test_admin_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email'    => 'admin@roamnepura.com',
            'password' => bcrypt('correct_password'),
            'is_admin' => true,
        ]);

        $response = $this->postJson('/api/admin/login', [
            'email'    => 'admin@roamnepura.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401);
    }

    public function test_non_admin_user_cannot_login_as_admin(): void
    {
        User::factory()->create([
            'email'    => 'user@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);

        $response = $this->postJson('/api/admin/login', [
            'email'    => 'user@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(403);
    }

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/admin/login', []);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_requires_valid_email_format(): void
    {
        $response = $this->postJson('/api/admin/login', [
            'email'    => 'not-an-email',
            'password' => 'password',
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    // ─── ADMIN LOGOUT ──────────────────────────────────────────────────────

    public function test_admin_can_logout(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/admin/logout');
        $response->assertStatus(200);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    // ─── PASSWORD CHANGE ───────────────────────────────────────────────────

    public function test_admin_can_change_password(): void
    {
        $admin = User::factory()->create([
            'password' => bcrypt('old_password'),
            'is_admin' => true,
        ]);
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->patchJson('/api/admin/change-password', [
            'current_password'      => 'old_password',
            'password'              => 'new_secure_password',
            'password_confirmation' => 'new_secure_password',
        ]);

        $response->assertStatus(200);
    }

    public function test_password_change_fails_with_wrong_current_password(): void
    {
        $admin = User::factory()->create([
            'password' => bcrypt('real_password'),
            'is_admin' => true,
        ]);
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->patchJson('/api/admin/change-password', [
            'current_password'      => 'wrong_password',
            'password'              => 'new_password123',
            'password_confirmation' => 'new_password123',
        ]);

        $response->assertStatus(422);
    }

    // ─── SECURITY: Protected Routes ────────────────────────────────────────

    public function test_protected_routes_require_bearer_token(): void
    {
        $protectedRoutes = [
            ['GET', '/api/admin/bookings'],
            ['GET', '/api/admin/destinations'],
            ['GET', '/api/admin/tours'],
            ['GET', '/api/admin/inquiries'],
            ['GET', '/api/admin/notifications'],
        ];

        foreach ($protectedRoutes as [$method, $uri]) {
            $response = $this->json($method, $uri);
            $this->assertEquals(
                401,
                $response->status(),
                "Expected 401 for $method $uri but got {$response->status()}"
            );
        }
    }


    // ─── ADMIN INACTIVITY SECURITY ─────────────────────────────────────

    public function test_recently_active_admin_can_access_protected_routes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $token = $admin->createToken('test');

        $token->accessToken->forceFill([
            'last_activity_at' => now(),
        ])->save();

        $response = $this->withToken($token->plainTextToken)
            ->getJson('/api/admin/bookings');

        $response->assertSuccessful();
    }

    public function test_inactive_admin_token_is_rejected_and_deleted(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $token = $admin->createToken('test');

        $token->accessToken->forceFill([
            'last_activity_at' => now()->subMinutes(31),
        ])->save();

        $tokenId = $token->accessToken->id;

        $response = $this->withToken($token->plainTextToken)
            ->getJson('/api/admin/bookings');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Your session expired due to inactivity. Please log in again.',
            ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenId,
        ]);
    }

    public function test_token_without_activity_timestamp_uses_creation_time(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $token = $admin->createToken('test');

        $token->accessToken->forceFill([
            'last_activity_at' => null,
            'created_at' => now()->subMinutes(31),
        ])->save();

        $tokenId = $token->accessToken->id;

        $response = $this->withToken($token->plainTextToken)
            ->getJson('/api/admin/bookings');

        $response->assertStatus(401);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenId,
        ]);
    }

    public function test_admin_activity_endpoint_refreshes_timestamp(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $token = $admin->createToken('test');

        $token->accessToken->forceFill([
            'last_activity_at' => now()->subMinutes(5),
        ])->save();

        $response = $this->withToken($token->plainTextToken)
            ->postJson('/api/admin/activity');

        $response->assertOk()
            ->assertJson([
                'message' => 'Admin activity updated.',
            ]);

        $token->accessToken->refresh();

        $this->assertTrue(
    \Illuminate\Support\Carbon::parse(
        $token->accessToken->last_activity_at
    )->greaterThan(now()->subMinute())
);
    }
}
