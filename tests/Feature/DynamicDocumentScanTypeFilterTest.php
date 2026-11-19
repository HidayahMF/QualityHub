<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentScanMetadata;
use App\Models\DocumentScanType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicDocumentScanTypeFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $department = Department::create([
            'name' => 'Quality Control',
            'code' => 'QC',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'department_id' => $department->id,
            'name' => 'QC Scans',
            'slug' => 'qc-scans',
            'is_active' => true,
        ]);

        $this->employee = User::factory()->create([
            'department_id' => $department->id,
            'role' => 'user',
            'account_status' => 'active',
            'must_change_password' => false,
        ]);
    }

    private function customType(string $name, bool $active = true): DocumentScanType
    {
        return DocumentScanType::query()->create([
            'name' => $name,
            'is_active' => $active,
            'sort_order' => 500,
        ]);
    }

    private function scannedDocument(
        string $title,
        string $scanType,
        bool $active = true
    ): Document {
        $document = Document::create([
            'category_id' => $this->category->id,
            'uploaded_by' => $this->employee->id,
            'title' => $title,
            'document_code' => 'QC-'.bin2hex(random_bytes(4)),
            'file_name' => 'inspection.pdf',
            'file_path' => 'documents/test/'.bin2hex(random_bytes(4)).'.pdf',
            'file_extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 15,
            'document_date' => '2026-11-19',
            'is_active' => $active,
            'archived_at' => $active ? null : now(),
            'current_revision' => 0,
        ]);

        DocumentScanMetadata::create([
            'document_id' => $document->id,
            'source_type' => 'scan',
            'scan_type' => $scanType,
            'scan_date' => '2026-11-19',
        ]);

        return $document;
    }

    public function test_active_custom_pt_types_show_in_advanced_filter_without_documents(): void
    {
        $this->customType('PT BRAJA');
        $this->customType('PT BUMM');
        $this->customType('PT KWS');
        $this->customType('PT OLD INACTIVE', false);

        $response = $this->actingAs($this->employee)
            ->get(route('documents.index'))
            ->assertOk();

        $response->assertSee('<option value="PT BRAJA">', false)
            ->assertSee('<option value="PT BUMM">', false)
            ->assertSee('<option value="PT KWS">', false)
            ->assertDontSee('<option value="PT OLD INACTIVE">', false)
            ->assertSee('<option value="Retooling">', false)
            ->assertSee('<option value="Laporan Hasil Produksi - Axle Assy">', false)
            ->assertSee('<option value="Laporan Hasil Produksi - Brake Assy">', false);
    }

    public function test_pt_filter_returns_only_matching_active_documents_in_page_and_live_search(): void
    {
        $this->customType('PT BUMM');
        $this->customType('PT BRAJA');

        $matching = $this->scannedDocument('Inspeksi PT BUMM', 'PT BUMM');
        $this->scannedDocument('Inspeksi PT BRAJA', 'PT BRAJA');
        $this->scannedDocument('Arsip PT BUMM', 'PT BUMM', false);

        $this->actingAs($this->employee)
            ->get(route('documents.index', ['document_type' => 'PT BUMM']))
            ->assertOk()
            ->assertSee('Inspeksi PT BUMM')
            ->assertDontSee('Inspeksi PT BRAJA')
            ->assertDontSee('Arsip PT BUMM');

        $this->getJson(route('documents.search.live', ['document_type' => 'PT BUMM']))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.id', $matching->id)
            ->assertJsonPath('results.0.scan_type', 'PT BUMM')
            ->assertJsonPath('results.0.qc_type_label', 'PT BUMM');
    }

    public function test_inactive_custom_type_with_active_document_remains_filterable(): void
    {
        $this->customType('PT HMMI', false);
        $matching = $this->scannedDocument('Dokumen historis HMMI', 'PT HMMI');

        $this->actingAs($this->employee)
            ->get(route('documents.index'))
            ->assertOk()
            ->assertSee('<option value="PT HMMI">', false);

        $this->get(route('documents.index', ['document_type' => 'PT HMMI']))
            ->assertOk()
            ->assertSee('Dokumen historis HMMI');

        $this->getJson(route('documents.search.live', ['document_type' => 'PT HMMI']))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('results.0.id', $matching->id);
    }

    public function test_inactive_type_used_only_by_archived_documents_is_not_in_active_library_filter(): void
    {
        $this->customType('PT ARCHIVED ONLY', false);
        $this->scannedDocument('Arsip khusus', 'PT ARCHIVED ONLY', false);

        $this->actingAs($this->employee)
            ->get(route('documents.index'))
            ->assertOk()
            ->assertDontSee('<option value="PT ARCHIVED ONLY">', false);

        $this->getJson(route('documents.search.live', [
            'document_type' => 'PT ARCHIVED ONLY',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('document_type');
    }

    public function test_unknown_document_type_is_rejected_by_normal_and_live_search(): void
    {
        $this->actingAs($this->employee)
            ->get(route('documents.index', ['document_type' => 'PT UNKNOWN']))
            ->assertRedirect()
            ->assertSessionHasErrors('document_type');

        $this->getJson(route('documents.search.live', [
            'document_type' => 'PT UNKNOWN',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('document_type');
    }

    public function test_existing_system_type_filters_keep_working(): void
    {
        $matching = $this->scannedDocument('Instruksi perubahan line', 'Retooling');
        $this->scannedDocument('Laporan brake test', 'Laporan Hasil Produksi - Brake Assy');

        $this->actingAs($this->employee)
            ->get(route('documents.index', ['document_type' => 'Retooling']))
            ->assertOk()
            ->assertSee('Instruksi perubahan line')
            ->assertDontSee('Laporan brake test');

        $this->getJson(route('documents.search.live', ['document_type' => 'Retooling']))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('results.0.id', $matching->id);
    }
}
