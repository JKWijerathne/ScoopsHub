<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $manager;
    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->owner()->create([
            'email' => 'owner@scoopshub.test',
        ]);

        $this->manager = User::factory()->manager()->create([
            'email' => 'manager@scoopshub.test',
        ]);

        $this->cashier = User::factory()->cashier()->create([
            'email' => 'cashier@scoopshub.test',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Authentication & General Tests
    |--------------------------------------------------------------------------
    */

    public function test_unauthenticated_user_cannot_list_customers(): void
    {
        $response = $this->getJson('/api/customers');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_unauthenticated_user_request_without_accept_json_returns_json_401(): void
    {
        $response = $this->get('/api/customers');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_unauthenticated_user_cannot_create_customer(): void
    {
        $response = $this->postJson('/api/customers', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_unauthenticated_user_cannot_view_specific_customer(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->getJson("/api/customers/{$customer->id}");

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_unauthenticated_user_cannot_update_customer(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->putJson("/api/customers/{$customer->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_unauthenticated_user_cannot_delete_customer(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->deleteJson("/api/customers/{$customer->id}");

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Read (List & Show) Tests
    |--------------------------------------------------------------------------
    */

    public function test_authenticated_owner_manager_and_cashier_can_list_customers(): void
    {
        Customer::factory()->count(3)->create();

        foreach ([$this->owner, $this->manager, $this->cashier] as $user) {
            Sanctum::actingAs($user);

            $response = $this->getJson('/api/customers');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['id', 'name', 'email', 'phone', 'created_at', 'updated_at'],
                    ],
                ])
                ->assertJsonCount(3, 'data');
        }
    }

    public function test_authenticated_owner_manager_and_cashier_can_view_specific_customer(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Kamal Silva',
            'email' => 'kamal@example.com',
            'phone' => '0771234567',
        ]);

        foreach ([$this->owner, $this->manager, $this->cashier] as $user) {
            Sanctum::actingAs($user);

            $response = $this->getJson("/api/customers/{$customer->id}");

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $customer->id,
                        'name' => 'Kamal Silva',
                        'email' => 'kamal@example.com',
                        'phone' => '0771234567',
                    ],
                ]);
        }
    }

    public function test_unknown_customer_id_returns_404(): void
    {
        Sanctum::actingAs($this->cashier);

        $response = $this->getJson('/api/customers/99999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Customer not found',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create (Store) Permissions & Validation Tests
    |--------------------------------------------------------------------------
    */

    public function test_owner_manager_and_cashier_can_create_customers(): void
    {
        foreach ([$this->owner, $this->manager, $this->cashier] as $index => $user) {
            Sanctum::actingAs($user);

            $email = "customer_{$user->role}_{$index}@example.com";
            $name = "Customer Created by {$user->role}";

            $response = $this->postJson('/api/customers', [
                'name' => $name,
                'email' => $email,
                'phone' => "07700000{$index}",
            ]);

            $response->assertStatus(201)
                ->assertJson([
                    'message' => 'Customer created successfully',
                    'data' => [
                        'name' => $name,
                        'email' => $email,
                        'phone' => "07700000{$index}",
                    ],
                ]);

            $this->assertDatabaseHas('customers', [
                'name' => $name,
                'email' => $email,
            ]);
        }
    }

    public function test_customer_creation_requires_name(): void
    {
        Sanctum::actingAs($this->cashier);

        $response = $this->postJson('/api/customers', [
            'email' => 'noname@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_customer_creation_with_null_email_and_phone_succeeds(): void
    {
        Sanctum::actingAs($this->cashier);

        $response = $this->postJson('/api/customers', [
            'name' => 'Walk-in Customer 1',
            'email' => null,
            'phone' => null,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Customer created successfully',
                'data' => [
                    'name' => 'Walk-in Customer 1',
                    'email' => null,
                    'phone' => null,
                ],
            ]);

        $this->assertDatabaseHas('customers', [
            'name' => 'Walk-in Customer 1',
            'email' => null,
        ]);

        // A second customer with null email must also be allowed
        $response2 = $this->postJson('/api/customers', [
            'name' => 'Walk-in Customer 2',
            'email' => null,
            'phone' => null,
        ]);

        $response2->assertStatus(201);
    }

    public function test_invalid_email_format_is_rejected(): void
    {
        Sanctum::actingAs($this->manager);

        $response = $this->postJson('/api/customers', [
            'name' => 'Bad Email Customer',
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        Customer::factory()->create([
            'email' => 'duplicate@example.com',
        ]);

        Sanctum::actingAs($this->cashier);

        $response = $this->postJson('/api/customers', [
            'name' => 'Second Person',
            'email' => 'duplicate@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /*
    |--------------------------------------------------------------------------
    | Update (PUT & PATCH) Permissions & Behavior Tests
    |--------------------------------------------------------------------------
    */

    public function test_owner_manager_and_cashier_can_update_customer_with_put(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Initial Name',
            'email' => 'initial@example.com',
            'phone' => '0711111111',
        ]);

        foreach ([$this->owner, $this->manager, $this->cashier] as $index => $user) {
            Sanctum::actingAs($user);

            $newName = "Updated by {$user->role}";
            $newEmail = "updated_by_{$user->role}@example.com";
            $newPhone = "07799999{$index}";

            $response = $this->putJson("/api/customers/{$customer->id}", [
                'name' => $newName,
                'email' => $newEmail,
                'phone' => $newPhone,
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Customer updated successfully',
                    'data' => [
                        'id' => $customer->id,
                        'name' => $newName,
                        'email' => $newEmail,
                        'phone' => $newPhone,
                    ],
                ]);

            $this->assertDatabaseHas('customers', [
                'id' => $customer->id,
                'name' => $newName,
                'email' => $newEmail,
                'phone' => $newPhone,
            ]);
        }
    }

    public function test_put_requires_name_field(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($this->cashier);

        $response = $this->putJson("/api/customers/{$customer->id}", [
            'email' => 'emailonly@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_updating_customer_to_their_own_existing_email_succeeds(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Existing Customer',
            'email' => 'same@example.com',
            'phone' => '0712345678',
        ]);

        Sanctum::actingAs($this->cashier);

        $response = $this->putJson("/api/customers/{$customer->id}", [
            'name' => 'Existing Customer Updated Name',
            'email' => 'same@example.com',
            'phone' => '0712345678',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Customer updated successfully',
                'data' => [
                    'name' => 'Existing Customer Updated Name',
                    'email' => 'same@example.com',
                ],
            ]);
    }

    public function test_updating_customer_email_to_another_customers_email_is_rejected(): void
    {
        $customerA = Customer::factory()->create(['email' => 'first@example.com']);
        $customerB = Customer::factory()->create(['email' => 'second@example.com']);

        Sanctum::actingAs($this->cashier);

        $response = $this->putJson("/api/customers/{$customerB->id}", [
            'name' => 'Customer B Renamed',
            'email' => 'first@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_patch_supports_partial_updates(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Original Customer',
            'email' => 'original@example.com',
            'phone' => '0771112233',
        ]);

        Sanctum::actingAs($this->cashier);

        // Update ONLY phone
        $response = $this->patchJson("/api/customers/{$customer->id}", [
            'phone' => '0779998877',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Customer updated successfully',
                'data' => [
                    'id' => $customer->id,
                    'name' => 'Original Customer',
                    'email' => 'original@example.com',
                    'phone' => '0779998877',
                ],
            ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Original Customer',
            'email' => 'original@example.com',
            'phone' => '0779998877',
        ]);
    }

    public function test_updating_unknown_customer_id_returns_404(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->putJson('/api/customers/99999', [
            'name' => 'Ghost Customer',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Customer not found',
            ]);
    }

    public function test_client_supplied_id_and_timestamps_are_not_writable_on_customers(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Safe Customer',
        ]);

        Sanctum::actingAs($this->cashier);

        $response = $this->putJson("/api/customers/{$customer->id}", [
            'id' => 88888,
            'name' => 'Safe Customer Renamed',
            'created_at' => '2001-01-01 00:00:00',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('customers', ['id' => 88888]);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Safe Customer Renamed']);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete (Destroy) Permissions Tests
    |--------------------------------------------------------------------------
    */

    public function test_only_owner_can_delete_a_customer(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($this->owner);

        $response = $this->deleteJson("/api/customers/{$customer->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Customer deleted successfully',
            ]);

        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id,
        ]);
    }

    public function test_manager_cannot_delete_a_customer_and_receives_403(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($this->manager);

        $response = $this->deleteJson("/api/customers/{$customer->id}");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden. You do not have permission to access this resource.',
            ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
        ]);
    }

    public function test_cashier_cannot_delete_a_customer_and_receives_403(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($this->cashier);

        $response = $this->deleteJson("/api/customers/{$customer->id}");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden. You do not have permission to access this resource.',
            ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
        ]);
    }

    public function test_deleting_unknown_customer_id_returns_404(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->deleteJson('/api/customers/99999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Customer not found',
            ]);
    }
}

