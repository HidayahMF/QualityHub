<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentScanMetadata;
use App\Models\DocumentRetooling;
use App\Models\DocumentVersion;
use App\Models\LineProduct;
use App\Models\ProductionLine;
use App\Services\MultiPageScanPdfService;
use App\Services\PrivateDocumentStorageService;
use App\Services\ScanImageSecurityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ScanDocumentController extends Controller
{
    private const SCAN_RETOOLING = 'Retooling';
    private const SCAN_AXLE = 'Laporan Hasil Produksi - Axle Assy';
    private const SCAN_BRAKE = 'Laporan Hasil Produksi - Brake Assy';
    private const SCAN_OTHER = 'Lainnya';

    private const BRAKE_PART_PREFIX = 'A 4304200';

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
     * Menyimpan hasil Scan / Capture QC.
     *
     * - 1 foto  -> disimpan sebagai gambar seperti workflow lama.
     * - 2+ foto -> seluruh foto diverifikasi satu per satu lalu digabung server-side
     *              menjadi satu PDF multi-halaman.
     */
    public function store(
        Request $request,
        ScanImageSecurityService $imageSecurity,
        MultiPageScanPdfService $pdfService,
        PrivateDocumentStorageService $privateStorage
    ): RedirectResponse {
        try {
            return $this->storeScan(
                $request,
                $imageSecurity,
                $pdfService,
                $privateStorage
            );
        } catch (ValidationException $exception) {
            throw $exception->redirectTo(route('documents.scan.create'));
        }
    }

    private function storeScan(
        Request $request,
        ScanImageSecurityService $imageSecurity,
        MultiPageScanPdfService $pdfService,
        PrivateDocumentStorageService $privateStorage
    ): RedirectResponse {
        $this->ensureAdmin();

        /*
         * Backward compatibility untuk client lama yang masih mengirim `file`.
         */
        if (! $request->hasFile('files') && $request->hasFile('file')) {
            $legacyFile = $request->file('file');

            if ($legacyFile !== null) {
                $request->files->set('files', [$legacyFile]);
            }
        }

        $maxFiles = max(
            1,
            (int) config('smartfind_scan_security.max_files', 10)
        );
        $maxFileKilobytes = max(
            1,
            (int) floor(
                (int) config(
                    'smartfind_scan_security.max_file_size_bytes',
                    20 * 1024 * 1024
                ) / 1024
            )
        );

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
            'scan_type' => [
                'required',
                'string',
                Rule::in([
                    self::SCAN_RETOOLING,
                    self::SCAN_AXLE,
                    self::SCAN_BRAKE,
                    self::SCAN_OTHER,
                ]),
            ],
            'scan_type_other' => [
                Rule::requiredIf(
                    fn () => $request->input('scan_type') === self::SCAN_OTHER
                ),
                'nullable',
                'string',
                'max:50',
            ],
            'scan_date' => ['required', 'date_format:Y-m-d'],
            'shift' => [
                Rule::requiredIf(
                    fn () => $request->input('scan_type') === self::SCAN_RETOOLING
                ),
                'nullable',
                Rule::in(['1', '2', '3']),
            ],

            'production_line_id' => [
                Rule::requiredIf(
                    fn () => $request->input('scan_type') === self::SCAN_RETOOLING
                ),
                'nullable',
                'integer',
                Rule::exists('production_lines', 'id')->where(
                    fn ($query) => $query->where('is_active', true)
                ),
            ],

            'from_casting_id' => [
                Rule::requiredIf(
                    fn () => $request->input('scan_type') === self::SCAN_RETOOLING
                ),
                'nullable',
                'integer',
                Rule::exists('castings', 'id')->where(
                    fn ($query) => $query->where('is_active', true)
                ),
            ],

            'to_casting_id' => [
                Rule::requiredIf(
                    fn () => $request->input('scan_type') === self::SCAN_RETOOLING
                ),
                'nullable',
                'integer',
                Rule::exists('castings', 'id')->where(
                    fn ($query) => $query->where('is_active', true)
                ),
            ],

            /*
             * Metadata teks lama tetap diterima untuk workflow non-Retooling
             * dan kompatibilitas dokumen lama.
             */
            'line' => ['nullable', 'string', 'max:100'],
            'from_type' => ['nullable', 'string', 'max:150'],
            'to_type' => ['nullable', 'string', 'max:150'],

            'scan_time' => ['nullable', 'date_format:H:i'],

            'axle_assy_type' => [
                Rule::requiredIf(
                    fn () => $request->input('scan_type') === self::SCAN_AXLE
                ),
                'nullable',
                Rule::in(['Module Assy', 'Axle Assy']),
            ],
            'axle_side' => [
                Rule::requiredIf(
                    fn () => $request->input('scan_type') === self::SCAN_AXLE
                ),
                'nullable',
                Rule::in(['LH', 'RH']),
            ],
            'brake_part_variant' => [
                Rule::requiredIf(
                    fn () => $request->input('scan_type') === self::SCAN_BRAKE
                ),
                'nullable',
                Rule::in([
                    '402 / 502',
                    '201 / 301',
                    '202 / 302',
                ]),
            ],

            'capture_source' => [
                'nullable',
                Rule::in(['upload', 'camera', 'mixed']),
            ],

            'files' => [
                'required',
                'array',
                'min:1',
                'max:' . $maxFiles,
            ],
            'files.*' => [
                'required',
                'file',
                'max:' . $maxFileKilobytes,
                'mimes:jpg,jpeg,png,webp,gif,bmp,tif,tiff',
            ],
        ]);

        foreach ([
            'title',
            'file_name',
            'scan_type_other',
            'shift',
            'line',
            'from_type',
            'to_type',
        ] as $field) {
            if (array_key_exists($field, $validated)) {
                $validated[$field] = $this->normalizePlainText(
                    $validated[$field]
                );
            }
        }

        if ($validated['title'] === null) {
            throw ValidationException::withMessages([
                'title' => 'Judul dokumen tidak boleh kosong.',
            ]);
        }

        if ($validated['file_name'] === null) {
            throw ValidationException::withMessages([
                'file_name' => 'Nama file tidak boleh kosong.',
            ]);
        }

        $originalScanType = $validated['scan_type'];

        if ($originalScanType === self::SCAN_OTHER) {
            $otherScanType = $validated['scan_type_other'] ?? null;

            if ($otherScanType === null) {
                throw ValidationException::withMessages([
                    'scan_type_other' => 'Jenis dokumen lainnya tidak boleh kosong.',
                ]);
            }

            $reservedScanTypes = array_map(
                'strtolower',
                [
                    self::SCAN_RETOOLING,
                    self::SCAN_AXLE,
                    self::SCAN_BRAKE,
                    self::SCAN_OTHER,
                ]
            );

            if (in_array(strtolower($otherScanType), $reservedScanTypes, true)) {
                throw ValidationException::withMessages([
                    'scan_type_other' => 'Gunakan pilihan jenis dokumen yang sudah tersedia.',
                ]);
            }

            $validated['scan_type'] = $otherScanType;
        }

        unset($validated['scan_type_other']);

        $isRetooling = $originalScanType === self::SCAN_RETOOLING;
        $isAxle = $originalScanType === self::SCAN_AXLE;
        $isBrake = $originalScanType === self::SCAN_BRAKE;

        $productionDate = ($isAxle || $isBrake)
            ? $validated['scan_date']
            : null;

        if (! $isAxle) {
            $validated['axle_assy_type'] = null;
            $validated['axle_side'] = null;
        }

        if (! $isBrake) {
            $validated['brake_part_variant'] = null;
        }

        if ($isAxle || $isBrake) {
            $validated['shift'] = null;
            $validated['line'] = null;
            $validated['from_type'] = null;
            $validated['to_type'] = null;
            $validated['scan_time'] = null;
        }

        $brakePartPrefix = $isBrake
            ? self::BRAKE_PART_PREFIX
            : null;

        $brakePartNumber = $isBrake
            ? self::BRAKE_PART_PREFIX . ' ' . $validated['brake_part_variant']
            : null;

        if ($isRetooling) {
            $validated['title'] = 'INSTRUKSI PERUBAHAN TYPE ( RETOOLING )';
        } elseif ($isAxle) {
            $validated['title'] = 'LAPORAN HASIL PRODUKSI - AXLE ASSY - '
                . $validated['axle_assy_type']
                . ' - '
                . $validated['axle_side']
                . ' - '
                . $validated['scan_date'];
        } elseif ($isBrake) {
            $validated['title'] = 'LAPORAN HASIL PRODUKSI - BRAKE ASSY - '
                . $brakePartNumber
                . ' - '
                . $validated['scan_date'];
        }

        $captureSource = $validated['capture_source'] ?? 'upload';

        $category = Category::query()
            ->with('department')
            ->where('is_active', true)
            ->whereHas(
                'department',
                fn (Builder $query) => $query->where('is_active', true)
            )
            ->find($validated['category_id']);

        if (! $category) {
            return redirect()->route('documents.scan.create')
                ->withInput()
                ->withErrors([
                    'category_id' => 'Kategori atau departemen yang dipilih sudah tidak aktif.',
                ]);
        }

        /** @var array<int, \Illuminate\Http\UploadedFile> $files */
        $files = array_values(
            array_filter(
                (array) $request->file('files'),
                fn ($file) => $file !== null
            )
        );

        if ($files === []) {
            throw ValidationException::withMessages([
                'files' => 'Minimal satu foto Scan / Capture wajib dipilih.',
            ]);
        }

        $securityResults = [];
        $sourceTotalBytes = 0;

        foreach ($files as $index => $file) {
            if (! $file->isValid()) {
                throw ValidationException::withMessages([
                    "files.{$index}" => 'Salah satu foto yang dipilih tidak valid.',
                ]);
            }

            $security = $imageSecurity->validate($file);
            $securityResults[] = $security;
            $sourceTotalBytes += (int) $security['file_size'];
        }

        $maxTotalSourceBytes = max(
            1,
            (int) config(
                'smartfind_scan_security.max_total_source_bytes',
                60 * 1024 * 1024
            )
        );

        if ($sourceTotalBytes > $maxTotalSourceBytes) {
            throw ValidationException::withMessages([
                'files' => 'Total ukuran seluruh foto terlalu besar. Kurangi jumlah atau ukuran foto.',
            ]);
        }

        $sourceCount = count($files);
        $isMultiPhoto = $sourceCount > 1;
        $generatedPdfPath = null;

        if ($isMultiPhoto) {
            $pdfResult = $pdfService->build(
                $files,
                $securityResults,
                $validated['title']
            );

            $generatedPdfPath = $pdfResult['path'];
            $extension = 'pdf';
            $mimeType = $pdfResult['mime_type'];
            $verifiedFileSize = $pdfResult['file_size'];
            $outputSignature = $pdfResult['signature'];
        } else {
            $singleSecurity = $securityResults[0];
            $extension = $singleSecurity['extension'];
            $mimeType = $singleSecurity['mime_type'];
            $verifiedFileSize = $singleSecurity['file_size'];
            $outputSignature = $singleSecurity['signature'];
        }

        $baseNameSource = ($isRetooling || $isAxle || $isBrake)
            ? $validated['title']
            : $validated['file_name'];

        $safeBaseName = Str::slug((string) $baseNameSource);

        if ($safeBaseName === '') {
            $pdfService->cleanup($generatedPdfPath);

            return redirect()->route('documents.scan.create')
                ->withInput()
                ->withErrors([
                    'file_name' => 'Nama file tidak menghasilkan nama yang valid.',
                ]);
        }

        $displayFileName = $safeBaseName . '.' . $extension;

        $directory = implode('/', [
            Str::slug($category->department->name),
            $category->slug,
            now()->format('Y'),
            now()->format('m'),
            'scans',
        ]);

        $storedFileName = Str::uuid()->toString() . '.' . $extension;
        $storedPath = $directory . '/' . $storedFileName;
        $stored = false;

        try {
            if ($isMultiPhoto) {
                $stream = fopen((string) $generatedPdfPath, 'rb');

                if ($stream === false) {
                    throw new \RuntimeException('PDF temporary tidak dapat dibaca.');
                }

                try {
                    $stored = Storage::disk('bmc_documents')->writeStream(
                        $storedPath,
                        $stream
                    );
                } finally {
                    fclose($stream);
                }
            } else {
                $result = $files[0]->storeAs(
                    $directory,
                    $storedFileName,
                    'bmc_documents'
                );

                $stored = is_string($result) && $result !== '';
                if ($stored) {
                    $storedPath = $result;
                }
            }

            if (! $stored) {
                throw new \RuntimeException('Berkas scan gagal disimpan ke penyimpanan internal.');
            }

            if ($privateStorage->resolveExistingFile($storedPath) === null) {
                Storage::disk('bmc_documents')->delete($storedPath);

                throw ValidationException::withMessages([
                    'files' => 'Berkas scan gagal melewati verifikasi penyimpanan private.',
                ]);
            }

            $sourceImageMetadata = array_map(
                static fn (array $security): array => [
                    'extension' => $security['extension'],
                    'mime_type' => $security['mime_type'],
                    'server_mime' => $security['server_mime'],
                    'file_size' => $security['file_size'],
                    'width' => $security['width'],
                    'height' => $security['height'],
                    'pixels' => $security['pixels'],
                    'signature' => $security['signature'],
                ],
                $securityResults
            );

            $scanSecurityMetadata = [
                'mode' => $isMultiPhoto ? 'multi_photo_pdf' : 'single_image',
                'page_count' => $sourceCount,
                'source_image_count' => $sourceCount,
                'source_total_bytes' => $sourceTotalBytes,
                'output_extension' => $extension,
                'output_mime' => $mimeType,
                'output_size' => $verifiedFileSize,
                'output_signature' => $outputSignature,
                'source_images' => $sourceImageMetadata,
            ];

            $document = DB::transaction(function () use (
                $request,
                $validated,
                $category,
                $displayFileName,
                $extension,
                $storedPath,
                $mimeType,
                $verifiedFileSize,
                $scanSecurityMetadata,
                $brakePartPrefix,
                $brakePartNumber,
                $productionDate,
                $captureSource,
                $isAxle,
                $isBrake,
                $isMultiPhoto,
                $sourceCount
            ) {
                // Resolve names and Cycle Time from active master records.
                $retoolingMaster = null;

                if (($validated['scan_type'] ?? null) === self::SCAN_RETOOLING) {
                    $productionLine = ProductionLine::query()
                        ->whereKey($validated['production_line_id'])
                        ->where('is_active', true)
                        ->first();

                    if (! $productionLine) {
                        throw ValidationException::withMessages([
                            'production_line_id' => 'Line yang dipilih sudah tidak tersedia.',
                        ]);
                    }

                    $fromLineProduct = LineProduct::query()
                        ->with(['casting:id,code,name,is_active'])
                        ->where('production_line_id', $productionLine->id)
                        ->where('casting_id', $validated['from_casting_id'])
                        ->where('is_active', true)
                        ->whereHas(
                            'casting',
                            fn (Builder $query) => $query->where('is_active', true)
                        )
                        ->first();

                    $toLineProduct = LineProduct::query()
                        ->with(['casting:id,code,name,is_active'])
                        ->where('production_line_id', $productionLine->id)
                        ->where('casting_id', $validated['to_casting_id'])
                        ->where('is_active', true)
                        ->whereHas(
                            'casting',
                            fn (Builder $query) => $query->where('is_active', true)
                        )
                        ->first();

                    if (! $fromLineProduct) {
                        throw ValidationException::withMessages([
                            'from_casting_id' => 'Dari Type tidak tersedia pada Line yang dipilih.',
                        ]);
                    }

                    if (! $toLineProduct) {
                        throw ValidationException::withMessages([
                            'to_casting_id' => 'Ke Type tidak tersedia pada Line yang dipilih.',
                        ]);
                    }

                    // Keep legacy metadata for search, detail, and revision history.
                    $validated['line'] = $productionLine->code;
                    $validated['from_type'] = $fromLineProduct->casting->code;
                    $validated['to_type'] = $toLineProduct->casting->code;

                    $retoolingMaster = [
                        'production_line_id' => $productionLine->id,
                        'from_casting_id' => $fromLineProduct->casting_id,
                        'to_casting_id' => $toLineProduct->casting_id,
                        'from_cycle_time' => $fromLineProduct->cycle_time,
                        'to_cycle_time' => $toLineProduct->cycle_time,
                    ];
                }

                $keywords = implode(', ', array_filter([
                    $validated['scan_type'] ?? null,
                    $validated['shift'] ?? null,
                    $validated['line'] ?? null,
                    $validated['from_type'] ?? null,
                    $validated['to_type'] ?? null,
                    $validated['axle_assy_type'] ?? null,
                    $validated['axle_side'] ?? null,
                    $validated['brake_part_variant'] ?? null,
                    $brakePartPrefix,
                    $brakePartNumber,
                    $productionDate,
                    $isAxle ? 'Axle Assy' : null,
                    $isBrake ? 'Brake Assy' : null,
                    $isMultiPhoto ? 'multi photo' : null,
                ]));

                $description = 'Dokumen hasil Scan / Capture QC - '
                    . $validated['scan_type'] . '.';

                if ($isMultiPhoto) {
                    $description .= ' ' . $sourceCount
                        . ' foto digabung menjadi satu PDF.';
                }

                $document = Document::create([
                    'category_id' => $category->id,
                    'uploaded_by' => Auth::id(),
                    'title' => $validated['title'],
                    'document_code' => null,
                    'part_number' => $brakePartNumber,
                    'file_name' => $displayFileName,
                    'file_path' => $storedPath,
                    'file_extension' => $extension,
                    'mime_type' => $mimeType,
                    'file_size' => $verifiedFileSize,
                    'document_date' => $validated['scan_date'],
                    'description' => $description,
                    'keywords' => $keywords,
                    'is_active' => true,
                    'archived_at' => null,
                    'archived_by' => null,
                    'archive_reason' => null,
                    'current_revision' => 0,
                ]);

                $scanMetadata = new DocumentScanMetadata();
                $scanMetadata->forceFill([
                    'document_id' => $document->id,
                    'source_type' => 'scan',
                    'scan_type' => $validated['scan_type'] ?? null,
                    'scan_date' => $validated['scan_date'],
                    'shift' => $validated['shift'] ?? null,
                    'line' => $validated['line'] ?? null,
                    'from_type' => $validated['from_type'] ?? null,
                    'to_type' => $validated['to_type'] ?? null,
                    'scan_time' => $validated['scan_time'] ?? null,
                    'production_date' => $productionDate,
                    'axle_assy_type' => $validated['axle_assy_type'] ?? null,
                    'axle_side' => $validated['axle_side'] ?? null,
                    'brake_part_prefix' => $brakePartPrefix,
                    'brake_part_variant' => $validated['brake_part_variant'] ?? null,
                    'brake_part_number' => $brakePartNumber,
                    'image_format' => $extension,
                    'capture_source' => $captureSource,
                ])->save();

                // Snapshot Cycle Time before master values can change.
                if ($retoolingMaster !== null) {
                    DocumentRetooling::create([
                        'document_id' => $document->id,
                        'shift' => (int) $validated['shift'],
                        'production_line_id' => $retoolingMaster['production_line_id'],
                        'from_casting_id' => $retoolingMaster['from_casting_id'],
                        'to_casting_id' => $retoolingMaster['to_casting_id'],
                        'from_cycle_time' => $retoolingMaster['from_cycle_time'],
                        'to_cycle_time' => $retoolingMaster['to_cycle_time'],
                    ]);
                }

                DocumentVersion::create([
                    'document_id' => $document->id,
                    'revision_number' => 0,
                    'uploaded_by' => Auth::id(),
                    'file_name' => $document->file_name,
                    'file_path' => $document->file_path,
                    'file_extension' => $document->file_extension,
                    'mime_type' => $document->mime_type,
                    'file_size' => $document->file_size,
                    'revision_note' => $isMultiPhoto
                        ? "Hasil Scan / Capture QC ({$sourceCount} foto menjadi 1 PDF)."
                        : 'Hasil Scan / Capture QC.',
                    'metadata_snapshot' => [
                        'title' => $document->title,
                        'category_id' => $category->id,
                        'category_name' => $category->name,
                        'department_id' => $category->department?->id,
                        'department_name' => $category->department?->name,
                        'document_date' => $document->document_date?->format('Y-m-d'),
                        'part_number' => $brakePartNumber,
                        'scan_metadata' => [
                            'scan_type' => $validated['scan_type'] ?? null,
                            'shift' => $validated['shift'] ?? null,
                            'line' => $validated['line'] ?? null,
                            'from_type' => $validated['from_type'] ?? null,
                            'to_type' => $validated['to_type'] ?? null,
                            'scan_time' => $validated['scan_time'] ?? null,
                            'production_date' => $productionDate,
                            'axle_assy_type' => $validated['axle_assy_type'] ?? null,
                            'axle_side' => $validated['axle_side'] ?? null,
                            'brake_part_prefix' => $brakePartPrefix,
                            'brake_part_variant' => $validated['brake_part_variant'] ?? null,
                            'brake_part_number' => $brakePartNumber,
                            'image_format' => $extension,
                            'capture_source' => $captureSource,
                            'photo_count' => $sourceCount,
                        ],
                        'scan_security' => $scanSecurityMetadata,
                    ],
                ]);

                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'document_id' => $document->id,
                    'action' => 'scan_document',
                    'description' => Auth::user()->name
                        . ' menyimpan hasil Scan / Capture QC "'
                        . $document->title
                        . '" sebagai Revisi 0'
                        . ($isMultiPhoto
                            ? " ({$sourceCount} foto menjadi 1 PDF)."
                            : '.'),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'metadata' => [
                        'source_type' => 'scan',
                        'capture_source' => $captureSource,
                        'file_name' => $document->file_name,
                        'image_format' => $extension,
                        'verified_mime' => $mimeType,
                        'photo_count' => $sourceCount,
                        'multi_photo_pdf' => $isMultiPhoto,
                        'scan_type' => $validated['scan_type'] ?? null,
                        'scan_date' => $validated['scan_date'],
                        'production_date' => $productionDate,
                        'shift' => $validated['shift'] ?? null,
                        'line' => $validated['line'] ?? null,
                        'axle_assy_type' => $validated['axle_assy_type'] ?? null,
                        'axle_side' => $validated['axle_side'] ?? null,
                        'brake_part_prefix' => $brakePartPrefix,
                        'brake_part_variant' => $validated['brake_part_variant'] ?? null,
                        'brake_part_number' => $brakePartNumber,
                    ],
                ]);

                return $document;
            });
        } catch (ValidationException $exception) {
            if ($stored) {
                Storage::disk('bmc_documents')->delete($storedPath);
            }

            throw $exception;
        } catch (Throwable $exception) {
            if ($stored) {
                Storage::disk('bmc_documents')->delete($storedPath);
            }

            report($exception);

            return redirect()->route('documents.scan.create')
                ->withInput()
                ->withErrors([
                    'files' => 'Berkas scan tidak dapat disimpan. Proses dibatalkan dengan aman.',
                ]);
        } finally {
            $pdfService->cleanup($generatedPdfPath);
        }

        return redirect()
            ->route('documents.scan.create')
            ->with(
                'success',
                $isMultiPhoto
                    ? "{$sourceCount} foto berhasil digabung menjadi 1 PDF dan disimpan ke BMC QualityHub."
                    : 'Dokumen berhasil disimpan ke BMC QualityHub.'
            )
            ->with('saved_document_id', $document->id)
            ->with('saved_document_title', $document->title);
    }

    private function normalizePlainText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = (string) $value;

        $text = preg_replace(
            '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F\x{202A}-\x{202E}\x{2066}-\x{2069}]/u',
            '',
            $text
        ) ?? '';

        $text = preg_replace('/\s+/u', ' ', $text) ?? '';
        $text = trim($text);

        return $text === '' ? null : $text;
    }

    private function ensureAdmin(): void
    {
        abort_unless(Auth::check() && Auth::user()->isAdmin(), 403);
    }
}
