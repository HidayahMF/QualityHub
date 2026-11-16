<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DivisionTerminologyUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_division_management_pages_use_divisi_labels(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);

        $division = Department::create([
            'name' => 'Quality Control',
            'code' => 'QC',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $this->get(route('departments.index'))
            ->assertOk()
            ->assertSee('Total Divisi')
            ->assertSee('Divisi Aktif')
            ->assertSee('Divisi Tidak Aktif')
            ->assertSee('Daftar Divisi')
            ->assertSee('Tambah Divisi')
            ->assertDontSee('Department Management')
            ->assertDontSee('Total Departemen');

        $this->get(route('departments.create'))
            ->assertOk()
            ->assertSee('Tambah Divisi')
            ->assertSee('Nama Divisi')
            ->assertSee('Kode Divisi')
            ->assertDontSee('Add Department');

        $this->get(route('departments.edit', $division))
            ->assertOk()
            ->assertSee('Edit Divisi')
            ->assertSee('Nama Divisi')
            ->assertSee('Status Divisi')
            ->assertDontSee('Edit Department');
    }

    public function test_user_facing_blade_templates_and_validation_use_divisi(): void
    {
        $views = [
            'views/layouts/app.blade.php',
            'views/departments/index.blade.php',
            'views/departments/create.blade.php',
            'views/departments/edit.blade.php',
            'views/dashboard.blade.php',
            'views/categories/index.blade.php',
            'views/categories/create.blade.php',
            'views/categories/edit.blade.php',
            'views/components/category-combobox.blade.php',
            'views/users/index.blade.php',
            'views/users/create.blade.php',
            'views/auth/register.blade.php',
            'views/activity-logs/index.blade.php',
            'views/documents/archived.blade.php',
            'views/documents/index.blade.php',
            'views/documents/show.blade.php',
            'views/favorites/index.blade.php',
            'views/profile/partials/update-profile-information-form.blade.php',
        ];

        foreach ($views as $path) {
            $source = file_get_contents(resource_path($path));

            $this->assertIsString($source, $path);
            $this->assertDoesNotMatchRegularExpression(
                '/departemen/ui',
                $source,
                "Istilah lama masih muncul dalam {$path}"
            );
            $this->assertStringNotContainsString(
                'Department Management',
                $source,
                $path
            );
        }

        $validation = require base_path('lang/id/validation.php');

        $this->assertSame('divisi', $validation['attributes']['department_id']);
    }

    public function test_existing_routes_database_keys_and_delete_confirmation_remain_compatible(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $management = file_get_contents(resource_path('views/departments/index.blade.php'));
        $category = file_get_contents(resource_path('views/categories/create.blade.php'));

        $this->assertIsString($layout);
        $this->assertIsString($management);
        $this->assertIsString($category);

        $this->assertStringContainsString("route('departments.index')", $layout);
        $this->assertStringContainsString("route('departments.destroy',", $management);
        $this->assertStringContainsString('data-department-confirmation-code', $management);
        $this->assertStringContainsString('name="department_id"', $category);
        $this->assertStringContainsString('Hapus Divisi Permanen?', $management);
        $this->assertStringContainsString("str_replace('Department', 'Divisi'", file_get_contents(resource_path('views/activity-logs/index.blade.php')));
        $this->assertStringContainsString('Divisi', $layout);
    }
}
