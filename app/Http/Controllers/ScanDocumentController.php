<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentScanMetadata;
use App\Models\DocumentVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class ScanDocumentController extends Controller
{
    /**
     * Menampilkan workflow Scan / Capture QC.
     */
    public function create(): View
    {
        $this->ensureAdmin();

        $categories = Category::query()
            ->with('department')
            ->where('is_active', true)
            ->whereHas(
                'department',
                fn (Builder $query) => $query->where('is_active', true)
            )
            ->orderBy('name')
            ->get();

        return view('documents.scan', compact('categories'));
    }

    /**
     * Menyimpan hasil scan/capture sebagai dokumen baru.
     *
     * File gambar disimpan di private disk dan metadata khusus scan
     * disimpan terpisah dari tabel documents.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(
                    fn ($query) => $query->where('is_active', true)
                ),
            ],
            'title' => ['required', 'string', 'max:255'],
            'file_name' => ['required', 'string', 'max:180'],
            'scan_type' => ['nullable', 'string', 'max:50'],
            'scan_date' => ['required', 'date'],
            'shift' => ['nullable', 'string', 'max:20'],
            'line' => ['nullable', 'string', 'max:100'],
            'from_type' => ['nullable', 'string', 'max:150'],
            'to_type' => ['nullable', 'string', 'max:150'],
            'scan_time' => ['nullable', 'date_format:H:i'],
            'file' => [
                'required',
                'file',
                'max:20480',
                'mimes:jpg,jpeg,png,webp,gif,bmp,tif,tiff',
            ],
        ]);

        $category = Category::query()
            ->with('department')
            ->where('is_active', true)
            ->whereHas(
                'department',
                fn (Builder $query) => $query->where('is_active', true)
            )
            ->find($validated['category_id']);

        if (! $category) {
            return back()
                ->withInput()
                ->withErrors([
                    'category_id' => 'Kategori atau departemen yang dipilih sudah tidak aktif.',
                ]);
        }

        $file = $request->file('file');

        if ($file === null || ! $file->isValid()) {
            return back()
                ->withInput()
                ->withErrors([
                    'file' => 'Foto/berkas gambar yang dipilih tidak valid.',
                ]);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $safeBaseName = Str::slug($validated['file_name']);

        if ($safeBaseName === '') {
            return back()
                ->withInput()
                ->withErrors([
                    'file_name' => 'Nama file tidak menghasilkan nama yang valid.',
                ]);
        }

        $displayFileName = $safeBaseName . '.' . $extension;

        $departmentSlug = Str::slug($category->department->name);
        $categorySlug = $category->slug;
        $year = now()->format('Y');
        $month = now()->format('m');

        $directory = implode('/', [
            $departmentSlug,
            $categorySlug,
            $year,
            $month,
            'scans',
        ]);

        // Nama fisik memakai UUID agar nama asli tidak menjadi path storage.
        $storedFileName = Str::uuid()->toString() . '.' . $extension;

        $storedPath = $file->storeAs(
            $directory,
            $storedFileName,
            'bmc_documents'
        );

        if (! $storedPath) {
            return back()
                ->withInput()
                ->withErrors([
                    'file' => 'Berkas scan gagal disimpan ke penyimpanan internal.',
                ]);
        }

        try {
            $document = DB::transaction(function () use (
                $request,
                $validated,
                $category,
                $displayFileName,
                $extension,
                $storedPath,
                $file
            ) {
                $document = Document::create([
                    'category_id' => $category->id,
                    'uploaded_by' => Auth::id(),
                    'title' => $validated['title'],
                    'document_code' => null,
                    'part_number' => null,
                    'file_name' => $displayFileName,
                    'file_path' => $storedPath,
                    'file_extension' => $extension,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'document_date' => $validated['scan_date'],
                    'description' => 'Dokumen hasil Scan / Capture QC.',
                    'keywords' => implode(', ', array_filter([
                        $validated['scan_type'] ?? null,
                        $validated['shift'] ?? null,
                        $validated['line'] ?? null,
                        $validated['from_type'] ?? null,
                        $validated['to_type'] ?? null,
                    ])),
                    'is_active' => true,
                    'archived_at' => null,
                    'archived_by' => null,
                    'archive_reason' => null,
                    'current_revision' => 0,
                ]);

                DocumentScanMetadata::create([
                    'document_id' => $document->id,
                    'source_type' => 'scan',
                    'scan_type' => $validated['scan_type'] ?? null,
                    'scan_date' => $validated['scan_date'],
                    'shift' => $validated['shift'] ?? null,
                    'line' => $validated['line'] ?? null,
                    'from_type' => $validated['from_type'] ?? null,
                    'to_type' => $validated['to_type'] ?? null,
                    'scan_time' => $validated['scan_time'] ?? null,
                    'image_format' => $extension,
                    'capture_source' => 'upload',
                ]);

                DocumentVersion::create([
                    'document_id' => $document->id,
                    'revision_number' => 0,
                    'uploaded_by' => Auth::id(),
                    'file_name' => $document->file_name,
                    'file_path' => $document->file_path,
                    'file_extension' => $document->file_extension,
                    'mime_type' => $document->mime_type,
                    'file_size' => $document->file_size,
                    'revision_note' => 'Hasil Scan / Capture QC.',
                    'metadata_snapshot' => [
                        'title' => $document->title,
                        'category_id' => $category->id,
                        'category_name' => $category->name,
                        'department_id' => $category->department?->id,
                        'department_name' => $category->department?->name,
                        'document_date' => $document->document_date?->format('Y-m-d'),
                        'scan_metadata' => [
                            'scan_type' => $validated['scan_type'] ?? null,
                            'shift' => $validated['shift'] ?? null,
                            'line' => $validated['line'] ?? null,
                            'from_type' => $validated['from_type'] ?? null,
                            'to_type' => $validated['to_type'] ?? null,
                            'scan_time' => $validated['scan_time'] ?? null,
                            'image_format' => $extension,
                            'capture_source' => 'upload',
                        ],
                    ],
                ]);

                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'document_id' => $document->id,
                    'action' => 'scan_document',
                    'description' => Auth::user()->name
                        . ' menyimpan hasil Scan / Capture QC "'
                        . $document->title
                        . '" sebagai Revisi 0.',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'metadata' => [
                        'source_type' => 'scan',
                        'file_name' => $document->file_name,
                        'image_format' => $extension,
                        'scan_type' => $validated['scan_type'] ?? null,
                        'shift' => $validated['shift'] ?? null,
                        'line' => $validated['line'] ?? null,
                    ],
                ]);

                return $document;
            });
        } catch (Throwable $exception) {
            Storage::disk('bmc_documents')->delete($storedPath);
            report($exception);

            return back()
                ->withInput()
                ->withErrors([
                    'file' => 'Berkas scan tidak dapat disimpan. Proses dibatalkan dengan aman.',
                ]);
        }

        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'Berkas hasil Scan / Capture berhasil disimpan ke BMC QualityHub.');
    }

    private function ensureAdmin(): void
    {
        abort_unless(Auth::check() && Auth::user()->isAdmin(), 403);
    }
}
