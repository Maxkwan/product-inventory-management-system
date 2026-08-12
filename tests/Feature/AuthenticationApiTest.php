<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_and_receive_a_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'device_name' => 'postman',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'jane@example.com')
            ->assertJsonStructure(['user', 'token']);

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_a_user_can_login_with_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'jane@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->postJson('/api/login', [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ])->assertOk()->assertJsonStructure(['user', 'token']);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'jane@example.com']);

        $this->postJson('/api/login', [
            'email' => 'jane@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_inventory_routes_require_authentication(): void
    {
        $this->getJson('/api/products')->assertUnauthorized();
        $this->getJson('/api/categories')->assertUnauthorized();
        $this->getJson('/api/suppliers')->assertUnauthorized();
    }

    public function test_api_authentication_failure_returns_json_without_an_accept_header(): void
    {
        $this->get('/api/suppliers')
            ->assertUnauthorized()
            ->assertHeader('content-type', 'application/json')
            ->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public function test_an_authenticated_user_can_view_their_profile_and_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $headers = ['Authorization' => "Bearer {$token}"];

        $this->withHeaders($headers)->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);

        $this->withHeaders($headers)->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_an_authenticated_user_can_list_and_view_users(): void
    {
        $authenticatedUser = User::factory()->create();
        $otherUser = User::factory()->create(['name' => 'Other User']);
        $token = $authenticatedUser->createToken('test')->plainTextToken;
        $headers = ['Authorization' => "Bearer {$token}"];

        $this->withHeaders($headers)->getJson('/api/users')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissingPath('data.0.password');

        $this->withHeaders($headers)->getJson("/api/users/{$otherUser->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $otherUser->id)
            ->assertJsonPath('data.name', 'Other User')
            ->assertJsonMissingPath('data.password');
    }

    public function test_user_listing_requires_authentication(): void
    {
        $this->get('/api/users')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.']);
    }
}
