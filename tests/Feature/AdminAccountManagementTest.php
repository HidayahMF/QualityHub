<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    private const TEMP_PASSWORD = 'Temporary#Quality2026';
    private const OTHER_PASSWORD = 'Another#Quality2026';

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);
    }

    private function accountInput(Department $department, array $overrides = []): array
    {
        return array_merge([
            'employee_id' => 'BMC-NEW-001',
            'name' => 'Test Employee',
            'email' => 'employee.new@example.test',
            'department_id' => $department->id,
            'role' => 'user',
            'password' => self::TEMP_PASSWORD,
            'password_confirmation' => self::TEMP_PASSWORD,
        ], $overrides);
    }

    private function department(): Department
    {
        return Department::create([
            'name' => 'Quality Control',
            'code' => 'QC',
            'is_active' => true,
        ]);
    }

    public function test_super_admin_can_create_active_employee_without_forced_password_change(): void
    {
        $admin = $this->superAdmin();
        $department = $this->department();

        $this->actingAs($admin)
            ->post(route('users.store'), $this->accountInput($department))
            ->assertRedirect(route('users.index'))
            ->assertSessionHasNoErrors();

        $employee = User::query()->where('email', 'employee.new@example.test')->firstOrFail();
        $this->assertSame('user', $employee->role);
        $this->assertSame('active', $employee->account_status);
        $this->assertFalse($employee->must_change_password);
        $this->assertTrue(Hash::check(self::TEMP_PASSWORD, $employee->password));
        $this->assertSame(
            1,
            ActivityLog::query()->where('action', 'create_user_by_super_admin')->count()
        );
    }

    public function test_super_admin_can_create_document_admin_but_cannot_create_another_super_admin(): void
    {
        $admin = $this->superAdmin();
        $department = $this->department();

        $this->actingAs($admin)->post(
            route('users.store'),
            $this->accountInput($department, ['role' => 'admin'])
        )->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'employee.new@example.test',
            'role' => 'admin',
        ]);

        $this->actingAs($admin)->post(
            route('users.store'),
            $this->accountInput($department, [
                'employee_id' => 'BMC-NEW-002',
                'email' => 'second.employee@example.test',
                'role' => 'super_admin',
            ])
        )->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', [
            'email' => 'second.employee@example.test',
        ]);
    }

    public static function unauthorizedRoles(): array
    {
        return [
            'document admin' => ['admin'],
            'employee' => ['user'],
        ];
    }

    #[DataProvider('unauthorizedRoles')]
    public function test_admin_and_employee_cannot_create_or_reset_other_users(string $role): void
    {
        // Each role has its own PHPUnit test session; prevents reusing
        // session-security bindings across different authenticated users.
        $target = User::factory()->create(['role' => 'user']);
        $department = $this->department();
        $actor = User::factory()->create(['role' => $role]);

        $this->actingAs($actor)->get(route('users.create'))->assertForbidden();
        $this->actingAs($actor)
            ->post(route('users.store'), $this->accountInput($department))
            ->assertForbidden();
        $this->actingAs($actor)
            ->get(route('users.password.edit', $target))
            ->assertForbidden();
        $this->actingAs($actor)
            ->patch(route('users.password.update', $target), [
                'current_password' => 'password',
                'password' => self::OTHER_PASSWORD,
                'password_confirmation' => self::OTHER_PASSWORD,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', [
            'email' => 'employee.new@example.test',
        ]);
    }

    public function test_super_admin_cannot_reset_another_super_admin_or_self(): void
    {
        $actor = $this->superAdmin();
        $another = $this->superAdmin();
        $form = [
            'current_password' => 'password',
            'password' => self::OTHER_PASSWORD,
            'password_confirmation' => self::OTHER_PASSWORD,
        ];

        $this->actingAs($actor)
            ->patch(route('users.password.update', $another), $form)
            ->assertForbidden();
        $this->actingAs($actor)
            ->patch(route('users.password.update', $actor), $form)
            ->assertForbidden();
    }

    public function test_password_reset_revokes_sessions_without_forcing_self_change(): void
    {
        config(['session.driver' => 'database']);

        $actor = $this->superAdmin();
        $target = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        DB::table('sessions')->insert([
            'id' => 'old-target-session',
            'user_id' => $target->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => '',
            'last_activity' => time(),
        ]);

        $this->actingAs($actor)
            ->patch(route('users.password.update', $target), [
                'current_password' => 'password',
                'password' => self::TEMP_PASSWORD,
                'password_confirmation' => self::TEMP_PASSWORD,
            ])->assertRedirect(route('users.index'))->assertSessionHasNoErrors();

        $target->refresh();

        $this->assertTrue(Hash::check(self::TEMP_PASSWORD, $target->password));
        $this->assertFalse($target->must_change_password);
        $this->assertDatabaseMissing('sessions', ['id' => 'old-target-session']);
        $this->assertSame(
            1,
            ActivityLog::query()->where('action', 'reset_user_password_by_super_admin')->count()
        );
    }

    public function test_wrong_super_admin_password_cannot_change_target_password(): void
    {
        $actor = $this->superAdmin();
        $target = User::factory()->create(['role' => 'user']);
        $oldHash = $target->password;

        $this->actingAs($actor)->patch(route('users.password.update', $target), [
            'current_password' => 'incorrect',
            'password' => self::TEMP_PASSWORD,
            'password_confirmation' => self::TEMP_PASSWORD,
        ])->assertSessionHasErrors('current_password');

        $this->assertSame($oldHash, $target->fresh()->password);
    }

    public function test_existing_legacy_password_flag_does_not_block_login(): void
    {
        $target = User::factory()->create([
            'role' => 'user',
            'employee_id' => 'BMC_LOGIN_001',
            'account_status' => 'active',
            'must_change_password' => true,
        ]);

        $this->post(route('login'), [
            'email' => 'BMC_LOGIN_001',
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($target);
        $this->get(route('dashboard'))->assertOk();
        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee('Kata sandi sementara wajib diganti');
    }

    public function test_newly_created_employee_can_log_in_and_use_dashboard_immediately(): void
    {
        $admin = $this->superAdmin();
        $department = $this->department();

        $this->actingAs($admin)
            ->post(route('users.store'), $this->accountInput($department, [
                'password' => 'plainpass',
                'password_confirmation' => 'plainpass',
            ]))
            ->assertSessionHasNoErrors();

        $target = User::where('email', 'employee.new@example.test')->firstOrFail();
        $this->assertFalse($target->must_change_password);
        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->post(route('login'), [
            'email' => $target->email,
            'password' => 'plainpass',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($target);
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_password_policy_accepts_eight_simple_characters_and_rejects_seven(): void
    {
        $admin = $this->superAdmin();
        $department = $this->department();

        $this->actingAs($admin)
            ->post(route('users.store'), $this->accountInput($department, [
                'password' => '1234567',
                'password_confirmation' => '1234567',
            ]))->assertSessionHasErrors('password');

        $this->actingAs($admin)
            ->post(route('users.store'), $this->accountInput($department, [
                'password' => '12345678',
                'password_confirmation' => '12345678',
            ]))->assertSessionHasNoErrors();

        $target = User::where('email', 'employee.new@example.test')->firstOrFail();
        $this->assertFalse($target->must_change_password);
        $this->assertTrue(Hash::check('12345678', $target->password));
    }

    public function test_public_registration_and_email_reset_are_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
        $this->get('/forgot-password')->assertOk()
            ->assertSee('Hubungi Super Admin');
        $this->post('/forgot-password', [
            'email' => 'employee@example.test',
        ])->assertMethodNotAllowed();
        $this->get('/reset-password/test-token')->assertNotFound();
    }
}
