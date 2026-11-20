<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScanGalleryUploadPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private string $storageRoot;
    private Category $category;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storageRoot = storage_path(
            'framework/testing/scan-50-upload-'.bin2hex(random_bytes(8))
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

        $department = Department::create([
            'name' => 'Quality Control',
            'code' => 'QC',
            'is_active' => true,
        ]);
        $this->category = Category::create([
            'department_id' => $department->id,
            'name' => 'QC Scan',
            'slug' => 'qc-scan',
            'is_active' => true,
        ]);
        $this->admin = User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'active',
            'must_change_password' => false,
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

    private function payload(array $files): array
    {
        return [
            'title' => 'QC upload banyak foto',
            'file_name' => 'qc-upload-50',
            'category_id' => $this->category->id,
            'scan_type' => 'QC General Inspection',
            'scan_date' => '2026-11-20',
            'capture_source' => 'upload',
            'expected_photo_count' => count($files),
            'files' => $files,
        ];
    }

    public function test_scan_page_exposes_bounded_gallery_optimization_and_real_upload_progress(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('documents.scan.create'))
            ->assertOk()
            ->baseResponse->getContent();

        foreach ([
            'id="galleryProcessingStatus"',
            'id="galleryProcessingProgress"',
            'id="scanUploadStatus"',
            'id="scanUploadProgress"',
            'async function normalizeGalleryPhoto(file)',
            'async function prepareGalleryPhotos(files)',
            'await normalizeGalleryPhoto(file)',
            "image.loading = 'lazy'",
            "image.decoding = 'async'",
            "xhr.upload.addEventListener('progress'",
            "xhr.setRequestHeader('Accept', 'application/json')",
            'new FormData(scanDocumentForm)',
            'galleryPhotoProcessing || scanUploadInProgress',
            'window.location.reload()',
        ] as $text) {
            $this->assertStringContainsString($text, $html);
        }
    }

    public function test_json_upload_of_fifty_small_photos_creates_one_pdf_with_fifty_pages(): void
    {
        $files = [];
        for ($i = 0; $i < 50; $i++) {
            $files[] = UploadedFile::fake()->image(
                sprintf('page-%02d.jpg', $i + 1),
                180,
                260
            );
        }

        $this->actingAs($this->admin)
            ->postJson(route('documents.scan.store'), $this->payload($files))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('redirect', route('documents.scan.create'));

        $document = Document::query()->sole();
        $this->assertSame('pdf', $document->file_extension);
        $this->assertTrue(Storage::disk('bmc_documents')->exists($document->file_path));

        $pdfHeader = Storage::disk('bmc_documents')->get($document->file_path);
        $this->assertStringStartsWith('%PDF-', $pdfHeader);

        // Photo count belongs to the immutable revision snapshot, not
        // the document_scan_metadata table.
        $snapshot = DocumentVersion::query()->sole()->metadata_snapshot;
        $this->assertSame(50, (int) ($snapshot['scan_metadata']['photo_count'] ?? 0));
        $this->assertSame(50, (int) ($snapshot['scan_security']['source_image_count'] ?? 0));
        $this->assertDatabaseCount('documents', 1);
        $this->assertDatabaseCount('document_versions', 1);
    }

    public function test_json_upload_returns_422_for_invalid_input_without_faking_success(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('documents.scan.store'), $this->payload([]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('files');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_non_ajax_existing_form_redirect_still_works(): void
    {
        $this->actingAs($this->admin)
            ->post(route('documents.scan.store'), $this->payload([
                UploadedFile::fake()->image('one.jpg', 200, 300),
            ]))
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('documents', 1);
    }
}
