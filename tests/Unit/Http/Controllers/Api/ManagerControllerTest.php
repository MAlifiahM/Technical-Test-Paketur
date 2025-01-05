<?php

namespace Tests\Unit\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Enums\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

class ManagerControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $manager;
    protected $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock company
        $this->company = Company::create([
            'name' => 'Test Company',
            'email' => 'company@test.com',
            'phone' => '1234567890',
            'address' => '123 Faker Street',
        ]);

        // Create a mock manager linked to the company
        $this->manager = User::create([
            'name' => 'Test Manager',
            'email' => 'manager@test.com',
            'phone' => '1234567890',
            'password' => bcrypt('password'),
            'role' => UserType::Manager->value,
            'company_id' => $this->company->id,
        ]);

        // Step: Authenticate as the manager
        $this->actingAs($this->manager, 'api');
    }

    /** @test */
    public function manager_can_access_profile()
    {
        $response = $this->getJson('/api/manager/profile');

        $response->assertStatus(200); // Ensures the profile is accessible
        $response->assertJsonFragment(['name' => $this->manager->name]); // Check response contains manager info
    }

    /** @test */
    public function manager_can_update_profile()
    {
        $updatedData = [
            'name' => 'Updated Manager Name',
            'email' => 'updated.manager@test.com'
        ];

        $response = $this->put(route('update.manager'), $updatedData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $this->manager->id,
            'name' => $updatedData['name'],
            'email' => $updatedData['email']
        ]);
    }

    /** @test */
    public function manager_can_change_password()
    {
        $newPasswordData = [
            'old_password' => 'password',
            'new_password' => 'newpassword',
            'new_password_confirmation' => 'newpassword'
        ];

        $response = $this->putJson('/api/manager/profile/update/password', $newPasswordData);

        $response->assertStatus(200);

        $this->assertTrue(password_verify('newpassword', $this->manager->fresh()->password));
    }

    /** @test */
    public function manager_can_view_all_employees()
    {
        $response = $this->get(route('get.employees'));

        $response->assertStatus(200); // Ensure manager can view employees
        // Add assertions to verify data if mock employees are created
    }

    /** @test */
    public function manager_can_create_employee()
    {
        $employeeData = [
            'name' => 'Employee Name',
            'email' => 'employee@test.com',
            'phone' => '12345678901',
            'address' => '123 Faker Street',
            'role' => UserType::Employee->value,
        ];

        $response = $this->postJson('/api/manager/company/employees', $employeeData);

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas('users', [
            'name' => $employeeData['name'],
            'email' => $employeeData['email'],
            'phone' => $employeeData['phone'],
            'role' => UserType::Employee->value,
        ]);
    }

    /** @test */
    public function manager_can_update_employee()
    {
        $employee = User::create([
            'name' => 'Old Employee',
            'email' => 'old.employee@test.com',
            'password' => bcrypt('password'),
            'phone' => '123456789011',
            'address' => '123 Faker Street',
            'role' => UserType::Employee->value,
            'company_id' => $this->company->id,
        ]);

        $updateData = [
            'name' => 'Updated Employee',
            'email' => 'updated.employee@test.com'
        ];

        $response = $this->put(route('update.employee', $employee->id), $updateData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'name' => $updateData['name'],
            'email' => $updateData['email']
        ]);
    }

    /** @test */
    public function manager_can_delete_employee()
    {
        $employee = User::create([
            'name' => 'Employee To Delete',
            'email' => 'delete.employee@test.com',
            'phone' => '123456789011',
            'password' => bcrypt('password'),
            'user_type' => UserType::Employee->value,
            'company_id' => $this->company->id,
        ]);

        $response = $this->delete(route('delete.employee', $employee->id));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('users', [
            'id' => $employee->id,
        ]);
    }
}
