<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InputHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_input_is_normalized_before_storage(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'account_status' => 'active',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => "  <script>alert('x')</script>  Quality User  ",
                'email' => '  QUALITY.USER@EXAMPLE.COM  ',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('Quality User', $user->name);
        $this->assertSame('quality.user@example.com', $user->email);
    }

    public function test_registration_cannot_inject_privileged_role_or_active_status(): void
    {
        $departmentId = DB::table('departments')->insertGetId([
            'name' => 'Quality Control',
            'code' => 'QC',
            'description' => 'Input hardening test.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->post(route('register'), [
            'employee_id' => '  QC-SEC-001  ',
            'name' => '<b>Quality Tester</b>',
            'email' => '  TESTER@EXAMPLE.COM  ',
            'department_id' => $departmentId,
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',

            // Field berbahaya yang sengaja disisipkan via request manual.
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        $user = User::query()
            ->where('email', 'tester@example.com')
            ->firstOrFail();

        $this->assertSame('QC-SEC-001', $user->employee_id);
        $this->assertSame('Quality Tester', $user->name);
        $this->assertSame('user', $user->role);
        $this->assertSame('pending', $user->account_status);
    }

    public function test_registration_rejects_inactive_department_injected_from_devtools(): void
    {
        $departmentId = DB::table('departments')->insertGetId([
            'name' => 'Inactive Department',
            'code' => 'OFF',
            'description' => 'Input hardening test.',
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->from(route('register'))->post(route('register'), [
            'employee_id' => 'QC-SEC-002',
            'name' => 'Inactive Dept User',
            'email' => 'inactive@example.com',
            'department_id' => $departmentId,
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ]);

        $response
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('department_id');

        $this->assertDatabaseMissing('users', [
            'email' => 'inactive@example.com',
        ]);
    }

    public function test_user_management_rejects_invalid_filter_values(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
            'employee_id' => 'ADMIN-SEC-001',
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('users.index'))
            ->get(route('users.index', [
                'status' => 'root',
                'role' => 'owner',
            ]));

        $response
            ->assertRedirect(route('users.index'))
            ->assertSessionHasErrors(['status', 'role']);
    }

    public function test_super_admin_status_is_protected_on_backend(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
            'employee_id' => 'ADMIN-SEC-003',
        ]);

        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'inactive',
            'employee_id' => 'SUPER-SEC-001',
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('users.activate', $superAdmin));

        $response->assertForbidden();

        $superAdmin->refresh();
        $this->assertSame('inactive', $superAdmin->account_status);
    }
}
