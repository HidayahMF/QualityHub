<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\DocumentUploadSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use ZipArchive;

class PptxUploadTest extends TestCase
{
    use RefreshDatabase;

    private string $testDirectory;
    private Category $category;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDirectory = storage_path(
            'framework/testing/pptx-upload-' . bin2hex(random_bytes(8))
        );

        File::ensureDirectoryExists($this->testDirectory);

        config([
            'filesystems.disks.bmc_documents' => [
                'driver' => 'local',
                'root' => $this->testDirectory . '/private',
                'throw' => false,
            ],
        ]);

        Storage::purge('bmc_documents');

        $department = Department::create([
            'name' => 'Quality Control',
            'code' => 'QC',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Presentation',
            'slug' => 'presentation',
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        Storage::purge('bmc_documents');
        File::deleteDirectory($this->testDirectory);

        parent::tearDown();
    }

    /**
     * Create an actual OOXML package, so fileinfo and the PHP upload security
     * service inspect real ZIP bytes, not a mocked client MIME header.
     */
    private function pptx(
        string $name = 'laporan-quality-control.pptx',
        bool $macro = false,
        bool $wrongMainType = false
    ): UploadedFile {
        $path = $this->testDirectory . '/fixture-' . bin2hex(random_bytes(6)) . '.pptx';

        $zip = new ZipArchive();
        $this->assertTrue($zip->open(
            $path,
            ZipArchive::CREATE | ZipArchive::OVERWRITE
        ) === true);

        $mainType = $wrongMainType
            ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml'
            : 'application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml';

        $zip->addFromString('[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/ppt/presentation.xml" ContentType="' . $mainType . '"/>'
            . '</Types>'
        );

        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1"'
            . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument"'
            . ' Target="ppt/presentation.xml"/>'
            . '</Relationships>'
        );

        $zip->addFromString('ppt/presentation.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<p:presentation xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"/>'
        );

        if ($macro) {
            $zip->addFromString('ppt/vbaProject.bin', 'not-an-allowed-macro');
        }

        $this->assertTrue($zip->close());

        return new UploadedFile($path, $name, null, null, true);
    }

    private function payload(UploadedFile $file): array
    {
        return [
            'title' => 'Laporan PowerPoint Quality Control',
            'document_code' => 'PPTX-QC-2026',
            'document_date' => '2026-11-13',
            'description' => 'Presentasi internal QC',
            'category_id' => $this->category->id,
            'file' => $file,
        ];
    }

    public function test_real_pptx_passes_mime_signature_and_structure_checks(): void
    {
        $result = app(DocumentUploadSecurityService::class)
            ->validate($this->pptx());

        $this->assertSame('pptx', $result['extension']);
        $this->assertSame('zip_ooxml', $result['signature']);
        $this->assertSame('pptx', $result['structure']);
        $this->assertSame('passed', $result['macro_check']);
        $this->assertContains(
            $result['server_mime'],
            [
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/zip',
                'application/x-zip-compressed',
            ]
        );
    }

    public function test_real_pptx_upload_creates_document_and_private_revision_zero(): void
    {
        $this->actingAs($this->admin)
            ->post(route('documents.store'), $this->payload($this->pptx()))
            ->assertSessionHasNoErrors();

        $document = Document::query()->sole();
        $version = DocumentVersion::query()->sole();

        $this->assertSame('pptx', $document->file_extension);
        $this->assertSame('pptx', $version->file_extension);
        $this->assertSame(0, (int) $version->revision_number);
        $this->assertTrue(Storage::disk('bmc_documents')->exists($document->file_path));
        $this->assertDatabaseCount('documents', 1);
        $this->assertDatabaseCount('document_versions', 1);

        $this->get(route('documents.download', $document))
            ->assertOk()
            ->assertHeader('content-disposition');

        // The format is downloadable but not rendered inline by browsers.
        $this->get(route('documents.open', $document))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->get(route('documents.index', ['extension' => 'pptx']))
            ->assertOk();
    }

    public function test_pptx_revision_stores_new_file_without_destroying_prior_revision(): void
    {
        $this->actingAs($this->admin)
            ->post(route('documents.store'), $this->payload($this->pptx()))
            ->assertSessionHasNoErrors();

        $document = Document::query()->sole();
        $oldPath = $document->file_path;

        $this->post(route('documents.revisions.store', $document), [
            'file' => $this->pptx('laporan-quality-control-rev1.pptx'),
            'revision_file_name' => 'laporan-quality-control-rev1',
            'document_date' => '2026-11-13',
            'revision_note' => 'Perubahan presentasi QC untuk evaluasi',
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, DocumentVersion::query()->count());
        $this->assertDatabaseHas('document_versions', [
            'document_id' => $document->id,
            'revision_number' => 1,
            'file_extension' => 'pptx',
        ]);
        $this->assertTrue(Storage::disk('bmc_documents')->exists($oldPath));
    }

    public function test_macro_payload_in_pptx_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        app(DocumentUploadSecurityService::class)
            ->validate($this->pptx(macro: true));
    }

    public function test_renamed_zip_without_presentation_content_type_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        app(DocumentUploadSecurityService::class)
            ->validate($this->pptx(wrongMainType: true));
    }

    public function test_renamed_pptm_is_not_supported_as_pptx(): void
    {
        $this->expectException(ValidationException::class);

        app(DocumentUploadSecurityService::class)
            ->validate($this->pptx(name: 'macro-presentation.pptm'));
    }

    public function test_text_disguised_as_pptx_is_rejected_before_storage(): void
    {
        $fake = UploadedFile::fake()->createWithContent(
            'disguised.pptx',
            'not a PowerPoint presentation'
        );

        $this->actingAs($this->admin)
            ->from(route('documents.create'))
            ->post(route('documents.store'), $this->payload($fake))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('documents', 0);
        $this->assertSame([], Storage::disk('bmc_documents')->allFiles());
    }

    public function test_missing_file_redirects_to_add_document_not_avatar_and_preserves_errors(): void
    {
        $payload = $this->payload($this->pptx());
        unset($payload['file']);

        $this->actingAs($this->admin)
            ->withSession(['_previous.url' => route('profile.photo.show')])
            ->post(route('documents.store'), $payload)
            ->assertRedirect(route('documents.create'))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_invalid_document_metadata_redirects_to_form_not_avatar(): void
    {
        $payload = $this->payload($this->pptx());
        $payload['title'] = '';

        $this->actingAs($this->admin)
            ->withSession(['_previous.url' => route('profile.photo.show')])
            ->post(route('documents.store'), $payload)
            ->assertRedirect(route('documents.create'))
            ->assertSessionHasErrors('title');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_macro_pptx_upload_redirects_back_to_add_document_with_security_error(): void
    {
        $this->actingAs($this->admin)
            ->withSession(['_previous.url' => route('profile.photo.show')])
            ->post(route('documents.store'), $this->payload($this->pptx(macro: true)))
            ->assertRedirect(route('documents.create'))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_invalid_revision_redirects_to_revision_form_not_avatar(): void
    {
        $this->actingAs($this->admin)
            ->post(route('documents.store'), $this->payload($this->pptx()))
            ->assertSessionHasNoErrors();

        $document = Document::query()->sole();

        $this->withSession(['_previous.url' => route('profile.photo.show')])
            ->post(route('documents.revisions.store', $document), [
                'file' => $this->pptx('revisi-gagal.pptx'),
                'revision_file_name' => '',
                'document_date' => '2026-11-13',
                'revision_note' => 'Revisi QC',
            ])
            ->assertRedirect(route('documents.revisions.create', $document))
            ->assertSessionHasErrors('revision_file_name');

        $this->assertDatabaseCount('document_versions', 1);
    }

    public function test_security_rejected_revision_returns_to_revision_form_not_avatar(): void
    {
        $this->actingAs($this->admin)
            ->post(route('documents.store'), $this->payload($this->pptx()))
            ->assertSessionHasNoErrors();

        $document = Document::query()->sole();

        $this->withSession(['_previous.url' => route('profile.photo.show')])
            ->post(route('documents.revisions.store', $document), [
                'file' => $this->pptx('revisi-berbahaya.pptx', macro: true),
                'revision_file_name' => 'revisi-berbahaya',
                'document_date' => '2026-11-13',
                'revision_note' => 'Revisi QC',
            ])
            ->assertRedirect(route('documents.revisions.create', $document))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('document_versions', 1);
    }

    public function test_upload_and_revision_forms_advertise_pptx_without_inline_preview(): void
    {
        $this->actingAs($this->admin)
            ->get(route('documents.create'))
            ->assertOk()
            ->assertSee('accept=".pdf,.xls,.xlsx,.doc,.docx,.pptx,.csv,.txt"', false);

        $create = file_get_contents(resource_path('views/documents/create.blade.php'));
        $revisions = file_get_contents(resource_path('views/documents/revisions/create.blade.php'));
        $details = file_get_contents(resource_path('views/documents/show.blade.php'));

        $this->assertIsString($create);
        $this->assertIsString($revisions);
        $this->assertIsString($details);
        $this->assertStringContainsString('PowerPoint (PPTX)', $create);
        $this->assertStringContainsString('PowerPoint (PPTX)', $revisions);
        $this->assertStringNotContainsString("'pptx', 'pdf', 'txt'", $details);
    }
}
