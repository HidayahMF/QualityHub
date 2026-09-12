<?php

namespace Tests\Feature\Auth;

use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private const STRONG_PASSWORD =
        'QualityHub#2026Secure';

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
            'password' => self::STRONG_PASSWORD,
            'password_confirmation' => self::STRONG_PASSWORD,
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
        $response->assertRedirect(
            route('login', absolute: false)
        );
    }

    public function test_weak_password_is_rejected(): void
    {
        $department = Department::create([
            'name' => 'Quality Control',
            'code' => 'QC',
            'is_active' => true,
        ]);

        $response = $this
            ->from('/register')
            ->post('/register', [
                'employee_id' => 'EMP-002',
                'name' => 'Weak Password User',
                'email' => 'weak@example.com',
                'department_id' => $department->id,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

        $response
            ->assertRedirect('/register')
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', [
            'email' => 'weak@example.com',
        ]);
    }
}
