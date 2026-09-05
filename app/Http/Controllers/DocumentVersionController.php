<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDocumentAnalytics;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\DocumentAnalytics;
use App\Models\DocumentVersion;
use App\Services\DocumentUploadSecurityService;
use App\Services\PrivateDocumentStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Throwable;

class DocumentVersionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | REVISION HISTORY
    |--------------------------------------------------------------------------
    */

    public function index(
        Document $document
    ): View {

        $this->ensureRevisionHistoryAccess(
            $document
        );


        $document->load([
            'category.department',
            'uploader',
        ]);


        $versions = $document
            ->versions()
            ->with('uploader')
            ->orderByDesc(
                'revision_number'
            )
            ->paginate(20);


        /*
        |--------------------------------------------------------------------------
        | Analytics Status per Revision
        |--------------------------------------------------------------------------
        |
        | Satu query untuk seluruh version pada halaman ini.
        | Tidak melakukan query analytics berulang dari Blade.
        |
        */

        $versionIds =
            $versions
                ->getCollection()
                ->pluck('id');

        $analyticsByVersionId =
            $versionIds->isEmpty()
                ? collect()
                : DocumentAnalytics::query()
                    ->whereIn(
                        'document_version_id',
                        $versionIds
                    )
                    ->get()
                    ->keyBy(
                        'document_version_id'
                    );


        return view(
            'documents.revisions.index',
            compact(
                'document',
                'versions',
                'analyticsByVersionId'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | UPLOAD REVISION FORM
    |--------------------------------------------------------------------------
    */

    public function create(
        Document $document
    ): View {

        $this->ensureAdmin();

        $this->ensureActiveDocument(
            $document
        );


        $document->load([
            'category.department',
            'uploader',
        ]);


        return view(
            'documents.revisions.create',
            compact('document')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STORE NEW REVISION
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        Document $document,
        DocumentUploadSecurityService $uploadSecurity,
        PrivateDocumentStorageService $privateStorage
    ): RedirectResponse {

        $this->ensureAdmin();

        $this->ensureActiveDocument(
            $document
        );


        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'max:20480',
                'mimes:pdf,xls,xlsx,doc,docx,csv,txt',
            ],

            'revision_note' => [
                'required',
                'string',
                'min:3',
                'max:1000',
            ],
        ]);


        $file = $request->file('file');


        if (
            $file === null
            || ! $file->isValid()
        ) {

            return back()
                ->withInput()
                ->withErrors([
                    'file' =>
                        'File revisi yang diunggah tidak valid.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | File Security Inspection
        |--------------------------------------------------------------------------
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


        $storedPath = null;


        try {

            $version = DB::transaction(
                function () use (
                    $request,
                    $document,
                    $validated,
                    $file,
                    $extension,
                    $safeOriginalName,
                    $serverMime,
                    $verifiedFileSize,
                    $security,
                    $privateStorage,
                    &$storedPath
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Lock Document
                    |--------------------------------------------------------------------------
                    |
                    | Mencegah dua Admin menghasilkan nomor REV yang sama.
                    |
                    */

                    $lockedDocument = Document::query()
                        ->whereKey(
                            $document->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();


                    if (! $lockedDocument->is_active) {

                        throw new \RuntimeException(
                            'Dokumen yang diarsipkan tidak dapat menerima revisi baru.'
                        );
                    }


                    $lockedDocument->load([
                        'category.department',
                    ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Revision Number
                    |--------------------------------------------------------------------------
                    */

                    $previousRevision =
                        (int) $lockedDocument->current_revision;


                    $nextRevision =
                        $previousRevision + 1;


                    /*
                    |--------------------------------------------------------------------------
                    | Storage Directory
                    |--------------------------------------------------------------------------
                    */

                    $departmentSlug = Str::slug(
                        $lockedDocument
                            ->category
                            ?->department
                            ?->name
                        ?? 'unknown-department'
                    );


                    $categorySlug = Str::slug(
                        $lockedDocument
                            ->category
                            ?->slug
                        ?? 'uncategorized'
                    );


                    $revisionFolder =
                        'rev-'
                        . str_pad(
                            (string) $nextRevision,
                            3,
                            '0',
                            STR_PAD_LEFT
                        );


                    $directory = implode(
                        '/',
                        [
                            $departmentSlug,
                            $categorySlug,

                            'document-'
                            . $lockedDocument->id,

                            'revisions',

                            $revisionFolder,
                        ]
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Unique Physical Filename
                    |--------------------------------------------------------------------------
                    */

                    $storedFileName =
                        Str::uuid()->toString()
                        . '.'
                        . $extension;


                    /*
                    |--------------------------------------------------------------------------
                    | Store File
                    |--------------------------------------------------------------------------
                    */

                    $storedPath = $file->storeAs(
                        $directory,
                        $storedFileName,
                        'bmc_documents'
                    );


                    if (! $storedPath) {

                        throw new \RuntimeException(
                            'File revisi tidak dapat disimpan.'
                        );
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
                        throw new \RuntimeException(
                            'File revisi gagal melewati verifikasi penyimpanan private.'
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Metadata Snapshot
                    |--------------------------------------------------------------------------
                    */

                    $metadataSnapshot = [
                        'title' =>
                            $lockedDocument->title,

                        'document_code' =>
                            $lockedDocument->document_code,

                        'part_number' =>
                            $lockedDocument->part_number,

                        'category_id' =>
                            $lockedDocument->category_id,

                        'category_name' =>
                            $lockedDocument
                                ->category
                                ?->name,

                        'department_id' =>
                            $lockedDocument
                                ->category
                                ?->department
                                ?->id,

                        'department_name' =>
                            $lockedDocument
                                ->category
                                ?->department
                                ?->name,

                        'document_date' =>
                            $lockedDocument
                                ->document_date
                                ?->format('Y-m-d'),

                        'description' =>
                            $lockedDocument->description,

                        'keywords' =>
                            $lockedDocument->keywords,
                    ];


                    /*
                    |--------------------------------------------------------------------------
                    | Revision Record
                    |--------------------------------------------------------------------------
                    */

                    $version = DocumentVersion::create([
                        'document_id' =>
                            $lockedDocument->id,

                        'revision_number' =>
                            $nextRevision,

                        'uploaded_by' =>
                            Auth::id(),

                        'file_name' =>
                            $safeOriginalName,

                        'file_path' =>
                            $storedPath,

                        'file_extension' =>
                            $extension,

                        'mime_type' =>
                            $serverMime,

                        'file_size' =>
                            $verifiedFileSize,

                        'revision_note' =>
                            $validated['revision_note'],

                        'metadata_snapshot' =>
                            $metadataSnapshot,
                    ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Update Current Revision
                    |--------------------------------------------------------------------------
                    |
                    | documents.file_* selalu menunjuk revision terbaru.
                    |
                    | Revision lama TIDAK dihapus.
                    |
                    */

                    $lockedDocument->update([
                        'current_revision' =>
                            $nextRevision,

                        'file_name' =>
                            $safeOriginalName,

                        'file_path' =>
                            $storedPath,

                        'file_extension' =>
                            $extension,

                        'mime_type' =>
                            $serverMime,

                        'file_size' =>
                            $verifiedFileSize,
                    ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Audit
                    |--------------------------------------------------------------------------
                    */

                    ActivityLog::create([
                        'user_id' =>
                            Auth::id(),

                        'document_id' =>
                            $lockedDocument->id,

                        'action' =>
                            'upload_document_revision',

                        'description' =>
                            Auth::user()->name
                            . ' mengunggah Revisi '
                            . $nextRevision
                            . ' untuk dokumen "'
                            . $lockedDocument->title
                            . '".',

                        'ip_address' =>
                            $request->ip(),

                        'user_agent' =>
                            $request->userAgent(),

                        'metadata' => [
                            'version_id' =>
                                $version->id,

                            'previous_revision' =>
                                $previousRevision,

                            'new_revision' =>
                                $nextRevision,

                            'revision_label' =>
                                'REV ' . $nextRevision,

                            'revision_note' =>
                                $validated['revision_note'],

                            'previous_file_name' =>
                                $document->file_name,

                            'new_file_name' =>
                                $version->file_name,

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
                        ],
                    ]);


                    return $version;
                }
            );

        } catch (Throwable $exception) {

            /*
            |--------------------------------------------------------------------------
            | Rollback Physical File
            |--------------------------------------------------------------------------
            */

            if ($storedPath) {

                Storage::disk(
                    'bmc_documents'
                )->delete(
                    $storedPath
                );
            }


            report($exception);


            return back()
                ->withInput()
                ->withErrors([
                    'file' =>
                        'Revisi baru tidak dapat disimpan. '
                        . 'Tidak ada revisi sebelumnya yang diubah.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Automatic Spreadsheet Analytics
        |--------------------------------------------------------------------------
        |
        | Revision spreadsheet baru memiliki analytics record sendiri.
        | Analytics revision sebelumnya tidak ditimpa.
        |
        */

        ProcessDocumentAnalytics
            ::dispatchForVersion(
                $version
            );


        return redirect()
            ->route(
                'documents.revisions.index',
                $document
            )
            ->with(
                'success',
                'Revisi ' . (int) $version->revision_number
                . ' berhasil diunggah. '
                . 'Revisi sebelumnya tetap tidak berubah.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | OPEN REVISION
    |--------------------------------------------------------------------------
    */

    public function open(
        Request $request,
        Document $document,
        DocumentVersion $version,
        PrivateDocumentStorageService $privateStorage
    ): BinaryFileResponse {

        $this->ensureRevisionHistoryAccess(
            $document
        );


        $this->ensureVersionBelongsToDocument(
            $document,
            $version
        );


        $absolutePath =
            $privateStorage->resolveExistingFile(
                (string) $version->file_path
            );


        abort_unless(
            $absolutePath !== null,
            404,
            'File revisi tidak ditemukan atau path penyimpanan tidak valid.'
        );


        $this->logRevisionActivity(
            request: $request,
            document: $document,
            version: $version,
            action: 'open_document_revision',
            description:
                Auth::user()->name
                . ' membuka '
                . 'Revisi ' . (int) $version->revision_number
                . ' dari dokumen "'
                . $document->title
                . '".'
        );


        $safeFileName = $this->safeFileName(
            $version
        );


        $previewableExtensions = [
            'pdf',
            'txt',
            'csv',
        ];


        if (
            ! in_array(
                strtolower(
                    (string) $version->file_extension
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
        | Inline Preview
        |--------------------------------------------------------------------------
        */

        $response = response()->file(
            $absolutePath,
            [
                'Content-Type' =>
                    match(
                        strtolower(
                            (string) $version->file_extension
                        )
                    ) {
                        'pdf' =>
                            'application/pdf',

                        'csv' =>
                            'text/csv; charset=UTF-8',

                        'txt' =>
                            'text/plain; charset=UTF-8',

                        default =>
                            'application/octet-stream',
                    },

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


        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $safeFileName
        );


        return $response;
    }


    /*
    |--------------------------------------------------------------------------
    | DOWNLOAD REVISION
    |--------------------------------------------------------------------------
    */

    public function download(
        Request $request,
        Document $document,
        DocumentVersion $version,
        PrivateDocumentStorageService $privateStorage
    ): BinaryFileResponse {

        $this->ensureRevisionHistoryAccess(
            $document
        );


        $this->ensureVersionBelongsToDocument(
            $document,
            $version
        );


        $absolutePath =
            $privateStorage->resolveExistingFile(
                (string) $version->file_path
            );


        abort_unless(
            $absolutePath !== null,
            404,
            'File revisi tidak ditemukan atau path penyimpanan tidak valid.'
        );


        $this->logRevisionActivity(
            request: $request,
            document: $document,
            version: $version,
            action: 'download_document_revision',
            description:
                Auth::user()->name
                . ' mengunduh '
                . 'Revisi ' . (int) $version->revision_number
                . ' dari dokumen "'
                . $document->title
                . '".'
        );


        return response()->download(
            $absolutePath,
            $this->safeFileName(
                $version
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
    | ADMIN AUTHORIZATION
    |--------------------------------------------------------------------------
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
    | ACTIVE DOCUMENT
    |--------------------------------------------------------------------------
    */

    private function ensureActiveDocument(
        Document $document
    ): void {

        abort_unless(
            $document->is_active,
            409,
            'Pulihkan dokumen yang diarsipkan sebelum mengunggah revisi baru.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | REVISION HISTORY ACCESS
    |--------------------------------------------------------------------------
    */

    private function ensureRevisionHistoryAccess(
        Document $document
    ): void {

        /*
         * Semua authenticated user dapat melihat
         * revision history dokumen aktif.
         */

        if ($document->is_active) {
            return;
        }


        /*
         * Archived document hanya Admin / Super Admin.
         */

        $this->ensureAdmin();
    }


    /*
    |--------------------------------------------------------------------------
    | VERSION OWNERSHIP
    |--------------------------------------------------------------------------
    */

    private function ensureVersionBelongsToDocument(
        Document $document,
        DocumentVersion $version
    ): void {

        abort_unless(
            (int) $version->document_id
            === (int) $document->id,
            404
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SAFE FILE NAME
    |--------------------------------------------------------------------------
    */

    private function safeFileName(
        DocumentVersion $version
    ): string {

        $fileName = basename(
            str_replace(
                '\\',
                '/',
                (string) $version->file_name
            )
        );


        if ($fileName !== '') {
            return $fileName;
        }


        $extension = strtolower(
            (string) $version->file_extension
        );


        return 'revision-'
            . $version->revision_number
            . (
                $extension !== ''
                    ? '.' . $extension
                    : ''
            );
    }


    /*
    |--------------------------------------------------------------------------
    | REVISION AUDIT
    |--------------------------------------------------------------------------
    */

    private function logRevisionActivity(
        Request $request,
        Document $document,
        DocumentVersion $version,
        string $action,
        string $description
    ): void {

        ActivityLog::create([
            'user_id' =>
                Auth::id(),

            'document_id' =>
                $document->id,

            'action' =>
                $action,

            'description' =>
                $description,

            'ip_address' =>
                $request->ip(),

            'user_agent' =>
                $request->userAgent(),

            'metadata' => [
                'version_id' =>
                    $version->id,

                'revision_number' =>
                    $version->revision_number,

                'revision_label' =>
                    $version->getRevisionLabel(),

                'file_name' =>
                    $version->file_name,

                'file_extension' =>
                    $version->file_extension,

                'file_size' =>
                    $version->file_size,

                /*
                 * file_path sengaja tidak disimpan.
                 */
            ],
        ]);
    }
}