<?php

namespace Tests\Feature;

use App\Services\DocxPreviewConverter;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Tests\TestCase;

class DocxPreviewTest extends TestCase
{
    public function test_docx_preview_rejects_missing_document_without_modifying_storage(): void
    {
        $missing = storage_path(
            'framework/testing/docx-preview-missing-' . bin2hex(random_bytes(8)) . '.docx'
        );

        $this->expectException(RuntimeException::class);

        app(DocxPreviewConverter::class)->convert($missing);
    }

    public function test_docx_preview_rejects_disguised_text_file(): void
    {
        $path = storage_path(
            'framework/testing/docx-preview-invalid-' . bin2hex(random_bytes(8)) . '.docx'
        );

        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'This is plain text, not DOCX.');

        try {
            $this->expectException(RuntimeException::class);

            app(DocxPreviewConverter::class)->convert($path);
        } finally {
            File::delete($path);
        }
    }

    public function test_existing_open_route_is_reused_for_docx_preview(): void
    {
        $route = app('router')->getRoutes()->getByName('documents.open');

        $this->assertNotNull($route);
        $this->assertSame('GET', $route->methods()[0]);
        $this->assertStringContainsString(
            'DocumentController@open',
            $route->getActionName()
        );
    }
}
