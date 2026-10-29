<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_endpoint_rejects_requests_without_token(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_protected_endpoint_rejects_requests_without_accept_header_as_json(): void
    {
        // Calling without getJson / without Accept: application/json header
        $response = $this->get('/api/auth/me');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_protected_endpoint_accepts_valid_bearer_token(): void
    {
        $user = User::factory()->owner()->create([
            'email' => 'owner@scoopshub.test',
        ]);

        $token = $user->createToken('scoopshub-api')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'email' => 'owner@scoopshub.test',
                    'role' => 'owner',
                ],
            ]);
    }

    public function test_invalid_bearer_token_is_rejected_with_401(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token-string')
            ->getJson('/api/auth/me');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->owner()->create();
        $token = $user->createToken('scoopshub-api')->plainTextToken;

        $this->assertEquals(1, $user->tokens()->count());

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logged out successfully',
            ]);

        $this->assertEquals(0, $user->tokens()->count());
    }

    public function test_revoked_token_cannot_access_protected_endpoint_afterward(): void
    {
        $user = User::factory()->owner()->create();
        $token = $user->createToken('scoopshub-api')->plainTextToken;

        // Verify token works
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/me')
            ->assertStatus(200);

        // Logout
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/logout')
            ->assertStatus(200);

        $this->app['auth']->forgetGuards();

        // Attempt accessing endpoint with the now-revoked token
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/me')
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_logout_only_revokes_current_token_not_other_sessions(): void
    {
        $user = User::factory()->owner()->create();
        $token1 = $user->createToken('device-1')->plainTextToken;
        $token2 = $user->createToken('device-2')->plainTextToken;

        $this->assertEquals(2, $user->tokens()->count());

        // Logout using token1
        $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->postJson('/api/auth/logout')
            ->assertStatus(200);

        // User should still have 1 valid token
        $this->assertEquals(1, $user->tokens()->count());

        $this->app['auth']->forgetGuards();

        // Token 1 fails
        $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->getJson('/api/auth/me')
            ->assertStatus(401);

        $this->app['auth']->forgetGuards();

        // Token 2 still works
        $this->withHeader('Authorization', 'Bearer ' . $token2)
            ->getJson('/api/auth/me')
            ->assertStatus(200);
    }
}
