<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Menampilkan Global Analytics Dashboard BMC SmartFind.
     */
    public function index(Request $request): View
    {
        /*
        |--------------------------------------------------------------------------
        | Periode Analitik
        |--------------------------------------------------------------------------
        |
        | Filter ini hanya memengaruhi data berbasis waktu seperti:
        | - Dokumen baru
        | - Tren upload
        | - Aktivitas sistem
        | - Dokumen paling sering diakses
        |
        | Statistik struktur seperti total dokumen aktif, kategori, departemen,
        | dan jenis file selalu menampilkan kondisi database saat ini.
        |
        */

        $allowedPeriods = [
            7,
            30,
            90,
            365,
        ];

        $period = (int) $request->input(
            'period',
            30
        );

        if (! in_array(
            $period,
            $allowedPeriods,
            true
        )) {
            $period = 30;
        }

        $periodEnd = now()->endOfDay();

        $periodStart = now()
            ->subDays($period - 1)
            ->startOfDay();

        $previousPeriodEnd = $periodStart
            ->copy()
            ->subSecond();

        $previousPeriodStart = $previousPeriodEnd
            ->copy()
            ->subDays($period - 1)
            ->startOfDay();


        /*
        |--------------------------------------------------------------------------
        | KPI Utama
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | Document KPI — 1 Query
        |--------------------------------------------------------------------------
        |
        | Sebelumnya beberapa KPI melakukan query COUNT/SUM terpisah.
        | Nilainya tetap sama, tetapi sekarang dihitung dalam satu round-trip.
        |
        */

        $documentStats =
            Document::query()
                ->selectRaw(
                    '
                    SUM(
                        CASE
                            WHEN is_active = 1
                            THEN 1
                            ELSE 0
                        END
                    ) AS total_documents,

                    SUM(
                        CASE
                            WHEN is_active = 0
                                AND archived_at IS NOT NULL
                            THEN 1
                            ELSE 0
                        END
                    ) AS archived_documents,

                    SUM(
                        CASE
                            WHEN is_active = 1
                                AND file_extension IN (?, ?, ?)
                            THEN 1
                            ELSE 0
                        END
                    ) AS spreadsheet_documents,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN is_active = 1
                                THEN COALESCE(file_size, 0)
                                ELSE 0
                            END
                        ),
                        0
                    ) AS total_storage_bytes
                    ',
                    [
                        'xlsx',
                        'xls',
                        'csv',
                    ]
                )
                ->first();

        $totalDocuments =
            (int) (
                $documentStats
                    ?->total_documents
                ?? 0
            );

        $archivedDocuments =
            (int) (
                $documentStats
                    ?->archived_documents
                ?? 0
            );

        $spreadsheetDocuments =
            (int) (
                $documentStats
                    ?->spreadsheet_documents
                ?? 0
            );

        $totalStorageBytes =
            (int) (
                $documentStats
                    ?->total_storage_bytes
                ?? 0
            );


        /*
        |--------------------------------------------------------------------------
        | Department / Category KPI
        |--------------------------------------------------------------------------
        */

        $totalDepartments =
            Department::where(
                'is_active',
                true
            )->count();

        $totalCategories =
            Category::where(
                'is_active',
                true
            )->count();


        /*
        |--------------------------------------------------------------------------
        | User KPI — 1 Query
        |--------------------------------------------------------------------------
        */

        $userStats =
            User::query()
                ->selectRaw(
                    '
                    SUM(
                        CASE
                            WHEN account_status = ?
                            THEN 1
                            ELSE 0
                        END
                    ) AS active_users,

                    SUM(
                        CASE
                            WHEN account_status = ?
                            THEN 1
                            ELSE 0
                        END
                    ) AS pending_users
                    ',
                    [
                        'active',
                        'pending',
                    ]
                )
                ->first();

        $activeUsers =
            (int) (
                $userStats
                    ?->active_users
                ?? 0
            );

        $pendingUsers =
            (int) (
                $userStats
                    ?->pending_users
                ?? 0
            );


        /*
        | REV 0 merupakan versi awal dokumen.
        | Karena itu total revisi hanya menghitung REV 1 dan seterusnya.
        */

        $totalRevisions =
            DocumentVersion::where(
                'revision_number',
                '>',
                0
            )->count();


        /*
        |--------------------------------------------------------------------------
        | Period KPI
        |--------------------------------------------------------------------------
        */

        $activityInPeriod =
            ActivityLog::whereBetween(
                'created_at',
                [
                    $periodStart,
                    $periodEnd,
                ]
            )->count();

        $periodDocumentStats =
            Document::query()
                ->selectRaw(
                    '
                    SUM(
                        CASE
                            WHEN created_at BETWEEN ? AND ?
                            THEN 1
                            ELSE 0
                        END
                    ) AS current_period,

                    SUM(
                        CASE
                            WHEN created_at BETWEEN ? AND ?
                            THEN 1
                            ELSE 0
                        END
                    ) AS previous_period
                    ',
                    [
                        $periodStart,
                        $periodEnd,
                        $previousPeriodStart,
                        $previousPeriodEnd,
                    ]
                )
                ->first();

        $newDocumentsInPeriod =
            (int) (
                $periodDocumentStats
                    ?->current_period
                ?? 0
            );

        $newDocumentsPreviousPeriod =
            (int) (
                $periodDocumentStats
                    ?->previous_period
                ?? 0
            );

        $documentGrowth =
            $this->calculateGrowth(
                $newDocumentsInPeriod,
                $newDocumentsPreviousPeriod
            );


        /*
        |--------------------------------------------------------------------------
        | Dokumen & Aktivitas Terbaru
        |--------------------------------------------------------------------------
        */

        $recentDocuments = Document::query()
            ->where(
                'is_active',
                true
            )
            ->with([
                'category.department',
            ])
            ->latest('created_at')
            ->take(6)
            ->get();

        $recentActivities = ActivityLog::query()
            ->with([
                'user',
                'document',
            ])
            ->latest('created_at')
            ->take(6)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Tren Dokumen
        |--------------------------------------------------------------------------
        */

        $documentTrendRows = Document::query()
            ->selectRaw(
                'DATE(created_at) AS activity_date, COUNT(*) AS total'
            )
            ->whereBetween(
                'created_at',
                [
                    $periodStart,
                    $periodEnd,
                ]
            )
            ->groupByRaw(
                'DATE(created_at)'
            )
            ->orderByRaw(
                'DATE(created_at)'
            )
            ->get()
            ->keyBy('activity_date');


        /*
        |--------------------------------------------------------------------------
        | Tren Aktivitas
        |--------------------------------------------------------------------------
        */

        $activityTrendRows = ActivityLog::query()
            ->selectRaw(
                'DATE(created_at) AS activity_date, COUNT(*) AS total'
            )
            ->whereBetween(
                'created_at',
                [
                    $periodStart,
                    $periodEnd,
                ]
            )
            ->groupByRaw(
                'DATE(created_at)'
            )
            ->orderByRaw(
                'DATE(created_at)'
            )
            ->get()
            ->keyBy('activity_date');


        /*
        |--------------------------------------------------------------------------
        | Lengkapi Tanggal Kosong Agar Grafik Akurat
        |--------------------------------------------------------------------------
        */

        $trendLabels = [];
        $documentTrendData = [];
        $activityTrendData = [];

        $cursor = $periodStart
            ->copy()
            ->startOfDay();

        $lastDate = $periodEnd
            ->copy()
            ->startOfDay();

        while ($cursor->lte($lastDate)) {

            $dateKey = $cursor->format(
                'Y-m-d'
            );

            $trendLabels[] = $cursor
                ->locale('id')
                ->translatedFormat(
                    $period <= 30
                        ? 'd M'
                        : 'd M'
                );

            $documentTrendData[] =
                (int) (
                    $documentTrendRows
                        ->get($dateKey)
                        ?->total
                    ?? 0
                );

            $activityTrendData[] =
                (int) (
                    $activityTrendRows
                        ->get($dateKey)
                        ?->total
                    ?? 0
                );

            $cursor->addDay();
        }


        /*
        |--------------------------------------------------------------------------
        | Distribusi Dokumen per Kategori
        |--------------------------------------------------------------------------
        */

        $categoryRows = DB::table(
            'documents AS documents'
        )
            ->join(
                'categories AS categories',
                'categories.id',
                '=',
                'documents.category_id'
            )
            ->where(
                'documents.is_active',
                1
            )
            ->select(
                'categories.name',
                DB::raw(
                    'COUNT(documents.id) AS total'
                )
            )
            ->groupBy(
                'categories.id',
                'categories.name'
            )
            ->orderByDesc('total')
            ->get();

        $categoryDistribution =
            $this->compressDistribution(
                $categoryRows,
                7
            );


        /*
        |--------------------------------------------------------------------------
        | Distribusi Dokumen per Departemen
        |--------------------------------------------------------------------------
        */

        $departmentRows = DB::table(
            'documents AS documents'
        )
            ->join(
                'categories AS categories',
                'categories.id',
                '=',
                'documents.category_id'
            )
            ->join(
                'departments AS departments',
                'departments.id',
                '=',
                'categories.department_id'
            )
            ->where(
                'documents.is_active',
                1
            )
            ->select(
                'departments.name',
                DB::raw(
                    'COUNT(documents.id) AS total'
                )
            )
            ->groupBy(
                'departments.id',
                'departments.name'
            )
            ->orderByDesc('total')
            ->get();

        $departmentDistribution =
            $this->compressDistribution(
                $departmentRows,
                8
            );


        /*
        |--------------------------------------------------------------------------
        | Distribusi Jenis File
        |--------------------------------------------------------------------------
        */

        $fileTypeRows = Document::query()
            ->where(
                'is_active',
                true
            )
            ->select(
                'file_extension',
                DB::raw(
                    'COUNT(*) AS total'
                )
            )
            ->groupBy(
                'file_extension'
            )
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                return (object) [
                    'name' =>
                        strtoupper(
                            $row->file_extension
                            ?: 'LAINNYA'
                        ),

                    'total' =>
                        (int) $row->total,
                ];
            });

        $fileTypeDistribution =
            $this->compressDistribution(
                $fileTypeRows,
                7
            );


        /*
        |--------------------------------------------------------------------------
        | Komposisi Aktivitas pada Periode Aktif
        |--------------------------------------------------------------------------
        */

        $actionRows = ActivityLog::query()
            ->whereBetween(
                'created_at',
                [
                    $periodStart,
                    $periodEnd,
                ]
            )
            ->select(
                'action',
                DB::raw(
                    'COUNT(*) AS total'
                )
            )
            ->groupBy(
                'action'
            )
            ->get();

        $activityBreakdown = [
            'Upload' => 0,
            'Lihat' => 0,
            'Buka' => 0,
            'Unduh' => 0,
            'Revisi' => 0,
            'Kelola' => 0,
        ];

        foreach ($actionRows as $row) {

            $action = (string) $row->action;
            $total = (int) $row->total;

            if ($action === 'upload_document') {
                $activityBreakdown['Upload'] += $total;

                continue;
            }

            if (
                in_array(
                    $action,
                    [
                        'upload_document_revision',
                        'open_document_revision',
                        'download_document_revision',
                    ],
                    true
                )
            ) {
                $activityBreakdown['Revisi'] += $total;

                continue;
            }

            if ($action === 'view_document') {
                $activityBreakdown['Lihat'] += $total;

                continue;
            }

            if ($action === 'open_document') {
                $activityBreakdown['Buka'] += $total;

                continue;
            }

            if ($action === 'download_document') {
                $activityBreakdown['Unduh'] += $total;

                continue;
            }

            $activityBreakdown['Kelola'] += $total;
        }


        /*
        |--------------------------------------------------------------------------
        | Dokumen Paling Sering Diakses pada Periode Aktif
        |--------------------------------------------------------------------------
        */

        $topAccessedDocuments = ActivityLog::query()
            ->join(
                'documents',
                'documents.id',
                '=',
                'activity_logs.document_id'
            )
            ->whereBetween(
                'activity_logs.created_at',
                [
                    $periodStart,
                    $periodEnd,
                ]
            )
            ->where(
                'documents.is_active',
                true
            )
            ->whereIn(
                'activity_logs.action',
                [
                    'view_document',
                    'open_document',
                    'download_document',
                    'open_document_revision',
                    'download_document_revision',
                ]
            )
            ->select(
                'documents.id',
                'documents.title',
                'documents.document_code',
                DB::raw(
                    'COUNT(activity_logs.id) AS access_count'
                )
            )
            ->groupBy(
                'documents.id',
                'documents.title',
                'documents.document_code'
            )
            ->orderByDesc(
                'access_count'
            )
            ->take(5)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Data Grafik
        |--------------------------------------------------------------------------
        */

        $chartData = [
            'trend' => [
                'labels' =>
                    $trendLabels,

                'documents' =>
                    $documentTrendData,

                'activities' =>
                    $activityTrendData,
            ],

            'categories' => [
                'labels' =>
                    $categoryDistribution['labels'],

                'values' =>
                    $categoryDistribution['values'],
            ],

            'departments' => [
                'labels' =>
                    $departmentDistribution['labels'],

                'values' =>
                    $departmentDistribution['values'],
            ],

            'fileTypes' => [
                'labels' =>
                    $fileTypeDistribution['labels'],

                'values' =>
                    $fileTypeDistribution['values'],
            ],

            'activities' => [
                'labels' =>
                    array_keys(
                        $activityBreakdown
                    ),

                'values' =>
                    array_values(
                        $activityBreakdown
                    ),
            ],
        ];


        /*
        |--------------------------------------------------------------------------
        | Informasi Periode
        |--------------------------------------------------------------------------
        */

        $periodLabel =
            $period . ' Hari';

        $periodDateLabel =
            $periodStart
                ->locale('id')
                ->translatedFormat('d M Y')
            . ' — '
            . $periodEnd
                ->locale('id')
                ->translatedFormat('d M Y');


        return view(
            'dashboard',
            compact(
                'period',
                'periodLabel',
                'periodDateLabel',
                'totalDocuments',
                'archivedDocuments',
                'totalDepartments',
                'totalCategories',
                'activeUsers',
                'pendingUsers',
                'totalRevisions',
                'spreadsheetDocuments',
                'totalStorageBytes',
                'activityInPeriod',
                'newDocumentsInPeriod',
                'documentGrowth',
                'recentDocuments',
                'recentActivities',
                'topAccessedDocuments',
                'chartData'
            )
        );
    }


    /**
     * Menghitung persentase pertumbuhan dibanding periode sebelumnya.
     */
    private function calculateGrowth(
        int $current,
        int $previous
    ): ?float {

        if ($previous === 0) {
            return $current === 0
                ? 0.0
                : null;
        }

        return round(
            (($current - $previous) / $previous) * 100,
            1
        );
    }


    /**
     * Membatasi jumlah segmen grafik.
     *
     * Data di luar limit digabung sebagai "Lainnya" agar grafik
     * tetap terbaca tanpa mengubah total data sebenarnya.
     */
    private function compressDistribution(
        iterable $rows,
        int $limit
    ): array {

        $items = collect($rows)
            ->map(function ($row) {
                return [
                    'name' =>
                        (string) $row->name,

                    'total' =>
                        (int) $row->total,
                ];
            })
            ->values();

        $mainItems = $items
            ->take($limit);

        $remainingTotal = (int) $items
            ->slice($limit)
            ->sum('total');

        if ($remainingTotal > 0) {
            $mainItems->push([
                'name' =>
                    'Lainnya',

                'total' =>
                    $remainingTotal,
            ]);
        }

        return [
            'labels' =>
                $mainItems
                    ->pluck('name')
                    ->values()
                    ->all(),

            'values' =>
                $mainItems
                    ->pluck('total')
                    ->map(
                        fn ($value) =>
                            (int) $value
                    )
                    ->values()
                    ->all(),
        ];
    }
}
