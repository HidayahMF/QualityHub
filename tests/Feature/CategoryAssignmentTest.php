<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentScanMetadata;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CategoryAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private string $storageRoot;

    private Department $department;

    private Category $category;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storageRoot = storage_path(
            'framework/testing/category-assignment-' . bin2hex(random_bytes(8))
        );

        File::ensureDirectoryExists($this->storageRoot);

        config([
            'filesystems.disks.bmc_documents' => [
                'driver' => 'local',
                'root' => $this->storageRoot,
                'throw' => false,
            ],
        ]);

        Storage::purge('bmc_documents');

        $this->department = Department::create([
            'name' => 'Quality Control',
            'code' => 'QC',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'department_id' => $this->department->id,
            'name' => 'Inspection',
            'slug' => 'inspection',
            'is_active' => true,
        ]);

        $this->superAdmin = User::factory()->create([
            'name' => 'BMC Super Admin',
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        Storage::purge('bmc_documents');

        if (isset($this->storageRoot)) {
            File::deleteDirectory($this->storageRoot);
        }

        parent::tearDown();
    }

    public function test_existing_category_can_be_used_for_document_upload(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(
                route('documents.store'),
                $this->documentPayload([
                    'category_id' => $this->category->id,
                ])
            )
            ->assertSessionHasNoErrors();

        $document = Document::query()->sole();

        $response->assertRedirect(route('documents.show', $document));
        $this->assertSame($this->category->id, $document->category_id);
        $this->assertDatabaseCount('categories', 1);
        $this->assertDatabaseCount('document_versions', 1);
        $this->assertDatabaseCount('activity_logs', 1);
        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'create_category',
        ]);
        $this->assertCount(1, Storage::disk('bmc_documents')->allFiles());
    }

    public function test_category_combobox_is_accessible_and_permission_aware(): void
    {
        foreach (['documents.create', 'documents.scan.create'] as $routeName) {
            $this->actingAs($this->superAdmin)
                ->get(route($routeName))
                ->assertOk()
                ->assertSee('data-category-combobox', false)
                ->assertSee('role="combobox"', false)
                ->assertSee('role="listbox"', false)
                ->assertSee('aria-expanded="false"', false)
                ->assertSee('aria-controls="category_id_listbox"', false)
                ->assertSee('data-category-new-name', false)
                ->assertSee('data-category-new-department', false)
                ->assertSeeText('Quality Control (QC)');
        }

    }

    public function test_regular_admin_combobox_does_not_render_inline_creation_fields(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        foreach (['documents.create', 'documents.scan.create'] as $routeName) {
            $this->actingAs($admin)
                ->get(route($routeName))
                ->assertOk()
                ->assertSee('data-category-combobox', false)
                ->assertDontSee('data-category-new-name', false)
                ->assertDontSee('data-category-new-department', false);
        }
    }

    public function test_edit_combobox_preserves_current_inactive_category_without_inline_creation(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(
                route('documents.store'),
                $this->documentPayload([
                    'category_id' => $this->category->id,
                ])
            )
            ->assertSessionHasNoErrors();

        $document = Document::query()->sole();
        $this->category->update(['is_active' => false]);

        $this->get(route('documents.edit', $document))
            ->assertOk()
            ->assertSee('data-category-combobox', false)
            ->assertSeeText('Inspection')
            ->assertSeeText('Tidak Aktif')
            ->assertDontSee('name="new_category_name"', false)
            ->assertDontSee('name="new_category_department_id"', false);
    }

    public function test_create_combobox_restores_typed_new_category_old_input(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->withSession([
                '_old_input' => [
                    'new_category_name' => 'QC Pending Category',
                    'new_category_department_id' => (string) $this->department->id,
                ],
            ])
            ->get(route('documents.create'))
            ->assertOk()
            ->assertSee('data-category-mode="new"', false)
            ->assertSee('value="QC Pending Category"', false);

        $this->assertMatchesRegularExpression(
            '/id="category_id_new_department".*?<option\s+value="'
                . preg_quote((string) $this->department->id, '/')
                . '"\s+selected/s',
            $response->getContent()
        );
    }

    public function test_super_admin_can_create_category_inline_and_document_uses_it(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(
                route('documents.store'),
                $this->documentPayload([
                    'new_category_name' => '  QC   Trial Category  ',
                    'new_category_department_id' => $this->department->id,
                ])
            )
            ->assertSessionHasNoErrors();

        $category = Category::query()
            ->where('department_id', $this->department->id)
            ->where('slug', 'qc-trial-category')
            ->sole();
        $document = Document::query()->sole();

        $response->assertRedirect(route('documents.show', $document));
        $this->assertSame('QC Trial Category', $category->name);
        $this->assertTrue($category->is_active);
        $this->assertSame($category->id, $document->category_id);
        $this->assertDatabaseCount('categories', 2);
        $this->assertDatabaseCount('document_versions', 1);
        $this->assertCount(1, Storage::disk('bmc_documents')->allFiles());

        $log = ActivityLog::query()
            ->where('action', 'create_category')
            ->sole();

        $this->assertSame($this->superAdmin->id, $log->user_id);
        $this->assertNull($log->document_id);
        $this->assertSame(
            'BMC Super Admin created category "QC Trial Category".',
            $log->description
        );
        $this->assertSame([
            'target_category_id' => $category->id,
            'target_category_name' => 'QC Trial Category',
            'target_category_slug' => 'qc-trial-category',
            'department_id' => $this->department->id,
            'category_status' => 'active',
            'creation_source' => 'inline_document_upload',
        ], $log->metadata);
    }

    #[DataProvider('equivalentCategoryNameProvider')]
    public function test_inline_equivalent_name_reuses_existing_category_without_create_log(
        string $typedName
    ): void {
        $response = $this->actingAs($this->superAdmin)
            ->post(
                route('documents.store'),
                $this->documentPayload([
                    'new_category_name' => $typedName,
                    'new_category_department_id' => $this->department->id,
                ])
            )
            ->assertSessionHasNoErrors();

        $document = Document::query()->sole();

        $response->assertRedirect(route('documents.show', $document));
        $this->assertSame($this->category->id, $document->category_id);
        $this->assertDatabaseCount('categories', 1);
        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'create_category',
        ]);
        $this->assertDatabaseCount('activity_logs', 1);
    }

    public static function equivalentCategoryNameProvider(): array
    {
        return [
            'whitespace is normalized' => ['  Inspection  '],
            'case is ignored' => ['inspection'],
            'case and repeated whitespace are normalized' => ['  INSPECTION   '],
        ];
    }

    public function test_same_name_in_different_department_creates_separate_category(): void
    {
        $engineering = Department::create([
            'name' => 'Engineering',
            'code' => 'ENG',
            'is_active' => true,
        ]);

        $this->actingAs($this->superAdmin)
            ->post(
                route('documents.store'),
                $this->documentPayload([
                    'new_category_name' => 'Inspection',
                    'new_category_department_id' => $engineering->id,
                ])
            )
            ->assertSessionHasNoErrors();

        $newCategory = Category::query()
            ->where('department_id', $engineering->id)
            ->where('name', 'Inspection')
            ->sole();

        $this->assertNotSame($this->category->id, $newCategory->id);
        $this->assertSame($newCategory->id, Document::query()->sole()->category_id);
        $this->assertDatabaseCount('categories', 2);
        $this->assertDatabaseCount('activity_logs', 2);
    }

    public function test_regular_admin_cannot_create_category_by_manipulating_request(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(
                route('documents.store'),
                $this->documentPayload([
                    'new_category_name' => 'Unauthorized Category',
                    'new_category_department_id' => $this->department->id,
                ])
            )
            ->assertForbidden();

        $this->assertDatabaseCount('categories', 1);
        $this->assertNoDocumentArtifacts();
    }

    public function test_unknown_and_inactive_existing_categories_are_rejected_without_artifacts(): void
    {
        $this->actingAs($this->superAdmin)
            ->from(route('documents.create'))
            ->post(
                route('documents.store'),
                $this->documentPayload(['category_id' => 999999])
            )
            ->assertRedirect(route('documents.create'))
            ->assertSessionHasErrors('category_id');

        $this->assertNoDocumentArtifacts();

        $this->category->update(['is_active' => false]);

        $this->from(route('documents.create'))
            ->post(
                route('documents.store'),
                $this->documentPayload([
                    'category_id' => $this->category->id,
                ])
            )
            ->assertRedirect(route('documents.create'))
            ->assertSessionHasErrors('category_id');

        $this->assertNoDocumentArtifacts();
    }

    public function test_inline_name_matching_inactive_category_is_rejected_without_reactivation(): void
    {
        $inactive = Category::create([
            'department_id' => $this->department->id,
            'name' => 'Legacy Report',
            'slug' => 'legacy-report',
            'is_active' => false,
        ]);

        $this->actingAs($this->superAdmin)
            ->from(route('documents.create'))
            ->post(
                route('documents.store'),
                $this->documentPayload([
                    'new_category_name' => '  LEGACY   REPORT ',
                    'new_category_department_id' => $this->department->id,
                ])
            )
            ->assertRedirect(route('documents.create'))
            ->assertSessionHasErrors([
                'new_category_name' => 'Kategori dengan nama tersebut sedang tidak aktif.',
            ]);

        $this->assertFalse((bool) $inactive->fresh()->is_active);
        $this->assertDatabaseCount('categories', 2);
        $this->assertNoDocumentArtifacts();
    }

    public function test_inline_category_rejects_inactive_department_without_artifacts(): void
    {
        $inactiveDepartment = Department::create([
            'name' => 'Inactive Department',
            'code' => 'OFF',
            'is_active' => false,
        ]);

        $this->actingAs($this->superAdmin)
            ->from(route('documents.create'))
            ->post(
                route('documents.store'),
                $this->documentPayload([
                    'new_category_name' => 'Inactive Department Category',
                    'new_category_department_id' => $inactiveDepartment->id,
                ])
            )
            ->assertRedirect(route('documents.create'))
            ->assertSessionHasErrors('new_category_department_id');

        $this->assertDatabaseCount('categories', 1);
        $this->assertNoDocumentArtifacts();
    }

    public function test_inline_category_contract_rejects_malformed_oversized_and_ambiguous_input(): void
    {
        $invalidPayloads = [
            [
                'new_category_name' => '💡',
                'new_category_department_id' => $this->department->id,
                'error' => 'new_category_name',
            ],
            [
                'new_category_name' => str_repeat('A', 151),
                'new_category_department_id' => $this->department->id,
                'error' => 'new_category_name',
            ],
            [
                'category_id' => $this->category->id,
                'new_category_name' => 'Ambiguous Category',
                'new_category_department_id' => $this->department->id,
                'error' => 'category_id',
            ],
        ];

        foreach ($invalidPayloads as $invalid) {
            $error = $invalid['error'];
            unset($invalid['error']);

            $this->actingAs($this->superAdmin)
                ->from(route('documents.create'))
                ->post(
                    route('documents.store'),
                    $this->documentPayload($invalid)
                )
                ->assertRedirect(route('documents.create'))
                ->assertSessionHasErrors($error);

            $this->assertDatabaseCount('categories', 1);
            $this->assertNoDocumentArtifacts();
        }
    }

    public function test_validation_failure_preserves_normalized_typed_category_without_creating_it(): void
    {
        $payload = $this->documentPayload([
            'new_category_name' => '  QC   Pending Category  ',
            'new_category_department_id' => $this->department->id,
        ]);
        unset($payload['title']);

        $this->actingAs($this->superAdmin)
            ->from(route('documents.create'))
            ->post(route('documents.store'), $payload)
            ->assertRedirect(route('documents.create'))
            ->assertSessionHasErrors('title')
            ->assertSessionHasInput(
                'new_category_name',
                'QC Pending Category'
            )
            ->assertSessionHasInput(
                'new_category_department_id',
                (string) $this->department->id
            );

        $this->assertDatabaseMissing('categories', [
            'slug' => 'qc-pending-category',
        ]);
        $this->assertNoDocumentArtifacts();
    }

    public function test_scan_can_create_category_inline_with_activity_log(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(
                route('documents.scan.store'),
                $this->scanPayload([
                    'new_category_name' => 'Scan Trial Category',
                    'new_category_department_id' => $this->department->id,
                ])
            )
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $category = Category::query()
            ->where('slug', 'scan-trial-category')
            ->sole();
        $document = Document::query()->sole();

        $this->assertSame($category->id, $document->category_id);
        $this->assertDatabaseCount('document_scan_metadata', 1);
        $this->assertDatabaseCount('document_versions', 1);
        $this->assertCount(1, Storage::disk('bmc_documents')->allFiles());

        $log = ActivityLog::query()
            ->where('action', 'create_category')
            ->sole();

        $this->assertSame('inline_scan', $log->metadata['creation_source']);
        $this->assertSame($category->id, $log->metadata['target_category_id']);
    }

    public function test_scan_category_failure_leaves_no_document_or_file_artifacts(): void
    {
        $inactive = Category::create([
            'department_id' => $this->department->id,
            'name' => 'Inactive Scan Category',
            'slug' => 'inactive-scan-category',
            'is_active' => false,
        ]);

        $this->actingAs($this->superAdmin)
            ->from(route('documents.scan.create'))
            ->post(
                route('documents.scan.store'),
                $this->scanPayload([
                    'new_category_name' => ' inactive   scan category ',
                    'new_category_department_id' => $this->department->id,
                ])
            )
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasErrors('new_category_name');

        $this->assertFalse((bool) $inactive->fresh()->is_active);
        $this->assertDatabaseCount('categories', 2);
        $this->assertNoDocumentArtifacts(includeScan: true);
    }

    private function documentPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Category Assignment Document',
            'document_code' => 'CAT-TEST-001',
            'document_date' => '2026-09-18',
            'description' => 'Category assignment feature test.',
            'keywords' => 'category, assignment',
            'file' => UploadedFile::fake()->createWithContent(
                'category-assignment.txt',
                "BMC QualityHub category assignment test.\n"
            ),
        ], $overrides);
    }

    private function scanPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Category Assignment Scan',
            'file_name' => 'category-assignment-scan',
            'scan_type' => 'Lainnya',
            'scan_type_other' => 'Category Assignment Test',
            'scan_date' => '2026-09-18',
            'capture_source' => 'upload',
            'files' => [UploadedFile::fake()->image('scan.png', 2, 2)],
        ], $overrides);
    }

    private function assertNoDocumentArtifacts(bool $includeScan = false): void
    {
        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_versions', 0);
        $this->assertDatabaseCount('activity_logs', 0);

        if ($includeScan) {
            $this->assertDatabaseCount('document_scan_metadata', 0);
            $this->assertDatabaseCount('document_retoolings', 0);
            $this->assertDatabaseCount('document_axle_productions', 0);
            $this->assertDatabaseCount('document_brake_productions', 0);
        }

        $this->assertSame([], Storage::disk('bmc_documents')->allFiles());
    }
}
