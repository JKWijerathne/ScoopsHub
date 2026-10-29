<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_owner_account_can_login_and_receives_token(): void
    {
        $user = User::factory()->owner()->create([
            'email' => 'owner@scoopshub.test',
            'password' => 'Owner123!',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'owner@scoopshub.test',
            'password' => 'Owner123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'token',
                'token_type',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                ],
            ])
            ->assertJson([
                'message' => 'Login successful',
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'email' => 'owner@scoopshub.test',
                    'role' => 'owner',
                ],
            ]);

        $this->assertNotEmpty($response->json('token'));
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'scoopshub-api',
        ]);
    }

    public function test_valid_manager_account_can_login_and_receives_token(): void
    {
        $user = User::factory()->manager()->create([
            'email' => 'manager@scoopshub.test',
            'password' => 'Manager123!',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'manager@scoopshub.test',
            'password' => 'Manager123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Login successful',
                'user' => [
                    'id' => $user->id,
                    'email' => 'manager@scoopshub.test',
                    'role' => 'manager',
                ],
            ]);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_valid_cashier_account_can_login_and_receives_token(): void
    {
        $user = User::factory()->cashier()->create([
            'email' => 'cashier@scoopshub.test',
            'password' => 'Cashier123!',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'cashier@scoopshub.test',
            'password' => 'Cashier123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Login successful',
                'user' => [
                    'id' => $user->id,
                    'email' => 'cashier@scoopshub.test',
                    'role' => 'cashier',
                ],
            ]);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_incorrect_password_is_rejected_with_401(): void
    {
        User::factory()->owner()->create([
            'email' => 'owner@scoopshub.test',
            'password' => 'Owner123!',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'owner@scoopshub.test',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials',
            ]);
    }

    public function test_unknown_email_is_rejected_with_401(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'unknown@scoopshub.test',
            'password' => 'SomePassword123!',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials',
            ]);
    }

    public function test_missing_email_returns_422(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'password' => 'Owner123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_missing_password_returns_422(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'owner@scoopshub.test',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_invalid_email_format_returns_422(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'not-an-email',
            'password' => 'Owner123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_response_never_contains_password_or_hash(): void
    {
        User::factory()->owner()->create([
            'email' => 'owner@scoopshub.test',
            'password' => 'Owner123!',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'owner@scoopshub.test',
            'password' => 'Owner123!',
        ]);

        $response->assertStatus(200);

        $json = $response->json();
        $this->assertArrayNotHasKey('password', $json['user']);
        $this->assertArrayNotHasKey('password_hash', $json['user']);
        $this->assertStringNotContainsString('Owner123!', $response->getContent());
        $this->assertStringNotContainsString('$2y$', $response->getContent());
    }

    public function test_failed_login_attempts_do_not_create_tokens(): void
    {
        User::factory()->create([
            'email' => 'owner@scoopshub.test',
            'password' => 'Owner123!',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'owner@scoopshub.test',
            'password' => 'WrongPassword',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@scoopshub.test',
            'password' => 'Password123!',
        ]);

        $this->assertEquals(0, PersonalAccessToken::count());
    }
}
