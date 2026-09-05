<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DepartmentManagementTest extends TestCase
{
    use RefreshDatabase;


    public function test_super_admin_can_open_department_management_pages(): void
    {
        $superAdmin =
            $this->createSuperAdmin();

        $this->actingAs(
            $superAdmin
        );

        $this->get(
            route(
                'departments.index'
            )
        )
            ->assertOk()
            ->assertSee(
                'Department Management'
            )
            ->assertSee(
                'Tambah Departemen'
            );

        $this->get(
            route(
                'departments.create'
            )
        )
            ->assertOk()
            ->assertSee(
                'Add Department'
            )
            ->assertSee(
                'Nama Departemen'
            );
    }


    public function test_non_super_admin_is_forbidden_from_department_management(): void
    {
        $user =
            User::factory()
                ->create([
                    'role' =>
                        'admin',

                    'account_status' =>
                        'active',
                ]);

        $this->actingAs(
            $user
        );

        $this->get(
            route(
                'departments.index'
            )
        )->assertForbidden();

        $this->get(
            route(
                'departments.create'
            )
        )->assertForbidden();
    }


    public function test_super_admin_can_create_department_and_activity_is_logged(): void
    {
        $superAdmin =
            $this->createSuperAdmin();

        $response =
            $this
                ->actingAs(
                    $superAdmin
                )
                ->post(
                    route(
                        'departments.store'
                    ),
                    [
                        'name' =>
                            'Production Engineering',

                        'code' =>
                            'pe',

                        'description' =>
                            'Departemen engineering produksi.',
                    ]
                );

        $response
            ->assertRedirect(
                route(
                    'departments.index'
                )
            )
            ->assertSessionHas(
                'success',
                'Departemen berhasil ditambahkan.'
            );

        $department =
            Department::query()
                ->where(
                    'code',
                    'PE'
                )
                ->first();

        $this->assertNotNull(
            $department
        );

        $this->assertSame(
            'Production Engineering',
            $department->name
        );

        $this->assertTrue(
            (bool) $department->is_active
        );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'user_id' =>
                    $superAdmin->id,

                'action' =>
                    'create_department',
            ]
        );
    }


    public function test_duplicate_department_code_is_rejected(): void
    {
        $superAdmin =
            $this->createSuperAdmin();

        $this->createDepartment(
            name:
                'Existing Department',

            code:
                'EXIST'
        );

        $response =
            $this
                ->actingAs(
                    $superAdmin
                )
                ->from(
                    route(
                        'departments.create'
                    )
                )
                ->post(
                    route(
                        'departments.store'
                    ),
                    [
                        'name' =>
                            'Duplicate Department',

                        'code' =>
                            'EXIST',

                        'description' =>
                            null,
                    ]
                );

        $response
            ->assertRedirect(
                route(
                    'departments.create'
                )
            )
            ->assertSessionHasErrors(
                'code'
            );

        $this->assertDatabaseMissing(
            'departments',
            [
                'name' =>
                    'Duplicate Department',
            ]
        );
    }


    public function test_super_admin_can_update_department_and_activity_is_logged(): void
    {
        $superAdmin =
            $this->createSuperAdmin();

        $department =
            $this->createDepartment(
                name:
                    'Old Department Name',

                code:
                    'OLD'
            );

        $response =
            $this
                ->actingAs(
                    $superAdmin
                )
                ->put(
                    route(
                        'departments.update',
                        $department
                    ),
                    [
                        'name' =>
                            'New Department Name',

                        'code' =>
                            'new',

                        'description' =>
                            'Deskripsi baru.',
                    ]
                );

        $response
            ->assertRedirect(
                route(
                    'departments.index'
                )
            )
            ->assertSessionHas(
                'success',
                'Departemen berhasil diperbarui.'
            );

        $this->assertDatabaseHas(
            'departments',
            [
                'id' =>
                    $department->id,

                'name' =>
                    'New Department Name',

                'code' =>
                    'NEW',

                'description' =>
                    'Deskripsi baru.',
            ]
        );

        $log =
            ActivityLog::query()
                ->where(
                    'action',
                    'update_department'
                )
                ->where(
                    'user_id',
                    $superAdmin->id
                )
                ->latest(
                    'id'
                )
                ->first();

        $this->assertNotNull(
            $log
        );

        $metadata =
            is_array(
                $log->metadata
            )
                ? $log->metadata
                : json_decode(
                    (string) $log->metadata,
                    true
                );

        $this->assertSame(
            'Old Department Name',
            $metadata['before']['name']
                ?? null
        );

        $this->assertSame(
            'New Department Name',
            $metadata['after']['name']
                ?? null
        );
    }


    public function test_empty_department_can_be_deactivated_and_reactivated(): void
    {
        $superAdmin =
            $this->createSuperAdmin();

        $department =
            $this->createDepartment(
                name:
                    'Temporary Department',

                code:
                    'TMP'
            );

        $this
            ->actingAs(
                $superAdmin
            )
            ->patch(
                route(
                    'departments.toggle-status',
                    $department
                )
            )
            ->assertRedirect()
            ->assertSessionHas(
                'success',
                'Departemen berhasil dinonaktifkan.'
            );

        $this->assertDatabaseHas(
            'departments',
            [
                'id' =>
                    $department->id,

                'is_active' =>
                    false,
            ]
        );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'user_id' =>
                    $superAdmin->id,

                'action' =>
                    'deactivate_department',
            ]
        );

        $department->refresh();

        $this
            ->actingAs(
                $superAdmin
            )
            ->patch(
                route(
                    'departments.toggle-status',
                    $department
                )
            )
            ->assertRedirect()
            ->assertSessionHas(
                'success',
                'Departemen berhasil diaktifkan.'
            );

        $this->assertDatabaseHas(
            'departments',
            [
                'id' =>
                    $department->id,

                'is_active' =>
                    true,
            ]
        );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'user_id' =>
                    $superAdmin->id,

                'action' =>
                    'activate_department',
            ]
        );
    }


    public function test_department_with_active_category_cannot_be_deactivated(): void
    {
        $superAdmin =
            $this->createSuperAdmin();

        $department =
            $this->createDepartment(
                name:
                    'Quality Department',

                code:
                    'QUAL'
            );

        DB::table(
            'categories'
        )->insert([
            'department_id' =>
                $department->id,

            'name' =>
                'Active Category',

            'slug' =>
                'active-category',

            'description' =>
                'Kategori aktif untuk regression test.',

            'is_active' =>
                true,

            'created_at' =>
                now(),

            'updated_at' =>
                now(),
        ]);

        $response =
            $this
                ->actingAs(
                    $superAdmin
                )
                ->patch(
                    route(
                        'departments.toggle-status',
                        $department
                    )
                );

        $response
            ->assertRedirect()
            ->assertSessionHasErrors(
                'department'
            );

        $this->assertDatabaseHas(
            'departments',
            [
                'id' =>
                    $department->id,

                'is_active' =>
                    true,
            ]
        );

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'action' =>
                    'deactivate_department',
            ]
        );
    }


    public function test_department_with_active_user_cannot_be_deactivated(): void
    {
        $superAdmin =
            $this->createSuperAdmin();

        $department =
            $this->createDepartment(
                name:
                    'User Department',

                code:
                    'USR'
            );

        User::factory()
            ->create([
                'department_id' =>
                    $department->id,

                'role' =>
                    'user',

                'account_status' =>
                    'active',
            ]);

        $response =
            $this
                ->actingAs(
                    $superAdmin
                )
                ->patch(
                    route(
                        'departments.toggle-status',
                        $department
                    )
                );

        $response
            ->assertRedirect()
            ->assertSessionHasErrors(
                'department'
            );

        $this->assertDatabaseHas(
            'departments',
            [
                'id' =>
                    $department->id,

                'is_active' =>
                    true,
            ]
        );
    }


    public function test_department_index_search_and_status_filter_work(): void
    {
        $superAdmin =
            $this->createSuperAdmin();

        $this->createDepartment(
            name:
                'Quality Control',

            code:
                'QC',

            active:
                true
        );

        $this->createDepartment(
            name:
                'Warehouse',

            code:
                'WHS',

            active:
                false
        );

        $this
            ->actingAs(
                $superAdmin
            )
            ->get(
                route(
                    'departments.index',
                    [
                        'search' =>
                            'Quality',

                        'status' =>
                            'active',
                    ]
                )
            )
            ->assertOk()
            ->assertSee(
                'Quality Control'
            )
            ->assertDontSee(
                'Warehouse'
            );

        $this
            ->actingAs(
                $superAdmin
            )
            ->get(
                route(
                    'departments.index',
                    [
                        'status' =>
                            'inactive',
                    ]
                )
            )
            ->assertOk()
            ->assertSee(
                'Warehouse'
            );
    }


    private function createSuperAdmin(): User
    {
        return User::factory()
            ->create([
                'role' =>
                    'super_admin',

                'account_status' =>
                    'active',
            ]);
    }


    private function createDepartment(
        string $name,
        string $code,
        bool $active = true
    ): Department {

        return Department::create([
            'name' =>
                $name,

            'code' =>
                $code,

            'description' =>
                'Department regression test.',

            'is_active' =>
                $active,
        ]);
    }
}
