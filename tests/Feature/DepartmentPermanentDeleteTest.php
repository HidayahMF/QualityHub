<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentPermanentDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);
    }

    private function department(string $code = 'TMP', bool $active = false): Department
    {
        return Department::create([
            'name' => 'Departemen Percobaan',
            'code' => $code,
            'description' => 'Khusus pengujian',
            'is_active' => $active,
        ]);
    }

    public function test_super_admin_can_permanently_delete_unused_inactive_department_with_audit(): void
    {
        $admin = $this->admin();
        $department = $this->department();

        $this->actingAs($admin)->delete(route('departments.destroy', $department), [
            'confirmation_code' => $department->code,
        ])
            ->assertRedirect(route('departments.index'))
            ->assertSessionHas('success', 'Departemen berhasil dihapus permanen.');

        $this->assertDatabaseMissing('departments', ['id' => $department->id]);

        $log = ActivityLog::where('action', 'delete_department')->firstOrFail();
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame($department->id, $log->metadata['target_department_id']);
        $this->assertSame($department->code, $log->metadata['deleted_department']['code']);
        $this->assertTrue($log->metadata['deleted_permanently']);
    }

    public function test_department_must_be_inactive_even_when_unused(): void
    {
        $department = $this->department('ACT', true);

        $this->actingAs($this->admin())->delete(route('departments.destroy', $department), [
            'confirmation_code' => 'ACT',
        ])->assertRedirect()->assertSessionHasErrors('department');

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
        $this->assertDatabaseMissing('activity_logs', ['action' => 'delete_department']);
    }

    public function test_existing_inactive_category_blocks_permanent_delete(): void
    {
        $department = $this->department('CAT');
        Category::create([
            'department_id' => $department->id,
            'name' => 'Kategori Lama',
            'slug' => 'kategori-lama',
            'is_active' => false,
        ]);

        $this->actingAs($this->admin())->delete(route('departments.destroy', $department), [
            'confirmation_code' => 'CAT',
        ])->assertRedirect()->assertSessionHasErrors('department');

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
        $this->assertDatabaseHas('categories', ['department_id' => $department->id]);
    }

    public function test_existing_inactive_user_blocks_permanent_delete_without_detaching_user(): void
    {
        $department = $this->department('USR');
        $user = User::factory()->create([
            'department_id' => $department->id,
            'role' => 'user',
            'account_status' => 'inactive',
        ]);

        $this->actingAs($this->admin())->delete(route('departments.destroy', $department), [
            'confirmation_code' => 'USR',
        ])->assertRedirect()->assertSessionHasErrors('department');

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'department_id' => $department->id]);
        $this->assertDatabaseMissing('activity_logs', ['action' => 'delete_department']);
    }

    public function test_wrong_confirmation_code_and_missing_code_are_rejected(): void
    {
        $department = $this->department('TEMP1');
        $this->actingAs($this->admin());

        $this->delete(route('departments.destroy', $department), [
            'confirmation_code' => 'WRONG',
        ])->assertRedirect()->assertSessionHasErrors('department');

        $this->delete(route('departments.destroy', $department), [])
            ->assertRedirect()
            ->assertSessionHasErrors('confirmation_code');

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
        $this->assertDatabaseMissing('activity_logs', ['action' => 'delete_department']);
    }

    public function test_regular_admin_and_employee_are_forbidden(): void
    {
        $department = $this->department('SEC');
        foreach (['admin', 'user'] as $role) {
            $actor = User::factory()->create([
                'role' => $role,
                'account_status' => 'active',
            ]);

            $this->actingAs($actor)->delete(route('departments.destroy', $department), [
                'confirmation_code' => 'SEC',
            ])->assertForbidden();
        }

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
        $this->assertDatabaseMissing('activity_logs', ['action' => 'delete_department']);
    }

    public function test_guest_cannot_delete_department(): void
    {
        $department = $this->department('GUEST');

        $this->delete(route('departments.destroy', $department), [
            'confirmation_code' => 'GUEST',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    public function test_index_shows_delete_only_for_unused_inactive_department(): void
    {
        $unused = $this->department('TMP');
        $active = $this->department('LIVE', true);
        $used = $this->department('USED');
        Category::create([
            'department_id' => $used->id,
            'name' => 'Kategori Pengujian',
            'slug' => 'kategori-pengujian',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('departments.index'));

        $response->assertOk()
            ->assertSee('data-confirm-code="TMP"', false)
            ->assertSee('data-confirm-destructive="true"', false)
            ->assertDontSee('data-confirm-code="LIVE"', false)
            ->assertDontSee('data-confirm-code="USED"', false)
            ->assertSee('Hapus');
    }

    public function test_deleting_twice_returns_not_found_and_does_not_duplicate_audit(): void
    {
        $department = $this->department('ONCE');
        $this->actingAs($this->admin());

        $this->delete(route('departments.destroy', $department), [
            'confirmation_code' => 'ONCE',
        ])->assertRedirect(route('departments.index'));

        $this->delete(route('departments.destroy', $department), [
            'confirmation_code' => 'ONCE',
        ])->assertNotFound();

        $this->assertSame(1, ActivityLog::where('action', 'delete_department')->count());
    }
}
