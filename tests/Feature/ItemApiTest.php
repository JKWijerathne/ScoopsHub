<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ItemApiTest extends TestCase
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

    public function test_unauthenticated_user_cannot_list_items(): void
    {
        $response = $this->getJson('/api/items');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_unauthenticated_user_request_without_accept_json_returns_json_401(): void
    {
        $response = $this->get('/api/items');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_unauthenticated_user_cannot_create_items(): void
    {
        $response = $this->postJson('/api/items', [
            'name' => 'Vanilla Ice Cream',
            'price' => '350.00',
            'stock_quantity' => 15,
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_unauthenticated_user_cannot_view_specific_item(): void
    {
        $item = Item::factory()->create();

        $response = $this->getJson("/api/items/{$item->id}");

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_unauthenticated_user_cannot_update_item(): void
    {
        $item = Item::factory()->create();

        $response = $this->putJson("/api/items/{$item->id}", [
            'name' => 'Updated Name',
            'price' => '400.00',
            'stock_quantity' => 20,
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_unauthenticated_user_cannot_delete_item(): void
    {
        $item = Item::factory()->create();

        $response = $this->deleteJson("/api/items/{$item->id}");

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

    public function test_authenticated_owner_manager_and_cashier_can_list_items(): void
    {
        Item::factory()->count(3)->create();

        foreach ([$this->owner, $this->manager, $this->cashier] as $user) {
            Sanctum::actingAs($user);

            $response = $this->getJson('/api/items');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['id', 'name', 'description', 'price', 'stock_quantity', 'created_at', 'updated_at'],
                    ],
                ])
                ->assertJsonCount(3, 'data');
        }
    }

    public function test_authenticated_owner_manager_and_cashier_can_view_specific_item(): void
    {
        $item = Item::factory()->create([
            'name' => 'Mint Chocolate Chip',
            'price' => '420.00',
            'stock_quantity' => 25,
        ]);

        foreach ([$this->owner, $this->manager, $this->cashier] as $user) {
            Sanctum::actingAs($user);

            $response = $this->getJson("/api/items/{$item->id}");

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $item->id,
                        'name' => 'Mint Chocolate Chip',
                        'price' => '420.00',
                        'stock_quantity' => 25,
                    ],
                ]);
        }
    }

    public function test_unknown_item_id_returns_404_when_viewing(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/items/99999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Item not found',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create (Store) Permissions & Validation Tests
    |--------------------------------------------------------------------------
    */

    public function test_owner_can_create_an_item(): void
    {
        Sanctum::actingAs($this->owner);

        $payload = [
            'name' => 'Strawberry Bliss',
            'description' => 'Fresh strawberry churned daily',
            'price' => '450.00',
            'stock_quantity' => 30,
        ];

        $response = $this->postJson('/api/items', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Item created successfully',
                'data' => [
                    'name' => 'Strawberry Bliss',
                    'description' => 'Fresh strawberry churned daily',
                    'price' => '450.00',
                    'stock_quantity' => 30,
                ],
            ]);

        $this->assertDatabaseHas('items', [
            'name' => 'Strawberry Bliss',
            'price' => 450.00,
            'stock_quantity' => 30,
        ]);
    }

    public function test_manager_can_create_an_item(): void
    {
        Sanctum::actingAs($this->manager);

        $payload = [
            'name' => 'Mango Sorbet',
            'description' => 'Real Alphonso mango',
            'price' => '490.50',
            'stock_quantity' => 12,
        ];

        $response = $this->postJson('/api/items', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Item created successfully',
                'data' => [
                    'name' => 'Mango Sorbet',
                    'price' => '490.50',
                    'stock_quantity' => 12,
                ],
            ]);

        $this->assertDatabaseHas('items', [
            'name' => 'Mango Sorbet',
            'price' => 490.50,
            'stock_quantity' => 12,
        ]);
    }

    public function test_cashier_cannot_create_an_item_and_receives_403(): void
    {
        Sanctum::actingAs($this->cashier);

        $payload = [
            'name' => 'Forbidden Ice Cream',
            'price' => '200.00',
            'stock_quantity' => 5,
        ];

        $response = $this->postJson('/api/items', $payload);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden. You do not have permission to access this resource.',
            ]);

        $this->assertDatabaseMissing('items', [
            'name' => 'Forbidden Ice Cream',
        ]);
    }

    public function test_item_creation_requires_mandatory_fields(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/items', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price', 'stock_quantity']);
    }

    public function test_invalid_negative_price_is_rejected(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/items', [
            'name' => 'Negative Price Cone',
            'price' => -50.00,
            'stock_quantity' => 10,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    public function test_price_with_excess_decimal_precision_is_rejected(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/items', [
            'name' => 'Three Decimals Cone',
            'price' => '10.555',
            'stock_quantity' => 10,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    public function test_negative_stock_quantity_is_rejected(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/items', [
            'name' => 'Negative Stock Cone',
            'price' => '300.00',
            'stock_quantity' => -5,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['stock_quantity']);
    }

    public function test_non_integer_stock_quantity_is_rejected(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/items', [
            'name' => 'Float Stock Cone',
            'price' => '300.00',
            'stock_quantity' => 5.5,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['stock_quantity']);
    }

    /*
    |--------------------------------------------------------------------------
    | Update (PUT & PATCH) Permissions & Behavior Tests
    |--------------------------------------------------------------------------
    */

    public function test_owner_manager_and_cashier_can_update_an_item_with_put(): void
    {
        $item = Item::factory()->create([
            'name' => 'Old Ice Cream',
            'price' => '100.00',
            'stock_quantity' => 10,
        ]);

        foreach ([$this->owner, $this->manager, $this->cashier] as $index => $user) {
            Sanctum::actingAs($user);

            $newName = "Updated by Role {$user->role} {$index}";
            $newPrice = '250.00';
            $newStock = 50 + $index;

            $response = $this->putJson("/api/items/{$item->id}", [
                'name' => $newName,
                'description' => 'Updated description',
                'price' => $newPrice,
                'stock_quantity' => $newStock,
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Item updated successfully',
                    'data' => [
                        'id' => $item->id,
                        'name' => $newName,
                        'description' => 'Updated description',
                        'price' => $newPrice,
                        'stock_quantity' => $newStock,
                    ],
                ]);

            $this->assertDatabaseHas('items', [
                'id' => $item->id,
                'name' => $newName,
                'price' => 250.00,
                'stock_quantity' => $newStock,
            ]);
        }
    }

    public function test_put_requires_all_mandatory_fields(): void
    {
        $item = Item::factory()->create();
        Sanctum::actingAs($this->cashier);

        $response = $this->putJson("/api/items/{$item->id}", [
            'name' => 'Only Name Provided',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price', 'stock_quantity']);
    }

    public function test_patch_correctly_updates_only_supplied_fields(): void
    {
        $item = Item::factory()->create([
            'name' => 'Pistachio Supreme',
            'description' => 'Original Pistachio',
            'price' => '500.00',
            'stock_quantity' => 40,
        ]);

        Sanctum::actingAs($this->cashier);

        // Update ONLY stock quantity
        $response = $this->patchJson("/api/items/{$item->id}", [
            'stock_quantity' => 99,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Item updated successfully',
                'data' => [
                    'id' => $item->id,
                    'name' => 'Pistachio Supreme',
                    'description' => 'Original Pistachio',
                    'price' => '500.00',
                    'stock_quantity' => 99,
                ],
            ]);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'name' => 'Pistachio Supreme',
            'stock_quantity' => 99,
            'price' => 500.00,
        ]);
    }

    public function test_patch_rejects_invalid_partial_data(): void
    {
        $item = Item::factory()->create();
        Sanctum::actingAs($this->cashier);

        $response = $this->patchJson("/api/items/{$item->id}", [
            'price' => -20,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    public function test_updating_unknown_item_id_returns_404(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->putJson('/api/items/99999', [
            'name' => 'Non Existent',
            'price' => '100.00',
            'stock_quantity' => 10,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Item not found',
            ]);
    }

    public function test_client_provided_id_and_timestamps_are_not_writable(): void
    {
        $item = Item::factory()->create([
            'name' => 'Protected Item',
            'price' => '200.00',
            'stock_quantity' => 10,
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->putJson("/api/items/{$item->id}", [
            'id' => 77777,
            'name' => 'Protected Item Modified',
            'price' => '250.00',
            'stock_quantity' => 15,
            'created_at' => '1999-01-01 00:00:00',
        ]);

        $response->assertStatus(200);

        // Verify ID did NOT change to 77777
        $this->assertDatabaseMissing('items', ['id' => 77777]);
        $this->assertDatabaseHas('items', ['id' => $item->id, 'name' => 'Protected Item Modified']);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete (Destroy) Permissions Tests
    |--------------------------------------------------------------------------
    */

    public function test_only_owner_can_delete_an_item(): void
    {
        $item = Item::factory()->create();
        Sanctum::actingAs($this->owner);

        $response = $this->deleteJson("/api/items/{$item->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Item deleted successfully',
            ]);

        $this->assertDatabaseMissing('items', [
            'id' => $item->id,
        ]);
    }

    public function test_manager_cannot_delete_an_item_and_receives_403(): void
    {
        $item = Item::factory()->create();
        Sanctum::actingAs($this->manager);

        $response = $this->deleteJson("/api/items/{$item->id}");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden. You do not have permission to access this resource.',
            ]);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
        ]);
    }

    public function test_cashier_cannot_delete_an_item_and_receives_403(): void
    {
        $item = Item::factory()->create();
        Sanctum::actingAs($this->cashier);

        $response = $this->deleteJson("/api/items/{$item->id}");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden. You do not have permission to access this resource.',
            ]);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
        ]);
    }

    public function test_deleting_unknown_item_id_returns_404(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->deleteJson('/api/items/99999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Item not found',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Search Tests
    |--------------------------------------------------------------------------
    */

    public function test_search_returns_matching_items_case_insensitively(): void
    {
        Item::factory()->create(['name' => 'Dark Chocolate Truffle']);
        Item::factory()->create(['name' => 'White Chocolate Swirl']);
        Item::factory()->create(['name' => 'Classic Vanilla']);

        Sanctum::actingAs($this->cashier);

        $response = $this->getJson('/api/items?search=chocolate');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Dark Chocolate Truffle'));
        $this->assertTrue($names->contains('White Chocolate Swirl'));
        $this->assertFalse($names->contains('Classic Vanilla'));
    }

    public function test_search_does_not_return_unrelated_items(): void
    {
        Item::factory()->create(['name' => 'Blueberry Cheesecake']);
        Item::factory()->create(['name' => 'Salted Caramel']);

        Sanctum::actingAs($this->manager);

        $response = $this->getJson('/api/items?search=nonexistentflavor');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_empty_search_query_returns_all_items(): void
    {
        Item::factory()->count(4)->create();

        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/items?search=');

        $response->assertStatus(200)
            ->assertJsonCount(4, 'data');
    }
}
