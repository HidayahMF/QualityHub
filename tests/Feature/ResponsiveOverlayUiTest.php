<?php

namespace Tests\Feature;

use Tests\TestCase;

class ResponsiveOverlayUiTest extends TestCase
{
    public function test_global_layout_does_not_trap_dialogs_below_sticky_header(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertIsString($layout);
        $this->assertMatchesRegularExpression('/<main\\s+class="qualityhub-content\\s+relative\\s+flex-1/s', $layout);
        $this->assertStringContainsString('qualityhub-topbar sticky top-0 z-30', $layout);

        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertIsString($css);
        $this->assertStringNotContainsString('.qualityhub-content [role="dialog"] > div,', $css);
        $this->assertStringContainsString('.qh-bulk-modal-overlay', $css);
    }

    public function test_shared_confirmation_dialogs_scroll_in_short_viewports(): void
    {
        $modal = file_get_contents(resource_path('views/components/modal.blade.php'));
        $confirm = file_get_contents(resource_path('views/components/confirmation-modal.blade.php'));
        $divisions = file_get_contents(resource_path('views/departments/index.blade.php'));

        $this->assertStringContainsString('class="fixed inset-0 overflow-y-auto', $modal);
        $this->assertStringContainsString("max-h-[calc(100dvh-2rem)] overflow-y-auto", $confirm);
        $this->assertStringContainsString('max-h-[calc(100dvh-24px)]', $divisions);
        $this->assertStringContainsString('overflow-y-auto overscroll-contain', $divisions);
    }

    public function test_scan_type_create_dialog_uses_portal_and_safe_height(): void
    {
        $view = file_get_contents(resource_path('views/documents/scan.blade.php'));

        $this->assertStringContainsString('id="scanTypeCreateTitle"', $view);
        $this->assertStringContainsString('document.body.appendChild(scanTypeModal)', $view);
        $this->assertStringContainsString('qh-scan-type-create-open', $view);
        $this->assertStringContainsString('max-h-[calc(100dvh-24px)]', $view);
        $this->assertStringContainsString('scanTypeCreateReturnFocus.focus()', $view);
        $this->assertStringContainsString("event.key === 'Escape' && scanTypeModal", $view);
    }

    public function test_category_and_live_search_listboxes_are_not_clipped(): void
    {
        $category = file_get_contents(resource_path('js/category-combobox.js'));
        $allDocuments = file_get_contents(resource_path('views/documents/index.blade.php'));

        $this->assertStringContainsString('document.body.appendChild(listbox)', $category);
        $this->assertStringContainsString('listbox.contains(event.target)', $category);
        $this->assertStringContainsString('positionListbox', $category);
        $this->assertStringContainsString('openAbove', $category);

        $this->assertStringContainsString('x-ref="liveSearchPanel"', $allDocuments);
        $this->assertStringContainsString('updateLiveSearchPosition()', $allDocuments);
        $this->assertStringContainsString('bottom-full mb-2', $allDocuments);
        $this->assertStringContainsString('overflow-y-auto overscroll-contain', $allDocuments);
    }
}
