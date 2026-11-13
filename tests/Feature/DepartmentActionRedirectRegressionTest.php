<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression: Referrer-Policy: no-referrer + authenticated /profile/photo
 * can put the image URL in the session's previous URL slot.
 * Department actions must NEVER navigate to the image route.
 */
class DepartmentActionRedirectRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);
    }

    private function department(string $code, bool $active): Department
    {
        return Department::create([
            'name' => 'Department Redirect Regression',
            'code' => $code,
            'description' => 'Testing redirect safety',
            'is_active' => $active,
        ]);
    }

    private function previousUrlWasAvatar(): static
    {
        // The browser does not send Referer due to security headers.
        // An image GET can leave this URL as the last session page.
        return $this->withSession([
            '_previous.url' => route('profile.photo.show'),
        ]);
    }

    public function test_activate_redirects_to_departments_instead_of_profile_photo(): void
    {
        $department = $this->department('ACT', false);

        $this->actingAs($this->admin())
            ->previousUrlWasAvatar()
            ->patch(route('departments.toggle-status', $department))
            ->assertRedirect(route('departments.index'))
            ->assertSessionHas('success', 'Departemen berhasil diaktifkan.');

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'is_active' => true,
        ]);
    }

    public function test_deactivate_redirects_to_departments_instead_of_profile_photo(): void
    {
        $department = $this->department('DEACT', true);

        $this->actingAs($this->admin())
            ->previousUrlWasAvatar()
            ->patch(route('departments.toggle-status', $department))
            ->assertRedirect(route('departments.index'))
            ->assertSessionHas('success', 'Departemen berhasil dinonaktifkan.');

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'is_active' => false,
        ]);
    }

    public function test_blocked_deactivation_also_redirects_to_departments(): void
    {
        $department = $this->department('BUSY', true);
        Category::create([
            'department_id' => $department->id,
            'name' => 'Safety category',
            'slug' => 'safety-category',
            'description' => 'Attached active category',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->previousUrlWasAvatar()
            ->patch(route('departments.toggle-status', $department))
            ->assertRedirect(route('departments.index'))
            ->assertSessionHasErrors('department');

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'is_active' => true,
        ]);
    }

    public function test_permanent_delete_invalid_code_redirects_to_departments(): void
    {
        $department = $this->department('DELETE', false);

        $this->actingAs($this->admin())
            ->previousUrlWasAvatar()
            ->delete(route('departments.destroy', $department), [
                'confirmation_code' => 'WRONG',
            ])
            ->assertRedirect(route('departments.index'))
            ->assertSessionHasErrors('department');

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    public function test_permanent_delete_validation_redirects_to_departments(): void
    {
        $department = $this->department('VALID', false);

        $this->actingAs($this->admin())
            ->previousUrlWasAvatar()
            ->delete(route('departments.destroy', $department), [])
            ->assertRedirect(route('departments.index'))
            ->assertSessionHasErrors('confirmation_code');

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }
}
