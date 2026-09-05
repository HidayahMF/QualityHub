<?php

namespace Tests\Feature\Auth;

use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_submit_an_account_request(): void
    {
        $department = Department::create([
            'name' => 'Quality Control',
            'code' => 'QC',
            'is_active' => true,
        ]);

        $response = $this->post('/register', [
            'employee_id' => 'EMP-001',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'department_id' => $department->id,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'employee_id' => 'EMP-001',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'department_id' => $department->id,
            'role' => 'user',
            'account_status' => 'pending',
        ]);
        $response->assertRedirect(route('login', absolute: false));
    }
}
