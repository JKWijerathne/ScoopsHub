<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_access_all_role_endpoints(): void
    {
        $owner = User::factory()->owner()->create();
        $token = $owner->createToken('test-token')->plainTextToken;

        $headers = ['Authorization' => 'Bearer ' . $token];

        $this->withHeaders($headers)
            ->getJson('/api/probe/owner-only')
            ->assertStatus(200)
            ->assertJson(['message' => 'Owner access granted', 'role' => 'owner']);

        $this->withHeaders($headers)
            ->getJson('/api/probe/management')
            ->assertStatus(200)
            ->assertJson(['message' => 'Management access granted', 'role' => 'owner']);

        $this->withHeaders($headers)
            ->getJson('/api/probe/staff')
            ->assertStatus(200)
            ->assertJson(['message' => 'Staff access granted', 'role' => 'owner']);
    }

    public function test_manager_can_access_management_and_staff_but_forbidden_on_owner_only(): void
    {
        $manager = User::factory()->manager()->create();
        $token = $manager->createToken('test-token')->plainTextToken;

        $headers = ['Authorization' => 'Bearer ' . $token];

        // Forbidden on owner-only
        $this->withHeaders($headers)
            ->getJson('/api/probe/owner-only')
            ->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden. You do not have permission to access this resource.',
            ]);

        // Allowed on management
        $this->withHeaders($headers)
            ->getJson('/api/probe/management')
            ->assertStatus(200)
            ->assertJson(['message' => 'Management access granted', 'role' => 'manager']);

        // Allowed on staff
        $this->withHeaders($headers)
            ->getJson('/api/probe/staff')
            ->assertStatus(200)
            ->assertJson(['message' => 'Staff access granted', 'role' => 'manager']);
    }

    public function test_cashier_can_access_staff_but_forbidden_on_management_and_owner_only(): void
    {
        $cashier = User::factory()->cashier()->create();
        $token = $cashier->createToken('test-token')->plainTextToken;

        $headers = ['Authorization' => 'Bearer ' . $token];

        // Forbidden on owner-only
        $this->withHeaders($headers)
            ->getJson('/api/probe/owner-only')
            ->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden. You do not have permission to access this resource.',
            ]);

        // Forbidden on management
        $this->withHeaders($headers)
            ->getJson('/api/probe/management')
            ->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden. You do not have permission to access this resource.',
            ]);

        // Allowed on staff
        $this->withHeaders($headers)
            ->getJson('/api/probe/staff')
            ->assertStatus(200)
            ->assertJson(['message' => 'Staff access granted', 'role' => 'cashier']);
    }

    public function test_unauthenticated_request_to_role_endpoint_returns_401_not_403(): void
    {
        $this->getJson('/api/probe/owner-only')
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);

        $this->getJson('/api/probe/management')
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_user_with_invalid_or_missing_role_is_forbidden(): void
    {
        $userWithInvalidRole = User::factory()->create([
            'role' => 'guest_role',
        ]);
        $token = $userWithInvalidRole->createToken('test-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/probe/owner-only')
            ->assertStatus(403);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/probe/management')
            ->assertStatus(403);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/probe/staff')
            ->assertStatus(403);
    }

    public function test_user_model_role_helper_methods(): void
    {
        $owner = User::factory()->owner()->make();
        $this->assertTrue($owner->isOwner());
        $this->assertFalse($owner->isManager());
        $this->assertFalse($owner->isCashier());
        $this->assertTrue($owner->hasRole('owner'));
        $this->assertTrue($owner->hasRole(['owner', 'manager']));
        $this->assertFalse($owner->hasRole('cashier'));

        $manager = User::factory()->manager()->make();
        $this->assertFalse($manager->isOwner());
        $this->assertTrue($manager->isManager());
        $this->assertFalse($manager->isCashier());
        $this->assertTrue($manager->hasRole('manager'));
        $this->assertTrue($manager->hasRole('owner,manager'));
        $this->assertFalse($manager->hasRole('owner'));

        $cashier = User::factory()->cashier()->make();
        $this->assertFalse($cashier->isOwner());
        $this->assertFalse($cashier->isManager());
        $this->assertTrue($cashier->isCashier());
        $this->assertTrue($cashier->hasRole('cashier'));
        $this->assertFalse($cashier->hasRole(['owner', 'manager']));
    }
}
