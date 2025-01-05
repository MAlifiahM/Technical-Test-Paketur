<?php

namespace Tests\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    /**
     * Tests that the login method returns validation error when no data is provided.
     */
    public function testLoginValidationError()
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Validator Error.',
            ])
            ->assertJsonStructure([
                'data' => [
                    'email',
                    'password',
                ],
            ]);
    }

    /**
     * Tests that the login method returns an error for invalid credentials.
     */
    public function testLoginWithInvalidCredentials()
    {
        Auth::shouldReceive('guard')
            ->andReturnSelf()
            ->shouldReceive('attempt')
            ->with([
                'email' => 'wrong@test.com',
                'password' => 'invalidpass',
            ])
            ->andReturn(false);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'wrong@test.com',
            'password' => 'invalidpass',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Wrong email or password',
                'data' => [
                    'error' => 'Unauthorized',
                ],
            ]);
    }

    /**
     * Tests that the login method returns a valid response with correct credentials.
     */
    public function testLoginSuccessfully()
    {
        $mockToken = 'mocked-jwt-token';
        $mockUser = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        Auth::shouldReceive('guard')
            ->andReturnSelf()
            ->shouldReceive('attempt')
            ->with([
                'email' => 'john@example.com',
                'password' => 'validpass',
            ])
            ->andReturn($mockToken)
            ->shouldReceive('user')
            ->andReturn((object)$mockUser)
            ->shouldReceive('factory->getTTL')
            ->andReturn(60); // 60 minutes

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'validpass',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login successfully.',
            ])
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'token_type',
                    'user' => [
                        'id',
                        'name',
                        'email',
                    ],
                    'expires_in',
                ],
            ]);
    }
}
