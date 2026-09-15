<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDocumentAnalytics;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentAnalytics;
use App\Models\DocumentScanMetadata;
use App\Models\DocumentVersion;
use App\Services\DocumentUploadSecurityService;
use App\Services\InputSanitizationService;
use App\Services\PrivateDocumentStorageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Throwable;

class DocumentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | DOCUMENT LIBRARY
    |--------------------------------------------------------------------------
    */

    /**
     * Menampilkan seluruh dokumen aktif.
     *
     * Mendukung:
     * - Smart Search
     * - Category filter
     * - Department filter
     * - File type filter
     * - Date filter
     * - Favorite status per user
     * - Current revision information
     */
    public function index(Request $request): View
    {
        $this->validateSearchFilters($request);

        $query = Document::query()
            ->with([
                'category.department',
                'uploader',
            ])
            ->withExists([
                'favorites as is_favorited' => function ($favoriteQuery) {
                    $favoriteQuery->where(
                        'user_id',
                        Auth::id()
                    );
                },
            ])
            ->where(
                'is_active',
                true
            );

        /*
        |--------------------------------------------------------------------------
        | SmartFind Search & Filters
        |--------------------------------------------------------------------------
        */

        $this->applySearchFilters(
            $query,
            $request
        );

        /*
        |--------------------------------------------------------------------------
        | Results
        |--------------------------------------------------------------------------
        */

        $documents = $query
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Scan / Production Metadata For Current Page
        |--------------------------------------------------------------------------
        |
        | Metadata scan diambil dalam satu query tambahan (bukan query per baris),
        | sehingga Pustaka Dokumen dapat menampilkan Retooling, Axle Assy, dan
        | Brake Assy dengan tetap ringan.
        |
        */

        $pageDocumentIds = $documents
            ->getCollection()
            ->pluck('id');

        $scanMetadataByDocument = $pageDocumentIds->isEmpty()
            ? collect()
            : DocumentScanMetadata::query()
                ->whereIn('document_id', $pageDocumentIds)
                ->get()
                ->keyBy('document_id');

        /*
        |--------------------------------------------------------------------------
        | Filter Options
        |--------------------------------------------------------------------------
        */

        $categories = Category::query()
            ->with('department')
            ->where('is_active', true)
            ->whereHas(
                'department',
                fn (Builder $query) =>
                $query->where(
                    'is_active',
                    true
                )
            )
            ->orderBy('name')
            ->get();

        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $extensions = Document::query()
            ->where('is_active', true)
            ->whereNotNull('file_extension')
            ->where('file_extension', '!=', '')
            ->distinct()
            ->orderBy('file_extension')
            ->pluck('file_extension');

        $scanTypes = [
            'Retooling' =>
                'Instruksi Perubahan Type / Retooling',

            'Laporan Hasil Produksi - Axle Assy' =>
                'Laporan Hasil Produksi - Axle Assy',

            'Laporan Hasil Produksi - Brake Assy' =>
                'Laporan Hasil Produksi - Brake Assy',
        ];

        return view(
            'documents.index',
            compact(
                'documents',
                'categories',
                'departments',
                'extensions',
                'scanMetadataByDocument',
                'scanTypes'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE DOCUMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Menampilkan halaman tambah dokumen.
     */
    public function create(): View
    {
        $this->ensureAdmin();

        $categories = Category::query()
            ->with('department')
            ->where('is_active', true)
            ->whereHas(
                'department',
                fn (Builder $query) =>
                $query->where(
                    'is_active',
                    true
                )
            )
            ->orderBy('name')
            ->get();

        return view(
            'documents.create',
            compact('categories')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STORE DOCUMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Menyimpan dokumen baru ke private storage.
     *
     * Tahap 15:
     * Setiap dokumen baru otomatis menjadi REV 0.
     */
    public function store(
        Request $request,
        DocumentUploadSecurityService $uploadSecurity,
        PrivateDocumentStorageService $privateStorage,
        InputSanitizationService $input
    ): RedirectResponse
    {
        $this->ensureAdmin();

        /*
        |--------------------------------------------------------------------------
        | Plain-text Input Normalization
        |--------------------------------------------------------------------------
        |
        | Browser / DevTools bukan trust boundary. Semua metadata teks
        | dinormalisasi di server sebelum Laravel validation dijalankan.
        |
        */

        $request->merge([
            'title' =>
                $input->singleLine(
                    $request->input('title')
                ),

            'document_code' =>
                $input->singleLine(
                    $request->input('document_code')
                ),

            'part_number' =>
                $input->singleLine(
                    $request->input('part_number')
                ),

            'description' =>
                $input->multiline(
                    $request->input('description')
                ),

            'keywords' =>
                $input->singleLine(
                    $request->input('keywords')
                ),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'category_id' => [
                'required',
                'integer',

                Rule::exists(
                    'categories',
                    'id'
                )->where(
                    fn ($query) =>
                    $query->where(
                        'is_active',
                        true
                    )
                ),
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'document_code' => [
                'nullable',
                'string',
                'max:100',
            ],

            'part_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'document_date' => [
                'nullable',
                'date',
            ],

            'description' => [
                'nullable',
                'string',
                'max:3000',
            ],

            'keywords' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'file' => [
                'required',
                'file',

                // Maksimum 20 MB
                'max:20480',

                'mimes:pdf,xls,xlsx,doc,docx,csv,txt',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Category Validation
        |--------------------------------------------------------------------------
        */

        $category = Category::query()
            ->with('department')
            ->where('is_active', true)
            ->whereHas(
                'department',
                fn (Builder $query) =>
                $query->where(
                    'is_active',
                    true
                )
            )
            ->find(
                $validated['category_id']
            );

        if (! $category) {
            return back()
                ->withInput()
                ->withErrors([
                    'category_id' =>
                        'Kategori atau departemen yang dipilih sudah tidak aktif.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Uploaded File
        |--------------------------------------------------------------------------
        */

        $file = $request->file('file');

        if (
            $file === null
            || ! $file->isValid()
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'file' =>
                        'Dokumen yang diunggah tidak valid.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | File Security Inspection
        |--------------------------------------------------------------------------
        |
        | Laravel mimes validation tetap dipertahankan sebagai first layer.
        | Service ini menambahkan server MIME + magic bytes + structure check.
        |
        */

        $security =
            $uploadSecurity->validate(
                $file
            );

        $extension =
            $security['extension'];

        $safeOriginalName =
            $security['original_name'];

        $serverMime =
            $security['server_mime'];

        $verifiedFileSize =
            $security['file_size'];


        /*
        |--------------------------------------------------------------------------
        | Storage Directory
        |--------------------------------------------------------------------------
        |
        | Contoh:
        |
        | quality-control/
        | final-inspection/
        | 2026/
        | 08/
        |
        */

        $departmentSlug = Str::slug(
            $category->department->name
        );

        $categorySlug =
            $category->slug;

        $year =
            now()->format('Y');

        $month =
            now()->format('m');

        $directory = implode(
            '/',
            [
                $departmentSlug,
                $categorySlug,
                $year,
                $month,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Physical Filename
        |--------------------------------------------------------------------------
        |
        | Nama asli tidak digunakan sebagai nama file fisik.
        |
        | Contoh:
        |
        | 15b8ce2f-....pdf
        |
        */

        $storedFileName =
            Str::uuid()->toString()
            . '.'
            . $extension;

        /*
        |--------------------------------------------------------------------------
        | Private Storage
        |--------------------------------------------------------------------------
        */

        $storedPath = $file->storeAs(
            $directory,
            $storedFileName,
            'bmc_documents'
        );

        if (! $storedPath) {
            return back()
                ->withInput()
                ->withErrors([
                    'file' =>
                        'Dokumen gagal disimpan ke penyimpanan internal.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Private Storage Boundary Verification
        |--------------------------------------------------------------------------
        */

        if (
            $privateStorage->resolveExistingFile(
                $storedPath
            ) === null
        ) {

            Storage::disk(
                'bmc_documents'
            )->delete(
                $storedPath
            );

            return back()
                ->withInput()
                ->withErrors([
                    'file' =>
                        'Dokumen gagal melewati verifikasi penyimpanan private.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Database Transaction
        |--------------------------------------------------------------------------
        |
        | Dalam transaksi yang sama:
        |
        | 1. Document dibuat.
        | 2. DocumentVersion REV 0 dibuat.
        | 3. Activity Log dibuat.
        |
        | Jika salah satunya gagal:
        |
        | - seluruh database transaction rollback
        | - file fisik yang baru disimpan juga dihapus
        |
        */

        try {

            $document = DB::transaction(
                function () use (
                    $request,
                    $validated,
                    $category,
                    $file,
                    $extension,
                    $safeOriginalName,
                    $serverMime,
                    $verifiedFileSize,
                    $security,
                    $storedPath
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Main Document
                    |--------------------------------------------------------------------------
                    */

                    $document = Document::create([
                        'category_id' =>
                            $category->id,

                        'uploaded_by' =>
                            Auth::id(),

                        'title' =>
                            $validated['title'],

                        'document_code' =>
                            $validated['document_code']
                            ?? null,

                        'part_number' =>
                            $validated['part_number']
                            ?? null,

                        /*
                        |--------------------------------------------------------------------------
                        | Current Original Filename
                        |--------------------------------------------------------------------------
                        */

                        'file_name' =>
                            $safeOriginalName,

                        /*
                        |--------------------------------------------------------------------------
                        | Current Internal Storage Path
                        |--------------------------------------------------------------------------
                        */

                        'file_path' =>
                            $storedPath,

                        'file_extension' =>
                            $extension,

                        'mime_type' =>
                            $serverMime,

                        'file_size' =>
                            $verifiedFileSize,

                        'document_date' =>
                            $validated['document_date']
                            ?? null,

                        'description' =>
                            $validated['description']
                            ?? null,

                        'keywords' =>
                            $validated['keywords']
                            ?? null,

                        'is_active' =>
                            true,

                        /*
                        |--------------------------------------------------------------------------
                        | Archive State
                        |--------------------------------------------------------------------------
                        */

                        'archived_at' =>
                            null,

                        'archived_by' =>
                            null,

                        'archive_reason' =>
                            null,

                        /*
                        |--------------------------------------------------------------------------
                        | Initial Revision Pointer
                        |--------------------------------------------------------------------------
                        */

                        'current_revision' =>
                            0,
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Initial Revision — REV 0
                    |--------------------------------------------------------------------------
                    |
                    | File awal selalu dicatat sebagai REV 0.
                    |
                    | record documents.file_* dan document_versions REV 0
                    | menunjuk file fisik yang sama.
                    |
                    */

                    DocumentVersion::create([
                        'document_id' =>
                            $document->id,

                        'revision_number' =>
                            0,

                        'uploaded_by' =>
                            Auth::id(),

                        'file_name' =>
                            $document->file_name,

                        'file_path' =>
                            $document->file_path,

                        'file_extension' =>
                            $document->file_extension,

                        'mime_type' =>
                            $document->mime_type,

                        'file_size' =>
                            $document->file_size,

                        'revision_note' =>
                            'Versi awal dokumen.',

                        /*
                        |--------------------------------------------------------------------------
                        | Metadata Snapshot
                        |--------------------------------------------------------------------------
                        */

                        'metadata_snapshot' => [
                            'title' =>
                                $document->title,

                            'document_code' =>
                                $document->document_code,

                            'part_number' =>
                                $document->part_number,

                            'category_id' =>
                                $document->category_id,

                            'category_name' =>
                                $category->name,

                            'department_id' =>
                                $category->department?->id,

                            'department_name' =>
                                $category->department?->name,

                            'document_date' =>
                                $document->document_date
                                    ?->format('Y-m-d'),

                            'description' =>
                                $document->description,

                            'keywords' =>
                                $document->keywords,

                            'is_active' =>
                                true,
                        ],
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Audit Log
                    |--------------------------------------------------------------------------
                    */

                    $this->logActivity(
                        request: $request,
                        document: $document,
                        action: 'upload_document',
                        description:
                            Auth::user()->name
                            . ' mengunggah dokumen "'
                            . $document->title
                            . '" sebagai Revisi 0.',
                        additionalMetadata: [
                            'revision_number' =>
                                0,

                            'revision_label' =>
                                'REV 0',

                            'initial_revision_created' =>
                                true,

                            'security_validation' => [
                                'extension' =>
                                    $security['extension'],

                                'server_mime' =>
                                    $security['server_mime'],

                                'mime_status' =>
                                    $security['mime_status'],

                                'signature' =>
                                    $security['signature'],

                                'structure' =>
                                    $security['structure'],

                                'macro_check' =>
                                    $security['macro_check'],
                            ],
                        ]
                    );

                    return $document;
                }
            );

        } catch (Throwable $exception) {

            /*
            |--------------------------------------------------------------------------
            | Delete Orphan Physical File
            |--------------------------------------------------------------------------
            */

            Storage::disk(
                'bmc_documents'
            )->delete(
                $storedPath
            );

            report($exception);

            return back()
                ->withInput()
                ->withErrors([
                    'file' =>
                        'Dokumen tidak dapat disimpan. '
                        . 'File yang diunggah telah dibatalkan dengan aman.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Automatic Spreadsheet Analytics
        |--------------------------------------------------------------------------
        |
        | REV 0 spreadsheet otomatis diproses.
        | PDF / Word / TXT akan dilewati tanpa membuat analytics.
        |
        */

        $initialVersion =
            DocumentVersion::query()
                ->where(
                    'document_id',
                    $document->id
                )
                ->where(
                    'revision_number',
                    0
                )
                ->first();

        if ($initialVersion !== null) {

            ProcessDocumentAnalytics
                ::dispatchForVersion(
                    $initialVersion
                );
        }


        return redirect()
            ->route(
                'documents.show',
                $document
            )
            ->with(
                'success',
                'Dokumen berhasil ditambahkan ke BMC QualityHub sebagai Revisi 0.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | ARCHIVED DOCUMENT LIBRARY
    |--------------------------------------------------------------------------
    */

    /**
     * Menampilkan seluruh dokumen yang telah diarsipkan.
     */
    public function archived(Request $request): View
    {
        $this->ensureAdmin();

        $input =
            app(InputSanitizationService::class);

        $request->merge([
            'search' =>
                $input->search(
                    $request->input('search')
                ),
        ]);

        $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:160',
            ],

            'category' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            'department' => [
                'nullable',
                'integer',
                'exists:departments,id',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Archived Documents
        |--------------------------------------------------------------------------
        */

        $query = Document::query()
            ->with([
                'category.department',
                'uploader',
                'archivedBy',
            ])
            ->where(
                'is_active',
                false
            );

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search =
                $input->escapeLike(
                    (string) $request->input('search')
                );

            $query->where(
                function (Builder $query) use ($search) {

                    $query
                        ->where(
                            'title',
                            'like',
                            "%{$search}%"
                        )

                        ->orWhere(
                            'file_name',
                            'like',
                            "%{$search}%"
                        )

                        ->orWhere(
                            'document_code',
                            'like',
                            "%{$search}%"
                        )

                        ->orWhere(
                            'part_number',
                            'like',
                            "%{$search}%"
                        )

                        ->orWhere(
                            'keywords',
                            'like',
                            "%{$search}%"
                        )

                        ->orWhere(
                            'description',
                            'like',
                            "%{$search}%"
                        )

                        ->orWhere(
                            'archive_reason',
                            'like',
                            "%{$search}%"
                        )

                        ->orWhereHas(
                            'category',
                            function (
                                Builder $categoryQuery
                            ) use ($search) {

                                $categoryQuery
                                    ->where(
                                        'name',
                                        'like',
                                        "%{$search}%"
                                    )

                                    ->orWhere(
                                        'slug',
                                        'like',
                                        "%{$search}%"
                                    );
                            }
                        )

                        ->orWhereHas(
                            'category.department',
                            function (
                                Builder $departmentQuery
                            ) use ($search) {

                                $departmentQuery
                                    ->where(
                                        'name',
                                        'like',
                                        "%{$search}%"
                                    )

                                    ->orWhere(
                                        'code',
                                        'like',
                                        "%{$search}%"
                                    );
                            }
                        )

                        ->orWhereHas(
                            'archivedBy',
                            function (
                                Builder $userQuery
                            ) use ($search) {

                                $userQuery->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                );
                            }
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Category Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('category')) {

            $query->where(
                'category_id',
                $request->input('category')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Department Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('department')) {

            $departmentId =
                $request->input('department');

            $query->whereHas(
                'category',
                function (
                    Builder $categoryQuery
                ) use ($departmentId) {

                    $categoryQuery->where(
                        'department_id',
                        $departmentId
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Results
        |--------------------------------------------------------------------------
        */

        $documents = $query
            ->orderByDesc('archived_at')
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Filter Options
        |--------------------------------------------------------------------------
        |
        | Archived library tetap menampilkan category/department
        | inactive karena data historis tetap perlu dapat dicari.
        |
        */

        $categories = Category::query()
            ->with('department')
            ->orderBy('name')
            ->get();

        $departments = Department::query()
            ->orderBy('name')
            ->get();

        return view(
            'documents.archived',
            compact(
                'documents',
                'categories',
                'departments'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | EDIT DOCUMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Menampilkan halaman edit metadata.
     *
     * File fisik tidak diganti.
     *
     * Pergantian file dilakukan melalui Document Versioning.
     */
    public function edit(Document $document): View
    {
        $this->ensureAdmin();

        $document->load([
            'category.department',
            'uploader',
            'archivedBy',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Category Options
        |--------------------------------------------------------------------------
        |
        | Category yang masih aktif + category milik dokumen saat ini.
        |
        | Current category tetap ditampilkan walaupun sudah inactive
        | agar dokumen lama masih dapat dikelola.
        |
        */

        $categories = Category::query()
            ->with('department')
            ->where(
                function (
                    Builder $query
                ) use ($document) {

                    $query
                        ->where(
                            'id',
                            $document->category_id
                        )

                        ->orWhere(
                            function (
                                Builder $activeCategoryQuery
                            ) {

                                $activeCategoryQuery
                                    ->where(
                                        'is_active',
                                        true
                                    )

                                    ->whereHas(
                                        'department',
                                        fn (Builder $departmentQuery) =>
                                        $departmentQuery->where(
                                            'is_active',
                                            true
                                        )
                                    );
                            }
                        );
                }
            )
            ->orderBy('name')
            ->get();

        return view(
            'documents.edit',
            compact(
                'document',
                'categories'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE DOCUMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Update metadata dokumen.
     *
     * Tidak mengubah:
     *
     * - file fisik
     * - file_path
     * - current revision
     * - mime type
     * - ukuran file
     *
     * File revision baru harus melalui DocumentVersionController.
     */
    public function update(
        Request $request,
        Document $document
    ): RedirectResponse {

        $this->ensureAdmin();

        $input =
            app(InputSanitizationService::class);

        $request->merge([
            'title' =>
                $input->singleLine(
                    $request->input('title')
                ),

            'document_code' =>
                $input->singleLine(
                    $request->input('document_code')
                ),

            'part_number' =>
                $input->singleLine(
                    $request->input('part_number')
                ),

            'description' =>
                $input->multiline(
                    $request->input('description')
                ),

            'keywords' =>
                $input->singleLine(
                    $request->input('keywords')
                ),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'category_id' => [
                'required',
                'integer',
                'exists:categories,id',
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'document_code' => [
                'nullable',
                'string',
                'max:100',
            ],

            'part_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'document_date' => [
                'nullable',
                'date',
            ],

            'description' => [
                'nullable',
                'string',
                'max:3000',
            ],

            'keywords' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Selected Category
        |--------------------------------------------------------------------------
        */

        $category = Category::query()
            ->with('department')
            ->findOrFail(
                $validated['category_id']
            );

        /*
        |--------------------------------------------------------------------------
        | Category Assignment Protection
        |--------------------------------------------------------------------------
        |
        | Active document hanya boleh memakai category +
        | department aktif.
        |
        | Archived document boleh mempertahankan category lamanya
        | walaupun category tersebut sudah inactive.
        |
        */

        $categoryChanged =
            (int) $category->id
            !== (int) $document->category_id;

        $selectedCategoryIsActive =
            (bool) $category->is_active;

        $selectedDepartmentIsActive =
            (bool) (
                $category->department?->is_active
                ?? false
            );

        if (
            (
                $document->is_active
                || $categoryChanged
            )
            &&
            (
                ! $selectedCategoryIsActive
                || ! $selectedDepartmentIsActive
            )
        ) {

            return back()
                ->withInput()
                ->withErrors([
                    'category_id' =>
                        'Kategori dan departemen yang dipilih harus dalam status aktif.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Before Snapshot
        |--------------------------------------------------------------------------
        */

        $before = [
            'category_id' =>
                $document->category_id,

            'title' =>
                $document->title,

            'document_code' =>
                $document->document_code,

            'part_number' =>
                $document->part_number,

            'document_date' =>
                $document->document_date
                    ?->format('Y-m-d'),

            'description' =>
                $document->description,

            'keywords' =>
                $document->keywords,
        ];

        try {

            DB::transaction(
                function () use (
                    $request,
                    $document,
                    $validated,
                    $before
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Update Metadata
                    |--------------------------------------------------------------------------
                    */

                    $document->update([
                        'category_id' =>
                            $validated['category_id'],

                        'title' =>
                            $validated['title'],

                        'document_code' =>
                            $validated['document_code']
                            ?? null,

                        'part_number' =>
                            $validated['part_number']
                            ?? null,

                        'document_date' =>
                            $validated['document_date']
                            ?? null,

                        'description' =>
                            $validated['description']
                            ?? null,

                        'keywords' =>
                            $validated['keywords']
                            ?? null,
                    ]);

                    $document->refresh();

                    /*
                    |--------------------------------------------------------------------------
                    | Activity Log
                    |--------------------------------------------------------------------------
                    */

                    $this->logActivity(
                        request: $request,
                        document: $document,
                        action: 'update_document',
                        description:
                            Auth::user()->name
                            . ' memperbarui metadata dokumen "'
                            . $document->title
                            . '".',
                        additionalMetadata: [
                            'before' =>
                                $before,

                            'after' => [
                                'category_id' =>
                                    $document->category_id,

                                'title' =>
                                    $document->title,

                                'document_code' =>
                                    $document->document_code,

                                'part_number' =>
                                    $document->part_number,

                                'document_date' =>
                                    $document->document_date
                                        ?->format('Y-m-d'),

                                'description' =>
                                    $document->description,

                                'keywords' =>
                                    $document->keywords,
                            ],

                            'current_revision_unchanged' =>
                                $document->current_revision,
                        ]
                    );
                }
            );

        } catch (Throwable $exception) {

            report($exception);

            return back()
                ->withInput()
                ->withErrors([
                    'document' =>
                        'Metadata dokumen tidak dapat diperbarui. '
                        . 'Tidak ada perubahan yang disimpan.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        */

        if ($document->is_active) {

            return redirect()
                ->route(
                    'documents.show',
                    $document
                )
                ->with(
                    'success',
                    'Metadata dokumen berhasil diperbarui.'
                );
        }

        return redirect()
            ->route('documents.archived')
            ->with(
                'success',
                'Metadata dokumen yang diarsipkan berhasil diperbarui.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | ARCHIVE DOCUMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Arsipkan dokumen.
     *
     * Tidak melakukan hard delete.
     *
     * File current revision maupun revision lama tetap berada
     * di private storage.
     */
    public function archive(
        Request $request,
        Document $document
    ): RedirectResponse {

        $this->ensureAdmin();

        /*
        |--------------------------------------------------------------------------
        | Already Archived
        |--------------------------------------------------------------------------
        */

        if (! $document->is_active) {

            return redirect()
                ->route('documents.archived')
                ->with(
                    'success',
                    'Dokumen sudah diarsipkan.'
                );
        }

        $input =
            app(InputSanitizationService::class);

        $request->merge([
            'archive_reason' =>
                $input->multiline(
                    $request->input('archive_reason')
                ),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Archive Reason
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'archive_reason' => [
                'required',
                'string',
                'min:3',
                'max:1000',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Archive Transaction
        |--------------------------------------------------------------------------
        */

        try {

            DB::transaction(
                function () use (
                    $request,
                    $document,
                    $validated
                ) {

                    $document->update([
                        'is_active' =>
                            false,

                        'archived_at' =>
                            now(),

                        'archived_by' =>
                            Auth::id(),

                        'archive_reason' =>
                            $validated['archive_reason'],
                    ]);

                    $document->refresh();

                    /*
                    |--------------------------------------------------------------------------
                    | Audit
                    |--------------------------------------------------------------------------
                    */

                    $this->logActivity(
                        request: $request,
                        document: $document,
                        action: 'archive_document',
                        description:
                            Auth::user()->name
                            . ' mengarsipkan dokumen "'
                            . $document->title
                            . '".',
                        additionalMetadata: [
                            'archive_reason' =>
                                $document->archive_reason,

                            'archived_at' =>
                                $document->archived_at
                                    ?->toDateTimeString(),

                            'archived_by_user_id' =>
                                Auth::id(),

                            'physical_file_deleted' =>
                                false,

                            'revision_history_deleted' =>
                                false,

                            'current_revision_at_archive' =>
                                $document->current_revision,
                        ]
                    );
                }
            );

        } catch (Throwable $exception) {

            report($exception);

            return back()
                ->withErrors([
                    'document' =>
                        'Dokumen tidak dapat diarsipkan. '
                        . 'Tidak ada perubahan yang disimpan.',
                ]);
        }

        return redirect()
            ->route('documents.archived')
            ->with(
                'success',
                'Dokumen berhasil diarsipkan. '
                . 'Seluruh revisi dokumen tetap tersimpan dengan aman.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | RESTORE DOCUMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Restore dokumen dari archived library.
     */
    public function restore(
        Request $request,
        Document $document
    ): RedirectResponse {

        $this->ensureAdmin();

        /*
        |--------------------------------------------------------------------------
        | Already Active
        |--------------------------------------------------------------------------
        */

        if ($document->is_active) {

            return redirect()
                ->route(
                    'documents.show',
                    $document
                )
                ->with(
                    'success',
                    'Dokumen sudah dalam status aktif.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Verify Current Physical File
        |--------------------------------------------------------------------------
        |
        | documents.file_path selalu menunjuk current revision.
        |
        | Dokumen tidak direstore bila file current revision hilang.
        |
        */

        $disk = Storage::disk(
            'bmc_documents'
        );

        if (
            ! $disk->exists(
                $document->file_path
            )
        ) {

            return back()
                ->withErrors([
                    'document' =>
                        'Dokumen tidak dapat dipulihkan karena '
                        . 'file revisi saat ini tidak ditemukan di penyimpanan internal.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Verify Category / Department
        |--------------------------------------------------------------------------
        */

        $document->load(
            'category.department'
        );

        if (
            ! $document->category
            || ! $document->category->is_active
            || ! $document->category->department
            || ! $document->category->department->is_active
        ) {

            return redirect()
                ->route(
                    'documents.edit',
                    $document
                )
                ->withErrors([
                    'category_id' =>
                        'Dokumen ini tidak dapat dipulihkan sampai '
                        . 'kategori dan departemen aktif ditetapkan.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Archive Snapshot
        |--------------------------------------------------------------------------
        */

        $archiveSnapshot = [
            'archived_at' =>
                $document->archived_at
                    ?->toDateTimeString(),

            'archived_by' =>
                $document->archived_by,

            'archive_reason' =>
                $document->archive_reason,

            'current_revision' =>
                $document->current_revision,
        ];

        /*
        |--------------------------------------------------------------------------
        | Restore Transaction
        |--------------------------------------------------------------------------
        */

        try {

            DB::transaction(
                function () use (
                    $request,
                    $document,
                    $archiveSnapshot
                ) {

                    $document->update([
                        'is_active' =>
                            true,

                        'archived_at' =>
                            null,

                        'archived_by' =>
                            null,

                        'archive_reason' =>
                            null,
                    ]);

                    $document->refresh();

                    /*
                    |--------------------------------------------------------------------------
                    | Audit
                    |--------------------------------------------------------------------------
                    */

                    $this->logActivity(
                        request: $request,
                        document: $document,
                        action: 'restore_document',
                        description:
                            Auth::user()->name
                            . ' memulihkan dokumen "'
                            . $document->title
                            . '".',
                        additionalMetadata: [
                            'previous_archive' =>
                                $archiveSnapshot,

                            'restored_by_user_id' =>
                                Auth::id(),

                            'restored_at' =>
                                now()->toDateTimeString(),

                            'current_revision_after_restore' =>
                                $document->current_revision,
                        ]
                    );
                }
            );

        } catch (Throwable $exception) {

            report($exception);

            return back()
                ->withErrors([
                    'document' =>
                        'Dokumen tidak dapat dipulihkan. '
                        . 'Tidak ada perubahan yang disimpan.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Favorite + Revision Records
        |--------------------------------------------------------------------------
        |
        | Favorite tidak pernah dihapus saat archive.
        |
        | Revision history juga tidak pernah dihapus.
        |
        */

        return redirect()
            ->route(
                'documents.show',
                $document
            )
            ->with(
                'success',
                'Dokumen berhasil dipulihkan ke pustaka aktif.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | DOCUMENT DETAIL
    |--------------------------------------------------------------------------
    */

    /**
     * Menampilkan detail dokumen aktif.
     */
    public function show(
        Request $request,
        Document $document
    ): View {

        $this->ensureDocumentAvailable(
            $document
        );

        /*
        |--------------------------------------------------------------------------
        | Relations
        |--------------------------------------------------------------------------
        */

        $document->load([
            'category.department',
            'uploader',
            'scanMetadata',
            'axleProduction',
            'brakeProduction',
            'retooling.productionLine',
            'retooling.fromCasting',
            'retooling.toCasting',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Favorite Status
        |--------------------------------------------------------------------------
        */

        $isFavorited = $document
            ->favorites()
            ->where(
                'user_id',
                Auth::id()
            )
            ->exists();

        /*
        |--------------------------------------------------------------------------
        | Spreadsheet Analytics — Revision Aware
        |--------------------------------------------------------------------------
        |
        | Tanpa query ?revision=, dashboard memakai current revision.
        |
        | Contoh historical analytics:
        | /documents/10?revision=0
        |
        | Analytics tetap diikat ke document_version_id sehingga hasil
        | Revisi 0, Revisi 1, dan seterusnya tidak pernah tercampur.
        |
        */

        $supportedSpreadsheetExtensions = [
            'xlsx',
            'xls',
            'csv',
        ];

        $requestedRevision =
            $request->query(
                'revision'
            );

        $currentVersion = DocumentVersion::query()
            ->where(
                'document_id',
                $document->id
            )
            ->where(
                'revision_number',
                (int) $document->current_revision
            )
            ->first();

        $analyticsVersion =
            $currentVersion;

        if (
            $requestedRevision !== null
            && $requestedRevision !== ''
        ) {

            abort_unless(
                filter_var(
                    $requestedRevision,
                    FILTER_VALIDATE_INT
                ) !== false
                && (int) $requestedRevision >= 0,
                404
            );

            $analyticsVersion =
                DocumentVersion::query()
                    ->where(
                        'document_id',
                        $document->id
                    )
                    ->where(
                        'revision_number',
                        (int) $requestedRevision
                    )
                    ->firstOrFail();
        }

        $analyticsRevisionNumber =
            (int) (
                $analyticsVersion?->revision_number
                ?? $document->current_revision
            );

        $isHistoricalAnalytics =
            $analyticsRevisionNumber
            !== (int) $document->current_revision;

        $analyticsExtension = strtolower(
            (string) (
                $analyticsVersion?->file_extension
                ?? $document->file_extension
            )
        );

        $isSpreadsheet = in_array(
            $analyticsExtension,
            $supportedSpreadsheetExtensions,
            true
        );

        $documentAnalytics = null;
        $analyticsCellCount = 0;
        $analyticsCompleteness = null;

        if (
            $isSpreadsheet
            && $analyticsVersion !== null
        ) {

            $documentAnalytics = DocumentAnalytics::query()
                ->with([
                    'sheets',
                ])
                ->where(
                    'document_id',
                    $document->id
                )
                ->where(
                    'document_version_id',
                    $analyticsVersion->id
                )
                ->first();

            if ($documentAnalytics !== null) {

                $analyticsCellCount = (int) $documentAnalytics
                    ->sheets
                    ->sum(
                        fn ($sheet) =>
                            (int) $sheet->row_count
                            * (int) $sheet->column_count
                    );

                if ($analyticsCellCount > 0) {

                    $filledCells = max(
                        0,
                        $analyticsCellCount
                        - (int) $documentAnalytics->blank_cells
                    );

                    $analyticsCompleteness = round(
                        min(
                            100,
                            (
                                $filledCells
                                / $analyticsCellCount
                            ) * 100
                        ),
                        1
                    );
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Activity Log
        |--------------------------------------------------------------------------
        */

        $this->logActivity(
            request: $request,
            document: $document,
            action: 'view_document',
            description:
                Auth::user()->name
                . ' melihat dokumen "'
                . $document->title
                . '" '
                . '('
                . 'Revisi ' . $analyticsRevisionNumber
                . ').'
        );

        return view(
            'documents.show',
            compact(
                'document',
                'isFavorited',
                'isSpreadsheet',
                'currentVersion',
                'analyticsVersion',
                'analyticsRevisionNumber',
                'isHistoricalAnalytics',
                'documentAnalytics',
                'analyticsCellCount',
                'analyticsCompleteness'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SECURE OPEN / PREVIEW
    |--------------------------------------------------------------------------
    */

    /**
     * Membuka / preview current revision dokumen.
     *
     * Physical storage path tidak pernah dikirim
     * langsung kepada browser.
     */
    public function open(
        Request $request,
        Document $document
    ): BinaryFileResponse {

        $this->ensureDocumentAvailable(
            $document
        );

        $disk = Storage::disk(
            'bmc_documents'
        );

        /*
        |--------------------------------------------------------------------------
        | Physical File Check
        |--------------------------------------------------------------------------
        */

        abort_unless(
            $disk->exists(
                $document->file_path
            ),
            404,
            'File dokumen tidak ditemukan di penyimpanan.'
        );

        /*
        |--------------------------------------------------------------------------
        | Audit
        |--------------------------------------------------------------------------
        */

        $this->logActivity(
            request: $request,
            document: $document,
            action: 'open_document',
            description:
                Auth::user()->name
                . ' membuka revisi saat ini '
                . 'Revisi ' . (int) $document->current_revision
                . ' dari dokumen "'
                . $document->title
                . '".'
        );

        $absolutePath = $disk->path(
            $document->file_path
        );

        $safeFileName =
            $this->getSafeDownloadName(
                $document
            );

        /*
        |--------------------------------------------------------------------------
        | Previewable Extensions
        |--------------------------------------------------------------------------
        */

        $previewableExtensions = [
            'jpg',
            'jpeg',
            'png',
            'webp',
            'gif',
            'bmp',
            'tif',
            'tiff',
            'pdf',
            'txt',
            'csv',
        ];

        /*
        |--------------------------------------------------------------------------
        | Non-previewable File
        |--------------------------------------------------------------------------
        |
        | Word / Excel akan dikirim sebagai download.
        |
        */

        if (
            ! in_array(
                strtolower(
                    (string) $document->file_extension
                ),
                $previewableExtensions,
                true
            )
        ) {

            return response()->download(
                $absolutePath,
                $safeFileName,
                [
                    'X-Content-Type-Options' =>
                        'nosniff',

                    'Cache-Control' =>
                        'private, no-store, no-cache, must-revalidate',

                    'Pragma' =>
                        'no-cache',

                    'Expires' =>
                        '0',

                    'Referrer-Policy' =>
                        'no-referrer',
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Inline Response
        |--------------------------------------------------------------------------
        */

        $response = response()->file(
            $absolutePath,
            [
                'Content-Type' =>
                    $document->mime_type
                    ?: 'application/octet-stream',

                'X-Content-Type-Options' =>
                    'nosniff',

                'Cache-Control' =>
                    'private, no-store, no-cache, must-revalidate',

                'Pragma' =>
                    'no-cache',

                'Expires' =>
                    '0',

                'Referrer-Policy' =>
                    'no-referrer',

                'X-Frame-Options' =>
                    'DENY',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Safe Content-Disposition
        |--------------------------------------------------------------------------
        */

        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $safeFileName
        );

        return $response;
    }


    /*
    |--------------------------------------------------------------------------
    | SECURE DOWNLOAD
    |--------------------------------------------------------------------------
    */

    /**
     * Download current revision dokumen secara aman.
     */
    public function download(
        Request $request,
        Document $document
    ): BinaryFileResponse {

        $this->ensureDocumentAvailable(
            $document
        );

        $disk = Storage::disk(
            'bmc_documents'
        );

        /*
        |--------------------------------------------------------------------------
        | Physical File Check
        |--------------------------------------------------------------------------
        */

        abort_unless(
            $disk->exists(
                $document->file_path
            ),
            404,
            'File dokumen tidak ditemukan di penyimpanan.'
        );

        /*
        |--------------------------------------------------------------------------
        | Audit
        |--------------------------------------------------------------------------
        */

        $this->logActivity(
            request: $request,
            document: $document,
            action: 'download_document',
            description:
                Auth::user()->name
                . ' mengunduh revisi saat ini '
                . 'Revisi ' . (int) $document->current_revision
                . ' dari dokumen "'
                . $document->title
                . '".'
        );

        /*
        |--------------------------------------------------------------------------
        | Download
        |--------------------------------------------------------------------------
        */

        return response()->download(
            $disk->path(
                $document->file_path
            ),
            $this->getSafeDownloadName(
                $document
            ),
            [
                'X-Content-Type-Options' =>
                    'nosniff',

                'Cache-Control' =>
                    'private, no-store, no-cache, must-revalidate',

                'Pragma' =>
                    'no-cache',

                'Expires' =>
                    '0',

                'Referrer-Policy' =>
                    'no-referrer',
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | LIVE SMART SEARCH
    |--------------------------------------------------------------------------
    */

    /**
     * Real-time search endpoint.
     */
    public function liveSearch(
        Request $request
    ): JsonResponse {

        $this->validateSearchFilters(
            $request
        );

        $query = Document::query()
            ->with([
                'category.department',
            ])
            ->withExists([
                'favorites as is_favorited' => function ($favoriteQuery) {
                    $favoriteQuery->where(
                        'user_id',
                        Auth::id()
                    );
                },
            ])
            ->where(
                'is_active',
                true
            );

        $this->applySearchFilters(
            $query,
            $request
        );

        $total =
            (clone $query)->count();

        $documents = $query
            ->latest('created_at')
            ->limit(20)
            ->get();

        $documentIds = $documents->pluck('id');

        $scanMetadataByDocument = $documentIds->isEmpty()
            ? collect()
            : DocumentScanMetadata::query()
                ->whereIn('document_id', $documentIds)
                ->get()
                ->keyBy('document_id');

        $results = $documents->map(
            function (Document $document) use ($scanMetadataByDocument) {

                $scanMetadata =
                    $scanMetadataByDocument->get($document->id);

                $scanType =
                    $scanMetadata?->scan_type;

                $qcTypeLabel = match ($scanType) {
                    'Retooling' =>
                        'Instruksi Perubahan',

                    'Laporan Hasil Produksi - Axle Assy' =>
                        'Axle Assy',

                    'Laporan Hasil Produksi - Brake Assy' =>
                        'Brake Assy',

                    default =>
                        null,
                };

                $qcDetail = match ($scanType) {
                    'Retooling' =>
                        implode(
                            ' → ',
                            array_filter([
                                $scanMetadata?->from_type,
                                $scanMetadata?->to_type,
                            ])
                        ) ?: null,

                    'Laporan Hasil Produksi - Axle Assy' =>
                        implode(
                            ' • ',
                            array_filter([
                                $scanMetadata?->axle_assy_type,
                                $scanMetadata?->axle_side,
                            ])
                        ) ?: null,

                    'Laporan Hasil Produksi - Brake Assy' =>
                        $scanMetadata?->brake_part_number
                        ?: implode(
                            ' ',
                            array_filter([
                                $scanMetadata?->brake_part_prefix,
                                $scanMetadata?->brake_part_variant,
                            ])
                        )
                        ?: null,

                    default =>
                        null,
                };

                return [
                    'id' =>
                        $document->id,

                    'title' =>
                        $document->title,

                    'document_code' =>
                        $document->document_code,

                    'part_number' =>
                        $document->part_number,

                    'file_name' =>
                        $document->file_name,

                    'extension' =>
                        strtoupper(
                            $document->file_extension
                            ?? 'FILE'
                        ),

                    'category' =>
                        $document->category?->name,

                    'department' =>
                        $document
                            ->category
                            ?->department
                            ?->name,

                    'date' =>
                        $document->document_date
                            ?->locale('id')->translatedFormat('d M Y'),

                    'scan_type' =>
                        $scanType,

                    'qc_type_label' =>
                        $qcTypeLabel,

                    'qc_detail' =>
                        $qcDetail,

                    'production_date' =>
                        $scanMetadata?->production_date
                            ? \Carbon\Carbon::parse(
                                $scanMetadata->production_date
                            )
                                ->locale('id')
                                ->translatedFormat('d M Y')
                            : null,

                    'current_revision' =>
                        (int) $document->current_revision,

                    'revision_label' =>
                        'Revisi ' . (int) $document->current_revision,

                    'is_favorited' =>
                        (bool) $document->is_favorited,

                    'url' =>
                        route(
                            'documents.show',
                            $document
                        ),
                ];
            }
        );

        return response()->json([
            'count' =>
                $total,

            'shown' =>
                $documents->count(),

            'limited' =>
                $total > 20,

            'results' =>
                $results,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | PRIVATE — SEARCH VALIDATION
    |--------------------------------------------------------------------------
    */

    /**
     * Validation query SmartFind.
     */
    private function validateSearchFilters(
        Request $request
    ): void {

        $input =
            app(InputSanitizationService::class);

        $request->merge([
            'search' =>
                $input->search(
                    $request->input('search')
                ),

            'extension' =>
                (($extension = $input->singleLine(
                    $request->input('extension')
                )) !== null)
                    ? Str::lower($extension)
                    : null,
        ]);

        $dateToRules = [
            'nullable',
            'date',
        ];

        if ($request->filled('date_from')) {
            $dateToRules[] =
                'after_or_equal:date_from';
        }

        $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:160',
            ],

            'category' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            'department' => [
                'nullable',
                'integer',
                'exists:departments,id',
            ],

            'extension' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[A-Za-z0-9]+$/',
            ],

            'document_type' => [
                'nullable',
                'string',
                Rule::in([
                    'Retooling',
                    'Laporan Hasil Produksi - Axle Assy',
                    'Laporan Hasil Produksi - Brake Assy',
                ]),
            ],

            'date_from' => [
                'nullable',
                'date',
            ],

            'date_to' =>
                $dateToRules,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | PRIVATE — SMART SEARCH ENGINE
    |--------------------------------------------------------------------------
    */

    /**
     * Search engine utama.
     *
     * Digunakan oleh:
     *
     * - index()
     * - liveSearch()
     */
    private function applySearchFilters(
        Builder $query,
        Request $request
    ): Builder {

        $search =
            (string) $request->input(
                'search',
                ''
            );

        $likeSearch =
            app(InputSanitizationService::class)
                ->escapeLike($search);

        if ($search !== '') {

            $query->where(
                function (
                    Builder $searchQuery
                ) use ($likeSearch) {

                    $searchQuery
                        ->where(
                            'title',
                            'like',
                            "%{$likeSearch}%"
                        )
                        ->orWhere(
                            'file_name',
                            'like',
                            "%{$likeSearch}%"
                        )
                        ->orWhere(
                            'document_code',
                            'like',
                            "%{$likeSearch}%"
                        )
                        ->orWhere(
                            'part_number',
                            'like',
                            "%{$likeSearch}%"
                        )
                        ->orWhere(
                            'keywords',
                            'like',
                            "%{$likeSearch}%"
                        )
                        ->orWhere(
                            'description',
                            'like',
                            "%{$likeSearch}%"
                        );

                    $searchQuery->orWhereHas(
                        'category',
                        function (
                            Builder $categoryQuery
                        ) use ($likeSearch) {

                            $categoryQuery
                                ->where(
                                    'name',
                                    'like',
                                    "%{$likeSearch}%"
                                )
                                ->orWhere(
                                    'slug',
                                    'like',
                                    "%{$likeSearch}%"
                                );
                        }
                    );

                    $searchQuery->orWhereHas(
                        'category.department',
                        function (
                            Builder $departmentQuery
                        ) use ($likeSearch) {

                            $departmentQuery
                                ->where(
                                    'name',
                                    'like',
                                    "%{$likeSearch}%"
                                )
                                ->orWhere(
                                    'code',
                                    'like',
                                    "%{$likeSearch}%"
                                );
                        }
                    );

                    /*
                    |--------------------------------------------------------------
                    | Scan / Production Metadata Search
                    |--------------------------------------------------------------
                    |
                    | Membuat kata seperti Axle, RH, Module Assy, Brake, 402 / 502,
                    | Retooling, line, atau type perubahan langsung menemukan
                    | dokumen scan yang sesuai.
                    |
                    */

                    $searchQuery->orWhereExists(
                        function ($metadataQuery) use ($likeSearch) {

                            $metadataQuery
                                ->select(DB::raw(1))
                                ->from('document_scan_metadata')
                                ->whereColumn(
                                    'document_scan_metadata.document_id',
                                    'documents.id'
                                )
                                ->where(
                                    function ($scanQuery) use ($likeSearch) {

                                        $fields = [
                                            'scan_type',
                                            'shift',
                                            'line',
                                            'from_type',
                                            'to_type',
                                            'axle_assy_type',
                                            'axle_side',
                                            'brake_part_prefix',
                                            'brake_part_variant',
                                            'brake_part_number',
                                        ];

                                        foreach ($fields as $index => $field) {
                                            $method = $index === 0
                                                ? 'where'
                                                : 'orWhere';

                                            $scanQuery->{$method}(
                                                $field,
                                                'like',
                                                "%{$likeSearch}%"
                                            );
                                        }
                                    }
                                );
                        }
                    );
                }
            );
        }

        if ($request->filled('category')) {
            $query->where(
                'category_id',
                $request->input('category')
            );
        }

        if ($request->filled('department')) {

            $departmentId =
                $request->input('department');

            $query->whereHas(
                'category',
                function (
                    Builder $categoryQuery
                ) use ($departmentId) {

                    $categoryQuery->where(
                        'department_id',
                        $departmentId
                    );
                }
            );
        }

        if ($request->filled('extension')) {
            $query->where(
                'file_extension',
                strtolower(
                    (string) $request->input(
                        'extension'
                    )
                )
            );
        }

        if ($request->filled('document_type')) {

            $documentType =
                (string) $request->input('document_type');

            $query->whereExists(
                function ($metadataQuery) use ($documentType) {

                    $metadataQuery
                        ->select(DB::raw(1))
                        ->from('document_scan_metadata')
                        ->whereColumn(
                            'document_scan_metadata.document_id',
                            'documents.id'
                        )
                        ->where(
                            'document_scan_metadata.scan_type',
                            $documentType
                        );
                }
            );
        }

        if ($request->filled('date_from')) {
            $query->whereDate(
                'document_date',
                '>=',
                $request->input('date_from')
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'document_date',
                '<=',
                $request->input('date_to')
            );
        }

        return $query;
    }


    /*
    |--------------------------------------------------------------------------
    | PRIVATE — DOCUMENT AVAILABILITY
    |--------------------------------------------------------------------------
    */

    /**
     * Active document only.
     *
     * Archived document tidak dapat:
     *
     * - dibuka
     * - dipreview
     * - didownload melalui current document route
     * - tampil sebagai detail aktif
     *
     * Revision history archived document diatur terpisah
     * oleh DocumentVersionController.
     */
    private function ensureDocumentAvailable(
        Document $document
    ): void {

        abort_unless(
            $document->is_active,
            404
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PRIVATE — ADMIN AUTHORIZATION
    |--------------------------------------------------------------------------
    */

    /**
     * Document administration hanya untuk:
     *
     * - admin
     * - super_admin
     */
    private function ensureAdmin(): void
    {
        abort_unless(
            Auth::check()
            && Auth::user()->isAdmin(),
            403
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PRIVATE — SAFE DOWNLOAD NAME
    |--------------------------------------------------------------------------
    */

    /**
     * Membersihkan nama file yang akan dikirim ke browser.
     *
     * Tidak memengaruhi physical filename di storage.
     */
    private function getSafeDownloadName(
        Document $document
    ): string {

        $fileName = basename(
            str_replace(
                '\\',
                '/',
                (string) $document->file_name
            )
        );

        if ($fileName !== '') {
            return $fileName;
        }

        $extension = strtolower(
            (string) $document->file_extension
        );

        return 'document-'
            . $document->id
            . (
                $extension !== ''
                    ? '.' . $extension
                    : ''
            );
    }


    /*
    |--------------------------------------------------------------------------
    | PRIVATE — ACTIVITY LOG
    |--------------------------------------------------------------------------
    */

    /**
     * Audit log untuk aktivitas penting dokumen.
     *
     * $additionalMetadata digunakan oleh:
     *
     * - upload_document
     * - update_document
     * - archive_document
     * - restore_document
     *
     * Revision-specific activity tetap dapat dibuat oleh
     * DocumentVersionController.
     */
    private function logActivity(
        Request $request,
        Document $document,
        string $action,
        string $description,
        array $additionalMetadata = []
    ): void {

        ActivityLog::create([
            /*
            |--------------------------------------------------------------------------
            | Actor
            |--------------------------------------------------------------------------
            */

            'user_id' =>
                Auth::id(),

            /*
            |--------------------------------------------------------------------------
            | Target Document
            |--------------------------------------------------------------------------
            */

            'document_id' =>
                $document->id,

            /*
            |--------------------------------------------------------------------------
            | Action
            |--------------------------------------------------------------------------
            */

            'action' =>
                $action,

            'description' =>
                $description,

            /*
            |--------------------------------------------------------------------------
            | Request Information
            |--------------------------------------------------------------------------
            */

            'ip_address' =>
                $request->ip(),

            'user_agent' =>
                $request->userAgent(),

            /*
            |--------------------------------------------------------------------------
            | Metadata Snapshot
            |--------------------------------------------------------------------------
            |
            | file_path sengaja TIDAK dimasukkan.
            |
            | Lokasi private storage tidak diekspos
            | melalui activity_logs.
            |
            */

            'metadata' => array_merge(
                [
                    'document_id' =>
                        $document->id,

                    'document_title' =>
                        $document->title,

                    'document_code' =>
                        $document->document_code,

                    'part_number' =>
                        $document->part_number,

                    'category_id' =>
                        $document->category_id,

                    'original_file_name' =>
                        $document->file_name,

                    'file_extension' =>
                        $document->file_extension,

                    'mime_type' =>
                        $document->mime_type,

                    'file_size' =>
                        $document->file_size,

                    /*
                    |--------------------------------------------------------------------------
                    | Revision Context
                    |--------------------------------------------------------------------------
                    */

                    'current_revision' =>
                        (int) $document->current_revision,

                    'revision_label' =>
                        'Revisi ' . (int) $document->current_revision,

                    'document_status' =>
                        $document->is_active
                            ? 'active'
                            : 'archived',
                ],
                $additionalMetadata
            ),
        ]);
    }
}