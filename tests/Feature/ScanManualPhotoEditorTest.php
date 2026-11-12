<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanManualPhotoEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_scan_form_includes_manual_crop_with_eight_resize_handles(): void
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

        $this->assertStringContainsString('Crop / Potong Manual', $html);
        $this->assertStringContainsString('id="manualCropButton"', $html);
        $this->assertStringContainsString('id="manualCropModal"', $html);
        $this->assertStringContainsString('id="manualCropCanvas"', $html);
        $this->assertStringContainsString('id="manualCropSelection"', $html);
        $this->assertStringContainsString('id="confirmCropButton"', $html);
        $this->assertStringContainsString('id="resetCropButton"', $html);
        $this->assertStringNotContainsString('id="cropRange"', $html);

        foreach (['nw', 'n', 'ne', 'e', 'se', 's', 'sw', 'w'] as $handle) {
            $this->assertStringContainsString('data-crop-handle="'.$handle.'"', $html);
        }

        $this->assertStringContainsString("manualCropStage.addEventListener('pointerdown'", $html);
        $this->assertStringContainsString("manualCropStage.addEventListener('pointermove'", $html);
        $this->assertStringContainsString('renderEditedCanvas(manualCropCanvas)', $html);
        $this->assertStringContainsString('ctx.drawImage(manualCropCanvas, sx, sy, width, height', $html);
        $this->assertStringContainsString('await replaceActivePhotoFromCanvas(result)', $html);
    }

    public function test_photo_queue_shows_page_numbers_and_updates_after_reorder(): void
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

        $this->assertStringContainsString('id="photoQueue"', $html);
        $this->assertStringContainsString('id="activePhotoPosition"', $html);
        $this->assertStringContainsString("title.textContent = 'Foto ' + (index + 1)", $html);
        $this->assertStringContainsString("'Halaman PDF ' + (index + 1) + ' dari ' + count", $html);
        $this->assertStringContainsString("' · PERTAMA'", $html);
        $this->assertStringContainsString("' · TERAKHIR'", $html);
        $this->assertStringContainsString('function movePhoto(index, direction)', $html);
        $this->assertStringContainsString('function updateMultiPhotoState()', $html);
        $this->assertStringContainsString('selectedItems.forEach(item => transfer.items.add(item.file))', $html);
    }

    public function test_manual_crop_modal_uses_responsive_qualityhub_theme_and_body_portal(): void
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

        $this->assertStringContainsString('id="manualCropPanel" class="qh-crop-panel"', $html);
        $this->assertStringContainsString('BMC QUALITYHUB · EDITOR FOTO', $html);
        $this->assertStringContainsString('id="manualCropPhotoName"', $html);
        $this->assertStringContainsString('id="manualCropHelp"', $html);
        $this->assertStringContainsString('body.qh-crop-open', $html);
        $this->assertStringContainsString('.qh-crop-overlay:not(.hidden)', $html);
        $this->assertStringContainsString('z-index: 2147483000', $html);
        $this->assertStringContainsString('max-height: calc(100dvh - 24px)', $html);
        $this->assertStringContainsString('document.body.appendChild(manualCropModal)', $html);
        $this->assertStringContainsString("document.body.classList.add('qh-crop-open')", $html);
        $this->assertStringContainsString("document.body.classList.remove('qh-crop-open')", $html);
        $this->assertStringContainsString('aria-modal="true"', $html);
        $this->assertStringContainsString('Terapkan Crop', $html);
        $this->assertStringContainsString('Pilih Seluruh Foto', $html);
    }

    public function test_employee_cannot_access_scan_editor(): void
    {
        $employee = User::factory()->create([
            'role' => 'user',
            'account_status' => 'active',
        ]);

        $this->actingAs($employee)
            ->get(route('documents.scan.create'))
            ->assertForbidden();
    }
}
