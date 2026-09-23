<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::create([
            'name' => 'Quality Control',
            'code' => 'QC',
            'description' => 'Quality Control test department.',
            'is_active' => true,
        ]);

        $this->superAdmin = User::factory()->create([
            'name' => 'BMC Super Admin',
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);
    }

    public function test_management_ui_uses_document_count_actions_and_csp_safe_closed_modals(): void
    {
        $unused = $this->createCategory('KRS', true);
        $used = $this->createCategory('Historical Inspection', true);
        $inactive = $this->createCategory('Inactive Reference', false);

        $this->createDocument($used, 'Historical Quality Record');

        $response = $this->actingAs($this->superAdmin)
            ->get(route('categories.index'))
            ->assertOk()
            ->assertSee('action="' . route('categories.destroy', $unused) . '"', false)
            ->assertDontSee('action="' . route('categories.deactivate', $unused) . '"', false)
            ->assertSee('action="' . route('categories.deactivate', $used) . '"', false)
            ->assertDontSee('action="' . route('categories.destroy', $used) . '"', false)
            ->assertSee('action="' . route('categories.activate', $inactive) . '"', false)
            ->assertSeeText('Hapus Permanen')
            ->assertSeeText('Nonaktifkan Kategori')
            ->assertSeeText('Aktifkan Kembali')
            ->assertSee('data-qh-modal-show="false"', false)
            ->assertDontSee('data-qh-modal-show="true"', false)
            ->assertSee('x-data="qualityHubModal"', false)
            ->assertSee('x-cloak', false)
            ->assertDontSee('qualityhub-confirm', false);

        $response
            ->assertSeeText('Hapus Kategori?')
            ->assertSeeText('Kategori KRS belum digunakan oleh dokumen dan akan dihapus permanen.')
            ->assertSeeText('BMC QualityHub')
            ->assertSee('bg-slate-950/45 backdrop-blur-[2px]', false)
            ->assertSee('bg-red-600 hover:bg-red-700', false)
            ->assertSee('bg-amber-600 hover:bg-amber-700', false)
            ->assertSee('aria-labelledby="confirmation-delete-category-' . $unused->id . '-title"', false)
            ->assertSee('aria-describedby="confirmation-deactivate-category-' . $used->id . '-description"', false);
        $this->assertMatchesRegularExpression(
            '/digunakan\s+oleh\s+1\s+dokumen/i',
            $response->getContent()
        );
    }

    public function test_user_and_category_confirmations_share_the_bmc_modal_shell(): void
    {
        $category = $this->createCategory('Shared Modal Category', true);
        $targetUser = User::factory()->create([
            'role' => 'user',
            'account_status' => 'active',
        ]);

        $categories = $this->actingAs($this->superAdmin)
            ->get(route('categories.index'))
            ->assertOk()
            ->getContent();

        $users = $this->get(route('users.index'))
            ->assertOk()
            ->getContent();

        foreach ([$categories, $users] as $html) {
            $this->assertStringContainsString('data-qh-modal-show="false"', $html);
            $this->assertStringNotContainsString('data-qh-modal-show="true"', $html);
            $this->assertStringContainsString('x-data="qualityHubModal"', $html);
            $this->assertStringContainsString('rounded-3xl border border-blue-100 bg-white shadow-2xl', $html);
            $this->assertStringContainsString('bg-slate-950/45 backdrop-blur-[2px]', $html);
            $this->assertStringContainsString('BMC QualityHub', $html);
            $this->assertStringContainsString('aria-modal="true"', $html);
        }

        $this->assertStringContainsString(
            'action="' . route('categories.destroy', $category) . '"',
            $categories
        );
        $this->assertStringContainsString(
            'action="' . route('users.deactivate', $targetUser) . '"',
            $users
        );
        $this->assertStringNotContainsString('qualityhub-user-confirm', $users);
    }

    public function test_super_admin_can_hard_delete_unused_category_and_log_snapshot(): void
    {
        $category = $this->createCategory('KRS', true);
        $categoryId = $category->id;

        $this->actingAs($this->superAdmin)
            ->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('categories', [
            'id' => $categoryId,
        ]);
        $this->assertDatabaseHas('departments', [
            'id' => $this->department->id,
        ]);

        $this->assertCategoryActivity(
            action: 'delete_category',
            categoryId: $categoryId,
            categoryName: 'KRS',
            status: 'active',
            documentCount: 0
        );
    }

    public function test_deleted_category_is_absent_from_active_document_category_options(): void
    {
        $category = $this->createCategory('KRS Dropdown Test', true);
        $categoryId = $category->id;

        $this->actingAs($this->superAdmin)
            ->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'));

        foreach (['documents.create', 'documents.scan.create'] as $routeName) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertDontSee(
                    'data-category-value="' . $categoryId . '"',
                    false
                )
                ->assertDontSeeText('KRS Dropdown Test');
        }

        $response = $this->get(route('documents.index'))->assertOk();

        $this->assertCategoryFilterDoesNotContain(
            $response->getContent(),
            $categoryId,
            'KRS Dropdown Test'
        );
    }

    public function test_guest_cannot_delete_category(): void
    {
        $unused = $this->createCategory('Permission Delete', true);

        $this->delete(route('categories.destroy', $unused))
            ->assertRedirect(route('login'));

        $this->assertTrue((bool) $unused->fresh()?->is_active);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    #[DataProvider('unauthorizedRoleProvider')]
    public function test_non_super_admin_roles_cannot_mutate_categories(
        string $role
    ): void {
        $unused = $this->createCategory('Permission Delete', true);
        $used = $this->createCategory('Permission Deactivate', true);
        $inactive = $this->createCategory('Permission Activate', false);
        $this->createDocument($used, 'Permission Historical Document');
        $actor = User::factory()->create([
            'role' => $role,
            'account_status' => 'active',
        ]);

        $this->actingAs($actor)
            ->delete(route('categories.destroy', $unused))
            ->assertForbidden();

        $this->patch(route('categories.deactivate', $used))
            ->assertForbidden();

        $this->patch(route('categories.activate', $inactive))
            ->assertForbidden();

        $this->assertTrue((bool) $unused->fresh()?->is_active);
        $this->assertTrue((bool) $used->fresh()?->is_active);
        $this->assertFalse((bool) $inactive->fresh()?->is_active);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public static function unauthorizedRoleProvider(): array
    {
        return [
            'admin' => ['admin'],
            'user' => ['user'],
            'viewer' => ['viewer'],
        ];
    }

    public function test_used_category_cannot_be_hard_deleted_and_document_is_preserved(): void
    {
        $category = $this->createCategory('Used Category', true);
        $document = $this->createDocument(
            $category,
            'Historical Document That Must Survive'
        );

        $this->actingAs($this->superAdmin)
            ->from(route('categories.index'))
            ->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'))
            ->assertSessionHasErrors('category');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
        ]);
        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'category_id' => $category->id,
        ]);
        $this->assertSame(
            $category->id,
            $document->fresh()?->category?->id
        );
        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'delete_category',
        ]);
    }

    public function test_used_category_can_be_deactivated_without_breaking_historical_document(): void
    {
        $category = $this->createCategory('Legacy Production Category', true);
        $document = $this->createDocument(
            $category,
            'Legacy Production History'
        );

        $this->actingAs($this->superAdmin)
            ->from(route('categories.index'))
            ->patch(route('categories.deactivate', $category))
            ->assertRedirect(route('categories.index'))
            ->assertSessionHasNoErrors();

        $this->assertFalse((bool) $category->fresh()?->is_active);
        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'category_id' => $category->id,
        ]);
        $this->assertSame(
            'Legacy Production Category',
            $document->fresh()?->category?->name
        );

        $this->get(route('documents.show', $document))
            ->assertOk()
            ->assertSeeText('Legacy Production Category');

        $searchResponse = $this->get(route('documents.index', [
            'search' => 'Legacy Production Category',
        ]))
            ->assertOk()
            ->assertSeeText('Legacy Production History')
            ->assertSeeText('Legacy Production Category');

        $this->assertCategoryFilterDoesNotContain(
            $searchResponse->getContent(),
            $category->id,
            $category->name
        );

        foreach (['documents.create', 'documents.scan.create'] as $routeName) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertDontSee(
                    'data-category-value="' . $category->id . '"',
                    false
                );
        }

        $this->assertCategoryActivity(
            action: 'deactivate_category',
            categoryId: $category->id,
            categoryName: $category->name,
            status: 'inactive',
            documentCount: 1
        );
    }

    public function test_inactive_category_cannot_be_assigned_to_a_new_document(): void
    {
        $category = $this->createCategory('Inactive Assignment', false);

        $this->actingAs($this->superAdmin)
            ->from(route('documents.create'))
            ->post(route('documents.store'), [
                'category_id' => $category->id,
                'title' => 'Rejected Inactive Category Document',
                'document_date' => '2026-09-21',
                'file' => UploadedFile::fake()->createWithContent(
                    'inactive-category.txt',
                    "Inactive categories must not accept new documents.\n"
                ),
            ])
            ->assertRedirect(route('documents.create'))
            ->assertSessionHasErrors('category_id');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_inactive_category_can_be_reactivated_and_logged(): void
    {
        $category = $this->createCategory('Reactivate Category', false);

        $this->actingAs($this->superAdmin)
            ->from(route('categories.index'))
            ->patch(route('categories.activate', $category))
            ->assertRedirect(route('categories.index'))
            ->assertSessionHasNoErrors();

        $this->assertTrue((bool) $category->fresh()?->is_active);

        foreach (['documents.create', 'documents.scan.create'] as $routeName) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertSee(
                    'data-category-value="' . $category->id . '"',
                    false
                );
        }

        $this->assertCategoryActivity(
            action: 'activate_category',
            categoryId: $category->id,
            categoryName: $category->name,
            status: 'active',
            documentCount: 0
        );
    }

    private function createCategory(string $name, bool $active): Category
    {
        return Category::create([
            'department_id' => $this->department->id,
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'description' => 'Category management regression test.',
            'is_active' => $active,
        ]);
    }

    private function createDocument(Category $category, string $title): Document
    {
        return Document::create([
            'category_id' => $category->id,
            'uploaded_by' => $this->superAdmin->id,
            'title' => $title,
            'document_code' => 'CAT-MGMT-' . $category->id,
            'file_name' => str($title)->slug() . '.pdf',
            'file_path' => 'private/tests/' . str($title)->slug() . '.pdf',
            'file_extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'document_date' => '2026-09-21',
            'description' => 'Historical document category test.',
            'keywords' => 'category, history',
            'is_active' => true,
            'current_revision' => 0,
        ]);
    }

    private function assertCategoryActivity(
        string $action,
        int $categoryId,
        string $categoryName,
        string $status,
        int $documentCount
    ): void {
        $log = ActivityLog::query()
            ->where('action', $action)
            ->sole();
        $metadata = $log->metadata;

        $this->assertSame($this->superAdmin->id, $log->user_id);
        $this->assertNull($log->document_id);
        $this->assertIsArray($metadata);
        $this->assertSame($categoryId, $metadata['category_id']);
        $this->assertSame($categoryName, $metadata['category_name']);
        $this->assertSame($this->department->id, $metadata['department_id']);
        $this->assertSame($this->department->name, $metadata['department_name']);
        $this->assertSame($this->superAdmin->id, $metadata['actor_user_id']);
        $this->assertSame($this->superAdmin->name, $metadata['actor_user_name']);
        $this->assertSame($documentCount, $metadata['document_count']);
        $this->assertSame($status, $metadata['category_status']);
        $this->assertSame($categoryId, $metadata['target_category_id']);
        $this->assertSame($categoryName, $metadata['target_category_name']);
        $this->assertArrayHasKey('timestamp', $metadata);
        $this->assertNotSame('', (string) $metadata['timestamp']);
        $this->assertInstanceOf(
            CarbonImmutable::class,
            CarbonImmutable::parse($metadata['timestamp'])
        );
    }

    private function assertCategoryFilterDoesNotContain(
        string $html,
        int $categoryId,
        string $categoryName
    ): void {
        $this->assertDoesNotMatchRegularExpression(
            '/<option\b[^>]*value=["\']'
                . preg_quote((string) $categoryId, '/')
                . '["\'][^>]*>\s*'
                . preg_quote($categoryName, '/')
                . '\s*<\/option>/i',
            $html
        );
    }
}
