<?php

namespace Tests\Http\Controllers\Api;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        // Create a Super Admin User
        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@test.com',
            'role' => UserType::SuperAdmin,
            'phone' => '9876543210',
            'address' => 'Super Admin Address',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_store_company_successfully_as_super_admin_with_jwt()
    {
        // Authenticate as Super Admin and retrieve the token
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $this->superAdmin->email,
            'password' => 'password', // This should match the password set in the factory
        ]);

        $loginResponse->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['data' => ['access_token']]);

        $accessToken = $loginResponse->json('data.access_token');

        // Company data to test
        $data = [
            'name' => 'Test Company',
            'email' => 'test@company.com',
            'address' => '123 Test Address',
            'phone' => '1234567890',
        ];

        // Make the request with the Bearer token in the Authorization header
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->postJson('/api/admin/register/company', $data);

        // Assert the response
        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'Company' => [
                        'id',
                        'name',
                        'email',
                        'address',
                        'phone',
                        'updated_at',
                        'created_at',
                    ],
                    'Manager' => [
                        'id',
                        'name',
                        'email',
                        'company_id',
                        'role',
                        'phone',
                        'address',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        // Assert database contains the entered company and manager
        $this->assertDatabaseHas('companies', [
            'name' => 'Test Company',
            'email' => 'test@company.com',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'manager of Test Company',
            'email' => 'manager@test-company.com',
            'role' => UserType::Manager->value,
        ]);
    }

    public function test_store_company_fails_without_jwt_token()
    {
        $data = [
            'name' => 'New Company',
            'email' => 'new@company.com',
            'address' => '123 Test Address',
            'phone' => '1234567890',
        ];

        // Make the request without providing a JWT token
        $response = $this->postJson('/api/admin/register/company', $data);

        // Assert the response
        $response->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJsonFragment([
                'success' => false,
                'message' => 'Unauthenticated.'
            ]);
    }

    public function test_store_company_fails_for_duplicate_email_or_phone()
    {
        // Insert an existing company
        $existingCompany = \App\Models\Company::create([
            'name' => 'Existing Company',
            'email' => 'existing@company.com',
            'address' => '456 Test Address',
            'phone' => '9876543210',
        ]);

        // Authenticate and retrieve the token
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $this->superAdmin->email,
            'password' => 'password',
        ]);

        $accessToken = $loginResponse->json('data.access_token');

        // Try to add a new company with the duplicate email and phone
        $data = [
            'name' => 'Duplicate Company',
            'email' => 'existing@company.com', // Duplicate email
            'address' => '123 Test Address',
            'phone' => '9876543210', // Duplicate phone
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->postJson('/api/admin/register/company', $data);

        // Assert validation failure response
        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonFragment([
                'success' => false,
                'message' => 'Validation Error.',
            ])
            ->assertJsonStructure(['data' => ['email', 'phone']]);
    }

    public function test_store_company_fails_for_non_super_admin_user()
    {
        // Create a regular admin user
        $admin = User::factory()->create([
            'name' => 'Regular Admin',
            'email' => 'admin@test.com',
            'phone' => '1234567890',
            'role' => UserType::Manager->value,
            'password' => bcrypt('password'),
        ]);

        // Authenticate and retrieve the token
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $accessToken = $loginResponse->json('data.access_token');

        // Test data
        $data = [
            'name' => 'Unauthorized Company',
            'email' => 'unauthorized@company.com',
            'address' => '123 Test Address',
            'phone' => '1234567890',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->postJson('/api/admin/register/company', $data);

        // Assert forbidden response
        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonFragment([
                'success' => false,
                'message' => 'Unauthorized Action'
            ]);
    }

    public function test_store_company_fails_for_missing_required_fields()
    {
        // Authenticate and retrieve the token
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $this->superAdmin->email,
            'password' => 'password',
        ]);

        $accessToken = $loginResponse->json('data.access_token');

        // Missing data
        $data = [
            'name' => 'Valid Name',
            // Missing 'email', 'phone', and 'address'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->postJson('/api/admin/register/company', $data);

        // Assert validation error response
        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonFragment([
                'success' => false,
                'message' => 'Validation Error.',
            ])
            ->assertJsonStructure(['data' => ['email', 'phone', 'address']]);
    }

    public function test_store_company_fails_for_invalid_input_format()
    {
        // Authenticate and retrieve the token
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $this->superAdmin->email,
            'password' => 'password',
        ]);

        $accessToken = $loginResponse->json('data.access_token');

        // Invalid data
        $data = [
            'name' => 'Invalid Input Company',
            'email' => 'invalid-email-format',
            'address' => 'Short',
            'phone' => 'Not-A-Number',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->postJson('/api/admin/register/company', $data);

        // Assert validation error response
        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonFragment([
                'success' => false,
                'message' => 'Validation Error.',
            ])
            ->assertJsonStructure(['data' => ['email', 'phone', 'phone']]);
    }
}
