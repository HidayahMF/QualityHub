<?php

namespace Tests\Feature;

use App\Models\DocumentScanType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentScanTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_scan_page_renders_searchable_custom_scan_type_ui(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);

        DocumentScanType::query()->create([
            'name' => 'Surat Pemeriksaan Dimensi',
            'is_active' => true,
            'sort_order' => 500,
        ]);

        $html = $this->actingAs($admin)
            ->get(route('documents.scan.create'))
            ->assertOk()
            ->baseResponse
            ->getContent();

        $this->assertStringContainsString('data-scan-type-combobox', $html);
        $this->assertStringContainsString('Cari atau pilih Jenis Dokumen...', $html);
        $this->assertStringContainsString('Hapus Jenis Dokumen', $html);
        $this->assertStringContainsString('Surat Pemeriksaan Dimensi', $html);
    }

    public function test_scan_type_management_dialog_stays_accessible_with_many_options(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);

        $html = $this->actingAs($superAdmin)
            ->get(route('documents.scan.create'))
            ->assertOk()
            ->baseResponse
            ->getContent();

        // The full-screen dialog must be portalled out of the sidebar/layout
        // and keep its title/close button visible when the result list is long.
        $this->assertStringContainsString('id="scanTypeDeleteModal"', $html);
        $this->assertStringContainsString('aria-labelledby="scanTypeManagerTitle"', $html);
        $this->assertStringContainsString('max-h-[calc(100dvh-24px)]', $html);
        $this->assertStringContainsString('max-h-[45dvh]', $html);
        $this->assertStringContainsString('overscroll-contain', $html);
        $this->assertStringContainsString('document.body.appendChild(scanTypeDeleteModal)', $html);
        $this->assertStringContainsString('qh-scan-type-manage-open', $html);
        $this->assertStringContainsString('scanTypeDeleteReturnFocus.focus()', $html);
        $this->assertStringContainsString("event.key === 'Escape' && scanTypeDeleteModal", $html);

        // Names are inserted as text, not untrusted HTML.
        $this->assertStringContainsString("label.textContent = String(item.label || item.value || '').trim()", $html);
        $this->assertStringContainsString('Nonaktifkan Jenis Dokumen?', $html);
        $this->assertStringContainsString('loadedDeleteScanTypes', $html);
        $this->assertStringContainsString("if (!response.ok || response.redirected)", $html);
    }

    public function test_admin_does_not_see_super_admin_only_deactivation_button(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        $html = $this->actingAs($admin)
            ->get(route('documents.scan.create'))
            ->assertOk()
            ->baseResponse
            ->getContent();

        $this->assertStringContainsString('id="addScanTypeButton"', $html);
        $this->assertStringNotContainsString('id="deleteScanTypeButton"', $html);
    }

    public function test_scan_type_endpoint_can_list_many_active_custom_types(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);

        for ($number = 1; $number <= 30; $number++) {
            DocumentScanType::query()->create([
                'name' => sprintf('Jenis Dokumen Uji %02d', $number),
                'is_active' => true,
                'sort_order' => 500,
            ]);
        }

        $this->actingAs($superAdmin)
            ->getJson(route('documents.scan.types.index'))
            ->assertOk()
            ->assertJsonCount(30, 'results');
    }

    public function test_fresh_scan_page_starts_with_empty_scan_type_and_empty_generated_values(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);

        $html = $this->actingAs($admin)
            ->get(route('documents.scan.create'))
            ->assertOk()
            ->baseResponse
            ->getContent();

        $this->assertStringContainsString('placeholder="Cari atau pilih Jenis Dokumen..."', $html);
        $this->assertStringContainsString('<option value="">', $html);
        $this->assertStringContainsString('name="scan_type"', $html);
        $this->assertStringNotContainsString('old(\'scan_type\', \'Retooling\')', $html);
        $this->assertStringNotContainsString('old(\'scan_type\', "Retooling")', $html);
        $this->assertStringNotContainsString('value="Retooling" selected', $html);
    }

    public function test_backend_rejects_scan_submission_without_scan_type(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);

        $this->actingAs($admin)
            ->from(route('documents.scan.create'))
            ->post(route('documents.scan.store'), [
                'title' => 'Judul Uji',
                'file_name' => 'judul-uji',
                'scan_date' => now()->format('Y-m-d'),
                'category_id' => 1,
                'scan_type' => '',
            ])
            ->assertSessionHasErrors('scan_type');
    }

    public function test_old_scan_type_and_titles_are_preserved_after_validation_failure(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);

        $this->actingAs($admin)
            ->from(route('documents.scan.create'))
            ->withSession(['_token' => 'test'])
            ->post(route('documents.scan.store'), [
                'title' => 'Dokumen Test',
                'file_name' => 'dokumen-test',
                'scan_type' => 'Surat Pemeriksaan Dimensi',
                'scan_date' => now()->format('Y-m-d'),
                'category_id' => '',
            ])
            ->assertRedirect(route('documents.scan.create'));

        $this->assertSame('Surat Pemeriksaan Dimensi', session()->getOldInput()['scan_type'] ?? null);
        $this->assertSame('Dokumen Test', session()->getOldInput()['title'] ?? null);
        $this->assertSame('dokumen-test', session()->getOldInput()['file_name'] ?? null);
    }

    public function test_super_admin_can_deactivate_custom_scan_type_without_hard_delete(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);

        $type = DocumentScanType::query()->create([
            'name' => 'Surat Pemeriksaan Dimensi',
            'is_active' => true,
            'sort_order' => 500,
        ]);

        $response = $this->actingAs($admin)
            ->patch(route('documents.scan.types.deactivate', $type));

        $response->assertRedirect();
        $this->assertFalse((bool) $type->fresh()->is_active);
        $this->assertDatabaseHas('document_scan_types', [
            'id' => $type->id,
            'is_active' => false,
        ]);
    }

    public function test_non_super_admin_cannot_deactivate_custom_scan_type(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        $type = DocumentScanType::query()->create([
            'name' => 'Surat Pemeriksaan Dimensi',
            'is_active' => true,
            'sort_order' => 500,
        ]);

        $this->actingAs($admin)
            ->patch(route('documents.scan.types.deactivate', $type))
            ->assertForbidden();

        $this->assertTrue((bool) $type->fresh()->is_active);
    }

    public function test_store_scan_type_reactivates_inactive_custom_type_after_duplicate_name(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);

        $type = DocumentScanType::query()->create([
            'name' => 'Surat Pemeriksaan Dimensi',
            'is_active' => false,
            'sort_order' => 500,
        ]);

        $this->actingAs($admin)
            ->postJson(route('documents.scan.types.store'), ['name' => 'Surat Pemeriksaan Dimensi'])
            ->assertOk();

        $this->assertTrue((bool) $type->fresh()->is_active);
        $this->assertSame(1, DocumentScanType::query()->where('name', 'Surat Pemeriksaan Dimensi')->count());
    }
}
