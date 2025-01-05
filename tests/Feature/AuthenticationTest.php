<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_user_can_login_and_receive_jwt_token_with_mocked_data()
    {
        // Mocking the User model
        $mockUser = $this->createMock(User::class);

        // Define the mock attributes
        $mockUser->method('getAttribute')->willReturnMap([
            ['id', 1],
            ['email', 'test@example.com'],
            ['password', bcrypt('password123')], // Simulate mocked hashed password
            ['phone', '123456789012'],
            ['address', 'test address'],
            ['role', UserType::SuperAdmin],
        ]);

        // Ensure mocked user is saved or accessed as needed (optional)
        $this->assertInstanceOf(User::class, $mockUser);

        // Mocking login
        $this->partialMock(User::class, function ($mockUser) {
            $mockUser->shouldReceive('where')->andReturnSelf(); // Simulating "where" call
            $mockUser->shouldReceive('first')->andReturnSelf(); // Simulating fetching the object with "first"
        });

        // Attempt login
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Check if login is successful and token is returned in 'data.access_token'
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'access_token', // Verifying token structure
            ],
        ]);

        // Optionally assert token is not empty
        $this->assertNotEmpty($response->json('data.access_token'));
    }

    public function test_access_protected_route_without_token_fails()
    {
        $response = $this->getJson('/api/manager/profile');

        $response->assertStatus(401); // Unauthorized
    }
}
