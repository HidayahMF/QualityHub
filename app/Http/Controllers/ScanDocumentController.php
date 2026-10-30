<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AxlePosition;
use App\Models\AxleType;
use App\Models\BrakePartNumber;
use App\Models\Casting;
use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentAxleProduction;
use App\Models\DocumentBrakeProduction;
use App\Models\DocumentScanMetadata;
use App\Models\DocumentRetooling;
use App\Models\DocumentScanType;
use App\Models\DocumentVersion;
use App\Models\LineProduct;
use App\Models\ProductionLine;
use App\Services\CategoryAssignmentService;
use App\Services\MultiPageScanPdfService;
use App\Services\PrivateDocumentStorageService;
use App\Services\RetoolingDateRule;
use App\Services\ScanImageSecurityService;
use App\Services\ShiftTimeRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
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

    private static function systemScanTypes(): array
    {
        return [
            self::SCAN_RETOOLING,
            self::SCAN_AXLE,
            self::SCAN_BRAKE,
            self::SCAN_OTHER,
        ];
    }

    private function scanTypeChoices(): array
    {
        $reserved = self::systemScanTypes();
        $customTypes = DocumentScanType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->all();

        return array_values(array_unique([...$reserved, ...$customTypes]));
    }

    private function normalizeScanTypeName(string $value): string
    {
        $normalized = trim((string) $value);

        return preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
    }

    private function isReservedScanType(?string $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $normalized = strtolower($this->normalizeScanTypeName($value));

        return in_array(
            $normalized,
            array_map('strtolower', self::systemScanTypes()),
            true
        );
    }

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

        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $allowCategoryCreation = Auth::user()->isSuperAdmin();
        $shiftTimeRanges = ShiftTimeRule::frontendRanges();
        $documentScanTypes = $this->scanTypeChoices();

        $productionLines = ProductionLine::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $lineProducts = LineProduct::query()
            ->with('casting:id,code,name,is_active')
            ->where('is_active', true)
            ->orderBy('production_line_id')
            ->orderBy('casting_id')
            ->get(['id', 'production_line_id', 'casting_id', 'cycle_time']);

        $castings = Casting::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $axleTypes = AxleType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        $axlePositions = AxlePosition::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        return view(
            'documents.scan',
            compact(
                'categories',
                'departments',
                'allowCategoryCreation',
                'shiftTimeRanges',
                'documentScanTypes',
                'productionLines',
                'lineProducts',
                'castings',
                'axleTypes',
                'axlePositions'
            )
        );
    }

    public function scanTypes(): JsonResponse
    {
        $this->ensureAdmin();

        $customTypes = DocumentScanType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'results' => $customTypes->map(
                fn (DocumentScanType $type): array => [
                    'id' => $type->id,
                    'value' => $type->name,
                    'label' => $type->name,
                ]
            )->all(),
        ]);
    }

    public function storeScanType(Request $request): JsonResponse
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $name = trim((string) $value);

                    if ($name === '') {
                        $fail('Nama jenis dokumen tidak boleh kosong.');

                        return;
                    }

                    if (in_array(strtolower($name), array_map('strtolower', self::systemScanTypes()), true)) {
                        $fail('Gunakan pilihan jenis dokumen yang sudah tersedia.');
                    }
                },
            ],
        ]);

        $name = $this->normalizeScanTypeName((string) $validated['name']);

        if ($name === '') {
            return response()->json([
                'message' => 'Nama jenis dokumen tidak boleh kosong.',
            ], 422);
        }

        $scanType = DocumentScanType::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first();

        if ($scanType) {
            if ((bool) $scanType->is_active) {
                return response()->json([
                    'message' => 'Jenis dokumen ini sudah tersedia.',
                ], 422);
            }

            $scanType->update([
                'is_active' => true,
                'sort_order' => 500,
            ]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'reactivate_scan_type',
                'description' => Auth::user()->name . ' mengaktifkan kembali jenis dokumen scan "' . $scanType->name . '".',
                'metadata' => [
                    'scan_type_id' => $scanType->id,
                    'scan_type_name' => $scanType->name,
                ],
            ]);

            return response()->json([
                'id' => $scanType->id,
                'name' => $scanType->name,
                'is_active' => true,
                'reactivated' => true,
            ]);
        }

        $scanType = DocumentScanType::query()->create([
            'name' => $name,
            'is_active' => true,
            'sort_order' => 500,
            'created_by' => Auth::id(),
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'create_scan_type',
            'description' => Auth::user()->name . ' menambahkan jenis dokumen scan "' . $scanType->name . '".',
            'metadata' => [
                'scan_type_id' => $scanType->id,
                'scan_type_name' => $scanType->name,
            ],
        ]);

        return response()->json([
            'id' => $scanType->id,
            'name' => $scanType->name,
            'is_active' => (bool) $scanType->is_active,
        ]);
    }

    public function deactivateScanType(Request $request, DocumentScanType $documentScanType): RedirectResponse|JsonResponse
    {
        $this->ensureSuperAdmin();

        if ($this->isReservedScanType($documentScanType->name)) {
            return response()->json([
                'message' => 'Jenis dokumen sistem tidak dapat dihapus.',
            ], 422);
        }

        if (! $documentScanType->is_active) {
            return response()->json([
                'id' => $documentScanType->id,
                'name' => $documentScanType->name,
                'is_active' => false,
                'already_inactive' => true,
            ]);
        }

        $documentScanType->update([
            'is_active' => false,
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'deactivate_scan_type',
            'description' => Auth::user()->name . ' menonaktifkan jenis dokumen scan "' . $documentScanType->name . '".',
            'metadata' => [
                'scan_type_id' => $documentScanType->id,
                'scan_type_name' => $documentScanType->name,
            ],
        ]);

        return redirect()->route('documents.scan.create');
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
        PrivateDocumentStorageService $privateStorage,
        CategoryAssignmentService $categoryAssignment
    ): RedirectResponse {
        try {
            return $this->storeScan(
                $request,
                $imageSecurity,
                $pdfService,
                $privateStorage,
                $categoryAssignment
            );
        } catch (ValidationException $exception) {
            throw $exception->redirectTo(route('documents.scan.create'));
        } catch (Throwable $exception) {
            // Jangan biarkan kegagalan library, filesystem, atau database menjadi HTTP 500.
            // Pengguna tetap dikembalikan ke form dan detail teknis dicatat di log.
            report($exception);

            return redirect()
                ->route('documents.scan.create')
                ->withInput()
                ->withErrors([
                    'files' => 'Berkas scan tidak dapat diproses. Periksa konfigurasi penyimpanan dan coba lagi.',
                ]);
        }
    }

    private function storeScan(
        Request $request,
        ScanImageSecurityService $imageSecurity,
        MultiPageScanPdfService $pdfService,
        PrivateDocumentStorageService $privateStorage,
        CategoryAssignmentService $categoryAssignment
    ): RedirectResponse {
        $this->ensureAdmin();

        $categoryAssignment->prepareRequest($request);

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
            ...$categoryAssignment->validationRules($request),
            'title' => ['required', 'string', 'max:255'],
            'file_name' => ['required', 'string', 'max:180'],
            'scan_type' => [
                'required',
                'string',
                'max:120',
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    $name = trim((string) $value);

                    if ($name === '') {
                        $fail('Jenis dokumen scan wajib diisi.');

                        return;
                    }

                    if (strtolower($name) === strtolower(self::SCAN_OTHER)) {
                        return;
                    }

                    if (in_array(strtolower($name), array_map('strtolower', self::systemScanTypes()), true)) {
                        return;
                    }

                    if ($request->input('scan_type') === self::SCAN_OTHER) {
                        $fail('Gunakan pilihan jenis dokumen yang sudah tersedia.');
                    }
                },
            ],
            'scan_type_other' => [
                Rule::requiredIf(
                    fn () => $request->input('scan_type') === self::SCAN_OTHER
                ),
                'nullable',
                'string',
                'max:120',
            ],
            'scan_date' => ['required', 'date_format:Y-m-d'],
            'shift' => [
                'nullable',
                Rule::in(['1', '2', '3']),
            ],

            'production_line_id' => [
                'nullable',
                'integer',
            ],

            'from_casting_id' => [
                'nullable',
                'string',
                'max:80',
            ],

            'to_casting_id' => [
                'nullable',
                'string',
                'max:80',
            ],

            /*
             * Metadata teks lama tetap diterima untuk workflow non-Retooling
             * dan kompatibilitas dokumen lama.
             */
            'line' => ['nullable', 'string', 'max:100'],
            'from_type' => ['nullable', 'string', 'max:150'],
            'to_type' => ['nullable', 'string', 'max:150'],

            'scan_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'axle_type_id' => [
                Rule::requiredIf(
                    fn () => $request->input('scan_type') === self::SCAN_AXLE
                ),
                'nullable',
                'integer',
                Rule::exists('axle_types', 'id')->where(
                    fn ($query) => $query->where('is_active', true)
                ),
            ],
            'axle_position_id' => [
                Rule::requiredIf(
                    fn () => $request->input('scan_type') === self::SCAN_AXLE
                ),
                'nullable',
                'integer',
                Rule::exists('axle_positions', 'id')->where(
                    fn ($query) => $query->where('is_active', true)
                ),
            ],
            'brake_part_number_id' => [
                Rule::requiredIf(
                    fn () => $request->input('scan_type') === self::SCAN_BRAKE
                ),
                'nullable',
                'integer',
                Rule::exists('brake_part_numbers', 'id')->where(
                    fn ($query) => $query->where('is_active', true)
                ),
            ],

            'capture_source' => [
                'nullable',
                Rule::in(['upload', 'camera', 'mixed']),
            ],

            'expected_photo_count' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                'max:' . $maxFiles,
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
        $isRetooling = $originalScanType === self::SCAN_RETOOLING;
        $isAxle = $originalScanType === self::SCAN_AXLE;
        $isBrake = $originalScanType === self::SCAN_BRAKE;

        $hasRetoolingMetadata = collect([
            $validated['shift'] ?? null,
            $validated['line'] ?? null,
            $validated['from_type'] ?? null,
            $validated['to_type'] ?? null,
            $validated['production_line_id'] ?? null,
            $validated['from_casting_id'] ?? null,
            $validated['to_casting_id'] ?? null,
            $validated['scan_time'] ?? null,
        ])->contains(fn ($value) => $value !== null && $value !== '');

        ShiftTimeRule::validate(
            $validated['shift'] ?? null,
            $validated['scan_time'] ?? null
        );

        if ($isRetooling && $hasRetoolingMetadata && ($validated['scan_time'] ?? null) === null) {
            throw ValidationException::withMessages([
                'scan_time' => 'Jam proses wajib diisi ketika metadata produksi Retooling diisi.',
            ]);
        }

        if ($isRetooling && $hasRetoolingMetadata) {
            $fromCastingId = $validated['from_casting_id'] ?? null;
            $toCastingId = $validated['to_casting_id'] ?? null;

            if (
                $fromCastingId !== null
                && $toCastingId !== null
                && strcasecmp(
                    trim((string) $fromCastingId),
                    trim((string) $toCastingId)
                ) === 0
            ) {
                throw ValidationException::withMessages([
                    'to_casting_id' => 'Dari Type dan Ke Type tidak boleh sama.',
                ]);
            }

            if (($validated['production_line_id'] ?? null) !== null && ($fromCastingId === null || $toCastingId === null)) {
                throw ValidationException::withMessages([
                    'from_casting_id' => 'Dari Type wajib dipilih saat data Retooling lengkap diisi.',
                ]);
            }
        }

        if ($originalScanType === self::SCAN_OTHER) {
            $otherScanType = $validated['scan_type_other'] ?? null;

            if ($otherScanType === null) {
                throw ValidationException::withMessages([
                    'scan_type_other' => 'Jenis dokumen lainnya tidak boleh kosong.',
                ]);
            }

            $otherScanType = $this->normalizeScanTypeName($otherScanType);

            if ($otherScanType === '') {
                throw ValidationException::withMessages([
                    'scan_type_other' => 'Jenis dokumen lainnya tidak boleh kosong.',
                ]);
            }

            $reservedScanTypes = array_map(
                'strtolower',
                self::systemScanTypes()
            );

            if (in_array(strtolower($otherScanType), $reservedScanTypes, true)) {
                throw ValidationException::withMessages([
                    'scan_type_other' => 'Gunakan pilihan jenis dokumen yang sudah tersedia.',
                ]);
            }

            $validated['scan_type'] = $otherScanType;
        }

        unset($validated['scan_type_other']);

        $customScanTypeName = $this->normalizeScanTypeName((string) ($validated['scan_type'] ?? ''));

        if (
            $customScanTypeName !== ''
            && ! $this->isReservedScanType($customScanTypeName)
            && ! DocumentScanType::query()->where('is_active', true)->whereRaw('LOWER(name) = ?', [strtolower($customScanTypeName)])->exists()
        ) {
            DocumentScanType::query()->firstOrCreate(
                ['name' => $customScanTypeName],
                ['is_active' => true, 'sort_order' => 500]
            );
        }

        $retoolingDate = $isRetooling
            ? RetoolingDateRule::resolve(
                $validated['scan_date'],
                $validated['shift'] ?? null,
                $validated['scan_time'] ?? null
            )
            : null;

        $productionDate = ($isAxle || $isBrake)
            ? $validated['scan_date']
            : null;

        $axleTypeMaster = null;
        $axlePositionMaster = null;
        $brakePartMaster = null;
        $productionLineMaster = null;

        if ($isAxle) {
            $axleTypeMaster = AxleType::query()
                ->whereKey($validated['axle_type_id'])
                ->where('is_active', true)
                ->first();

            $axlePositionMaster = AxlePosition::query()
                ->whereKey($validated['axle_position_id'])
                ->where('is_active', true)
                ->first();

            if (! $axleTypeMaster) {
                throw ValidationException::withMessages([
                    'axle_type_id' => 'Type Axle yang dipilih sudah tidak tersedia.',
                ]);
            }

            if (! $axlePositionMaster) {
                throw ValidationException::withMessages([
                    'axle_position_id' => 'Posisi Axle yang dipilih sudah tidak tersedia.',
                ]);
            }

            $validated['axle_assy_type'] = $axleTypeMaster->name;
            $validated['axle_side'] = $axlePositionMaster->name;
        } else {
            $validated['axle_assy_type'] = null;
            $validated['axle_side'] = null;
        }

        if ($isBrake) {
            $brakePartMaster = BrakePartNumber::query()
                ->whereKey($validated['brake_part_number_id'])
                ->where('is_active', true)
                ->first();

            if (! $brakePartMaster) {
                throw ValidationException::withMessages([
                    'brake_part_number_id' => 'Part Number Brake yang dipilih sudah tidak tersedia.',
                ]);
            }

            $validated['brake_part_variant'] = $brakePartMaster->variant;
        } else {
            $validated['brake_part_variant'] = null;
        }

        if ($isAxle || $isBrake) {
            if (($validated['production_line_id'] ?? null) !== null) {
                $productionLineMaster = DB::table('mtc.LINE')
                    ->where('KODE_LN', $validated['production_line_id'])
                    ->first([
                        'KODE_LN as id',
                        DB::raw("COALESCE(NULLIF(kd_ln, ''), NAMA_LN) as code"),
                        'NAMA_LN as name',
                    ]);

                if (! $productionLineMaster) {
                    throw ValidationException::withMessages([
                        'production_line_id' => 'Line yang dipilih sudah tidak tersedia.',
                    ]);
                }
            }

            $validated['line'] = $productionLineMaster?->code;
            $validated['from_type'] = null;
            $validated['to_type'] = null;
        }

        $brakePartPrefix = $isBrake
            ? $brakePartMaster->prefix
            : null;

        $brakePartNumber = $isBrake
            ? $brakePartMaster->display_name
            : null;

        if ($isRetooling) {
            $validated['title'] = 'INSTRUKSI PERUBAHAN TYPE (RETOOLING)';
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

        $categorySelection = $categoryAssignment->prepare($validated);

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

        if (
            $request->exists('expected_photo_count')
            && (int) $validated['expected_photo_count'] !== count($files)
        ) {
            throw ValidationException::withMessages([
                'files' => 'Jumlah foto yang diterima server tidak lengkap. '
                    . 'Anda memilih ' . $validated['expected_photo_count']
                    . ' foto, tetapi server hanya menerima ' . count($files)
                    . ' foto. Hubungi admin IT untuk memeriksa batas upload PHP.',
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
            Str::slug($categorySelection['department_name']),
            $categorySelection['slug'],
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
                $categoryAssignment,
                $categorySelection,
                $displayFileName,
                $extension,
                $storedPath,
                $mimeType,
                $verifiedFileSize,
                $scanSecurityMetadata,
                $axleTypeMaster,
                $axlePositionMaster,
                $brakePartMaster,
                $productionLineMaster,
                $brakePartPrefix,
                $brakePartNumber,
                $productionDate,
                $captureSource,
                $isRetooling,
                $retoolingDate,
                $isAxle,
                $isBrake,
                $isMultiPhoto,
                $sourceCount
            ) {
                $category = $categoryAssignment->resolve(
                    $request,
                    $categorySelection,
                    'inline_scan'
                );

                // Resolve names and Cycle Time from active master records.
                $retoolingMaster = null;

                if (($validated['scan_type'] ?? null) === self::SCAN_RETOOLING) {
                    $productionLineId = $validated['production_line_id'] ?? null;
                    $fromCastingId = $validated['from_casting_id'] ?? null;
                    $toCastingId = $validated['to_casting_id'] ?? null;

                    if ($productionLineId !== null || $fromCastingId !== null || $toCastingId !== null) {
                        $productionLine = DB::table('mtc.LINE')
                            ->where('KODE_LN', $productionLineId)
                            ->first([
                                'KODE_LN as id',
                                DB::raw("COALESCE(NULLIF(kd_ln, ''), NAMA_LN) as code"),
                                'NAMA_LN as name',
                            ]);

                        if ($productionLineId !== null && ! $productionLine) {
                            throw ValidationException::withMessages([
                                'production_line_id' => 'Line yang dipilih sudah tidak tersedia.',
                            ]);
                        }

                        if ($productionLineId !== null && ($fromCastingId === null || $toCastingId === null)) {
                            throw ValidationException::withMessages([
                                'from_casting_id' => 'Dari Type dan Ke Type wajib dipilih saat Line Retooling diisi.',
                            ]);
                        }

                        if ($fromCastingId !== null && $toCastingId !== null && $productionLineId !== null) {
                            $findPpicProduct = static function (string $code, int $lineId): ?object {
                                return DB::table('ppic.LINEprd')
                                    ->where('KODE_LN', $lineId)
                                    ->where('KODE_CST', $code)
                                    ->where('AKTIF', 1)
                                    ->first([
                                        'KODE_CST as code',
                                        'NAMA_CST as name',
                                        'CT as cycle_time',
                                    ]);
                            };

                            $fromLineProduct = $findPpicProduct($fromCastingId, (int) $productionLine->id);
                            $toLineProduct = $findPpicProduct($toCastingId, (int) $productionLine->id);

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
                            $validated['from_type'] = $fromLineProduct->code;
                            $validated['to_type'] = $toLineProduct->code;

                            $retoolingMaster = [
                                'production_line_id' => $productionLine->id,
                                'from_casting_id' => $fromLineProduct->code,
                                'to_casting_id' => $toLineProduct->code,
                                'from_cycle_time' => $fromLineProduct->cycle_time,
                                'to_cycle_time' => $toLineProduct->cycle_time,
                            ];
                        }
                    }
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
                if (($retoolingMaster['persist'] ?? false) === true) {
                    DocumentRetooling::create([
                        'document_id' => $document->id,
                        'shift' => (int) $validated['shift'],
                        'production_line_id' => $retoolingMaster['production_line_id'],
                        'from_casting_id' => $retoolingMaster['from_casting_id'],
                        'to_casting_id' => $retoolingMaster['to_casting_id'],
                        'from_cycle_time' => $retoolingMaster['from_cycle_time'],
                        'to_cycle_time' => $retoolingMaster['to_cycle_time'],
                        'retooling_date' => $retoolingDate?->toDateString(),
                    ]);
                }

                if ($isAxle) {
                    DocumentAxleProduction::create([
                        'document_id' => $document->id,
                        'axle_type_id' => $axleTypeMaster->id,
                        'axle_position_id' => $axlePositionMaster->id,
                        'production_line_id' => $productionLineMaster?->id,
                        'shift' => isset($validated['shift'])
                            ? (int) $validated['shift']
                            : null,
                        'production_time' => $validated['scan_time'] ?? null,
                        'snapshot_axle_type_code' => $axleTypeMaster->code,
                        'snapshot_axle_type_name' => $axleTypeMaster->name,
                        'snapshot_position_code' => $axlePositionMaster->code,
                        'snapshot_position_name' => $axlePositionMaster->name,
                        'snapshot_line_code' => $productionLineMaster?->code,
                        'snapshot_line_name' => $productionLineMaster?->name,
                    ]);
                }

                if ($isBrake) {
                    DocumentBrakeProduction::create([
                        'document_id' => $document->id,
                        'brake_part_number_id' => $brakePartMaster->id,
                        'production_line_id' => $productionLineMaster?->id,
                        'shift' => isset($validated['shift'])
                            ? (int) $validated['shift']
                            : null,
                        'production_time' => $validated['scan_time'] ?? null,
                        'snapshot_prefix' => $brakePartMaster->prefix,
                        'snapshot_variant' => $brakePartMaster->variant,
                        'snapshot_display_name' => $brakePartMaster->display_name,
                        'snapshot_line_code' => $productionLineMaster?->code,
                        'snapshot_line_name' => $productionLineMaster?->name,
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
                            ...($isRetooling ? [
                                'scan_date' => $validated['scan_date'],
                                'retooling_date' => $retoolingDate?->toDateString(),
                                'from_cycle_time' => $retoolingMaster['from_cycle_time'] ?? null,
                                'to_cycle_time' => $retoolingMaster['to_cycle_time'] ?? null,
                            ] : []),
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
                        ...($isRetooling ? [
                            'scan_time' => $validated['scan_time'] ?? null,
                            'retooling_date' => $retoolingDate?->toDateString(),
                        ] : []),
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

    private function ensureSuperAdmin(): void
    {
        abort_unless(Auth::check() && Auth::user()->isSuperAdmin(), 403);
    }
}
