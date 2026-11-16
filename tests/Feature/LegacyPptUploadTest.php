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

class LegacyPptUploadTest extends TestCase
{
    use RefreshDatabase;

    private string $testDirectory;
    private Category $category;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDirectory = storage_path('framework/testing/legacy-ppt-' . bin2hex(random_bytes(8)));
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
     * Small *synthetic* OLE/CFB test fixture, not a renderable Office deck.
     * It exercises header, FAT, directory and required stream gates.
     * A real .ppt from PowerPoint must also pass manual QA before release.
     */
    private function ppt(
        string $name = 'laporan-qc.ppt',
        bool $vba = false,
        string $presentationStream = 'PowerPoint Document',
        bool $currentUser = true,
        bool $brokenFat = false
    ): UploadedFile {
        $end = 0xFFFFFFFE;
        $free = 0xFFFFFFFF;
        $header = str_repeat("\0", 512);

        $header = substr_replace($header, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1", 0, 8);
        $header = substr_replace($header, pack('v', 3), 26, 2);
        $header = substr_replace($header, pack('v', 0xFFFE), 28, 2);
        $header = substr_replace($header, pack('v', 9), 30, 2);
        $header = substr_replace($header, pack('v', 6), 32, 2);
        $header = substr_replace($header, pack('V', 1), 44, 4);   // one FAT sector
        $header = substr_replace($header, pack('V', 0), 48, 4);   // directory sector 0
        $header = substr_replace($header, pack('V', 4096), 56, 4);
        $header = substr_replace($header, pack('V', $end), 60, 4);
        $header = substr_replace($header, pack('V', $end), 68, 4);
        $header = substr_replace($header, str_repeat(pack('V', $free), 109), 76, 436);
        $header = substr_replace($header, pack('V', 10), 76, 4); // FAT sector 10

        $directory = $this->entry('Root Entry', 5, $end, 0)
            . $this->entry($presentationStream, 2, 1, 4096)
            . $this->entry($currentUser ? 'Current User' : 'Other Stream', 2, 9, 32)
            . $this->entry($vba ? '_VBA_PROJECT_CUR' : 'SummaryInformation', 1, $end, 0);

        $data = $header . $directory;
        $data .= str_repeat('P', 4096); // 4096 bytes, 8 sectors
        $data .= str_repeat("\0", 512); // synthetic Current User stream sector

        $fat = str_repeat(pack('V', $free), 128);
        $fat = substr_replace($fat, pack('V', $end), 0, 4);
        for ($sector = 1; $sector <= 8; $sector++) {
            $fat = substr_replace(
                $fat,
                pack('V', $brokenFat && $sector === 4 ? 4 : ($sector === 8 ? $end : $sector + 1)),
                $sector * 4,
                4
            );
        }
        $fat = substr_replace($fat, pack('V', $end), 9 * 4, 4);
        $fat = substr_replace($fat, pack('V', 0xFFFFFFFD), 10 * 4, 4);
        $data .= $fat;

        $path = $this->testDirectory . '/fixture-' . bin2hex(random_bytes(6)) . '.ppt';
        file_put_contents($path, $data);

        return new UploadedFile($path, $name, null, null, true);
    }

    private function entry(string $name, int $type, int $start, int $size): string
    {
        $entry = str_repeat("\0", 128);
        $nameBytes = mb_convert_encoding($name . "\0", 'UTF-16LE', 'UTF-8');
        $entry = substr_replace($entry, $nameBytes, 0, strlen($nameBytes));
        $entry = substr_replace($entry, pack('v', strlen($nameBytes)), 64, 2);
        $entry[66] = chr($type);
        $entry = substr_replace($entry, pack('V', $start), 116, 4);
        $entry = substr_replace($entry, pack('V', $size), 120, 4);

        return $entry;
    }

    private function payload(UploadedFile $file): array
    {
        return [
            'title' => 'Presentasi QC Format Lama',
            'document_code' => 'PPT-QC-2026',
            'document_date' => '2026-11-16',
            'description' => 'Dokumen presentasi QC',
            'category_id' => $this->category->id,
            'file' => $file,
        ];
    }

    public function test_ole_powerpoint_fixture_passes_structure_validation(): void
    {
        $result = app(DocumentUploadSecurityService::class)->validate($this->ppt());

        $this->assertSame('ppt', $result['extension']);
        $this->assertSame('ole_compound', $result['signature']);
        $this->assertSame('ppt', $result['structure']);
        $this->assertSame('passed', $result['macro_check']);
    }

    /**
     * PHP fileinfo/Windows may assign an unexpected MIME to a genuine
     * OLE PowerPoint. Strict CFB verification must remain the deciding gate.
     */
    public function test_windows_unknown_mime_accepts_only_structurally_verified_ppt(): void
    {
        $original = $this->ppt('windows-office.ppt');

        $windowsMimeFile = new class(
            $original->getRealPath(),
            $original->getClientOriginalName(),
            null,
            null,
            true
        ) extends UploadedFile {
            public function getMimeType(): ?string
            {
                return 'application/x-unrecognized-windows-cfb';
            }
        };

        $result = app(DocumentUploadSecurityService::class)
            ->validate($windowsMimeFile);

        $this->assertSame('ppt', $result['extension']);
        $this->assertSame('ole_compound', $result['signature']);
        $this->assertSame(
            'ole_structure_verified_mime_fallback',
            $result['mime_status']
        );

        $this->actingAs($this->admin)
            ->post(route('documents.store'), $this->payload($windowsMimeFile))
            ->assertSessionHasNoErrors();

        $this->assertSame('ppt', Document::query()->sole()->file_extension);
    }

    public function test_unknown_mime_does_not_allow_fake_ppt_without_ole_structure(): void
    {
        $path = $this->testDirectory . '/fake-ole.ppt';
        file_put_contents($path, 'invalid Office file content');

        $spoof = new class($path, 'fake-ole.ppt', null, null, true) extends UploadedFile {
            public function getMimeType(): ?string
            {
                return 'application/x-unrecognized-windows-cfb';
            }
        };

        $this->actingAs($this->admin)
            ->post(route('documents.store'), $this->payload($spoof))
            ->assertRedirect(route('documents.create'))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('documents', 0);
        $this->assertSame([], Storage::disk('bmc_documents')->allFiles());
    }

    public function test_ppt_upload_and_download_retains_original_file_in_private_storage(): void
    {
        $this->actingAs($this->admin)
            ->post(route('documents.store'), $this->payload($this->ppt()))
            ->assertSessionHasNoErrors();

        $document = Document::query()->sole();
        $this->assertSame('ppt', $document->file_extension);
        $this->assertDatabaseHas('document_versions', [
            'document_id' => $document->id,
            'revision_number' => 0,
            'file_extension' => 'ppt',
        ]);
        $this->assertTrue(Storage::disk('bmc_documents')->exists($document->file_path));

        $this->get(route('documents.download', $document))
            ->assertOk()->assertHeader('content-disposition');

        $this->get(route('documents.open', $document))
            ->assertOk()->assertHeader('content-disposition');

        $this->get(route('documents.index', ['extension' => 'ppt']))->assertOk();
    }

    public function test_ppt_revision_keeps_original_file(): void
    {
        $this->actingAs($this->admin)
            ->post(route('documents.store'), $this->payload($this->ppt()))
            ->assertSessionHasNoErrors();

        $document = Document::query()->sole();
        $original = $document->file_path;

        $this->post(route('documents.revisions.store', $document), [
            'file' => $this->ppt('laporan-qc-revisi.ppt'),
            'revision_file_name' => 'laporan-qc-revisi',
            'document_date' => '2026-11-16',
            'revision_note' => 'Penambahan materi presentasi QC',
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, DocumentVersion::query()->count());
        $this->assertDatabaseHas('document_versions', [
            'document_id' => $document->id,
            'revision_number' => 1,
            'file_extension' => 'ppt',
        ]);
        $this->assertTrue(Storage::disk('bmc_documents')->exists($original));
    }

    public function test_ppt_rejects_vba_project_storage(): void
    {
        $this->expectException(ValidationException::class);
        app(DocumentUploadSecurityService::class)->validate($this->ppt(vba: true));
    }

    public function test_ppt_rejects_word_or_excel_streams_masquerading_as_powerpoint(): void
    {
        foreach (['WordDocument', 'Workbook'] as $stream) {
            try {
                app(DocumentUploadSecurityService::class)->validate(
                    $this->ppt(presentationStream: $stream)
                );
                $this->fail('Non-PowerPoint OLE stream must not pass: ' . $stream);
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_ppt_rejects_missing_current_user_stream(): void
    {
        $this->expectException(ValidationException::class);
        app(DocumentUploadSecurityService::class)->validate($this->ppt(currentUser: false));
    }

    public function test_ppt_rejects_corrupt_fat_cycle(): void
    {
        $this->expectException(ValidationException::class);
        app(DocumentUploadSecurityService::class)->validate($this->ppt(brokenFat: true));
    }

    public function test_ppt_rejects_text_with_fake_extension_without_persisting(): void
    {
        $fake = UploadedFile::fake()->createWithContent('fake.ppt', 'not an OLE compound file');

        $this->actingAs($this->admin)
            ->post(route('documents.store'), $this->payload($fake))
            ->assertRedirect(route('documents.create'))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('documents', 0);
        $this->assertSame([], Storage::disk('bmc_documents')->allFiles());
    }

    public function test_ppt_rejects_renamed_file_with_unsupported_extension(): void
    {
        $this->expectException(ValidationException::class);
        app(DocumentUploadSecurityService::class)->validate($this->ppt(name: 'legacy.pptm'));
    }

    public function test_normal_user_cannot_upload_legacy_powerpoint(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'account_status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('documents.store'), $this->payload($this->ppt()))
            ->assertForbidden();

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_upload_and_revision_forms_list_both_powerpoint_formats(): void
    {
        $this->actingAs($this->admin)
            ->get(route('documents.create'))
            ->assertOk()
            ->assertSee('accept=".pdf,.xls,.xlsx,.doc,.docx,.ppt,.pptx,.csv,.txt"', false)
            ->assertSee('PowerPoint (PPT / PPTX)');

        $create = file_get_contents(resource_path('views/documents/create.blade.php'));
        $revision = file_get_contents(resource_path('views/documents/revisions/create.blade.php'));
        $this->assertStringContainsString('PowerPoint (PPT / PPTX)', $create);
        $this->assertStringContainsString('PowerPoint (PPT / PPTX)', $revision);
        $this->assertStringContainsString('.ppt,.pptx', $revision);
    }
}
