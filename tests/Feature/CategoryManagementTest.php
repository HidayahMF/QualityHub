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
            ->assertSee('data-category-documents-trigger', false)
            ->assertSee(
                'data-category-documents-url="'
                    . route('categories.documents.index', $used, false)
                    . '"',
                false
            )
            ->assertSee('data-category-document-count="1"', false)
            ->assertSee('data-category-document-count="0"', false)
            ->assertSee('data-qh-modal-name="category-documents"', false)
            ->assertSee('aria-labelledby="category-documents-title"', false)
            ->assertSee('data-category-documents-search', false)
            ->assertSee('data-category-documents-loading', false)
            ->assertSee('data-category-documents-error', false)
            ->assertSee('data-category-documents-empty', false)
            ->assertSee('data-category-documents-no-results', false)
            ->assertSee('data-category-documents-results', false)
            ->assertSee('data-category-documents-header', false)
            ->assertSee('data-category-documents-scroll', false)
            ->assertSee('data-category-documents-retry', false)
            ->assertSee('data-category-documents-pagination', false)
            ->assertSee('data-category-documents-previous', false)
            ->assertSee('data-category-documents-next', false)
            ->assertSee('data-category-document-template', false)
            ->assertSee('data-category-document-title', false)
            ->assertSee('data-category-document-file-name', false)
            ->assertSee('data-category-document-code', false)
            ->assertSee('data-category-document-part-number', false)
            ->assertSee('data-category-document-date', false)
            ->assertSee('data-category-document-status-active', false)
            ->assertSee('data-category-document-status-archived', false)
            ->assertSee('data-category-document-detail', false)
            ->assertSee('data-qh-modal-initial-focus', false)
            ->assertSee('sm:!max-w-[94vw]', false)
            ->assertSee('lg:!max-w-[960px]', false)
            ->assertSee('max-h-[720px]', false)
            ->assertSee('sm:h-[74vh]', false)
            ->assertSee('lg:h-[72vh]', false)
            ->assertSee(
                'min-h-0 flex-1 overflow-y-auto overscroll-contain',
                false
            )
            ->assertDontSee('onclick=', false)
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
        $this->assertSame(
            1,
            substr_count(
                $response->getContent(),
                'data-category-documents-browser'
            )
        );
        $this->assertSame(
            3,
            substr_count(
                $response->getContent(),
                'data-category-documents-trigger'
            )
        );

        $appJs = (string) file_get_contents(
            resource_path('js/app.js')
        );

        $this->assertStringContainsString(
            "from './category-documents-modal'",
            $appJs
        );
        $this->assertStringContainsString(
            'initializeCategoryDocumentsModal();',
            $appJs
        );
    }

    public function test_super_admin_can_browse_only_selected_category_documents_including_archived(): void
    {
        $selected = $this->createCategory('Selected Category', true);
        $outside = $this->createCategory('Outside Category', true);

        $active = $this->createDocument(
            $selected,
            'Active Category Document',
            [
                'document_code' => null,
                'part_number' => null,
                'document_date' => null,
            ]
        );
        $archived = $this->createDocument(
            $selected,
            'Archived Category Document',
            [
                'is_active' => false,
                'part_number' => '0',
                'document_date' => '2026-08-21',
                'archived_at' => now(),
                'archived_by' => $this->superAdmin->id,
                'archive_reason' => 'Historical record',
            ]
        );
        $outsideDocument = $this->createDocument(
            $outside,
            'Outside Category Document'
        );

        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('categories.documents.index', $selected))
            ->assertOk()
            ->assertJsonPath('category.id', $selected->id)
            ->assertJsonPath('category.name', $selected->name)
            ->assertJsonPath('category_total', 2)
            ->assertJsonPath('filtered_total', 2)
            ->assertJsonCount(2, 'results');

        $results = collect($response->json('results'))->keyBy('id');

        $this->assertTrue($results->has($active->id));
        $this->assertTrue($results->has($archived->id));
        $this->assertFalse($results->has($outsideDocument->id));
        $this->assertSame('-', $results[$active->id]['document_code']);
        $this->assertSame('-', $results[$active->id]['part_number']);
        $this->assertSame('-', $results[$active->id]['document_date']);
        $this->assertSame(
            'Active Category Document',
            $results[$active->id]['title']
        );
        $this->assertSame(
            'active-category-document.pdf',
            $results[$active->id]['file_name']
        );
        $this->assertSame('Aktif', $results[$active->id]['status']);
        $this->assertTrue($results[$active->id]['is_active']);
        $this->assertSame(
            route('documents.show', $active, false),
            $results[$active->id]['detail_url']
        );
        $this->assertSame('Diarsipkan', $results[$archived->id]['status']);
        $this->assertFalse($results[$archived->id]['is_active']);
        $this->assertSame('0', $results[$archived->id]['part_number']);
        $this->assertSame(
            '21 Agt 2026',
            $results[$archived->id]['document_date']
        );
        $this->assertNull($results[$archived->id]['detail_url']);
    }

    public function test_category_document_search_is_scoped_and_searches_supported_fields(): void
    {
        $selected = $this->createCategory('Search Category', true);
        $outside = $this->createCategory('Outside Search Category', true);

        $byTitle = $this->createDocument(
            $selected,
            'Instruksi Khusus Alpha',
            ['document_code' => 'DOC-ALPHA']
        );
        $byCode = $this->createDocument(
            $selected,
            'Document By Code',
            ['document_code' => 'UNIQUE-CODE-742']
        );
        $byPart = $this->createDocument(
            $selected,
            'Document By Part',
            ['part_number' => 'PART-919-Z']
        );
        $byFile = $this->createDocument(
            $selected,
            'Document By File',
            ['file_name' => 'fixture-special-manual.pdf']
        );
        $this->createDocument(
            $outside,
            'Instruksi Khusus Alpha'
        );

        $cases = [
            '  <script>alert(1)</script> Instruksi Khusus Alpha  ' => $byTitle,
            'UNIQUE-CODE-742' => $byCode,
            'PART-919-Z' => $byPart,
            'fixture-special-manual.pdf' => $byFile,
        ];

        $this->actingAs($this->superAdmin);

        foreach ($cases as $search => $expectedDocument) {
            $response = $this->getJson(route(
                'categories.documents.index',
                [
                    'category' => $selected,
                    'search' => $search,
                ]
            ))
                ->assertOk()
                ->assertJsonPath('category_total', 4)
                ->assertJsonPath('filtered_total', 1)
                ->assertJsonCount(1, 'results');

            $this->assertSame(
                $expectedDocument->id,
                $response->json('results.0.id')
            );
        }
    }

    public function test_category_document_endpoint_handles_empty_category_and_no_search_results(): void
    {
        $empty = $this->createCategory('Empty Category', true);
        $populated = $this->createCategory('Populated Category', true);
        $this->createDocument($populated, 'Existing Document');

        $this->actingAs($this->superAdmin)
            ->getJson(route('categories.documents.index', $empty))
            ->assertOk()
            ->assertJsonPath('category_total', 0)
            ->assertJsonPath('filtered_total', 0)
            ->assertJsonCount(0, 'results');

        $this->getJson(route(
            'categories.documents.index',
            [
                'category' => $populated,
                'search' => 'does-not-exist',
            ]
        ))
            ->assertOk()
            ->assertJsonPath('category_total', 1)
            ->assertJsonPath('filtered_total', 0)
            ->assertJsonCount(0, 'results');
    }

    public function test_category_document_endpoint_paginates_large_categories(): void
    {
        $category = $this->createCategory('Paginated Category', true);

        foreach (range(1, 12) as $number) {
            $this->createDocument(
                $category,
                'Paginated Document ' . $number
            );
        }

        $this->actingAs($this->superAdmin)
            ->getJson(route('categories.documents.index', $category))
            ->assertOk()
            ->assertJsonPath('category_total', 12)
            ->assertJsonPath('filtered_total', 12)
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.last_page', 2)
            ->assertJsonCount(10, 'results');

        $this->getJson(route(
            'categories.documents.index',
            [
                'category' => $category,
                'page' => 2,
            ]
        ))
            ->assertOk()
            ->assertJsonPath('pagination.current_page', 2)
            ->assertJsonPath('pagination.last_page', 2)
            ->assertJsonCount(2, 'results');
    }

    public function test_category_document_endpoint_handles_invalid_category_and_input_safely(): void
    {
        $category = $this->createCategory('Validation Category', true);

        $this->actingAs($this->superAdmin)
            ->getJson('/categories/999999/documents')
            ->assertNotFound();

        $this->getJson('/categories/not-a-number/documents')
            ->assertNotFound();

        $this->getJson(route(
            'categories.documents.index',
            [
                'category' => $category,
                'search' => str_repeat('a', 161),
                'page' => 0,
            ]
        ))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'search',
                'page',
            ]);

        $this->getJson(route(
            'categories.documents.index',
            [
                'category' => $category,
                'search' => ['invalid-shape'],
            ]
        ))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('search');
    }

    public function test_guest_cannot_browse_category_documents(): void
    {
        $category = $this->createCategory('Private Category', true);

        $this->getJson(route('categories.documents.index', $category))
            ->assertUnauthorized();
    }

    #[DataProvider('unauthorizedRoleProvider')]
    public function test_non_super_admin_roles_cannot_browse_category_documents(
        string $role
    ): void {
        $category = $this->createCategory('Private Category', true);
        $actor = User::factory()->create([
            'role' => $role,
            'account_status' => 'active',
        ]);

        $this->actingAs($actor)
            ->getJson(route('categories.documents.index', $category))
            ->assertForbidden();
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

    private function createDocument(
        Category $category,
        string $title,
        array $overrides = []
    ): Document {
        return Document::create(array_merge([
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
        ], $overrides));
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
