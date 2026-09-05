@php
    $analyticsVersion =
        $analyticsVersion
        ?? $currentVersion
        ?? null;

    $analyticsRevisionNumber =
        (int) (
            $analyticsRevisionNumber
            ?? $analyticsVersion?->revision_number
            ?? $document->current_revision
            ?? 0
        );

    $isHistoricalAnalytics =
        (bool) (
            $isHistoricalAnalytics
            ?? (
                $analyticsRevisionNumber
                !== (int) ($document->current_revision ?? 0)
            )
        );

    $analyticsFileName =
        $analyticsVersion?->file_name
        ?? $document->file_name;

    $analyticsStatus = $documentAnalytics?->status;

    $statusDisplay = match($analyticsStatus) {
        'ready' => [
            'label' => 'Analitik Siap',
            'classes' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'dot' => 'bg-emerald-500',
        ],

        'processing' => [
            'label' => 'Sedang Diproses',
            'classes' => 'bg-blue-50 text-blue-700 border-blue-200',
            'dot' => 'bg-blue-500',
        ],

        'pending' => [
            'label' => 'Menunggu Proses',
            'classes' => 'bg-amber-50 text-amber-700 border-amber-200',
            'dot' => 'bg-amber-500',
        ],

        'failed' => [
            'label' => 'Pemrosesan Gagal',
            'classes' => 'bg-red-50 text-red-700 border-red-200',
            'dot' => 'bg-red-500',
        ],

        default => [
            'label' => 'Belum Diproses',
            'classes' => 'bg-slate-50 text-slate-600 border-slate-200',
            'dot' => 'bg-slate-400',
        ],
    };

    $analyticsSheets = $documentAnalytics?->sheets ?? collect();

    $totalDataCells = (int) ($analyticsCellCount ?? 0);

    $filledDataCells = max(
        0,
        $totalDataCells
        - (int) ($documentAnalytics?->blank_cells ?? 0)
    );

    $sheetChartPayload = $analyticsSheets
        ->map(function ($sheet) {
            $statistics = collect(
                $sheet->statistics ?? []
            );

            $categoricalCharts = $statistics
                ->filter(function ($column) {
                    return ($column['type'] ?? null) === 'categorical'
                        && ! empty($column['top_values'])
                        && ! ($column['unique_count_truncated'] ?? false);
                })
                ->take(3)
                ->map(function ($column) {
                    $topValues = collect(
                        $column['top_values'] ?? []
                    );

                    return [
                        'title' =>
                            'Distribusi ' . ($column['header'] ?? 'Kategori'),

                        'subtitle' =>
                            (
                                (int) ($column['unique_count'] ?? 0)
                                <= count($column['top_values'] ?? [])
                            )
                                ? 'Distribusi seluruh nilai kategori.'
                                : 'Nilai kategori yang paling sering muncul.',

                        'labels' =>
                            $topValues
                                ->pluck('value')
                                ->map(
                                    fn ($value) =>
                                        is_bool($value)
                                            ? ($value ? 'Ya' : 'Tidak')
                                            : (string) $value
                                )
                                ->values()
                                ->all(),

                        'values' =>
                            $topValues
                                ->pluck('count')
                                ->map(
                                    fn ($value) =>
                                        (int) $value
                                )
                                ->values()
                                ->all(),
                    ];
                })
                ->values()
                ->all();

            $numericCharts = $statistics
                ->filter(function ($column) {
                    return ($column['type'] ?? null) === 'numeric'
                        && is_array($column['numeric'] ?? null)
                        && ($column['numeric_count'] ?? 0) > 0;
                })
                ->take(3)
                ->map(function ($column) {
                    $numeric = $column['numeric'];

                    return [
                        'title' =>
                            'Ringkasan ' . ($column['header'] ?? 'Numerik'),

                        'subtitle' =>
                            'Minimum, rata-rata, dan maksimum dari seluruh data kolom.',

                        'labels' => [
                            'Minimum',
                            'Rata-rata',
                            'Maksimum',
                        ],

                        'values' => [
                            (float) ($numeric['min'] ?? 0),
                            (float) ($numeric['average'] ?? 0),
                            (float) ($numeric['max'] ?? 0),
                        ],
                    ];
                })
                ->values()
                ->all();

            return [
                'sheet_index' =>
                    (int) $sheet->sheet_index,

                'categorical' =>
                    $categoricalCharts,

                'numeric' =>
                    $numericCharts,
            ];
        })
        ->values()
        ->all();
@endphp


<section
    id="document-analytics-dashboard"
    class="mb-8"
>

    {{-- ========================================================= --}}
    {{-- ANALYTICS HERO --}}
    {{-- ========================================================= --}}

    <div
        class="analytics-reveal
               relative overflow-hidden
               rounded-[28px]
               bg-gradient-to-br
               from-slate-950 via-blue-950 to-blue-800
               shadow-xl shadow-blue-950/10"
    >

        <div
            class="absolute -top-28 -right-20
                   h-72 w-72
                   rounded-full
                   bg-white/[0.06]"
        ></div>

        <div
            class="absolute -bottom-36 left-[35%]
                   h-72 w-72
                   rounded-full
                   bg-cyan-300/[0.06]"
        ></div>

        <div
            class="relative
                   p-6 sm:p-7"
        >

            <div
                class="flex flex-col
                       lg:flex-row
                       lg:items-center
                       lg:justify-between
                       gap-5"
            >

                <div class="min-w-0">

                    <div
                        class="flex flex-wrap
                               items-center
                               gap-2"
                    >

                        <span
                            class="inline-flex
                                   items-center
                                   gap-2
                                   rounded-full
                                   border
                                   {{ $statusDisplay['classes'] }}
                                   px-3 py-1.5
                                   text-xs
                                   font-semibold"
                        >
                            <span
                                class="h-2 w-2
                                       rounded-full
                                       {{ $statusDisplay['dot'] }}"
                            ></span>

                            {{ $statusDisplay['label'] }}
                        </span>

                        <span
                            class="inline-flex
                                   rounded-full
                                   border border-white/10
                                   bg-white/[0.08]
                                   px-3 py-1.5
                                   text-xs font-semibold
                                   text-blue-100"
                        >
                            {{ strtoupper($document->file_extension ?? 'FILE') }}
                        </span>

                        <span
                            class="inline-flex
                                   rounded-full
                                   border border-white/10
                                   bg-white/[0.08]
                                   px-3 py-1.5
                                   text-xs font-semibold
                                   text-blue-100"
                        >
                            Revisi {{ $analyticsRevisionNumber }}
                        </span>

                        @if($isHistoricalAnalytics)

                            <span
                                class="inline-flex
                                       rounded-full
                                       border border-amber-300/30
                                       bg-amber-300/10
                                       px-3 py-1.5
                                       text-xs font-semibold
                                       text-amber-100"
                            >
                                Riwayat Revisi
                            </span>

                        @endif

                    </div>


                    <p
                        class="mt-5
                               text-xs font-semibold
                               uppercase tracking-[0.18em]
                               text-blue-200/70"
                    >
                        BMC QualityHub · Quality Analytics
                    </p>

                    <h2
                        class="mt-2
                               text-2xl sm:text-3xl
                               font-bold tracking-tight
                               text-white"
                    >
                        Dashboard Analitik Spreadsheet
                    </h2>

                    <p
                        class="mt-2
                               max-w-3xl
                               text-sm
                               leading-relaxed
                               text-blue-100/70"
                    >
                        @if($isHistoricalAnalytics)
                            Anda sedang melihat hasil analitik historis
                            Revisi {{ $analyticsRevisionNumber }}.
                            Data ini tetap terpisah dari Revisi
                            {{ (int) $document->current_revision }} yang sedang aktif.
                        @else
                            Ringkasan ini dibuat dari data pada revisi spreadsheet
                            yang sedang aktif.
                        @endif

                        File sumber tetap berada di private storage dan tidak
                        diubah oleh proses analitik.
                    </p>

                </div>


                <div
                    class="flex-shrink-0
                           rounded-2xl
                           border border-white/10
                           bg-white/[0.07]
                           px-5 py-4
                           backdrop-blur-sm"
                >

                    <p
                        class="text-[11px]
                               uppercase tracking-[0.16em]
                               font-semibold
                               text-blue-200/65"
                    >
                        Sumber Analitik
                    </p>

                    <p
                        class="mt-2
                               max-w-[280px]
                               truncate
                               text-sm font-semibold
                               text-white"
                    >
                        {{ $analyticsFileName }}
                    </p>

                    <p
                        class="mt-1
                               text-xs
                               text-blue-100/60"
                    >
                        @if($documentAnalytics?->processed_at)
                            Diproses
                            {{ $documentAnalytics->processed_at
                                ->locale('id')
                                ->translatedFormat('d M Y, H:i') }}
                        @else
                            Belum memiliki waktu pemrosesan
                        @endif
                    </p>


                    @if(
                        $isHistoricalAnalytics
                        && $analyticsVersion !== null
                        && Route::has('documents.revisions.open')
                        && Route::has('documents.revisions.download')
                    )

                        <div
                            class="mt-4
                                   flex flex-wrap
                                   gap-2"
                        >

                            <a
                                href="{{ route(
                                    'documents.revisions.open',
                                    [
                                        $document,
                                        $analyticsVersion,
                                    ]
                                ) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="rounded-lg
                                       border border-white/15
                                       bg-white/[0.08]
                                       px-3 py-2
                                       text-[11px] font-semibold
                                       text-white
                                       hover:bg-white/[0.14]
                                       transition"
                            >
                                Buka Revisi Ini
                            </a>

                            <a
                                href="{{ route(
                                    'documents.revisions.download',
                                    [
                                        $document,
                                        $analyticsVersion,
                                    ]
                                ) }}"
                                class="rounded-lg
                                       bg-white
                                       px-3 py-2
                                       text-[11px] font-semibold
                                       text-blue-900
                                       hover:bg-blue-50
                                       transition"
                            >
                                Unduh Revisi Ini
                            </a>

                        </div>

                    @endif

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- STATUS / EMPTY STATE --}}
    {{-- ========================================================= --}}

    @if($documentAnalytics === null)

        <div
            class="analytics-reveal
                   mt-5
                   rounded-3xl
                   border border-amber-200
                   bg-amber-50
                   p-6"
        >

            <div
                class="flex
                       flex-col sm:flex-row
                       gap-4"
            >

                <div
                    class="h-11 w-11
                           rounded-2xl
                           bg-amber-100
                           text-amber-700
                           flex items-center justify-center
                           flex-shrink-0"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="20"
                        height="20"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 8v4"/>
                        <path d="M12 16h.01"/>
                    </svg>
                </div>

                <div>

                    <h3
                        class="font-bold
                               text-amber-900"
                    >
                        Analitik revisi ini belum tersedia
                    </h3>

                    <p
                        class="mt-1
                               text-sm
                               leading-relaxed
                               text-amber-800/80"
                    >
                        Spreadsheet sudah dikenali oleh QualityHub, tetapi
                        current revision belum memiliki hasil pemrosesan
                        pada <code>document_analytics</code>.
                    </p>

                </div>

            </div>

        </div>

    @elseif($analyticsStatus !== 'ready')

        <div
            class="analytics-reveal
                   mt-5
                   rounded-3xl
                   border
                   {{ $statusDisplay['classes'] }}
                   p-5 sm:p-6"
        >

            <div
                class="flex
                       items-start
                       gap-4"
            >

                @if(in_array($analyticsStatus, ['pending', 'processing'], true))

                    <span
                        class="analytics-loading-ring
                               mt-0.5
                               h-9 w-9
                               rounded-full
                               border-[3px]
                               border-current/20
                               border-t-current
                               flex-shrink-0"
                        aria-hidden="true"
                    ></span>

                @else

                    <span
                        class="mt-1
                               h-3 w-3
                               rounded-full
                               {{ $statusDisplay['dot'] }}
                               flex-shrink-0"
                    ></span>

                @endif


                <div class="min-w-0 flex-1">

                    <div
                        class="flex flex-col
                               sm:flex-row
                               sm:items-center
                               sm:justify-between
                               gap-2"
                    >

                        <div>

                            <h3 class="font-bold">
                                {{ $statusDisplay['label'] }}
                            </h3>

                            <p
                                class="mt-1
                                       text-sm
                                       opacity-80"
                            >
                                @if($analyticsStatus === 'failed')
                                    Data analitik belum dapat ditampilkan karena
                                    proses pembacaan spreadsheet tidak selesai.
                                @else
                                    QualityHub sedang membaca dan menghitung data
                                    Revisi {{ $analyticsRevisionNumber }}.
                                    Halaman akan diperbarui otomatis.
                                @endif
                            </p>

                        </div>


                        @if(in_array($analyticsStatus, ['pending', 'processing'], true))

                            <span
                                class="inline-flex
                                       self-start
                                       items-center
                                       gap-1.5
                                       rounded-full
                                       bg-white/50
                                       px-3 py-1.5
                                       text-[11px]
                                       font-semibold"
                            >
                                <span class="analytics-loading-dot"></span>
                                <span class="analytics-loading-dot"></span>
                                <span class="analytics-loading-dot"></span>

                                Menyiapkan dashboard
                            </span>

                        @endif

                    </div>

                </div>

            </div>

        </div>


        @if(in_array($analyticsStatus, ['pending', 'processing'], true))

            {{-- ================================================= --}}
            {{-- SKELETON KPI --}}
            {{-- ================================================= --}}

            <div
                class="mt-5
                       grid grid-cols-1
                       sm:grid-cols-2
                       lg:grid-cols-3
                       2xl:grid-cols-6
                       gap-4"
                aria-hidden="true"
            >

                @for($skeletonIndex = 0; $skeletonIndex < 6; $skeletonIndex++)

                    <div
                        class="analytics-skeleton-card
                               rounded-3xl
                               border border-slate-200/70
                               bg-white
                               p-5
                               shadow-sm"
                    >

                        <div
                            class="analytics-skeleton
                                   h-10 w-10
                                   rounded-2xl"
                        ></div>

                        <div
                            class="analytics-skeleton
                                   mt-4
                                   h-3 w-24
                                   rounded-full"
                        ></div>

                        <div
                            class="analytics-skeleton
                                   mt-3
                                   h-8 w-20
                                   rounded-lg"
                        ></div>

                        <div
                            class="analytics-skeleton
                                   mt-3
                                   h-2.5 w-32
                                   max-w-full
                                   rounded-full"
                        ></div>

                    </div>

                @endfor

            </div>


            {{-- ================================================= --}}
            {{-- SKELETON CONTENT --}}
            {{-- ================================================= --}}

            <div
                class="mt-5
                       grid
                       xl:grid-cols-2
                       gap-5"
                aria-hidden="true"
            >

                @for($skeletonPanel = 0; $skeletonPanel < 2; $skeletonPanel++)

                    <div
                        class="rounded-3xl
                               border border-slate-200/70
                               bg-white
                               p-5 sm:p-6
                               shadow-sm"
                    >

                        <div
                            class="analytics-skeleton
                                   h-4 w-40
                                   max-w-full
                                   rounded-full"
                        ></div>

                        <div
                            class="analytics-skeleton
                                   mt-3
                                   h-3 w-64
                                   max-w-full
                                   rounded-full"
                        ></div>

                        <div
                            class="analytics-skeleton
                                   mt-6
                                   h-44
                                   rounded-2xl"
                        ></div>

                    </div>

                @endfor

            </div>

        @endif

    @else

        {{-- ===================================================== --}}
        {{-- KPI --}}
        {{-- ===================================================== --}}

        <div
            class="mt-5
                   grid grid-cols-1
                   sm:grid-cols-2
                   lg:grid-cols-3
                   2xl:grid-cols-6
                   gap-4"
        >

            @php
                $analyticsKpis = [
                    [
                        'label' => 'Jumlah Sheet',
                        'value' => (int) $documentAnalytics->sheet_count,
                        'description' => 'Worksheet terdeteksi',
                        'tone' => 'blue',
                    ],
                    [
                        'label' => 'Total Baris Data',
                        'value' => (int) $documentAnalytics->total_rows,
                        'description' => 'Tidak termasuk header',
                        'tone' => 'indigo',
                    ],
                    [
                        'label' => 'Total Kolom',
                        'value' => (int) $documentAnalytics->total_columns,
                        'description' => 'Akumulasi seluruh sheet',
                        'tone' => 'violet',
                    ],
                    [
                        'label' => 'Sel Numerik',
                        'value' => (int) $documentAnalytics->numeric_cells,
                        'description' => 'Nilai angka terdeteksi',
                        'tone' => 'cyan',
                    ],
                    [
                        'label' => 'Sel Kosong',
                        'value' => (int) $documentAnalytics->blank_cells,
                        'description' => 'Dalam baris data',
                        'tone' => 'amber',
                    ],
                    [
                        'label' => 'Kelengkapan',
                        'value' => $analyticsCompleteness === null
                            ? '-'
                            : number_format(
                                $analyticsCompleteness,
                                1,
                                ',',
                                '.'
                            ) . '%',
                        'description' => number_format($filledDataCells)
                            . ' dari '
                            . number_format($totalDataCells)
                            . ' sel terisi',
                        'tone' => 'emerald',
                    ],
                ];

                $toneClasses = [
                    'blue' => 'bg-blue-50 text-blue-700',
                    'indigo' => 'bg-indigo-50 text-indigo-700',
                    'violet' => 'bg-violet-50 text-violet-700',
                    'cyan' => 'bg-cyan-50 text-cyan-700',
                    'amber' => 'bg-amber-50 text-amber-700',
                    'emerald' => 'bg-emerald-50 text-emerald-700',
                ];
            @endphp


            @foreach($analyticsKpis as $kpi)

                <article
                    class="analytics-reveal analytics-card
                           rounded-3xl
                           border border-slate-200/70
                           bg-white
                           p-5
                           shadow-sm"
                >

                    <div
                        class="h-10 w-10
                               rounded-2xl
                               flex items-center justify-center
                               {{ $toneClasses[$kpi['tone']] }}"
                    >
                        <span
                            class="h-2.5 w-2.5
                                   rounded-full
                                   bg-current"
                        ></span>
                    </div>

                    <p
                        class="mt-4
                               text-xs font-medium
                               text-slate-500"
                    >
                        {{ $kpi['label'] }}
                    </p>

                    <p
                        class="mt-1
                               text-2xl font-bold
                               tracking-tight
                               text-slate-900"
                    >
                        @if(is_numeric($kpi['value']))
                            <span
                                class="analytics-counter"
                                data-value="{{ $kpi['value'] }}"
                            >
                                0
                            </span>
                        @else
                            {{ $kpi['value'] }}
                        @endif
                    </p>

                    <p
                        class="mt-2
                               text-[11px]
                               leading-relaxed
                               text-slate-400"
                    >
                        {{ $kpi['description'] }}
                    </p>

                </article>

            @endforeach

        </div>


        {{-- ===================================================== --}}
        {{-- WORKBOOK STATUS --}}
        {{-- ===================================================== --}}

        <div
            class="analytics-reveal
                   mt-5
                   rounded-3xl
                   border border-slate-200/70
                   bg-white
                   p-5 sm:p-6
                   shadow-sm"
        >

            <div
                class="flex flex-col
                       lg:flex-row
                       lg:items-center
                       lg:justify-between
                       gap-5"
            >

                <div>

                    <h3
                        class="font-bold
                               text-slate-900"
                    >
                        Struktur Workbook
                    </h3>

                    <p
                        class="mt-1
                               text-sm
                               text-slate-500"
                    >
                        Pilih sheet untuk melihat preview data,
                        statistik kolom, dan visualisasi.
                    </p>

                </div>


                <div
                    class="analytics-tab-scroller
                           flex
                           items-center
                           gap-2
                           overflow-x-auto
                           pb-1
                           lg:flex-wrap"
                    role="tablist"
                    aria-label="Sheet spreadsheet"
                >

                    @foreach($analyticsSheets as $sheet)

                        <button
                            type="button"
                            class="analytics-sheet-tab
                                   flex-shrink-0
                                   rounded-xl
                                   border border-slate-200
                                   bg-white
                                   px-4 py-2
                                   text-xs font-semibold
                                   text-slate-600
                                   hover:border-blue-200
                                   hover:bg-blue-50
                                   hover:text-blue-700
                                   transition"
                            data-sheet-tab="{{ (int) $sheet->sheet_index }}"
                            role="tab"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                        >
                            {{ $sheet->sheet_name }}
                        </button>

                    @endforeach

                </div>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- SHEET PANELS --}}
        {{-- ===================================================== --}}

        @foreach($analyticsSheets as $sheet)

            @php
                $statistics = collect(
                    $sheet->statistics ?? []
                );

                $suggestions = collect(
                    $sheet->chart_suggestions ?? []
                );

                $previewRows = collect(
                    $sheet->preview_data ?? []
                );

                $headers = collect(
                    $sheet->headers ?? []
                );
            @endphp


            <section
                class="analytics-sheet-panel
                       mt-5
                       space-y-5
                       {{ $loop->first ? '' : 'hidden' }}"
                data-sheet-panel="{{ (int) $sheet->sheet_index }}"
                role="tabpanel"
            >

                {{-- SHEET SUMMARY --}}

                <div
                    class="analytics-reveal
                           rounded-3xl
                           border border-slate-200/70
                           bg-white
                           p-5 sm:p-6
                           shadow-sm"
                >

                    <div
                        class="flex flex-col
                               sm:flex-row
                               sm:items-center
                               sm:justify-between
                               gap-4"
                    >

                        <div>

                            <p
                                class="text-xs font-semibold
                                       uppercase tracking-[0.16em]
                                       text-blue-600"
                            >
                                Sheet {{ (int) $sheet->sheet_index + 1 }}
                            </p>

                            <h3
                                class="mt-1
                                       text-xl font-bold
                                       text-slate-900"
                            >
                                {{ $sheet->sheet_name }}
                            </h3>

                        </div>


                        <div
                            class="flex flex-wrap
                                   items-center
                                   gap-2"
                        >

                            <span
                                class="rounded-xl
                                       bg-slate-100
                                       px-3 py-1.5
                                       text-xs font-semibold
                                       text-slate-600"
                            >
                                {{ number_format($sheet->row_count) }} baris
                            </span>

                            <span
                                class="rounded-xl
                                       bg-slate-100
                                       px-3 py-1.5
                                       text-xs font-semibold
                                       text-slate-600"
                            >
                                {{ number_format($sheet->column_count) }} kolom
                            </span>

                            <span
                                class="rounded-xl
                                       bg-blue-50
                                       px-3 py-1.5
                                       text-xs font-semibold
                                       text-blue-700"
                            >
                                {{ $statistics->count() }} tipe kolom dianalisis
                            </span>

                        </div>

                    </div>

                </div>


                {{-- CHARTS --}}

                <div
                    class="grid
                           xl:grid-cols-2
                           gap-5"
                >

                    <article
                        class="analytics-reveal
                               rounded-3xl
                               border border-slate-200/70
                               bg-white
                               shadow-sm
                               overflow-hidden"
                    >

                        <div
                            class="px-5 sm:px-6
                                   py-5
                                   border-b border-slate-100"
                        >
                            <h3
                                class="font-bold
                                       text-slate-900"
                            >
                                Distribusi Kategori
                            </h3>

                            <p
                                class="mt-1
                                       text-sm
                                       text-slate-500"
                            >
                                Komposisi kategori dihitung dari seluruh data sheet,
                                bukan hanya data preview.
                            </p>
                        </div>

                        <div
                            class="p-5 sm:p-6
                                   space-y-6"
                            data-category-chart-zone="{{ (int) $sheet->sheet_index }}"
                        ></div>

                    </article>


                    <article
                        class="analytics-reveal
                               rounded-3xl
                               border border-slate-200/70
                               bg-white
                               shadow-sm
                               overflow-hidden"
                    >

                        <div
                            class="px-5 sm:px-6
                                   py-5
                                   border-b border-slate-100"
                        >
                            <h3
                                class="font-bold
                                       text-slate-900"
                            >
                                Rentang Statistik Numerik
                            </h3>

                            <p
                                class="mt-1
                                       text-sm
                                       text-slate-500"
                            >
                                Minimum, rata-rata, maksimum, dan total berdasarkan
                                seluruh nilai numerik pada sheet.
                            </p>
                        </div>

                        <div
                            class="p-5 sm:p-6
                                   space-y-6"
                            data-numeric-chart-zone="{{ (int) $sheet->sheet_index }}"
                        ></div>

                    </article>

                </div>


                {{-- DETECTED PATTERNS --}}

                @if($suggestions->isNotEmpty())

                    <article
                        class="analytics-reveal
                               rounded-3xl
                               border border-blue-100
                               bg-blue-50/60
                               p-5 sm:p-6"
                    >

                        <div
                            class="flex flex-col
                                   md:flex-row
                                   md:items-start
                                   md:justify-between
                                   gap-4"
                        >

                            <div>

                                <h3
                                    class="font-bold
                                           text-blue-950"
                                >
                                    Pola Visualisasi Terdeteksi
                                </h3>

                                <p
                                    class="mt-1
                                           text-sm
                                           text-blue-800/70"
                                >
                                    Saran dibuat secara deterministic
                                    dari kombinasi tipe data pada sheet ini.
                                </p>

                            </div>

                            <div
                                class="flex flex-wrap
                                       gap-2"
                            >

                                @foreach($suggestions as $suggestion)

                                    <span
                                        class="inline-flex
                                               items-center
                                               gap-2
                                               rounded-xl
                                               bg-white
                                               border border-blue-100
                                               px-3 py-2
                                               text-xs
                                               text-blue-800"
                                        title="{{ $suggestion['reason'] ?? '' }}"
                                    >
                                        <strong
                                            class="uppercase
                                                   text-[10px]
                                                   tracking-wider"
                                        >
                                            {{ $suggestion['type'] ?? 'chart' }}
                                        </strong>

                                        <span>
                                            {{ $suggestion['title'] ?? 'Visualisasi Data' }}
                                        </span>
                                    </span>

                                @endforeach

                            </div>

                        </div>

                    </article>

                @endif


                {{-- COLUMN STATISTICS — PREMIUM / COLLAPSIBLE --}}

                <article
                    id="smartfind-column-statistics-{{ (int) $sheet->sheet_index }}"
                    class="analytics-reveal
                           analytics-premium-panel
                           overflow-hidden"
                    data-column-statistics-root
                >

                    <div
                        class="analytics-premium-panel-header
                               px-5
                               py-5
                               sm:px-6"
                    >

                        <div
                            class="flex
                                   flex-col
                                   gap-4
                                   xl:flex-row
                                   xl:items-end
                                   xl:justify-between"
                        >

                            <div>

                                <p
                                    class="text-[10px]
                                           font-bold
                                           uppercase
                                           tracking-[0.18em]
                                           text-violet-600"
                                >
                                    Column Intelligence
                                </p>

                                <h3
                                    class="mt-1
                                           text-lg
                                           font-bold
                                           text-slate-950"
                                >
                                    Statistik per Kolom
                                </h3>

                                <p
                                    class="mt-1
                                           max-w-2xl
                                           text-sm
                                           leading-relaxed
                                           text-slate-500"
                                >
                                    Ringkasan dibuat lebih sederhana.
                                    Klik satu kolom untuk membuka statistik detail
                                    sehingga halaman tidak penuh angka sekaligus.
                                </p>

                            </div>


                            <div
                                class="flex
                                       flex-wrap
                                       gap-2"
                            >
                                <button
                                    type="button"
                                    class="analytics-column-control"
                                    data-expand-all-columns
                                >
                                    Buka Semua
                                </button>

                                <button
                                    type="button"
                                    class="analytics-column-control"
                                    data-collapse-all-columns
                                >
                                    Tutup Semua
                                </button>
                            </div>

                        </div>


                        <div
                            class="mt-5
                                   grid
                                   gap-3
                                   xl:grid-cols-[minmax(260px,1fr)_auto]"
                        >

                            <label class="relative block">

                                <span
                                    class="pointer-events-none
                                           absolute
                                           left-3.5
                                           top-1/2
                                           -translate-y-1/2
                                           text-slate-400"
                                >
                                    <svg
                                        width="17"
                                        height="17"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <circle cx="11" cy="11" r="8"/>
                                        <path d="m21 21-4.3-4.3"/>
                                    </svg>
                                </span>

                                <input
                                    type="search"
                                    data-column-search
                                    placeholder="Cari nama kolom..."
                                    class="w-full
                                           rounded-xl
                                           border-slate-200
                                           bg-white
                                           py-2.5
                                           pl-10
                                           pr-4
                                           text-sm
                                           text-slate-700
                                           shadow-sm
                                           placeholder:text-slate-400
                                           focus:border-blue-500
                                           focus:ring-blue-500"
                                >

                            </label>


                            <div
                                class="analytics-column-filter-scroll
                                       flex
                                       items-center
                                       gap-2
                                       overflow-x-auto"
                            >

                                <button
                                    type="button"
                                    class="analytics-type-filter is-active"
                                    data-column-type-filter="all"
                                >
                                    Semua
                                </button>

                                @foreach([
                                    'numeric' => 'Numerik',
                                    'categorical' => 'Kategori',
                                    'text' => 'Teks',
                                    'date' => 'Tanggal',
                                    'boolean' => 'Boolean',
                                    'mixed' => 'Campuran',
                                    'empty' => 'Kosong',
                                ] as $filterType => $filterLabel)

                                    @if(
                                        $statistics->contains(
                                            fn ($column) =>
                                                ($column['type'] ?? 'mixed')
                                                === $filterType
                                        )
                                    )

                                        <button
                                            type="button"
                                            class="analytics-type-filter"
                                            data-column-type-filter="{{ $filterType }}"
                                        >
                                            {{ $filterLabel }}
                                        </button>

                                    @endif

                                @endforeach

                            </div>

                        </div>


                        <div
                            class="mt-3
                                   flex
                                   flex-wrap
                                   items-center
                                   justify-between
                                   gap-2
                                   text-[11px]
                                   text-slate-400"
                        >
                            <span>
                                <strong
                                    class="text-slate-700"
                                    data-visible-column-count
                                >
                                    {{ $statistics->count() }}
                                </strong>
                                dari {{ $statistics->count() }} kolom ditampilkan
                            </span>

                            <span>
                                Search dan filter hanya mengubah tampilan.
                            </span>
                        </div>

                    </div>


                    <div
                        class="grid
                               gap-4
                               p-5
                               sm:p-6
                               xl:grid-cols-2"
                        data-column-card-grid
                    >

                        @forelse($statistics as $column)

                            @php
                                $columnType =
                                    $column['type']
                                    ?? 'mixed';

                                $typeLabel = match($columnType) {
                                    'numeric' => 'Numerik',
                                    'date' => 'Tanggal',
                                    'categorical' => 'Kategori',
                                    'boolean' => 'Boolean',
                                    'text' => 'Teks',
                                    'empty' => 'Kosong',
                                    default => 'Campuran',
                                };

                                $typeClasses = match($columnType) {
                                    'numeric' =>
                                        'bg-blue-50 text-blue-700 border-blue-100',

                                    'date' =>
                                        'bg-cyan-50 text-cyan-700 border-cyan-100',

                                    'categorical' =>
                                        'bg-violet-50 text-violet-700 border-violet-100',

                                    'boolean' =>
                                        'bg-emerald-50 text-emerald-700 border-emerald-100',

                                    'text' =>
                                        'bg-slate-100 text-slate-700 border-slate-200',

                                    'empty' =>
                                        'bg-slate-50 text-slate-500 border-slate-200',

                                    default =>
                                        'bg-amber-50 text-amber-700 border-amber-100',
                                };

                                $nonEmpty =
                                    max(
                                        0,
                                        (int) (
                                            $column['non_empty_count']
                                            ?? 0
                                        )
                                    );

                                $blank =
                                    max(
                                        0,
                                        (int) (
                                            $column['blank_count']
                                            ?? 0
                                        )
                                    );

                                $columnTotal =
                                    $nonEmpty
                                    + $blank;

                                $columnCompleteness =
                                    $columnTotal > 0
                                        ? round(
                                            ($nonEmpty / $columnTotal) * 100,
                                            1
                                        )
                                        : 0;

                                $uniqueCount =
                                    max(
                                        0,
                                        (int) (
                                            $column['unique_count']
                                            ?? 0
                                        )
                                    );

                                $numeric =
                                    is_array(
                                        $column['numeric']
                                        ?? null
                                    )
                                        ? $column['numeric']
                                        : null;

                                $date =
                                    is_array(
                                        $column['date']
                                        ?? null
                                    )
                                        ? $column['date']
                                        : null;

                                $topValues =
                                    collect(
                                        $column['top_values']
                                        ?? []
                                    )->take(6);

                                $columnSearchText =
                                    mb_strtolower(
                                        (
                                            $column['header']
                                            ?? ''
                                        )
                                        . ' '
                                        . (
                                            $column['letter']
                                            ?? ''
                                        )
                                        . ' '
                                        . $typeLabel
                                    );
                            @endphp


                            <details
                                class="analytics-column-card"
                                data-column-stat-card
                                data-column-type="{{ $columnType }}"
                                data-column-search-text="{{ $columnSearchText }}"
                            >

                                <summary class="analytics-column-summary">

                                    <div
                                        class="flex
                                               min-w-0
                                               flex-1
                                               items-start
                                               gap-3.5"
                                    >

                                        <div
                                            class="flex
                                                   h-11
                                                   w-11
                                                   flex-shrink-0
                                                   items-center
                                                   justify-center
                                                   rounded-2xl
                                                   border
                                                   text-sm
                                                   font-black
                                                   {{ $typeClasses }}"
                                        >
                                            {{ $column['letter'] ?? '?' }}
                                        </div>


                                        <div class="min-w-0 flex-1">

                                            <div
                                                class="flex
                                                       flex-wrap
                                                       items-center
                                                       gap-2"
                                            >
                                                <h4
                                                    class="max-w-full
                                                           truncate
                                                           text-sm
                                                           font-bold
                                                           text-slate-900"
                                                    title="{{ $column['header'] ?? '-' }}"
                                                >
                                                    {{ $column['header'] ?? '-' }}
                                                </h4>

                                                <span
                                                    class="rounded-lg
                                                           border
                                                           px-2
                                                           py-0.5
                                                           text-[9px]
                                                           font-bold
                                                           uppercase
                                                           tracking-wider
                                                           {{ $typeClasses }}"
                                                >
                                                    {{ $typeLabel }}
                                                </span>
                                            </div>


                                            <div
                                                class="mt-2
                                                       flex
                                                       items-center
                                                       gap-3"
                                            >
                                                <div
                                                    class="h-1.5
                                                           min-w-0
                                                           flex-1
                                                           overflow-hidden
                                                           rounded-full
                                                           bg-slate-100"
                                                >
                                                    <div
                                                        class="h-full
                                                               rounded-full
                                                               bg-gradient-to-r
                                                               from-blue-600
                                                               to-cyan-500"
                                                        style="width: {{ $columnCompleteness }}%;"
                                                    ></div>
                                                </div>

                                                <strong
                                                    class="flex-shrink-0
                                                           text-[10px]
                                                           text-slate-500"
                                                >
                                                    {{
                                                        number_format(
                                                            $columnCompleteness,
                                                            1,
                                                            ',',
                                                            '.'
                                                        )
                                                    }}%
                                                </strong>
                                            </div>


                                            <div
                                                class="mt-2.5
                                                       flex
                                                       flex-wrap
                                                       gap-x-4
                                                       gap-y-1
                                                       text-[11px]
                                                       text-slate-400"
                                            >
                                                <span>
                                                    Terisi
                                                    <strong class="text-slate-700">
                                                        {{ number_format($nonEmpty) }}
                                                    </strong>
                                                </span>

                                                <span>
                                                    Kosong
                                                    <strong class="text-slate-700">
                                                        {{ number_format($blank) }}
                                                    </strong>
                                                </span>

                                                <span>
                                                    Unik
                                                    <strong class="text-slate-700">
                                                        {{ number_format($uniqueCount) }}
                                                        @if($column['unique_count_truncated'] ?? false)
                                                            +
                                                        @endif
                                                    </strong>
                                                </span>
                                            </div>

                                        </div>

                                    </div>


                                    <span class="analytics-column-chevron">
                                        <svg
                                            width="15"
                                            height="15"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path d="m6 9 6 6 6-6"/>
                                        </svg>
                                    </span>

                                </summary>


                                <div
                                    class="border-t
                                           border-slate-100
                                           bg-slate-50/50
                                           p-4
                                           sm:p-5"
                                >

                                    <div
                                        class="grid
                                               grid-cols-2
                                               gap-2.5
                                               sm:grid-cols-4"
                                    >

                                        @foreach([
                                            'Angka' => (int) ($column['numeric_count'] ?? 0),
                                            'Tanggal' => (int) ($column['date_count'] ?? 0),
                                            'Teks' => (int) ($column['text_count'] ?? 0),
                                            'Boolean' => (int) ($column['boolean_count'] ?? 0),
                                        ] as $detailLabel => $detailCount)

                                            <div class="analytics-column-mini-stat">
                                                <span>{{ $detailLabel }}</span>
                                                <strong>{{ number_format($detailCount) }}</strong>
                                            </div>

                                        @endforeach

                                    </div>


                                    @if($numeric !== null)

                                        <div class="mt-4">

                                            <p
                                                class="text-[10px]
                                                       font-bold
                                                       uppercase
                                                       tracking-[0.16em]
                                                       text-blue-600"
                                            >
                                                Ringkasan Numerik
                                            </p>

                                            <div
                                                class="mt-2.5
                                                       grid
                                                       grid-cols-2
                                                       gap-2.5
                                                       sm:grid-cols-4"
                                            >

                                                @foreach([
                                                    'Minimum' => $numeric['min'] ?? null,
                                                    'Rata-rata' => $numeric['average'] ?? null,
                                                    'Maksimum' => $numeric['max'] ?? null,
                                                    'Total' => $numeric['sum'] ?? null,
                                                ] as $numericLabel => $numericValue)

                                                    <div class="analytics-numeric-mini-stat">
                                                        <span>{{ $numericLabel }}</span>
                                                        <strong>
                                                            {{
                                                                is_numeric($numericValue)
                                                                    ? number_format(
                                                                        (float) $numericValue,
                                                                        2,
                                                                        ',',
                                                                        '.'
                                                                    )
                                                                    : '-'
                                                            }}
                                                        </strong>
                                                    </div>

                                                @endforeach

                                            </div>

                                        </div>

                                    @elseif($date !== null)

                                        <div
                                            class="mt-4
                                                   rounded-2xl
                                                   border
                                                   border-cyan-100
                                                   bg-cyan-50/70
                                                   p-4"
                                        >
                                            <p
                                                class="text-[10px]
                                                       font-bold
                                                       uppercase
                                                       tracking-[0.16em]
                                                       text-cyan-600"
                                            >
                                                Rentang Tanggal
                                            </p>

                                            <p
                                                class="mt-2
                                                       text-sm
                                                       font-semibold
                                                       text-cyan-950"
                                            >
                                                {{ $date['min'] ?? '-' }}
                                                <span class="mx-2 text-cyan-300">→</span>
                                                {{ $date['max'] ?? '-' }}
                                            </p>
                                        </div>

                                    @elseif($topValues->isNotEmpty())

                                        <div class="mt-4">

                                            <p
                                                class="text-[10px]
                                                       font-bold
                                                       uppercase
                                                       tracking-[0.16em]
                                                       text-violet-600"
                                            >
                                                Nilai Paling Sering Muncul
                                            </p>


                                            <div class="mt-3 space-y-2.5">

                                                @foreach($topValues as $topValue)

                                                    @php
                                                        $topCount =
                                                            max(
                                                                0,
                                                                (int) (
                                                                    $topValue['count']
                                                                    ?? 0
                                                                )
                                                            );

                                                        $topPercent =
                                                            $nonEmpty > 0
                                                                ? min(
                                                                    100,
                                                                    ($topCount / $nonEmpty) * 100
                                                                )
                                                                : 0;

                                                        $topDisplay =
                                                            is_bool(
                                                                $topValue['value']
                                                                ?? null
                                                            )
                                                                ? (
                                                                    ($topValue['value'] ?? false)
                                                                        ? 'Ya'
                                                                        : 'Tidak'
                                                                )
                                                                : (
                                                                    $topValue['value']
                                                                    ?? '-'
                                                                );
                                                    @endphp

                                                    <div class="analytics-top-value-row">

                                                        <div
                                                            class="flex
                                                                   items-center
                                                                   justify-between
                                                                   gap-3"
                                                        >
                                                            <span
                                                                class="min-w-0
                                                                       truncate
                                                                       text-xs
                                                                       font-medium
                                                                       text-slate-600"
                                                                title="{{ (string) $topDisplay }}"
                                                            >
                                                                {{ $topDisplay }}
                                                            </span>

                                                            <strong
                                                                class="flex-shrink-0
                                                                       text-xs
                                                                       text-slate-800"
                                                            >
                                                                {{ number_format($topCount) }}
                                                            </strong>
                                                        </div>

                                                        <div
                                                            class="mt-2
                                                                   h-1.5
                                                                   overflow-hidden
                                                                   rounded-full
                                                                   bg-slate-100"
                                                        >
                                                            <div
                                                                class="h-full
                                                                       rounded-full
                                                                       bg-gradient-to-r
                                                                       from-violet-500
                                                                       to-blue-500"
                                                                style="width: {{ $topPercent }}%;"
                                                            ></div>
                                                        </div>

                                                    </div>

                                                @endforeach

                                            </div>

                                        </div>

                                    @else

                                        <div
                                            class="mt-4
                                                   rounded-xl
                                                   border
                                                   border-dashed
                                                   border-slate-200
                                                   bg-white
                                                   p-4
                                                   text-xs
                                                   leading-relaxed
                                                   text-slate-400"
                                        >
                                            Tidak ada ringkasan tambahan yang relevan
                                            untuk tipe kolom ini.
                                        </div>

                                    @endif

                                </div>

                            </details>

                        @empty

                            <div
                                class="xl:col-span-2
                                       rounded-2xl
                                       border
                                       border-dashed
                                       border-slate-200
                                       bg-slate-50
                                       py-10
                                       text-center
                                       text-sm
                                       text-slate-400"
                            >
                                Belum ada statistik kolom.
                            </div>

                        @endforelse


                        <div
                            class="hidden
                                   xl:col-span-2
                                   rounded-2xl
                                   border
                                   border-dashed
                                   border-slate-200
                                   bg-slate-50
                                   py-10
                                   text-center"
                            data-column-no-result
                        >
                            <p class="text-sm font-semibold text-slate-600">
                                Kolom tidak ditemukan
                            </p>

                            <p class="mt-1 text-xs text-slate-400">
                                Ubah kata pencarian atau filter tipe kolom.
                            </p>
                        </div>

                    </div>

                </article>


                {{-- PREVIEW --}}

                <article
                    class="analytics-reveal
                           rounded-3xl
                           border border-slate-200/70
                           bg-white
                           shadow-sm
                           overflow-hidden"
                >

                    <div
                        class="px-5 sm:px-6
                               py-5
                               flex flex-col
                               sm:flex-row
                               sm:items-center
                               sm:justify-between
                               gap-3
                               border-b border-slate-100"
                    >

                        <div>

                            <h3
                                class="font-bold
                                       text-slate-900"
                            >
                                Preview Data
                            </h3>

                            <p
                                class="mt-1
                                       text-sm
                                       text-slate-500"
                            >
                                Menampilkan maksimal
                                {{ number_format(
                                    config(
                                        'smartfind_analytics.preview_rows',
                                        20
                                    )
                                ) }}
                                baris pertama yang disimpan oleh parser.
                            </p>

                        </div>

                        <span
                            class="inline-flex
                                   self-start
                                   rounded-xl
                                   bg-slate-100
                                   px-3 py-1.5
                                   text-xs font-semibold
                                   text-slate-600"
                        >
                            {{ number_format($previewRows->count()) }}
                            baris preview
                        </span>

                    </div>


                    @if($headers->isNotEmpty() && $previewRows->isNotEmpty())

                        <div
                            class="sm:hidden
                                   px-5 pt-4
                                   text-[11px]
                                   font-medium
                                   text-slate-400"
                        >
                            Geser tabel ke samping untuk melihat seluruh kolom.
                        </div>

                        <div
                            class="analytics-table-scroll
                                   overflow-auto"
                        >

                            <table
                                class="analytics-preview-table
                                       min-w-full
                                       text-left"
                            >

                                <thead
                                    class="bg-slate-50"
                                >
                                    <tr>

                                        <th
                                            class="sticky left-0 z-10
                                                   bg-slate-50
                                                   px-4 py-3
                                                   text-[11px]
                                                   font-bold
                                                   uppercase tracking-wider
                                                   text-slate-400"
                                        >
                                            #
                                        </th>

                                        @foreach($headers as $header)

                                            <th
                                                class="whitespace-nowrap
                                                       px-4 py-3
                                                       text-[11px]
                                                       font-bold
                                                       uppercase tracking-wider
                                                       text-slate-500"
                                            >
                                                {{ $header }}
                                            </th>

                                        @endforeach

                                    </tr>
                                </thead>


                                <tbody
                                    class="divide-y
                                           divide-slate-100"
                                >

                                    @foreach($previewRows as $rowIndex => $row)

                                        <tr
                                            class="hover:bg-blue-50/30
                                                   transition"
                                        >

                                            <td
                                                class="sticky left-0
                                                       bg-white
                                                       px-4 py-3
                                                       text-xs font-semibold
                                                       text-slate-400"
                                            >
                                                {{ $rowIndex + 1 }}
                                            </td>

                                            @foreach($headers as $header)

                                                @php
                                                    $cellValue = $row[$header] ?? null;
                                                @endphp

                                                <td
                                                    class="max-w-[320px]
                                                           px-4 py-3
                                                           text-sm
                                                           text-slate-700"
                                                >
                                                    @if($cellValue === null || $cellValue === '')
                                                        <span class="text-slate-300">
                                                            —
                                                        </span>
                                                    @elseif(is_bool($cellValue))
                                                        {{ $cellValue ? 'Ya' : 'Tidak' }}
                                                    @else
                                                        <span
                                                            class="block
                                                                   truncate"
                                                            title="{{ (string) $cellValue }}"
                                                        >
                                                            {{ $cellValue }}
                                                        </span>
                                                    @endif
                                                </td>

                                            @endforeach

                                        </tr>

                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                    @else

                        <div
                            class="p-10
                                   text-center"
                        >
                            <p
                                class="text-sm font-semibold
                                       text-slate-600"
                            >
                                Tidak ada data preview
                            </p>

                            <p
                                class="mt-1
                                       text-xs
                                       text-slate-400"
                            >
                                Sheet ini tidak memiliki baris data
                                yang dapat ditampilkan.
                            </p>
                        </div>

                    @endif

                </article>



            </section>

        @endforeach

    @endif


    {{-- ========================================================= --}}
    {{-- CHART DATA --}}
    {{-- ========================================================= --}}

    @if($documentAnalytics?->status === 'ready')

        <script
            id="document-analytics-chart-data"
            type="application/json"
        >@json($sheetChartPayload)</script>

    @endif


    {{-- ========================================================= --}}
    {{-- STYLE / ANIMATION --}}
    {{-- ========================================================= --}}

    <style>
        /*
        |--------------------------------------------------------------------------
        | TAHAP 16G — MOTION SYSTEM
        |--------------------------------------------------------------------------
        */

        #document-analytics-dashboard {
            --analytics-ease:
                cubic-bezier(0.22, 1, 0.36, 1);
        }

        #document-analytics-dashboard .analytics-reveal {
            opacity: 1;
            transform: none;
        }

        #document-analytics-dashboard .analytics-reveal.analytics-motion-ready {
            opacity: 0;
            transform: translateY(14px);
            transition:
                opacity 0.48s var(--analytics-ease),
                transform 0.48s var(--analytics-ease);
        }

        #document-analytics-dashboard .analytics-reveal.analytics-motion-ready.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        #document-analytics-dashboard .analytics-card {
            transition:
                transform 0.24s var(--analytics-ease),
                box-shadow 0.24s var(--analytics-ease),
                border-color 0.24s ease;
        }

        #document-analytics-dashboard .analytics-card:hover {
            transform: translateY(-3px);
            border-color: rgba(59, 130, 246, 0.22);
            box-shadow:
                0 16px 34px
                rgba(15, 23, 42, 0.07);
        }


        /*
        |--------------------------------------------------------------------------
        | SHEET NAVIGATION
        |--------------------------------------------------------------------------
        */

        #document-analytics-dashboard .analytics-tab-scroller {
            scrollbar-width: thin;
            scrollbar-color:
                rgb(203 213 225)
                transparent;
            scroll-behavior: smooth;
        }

        #document-analytics-dashboard .analytics-tab-scroller::-webkit-scrollbar {
            height: 5px;
        }

        #document-analytics-dashboard .analytics-tab-scroller::-webkit-scrollbar-thumb {
            background: rgb(203 213 225);
            border-radius: 999px;
        }

        #document-analytics-dashboard .analytics-sheet-tab {
            min-height: 38px;
            outline: none;
        }

        #document-analytics-dashboard .analytics-sheet-tab:focus-visible {
            box-shadow:
                0 0 0 3px
                rgba(59, 130, 246, 0.18);
        }

        #document-analytics-dashboard .analytics-sheet-tab[aria-selected="true"] {
            border-color: rgb(37 99 235);
            background: rgb(37 99 235);
            color: white;
            box-shadow:
                0 8px 20px
                rgba(37, 99, 235, 0.16);
        }

        #document-analytics-dashboard .analytics-sheet-panel.analytics-panel-enter {
            animation:
                analyticsPanelEnter
                0.30s
                var(--analytics-ease)
                both;
        }


        /*
        |--------------------------------------------------------------------------
        | CHART
        |--------------------------------------------------------------------------
        */

        #document-analytics-dashboard .analytics-chart-empty {
            min-height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: rgb(148 163 184);
            font-size: 12px;
        }

        #document-analytics-dashboard .analytics-chart-block {
            animation:
                analyticsChartEnter
                0.44s
                var(--analytics-ease)
                both;
        }

        #document-analytics-dashboard .analytics-bar-fill {
            transform-origin: left center;
            animation:
                documentAnalyticsBar
                0.70s
                var(--analytics-ease)
                both;
        }



        /*
        |--------------------------------------------------------------------------
        | PREMIUM ANALYTICS PANELS / COLUMN INTELLIGENCE
        |--------------------------------------------------------------------------
        */

        #document-analytics-dashboard .analytics-premium-panel {
            border: 1px solid rgb(226 232 240);
            border-radius: 1.6rem;
            background: white;
            box-shadow:
                0 12px 36px
                rgba(15, 23, 42, 0.055);
        }

        #document-analytics-dashboard .analytics-premium-panel-header {
            border-bottom: 1px solid rgb(241 245 249);
            background:
                linear-gradient(
                    120deg,
                    rgb(248 250 252),
                    #ffffff 55%,
                    rgba(245, 243, 255, 0.72)
                );
        }

        #document-analytics-dashboard .analytics-column-filter-scroll {
            scrollbar-width: thin;
            scrollbar-color:
                rgb(203 213 225)
                transparent;
        }

        #document-analytics-dashboard .analytics-column-filter-scroll::-webkit-scrollbar {
            height: 5px;
        }

        #document-analytics-dashboard .analytics-column-filter-scroll::-webkit-scrollbar-thumb {
            border-radius: 999px;
            background: rgb(203 213 225);
        }

        #document-analytics-dashboard .analytics-column-control,
        #document-analytics-dashboard .analytics-type-filter {
            flex-shrink: 0;
            border: 1px solid rgb(226 232 240);
            border-radius: 0.75rem;
            background: white;
            padding: 0.5rem 0.75rem;
            color: rgb(100 116 139);
            font-size: 0.68rem;
            font-weight: 700;
            transition:
                border-color 0.16s ease,
                background-color 0.16s ease,
                color 0.16s ease,
                box-shadow 0.16s ease;
        }

        #document-analytics-dashboard .analytics-column-control:hover,
        #document-analytics-dashboard .analytics-type-filter:hover {
            border-color: rgb(191 219 254);
            background: rgb(239 246 255);
            color: rgb(29 78 216);
        }

        #document-analytics-dashboard .analytics-type-filter.is-active {
            border-color: rgb(37 99 235);
            background:
                linear-gradient(
                    135deg,
                    rgb(30 64 175),
                    rgb(37 99 235)
                );
            color: white;
            box-shadow:
                0 7px 16px
                rgba(37, 99, 235, 0.16);
        }

        #document-analytics-dashboard .analytics-column-card {
            overflow: hidden;
            border: 1px solid rgb(226 232 240);
            border-radius: 1.15rem;
            background: white;
            box-shadow:
                0 7px 22px
                rgba(15, 23, 42, 0.035);
            transition:
                transform 0.18s var(--analytics-ease),
                border-color 0.18s ease,
                box-shadow 0.18s ease;
        }

        @media (hover: hover) and (pointer: fine) {
            #document-analytics-dashboard .analytics-column-card:hover {
                transform: translateY(-2px);
                border-color: rgb(191 219 254);
                box-shadow:
                    0 14px 32px
                    rgba(15, 23, 42, 0.07);
            }
        }

        #document-analytics-dashboard .analytics-column-card[open] {
            border-color: rgb(191 219 254);
            box-shadow:
                0 14px 34px
                rgba(37, 99, 235, 0.075);
        }

        #document-analytics-dashboard .analytics-column-summary {
            display: flex;
            cursor: pointer;
            list-style: none;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 1rem;
            user-select: none;
        }

        #document-analytics-dashboard .analytics-column-summary::-webkit-details-marker {
            display: none;
        }

        #document-analytics-dashboard .analytics-column-chevron {
            display: flex;
            width: 2rem;
            height: 2rem;
            flex: 0 0 2rem;
            align-items: center;
            justify-content: center;
            border-radius: 0.7rem;
            background: rgb(241 245 249);
            color: rgb(100 116 139);
            transition:
                transform 0.18s var(--analytics-ease),
                background-color 0.18s ease,
                color 0.18s ease;
        }

        #document-analytics-dashboard .analytics-column-card[open]
        .analytics-column-chevron {
            transform: rotate(180deg);
            background: rgb(239 246 255);
            color: rgb(37 99 235);
        }

        #document-analytics-dashboard .analytics-column-mini-stat,
        #document-analytics-dashboard .analytics-numeric-mini-stat {
            border: 1px solid rgb(226 232 240);
            border-radius: 0.8rem;
            background: white;
            padding: 0.7rem;
        }

        #document-analytics-dashboard .analytics-column-mini-stat span,
        #document-analytics-dashboard .analytics-numeric-mini-stat span {
            display: block;
            color: rgb(148 163 184);
            font-size: 0.625rem;
            font-weight: 600;
        }

        #document-analytics-dashboard .analytics-column-mini-stat strong,
        #document-analytics-dashboard .analytics-numeric-mini-stat strong {
            display: block;
            margin-top: 0.2rem;
            overflow-wrap: anywhere;
            color: rgb(30 41 59);
            font-size: 0.8rem;
            font-weight: 800;
        }

        #document-analytics-dashboard .analytics-numeric-mini-stat {
            border-color: rgb(219 234 254);
            background: rgba(239, 246, 255, 0.72);
        }

        #document-analytics-dashboard .analytics-numeric-mini-stat span {
            color: rgb(96 165 250);
        }

        #document-analytics-dashboard .analytics-numeric-mini-stat strong {
            color: rgb(30 58 138);
        }

        #document-analytics-dashboard .analytics-top-value-row {
            border: 1px solid rgb(226 232 240);
            border-radius: 0.8rem;
            background: white;
            padding: 0.65rem 0.75rem;
        }


        /*
        |--------------------------------------------------------------------------
        | PREMIUM CHART VISUAL
        |--------------------------------------------------------------------------
        */

        #document-analytics-dashboard [data-category-chart-zone],
        #document-analytics-dashboard [data-numeric-chart-zone] {
            min-height: 215px;
        }

        #document-analytics-dashboard .analytics-chart-block {
            overflow: hidden;
            border: 1px solid rgb(226 232 240);
            border-radius: 1.15rem;
            background:
                linear-gradient(
                    180deg,
                    #ffffff,
                    #fbfdff
                );
            padding: 1rem;
            box-shadow:
                0 7px 20px
                rgba(15, 23, 42, 0.035);
            animation:
                analyticsChartEnter
                0.44s
                var(--analytics-ease)
                both;
        }

        #document-analytics-dashboard .analytics-chart-empty {
            min-height: 210px;
            border: 1px dashed rgb(203 213 225);
            border-radius: 1rem;
            background: rgb(248 250 252);
        }

        #document-analytics-dashboard .analytics-donut-segment {
            transition:
                opacity 0.18s ease,
                filter 0.18s ease;
        }

        #document-analytics-dashboard .analytics-donut-svg:hover
        .analytics-donut-segment {
            opacity: 0.62;
        }

        #document-analytics-dashboard .analytics-donut-svg
        .analytics-donut-segment:hover {
            opacity: 1;
            filter:
                drop-shadow(
                    0 3px 4px
                    rgba(15, 23, 42, 0.14)
                );
        }

        #document-analytics-dashboard .analytics-range-track {
            position: relative;
            height: 0.6rem;
            border-radius: 999px;
            background:
                linear-gradient(
                    90deg,
                    rgb(219 234 254),
                    rgb(186 230 253)
                );
        }

        #document-analytics-dashboard .analytics-range-marker {
            position: absolute;
            top: 50%;
            width: 0.95rem;
            height: 0.95rem;
            transform: translate(-50%, -50%);
            border: 3px solid white;
            border-radius: 999px;
            background: rgb(37 99 235);
            box-shadow:
                0 2px 8px
                rgba(37, 99, 235, 0.3);
        }


        /*
        |--------------------------------------------------------------------------
        | PREVIEW TABLE
        |--------------------------------------------------------------------------
        */

        #document-analytics-dashboard .analytics-table-scroll {
            max-height: min(62vh, 560px);
            overscroll-behavior: contain;
            scrollbar-width: thin;
            scrollbar-color:
                rgb(203 213 225)
                transparent;
        }

        #document-analytics-dashboard .analytics-table-scroll::-webkit-scrollbar {
            width: 7px;
            height: 7px;
        }

        #document-analytics-dashboard .analytics-table-scroll::-webkit-scrollbar-thumb {
            background: rgb(203 213 225);
            border-radius: 999px;
        }

        #document-analytics-dashboard .analytics-preview-table {
            min-width: max-content;
        }

        #document-analytics-dashboard .analytics-preview-table thead {
            position: sticky;
            top: 0;
            z-index: 20;
        }

        #document-analytics-dashboard .analytics-preview-table tbody tr {
            transition:
                background-color 0.18s ease;
        }


        /*
        |--------------------------------------------------------------------------
        | LOADING
        |--------------------------------------------------------------------------
        */

        #document-analytics-dashboard .analytics-loading-ring {
            animation:
                analyticsSpin
                0.85s
                linear
                infinite;
        }

        #document-analytics-dashboard .analytics-loading-dot {
            width: 5px;
            height: 5px;
            border-radius: 999px;
            background: currentColor;
            opacity: 0.35;
            animation:
                analyticsDot
                1.2s
                ease-in-out
                infinite;
        }

        #document-analytics-dashboard .analytics-loading-dot:nth-child(2) {
            animation-delay: 0.14s;
        }

        #document-analytics-dashboard .analytics-loading-dot:nth-child(3) {
            animation-delay: 0.28s;
        }

        #document-analytics-dashboard .analytics-skeleton {
            position: relative;
            overflow: hidden;
            background: rgb(241 245 249);
        }

        #document-analytics-dashboard .analytics-skeleton::after {
            content: "";
            position: absolute;
            inset: 0;
            transform: translateX(-100%);
            background:
                linear-gradient(
                    90deg,
                    transparent,
                    rgba(255, 255, 255, 0.78),
                    transparent
                );
            animation:
                analyticsShimmer
                1.45s
                ease-in-out
                infinite;
        }

        #document-analytics-dashboard .analytics-skeleton-card {
            animation:
                analyticsSkeletonFade
                0.40s
                var(--analytics-ease)
                both;
        }


        /*
        |--------------------------------------------------------------------------
        | KEYFRAMES
        |--------------------------------------------------------------------------
        */

        @keyframes analyticsPanelEnter {
            from {
                opacity: 0;
                transform: translateY(6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes analyticsChartEnter {
            from {
                opacity: 0;
                transform: scale(0.985);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes documentAnalyticsBar {
            from {
                opacity: 0.40;
                transform: scaleX(0);
            }

            to {
                opacity: 1;
                transform: scaleX(1);
            }
        }

        @keyframes analyticsSpin {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes analyticsDot {
            0%,
            60%,
            100% {
                opacity: 0.30;
                transform: translateY(0);
            }

            30% {
                opacity: 1;
                transform: translateY(-2px);
            }
        }

        @keyframes analyticsShimmer {
            100% {
                transform: translateX(100%);
            }
        }

        @keyframes analyticsSkeletonFade {
            from {
                opacity: 0;
                transform: translateY(5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE POLISH
        |--------------------------------------------------------------------------
        */

        @media (max-width: 639px) {
            #document-analytics-dashboard {
                margin-left: -0.125rem;
                margin-right: -0.125rem;
            }

            #document-analytics-dashboard .analytics-card:hover {
                transform: none;
            }

            #document-analytics-dashboard .analytics-table-scroll {
                max-height: 440px;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | ACCESSIBILITY — REDUCED MOTION
        |--------------------------------------------------------------------------
        */

        @media (prefers-reduced-motion: reduce) {
            #document-analytics-dashboard *,
            #document-analytics-dashboard *::before,
            #document-analytics-dashboard *::after {
                scroll-behavior: auto !important;
            }

            #document-analytics-dashboard .analytics-reveal,
            #document-analytics-dashboard .analytics-reveal.analytics-motion-ready,
            #document-analytics-dashboard .analytics-reveal.analytics-motion-ready.is-visible,
            #document-analytics-dashboard .analytics-bar-fill,
            #document-analytics-dashboard .analytics-chart-block,
            #document-analytics-dashboard .analytics-sheet-panel.analytics-panel-enter,
            #document-analytics-dashboard .analytics-skeleton-card {
                opacity: 1 !important;
                transform: none !important;
                animation: none !important;
                transition: none !important;
            }

            #document-analytics-dashboard .analytics-loading-ring,
            #document-analytics-dashboard .analytics-loading-dot,
            #document-analytics-dashboard .analytics-skeleton::after {
                animation: none !important;
            }

            #document-analytics-dashboard .analytics-loading-ring {
                border-style: dotted;
            }

            #document-analytics-dashboard .analytics-card {
                transition: none !important;
            }
        }
    </style>


    {{-- ========================================================= --}}
    {{-- INTERACTION + NATIVE SVG CHART --}}
    {{-- ========================================================= --}}

    <script>
        (() => {
            'use strict';

            const root =
                document.getElementById(
                    'document-analytics-dashboard'
                );

            if (!root) {
                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Motion Preference
            |--------------------------------------------------------------------------
            */

            const reduceMotion =
                window.matchMedia(
                    '(prefers-reduced-motion: reduce)'
                ).matches;


            /*
            |--------------------------------------------------------------------------
            | Reveal on Viewport
            |--------------------------------------------------------------------------
            |
            | Progressive enhancement:
            | bila JavaScript gagal, content tetap visible.
            |
            */

            const revealElements =
                Array.from(
                    root.querySelectorAll(
                        '.analytics-reveal'
                    )
                );

            if (
                !reduceMotion
                && 'IntersectionObserver' in window
            ) {

                revealElements.forEach(
                    (element) => {
                        element.classList.add(
                            'analytics-motion-ready'
                        );
                    }
                );

                const revealObserver =
                    new IntersectionObserver(
                        (entries, observer) => {
                            entries.forEach(
                                (entry) => {
                                    if (!entry.isIntersecting) {
                                        return;
                                    }

                                    entry.target
                                        .classList
                                        .add(
                                            'is-visible'
                                        );

                                    observer.unobserve(
                                        entry.target
                                    );
                                }
                            );
                        },
                        {
                            threshold: 0.08,
                            rootMargin: '0px 0px -24px 0px',
                        }
                    );

                revealElements.forEach(
                    (element) => {
                        revealObserver.observe(
                            element
                        );
                    }
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Sheet Tabs
            |--------------------------------------------------------------------------
            */

            const tabs =
                Array.from(
                    root.querySelectorAll(
                        '[data-sheet-tab]'
                    )
                );

            const panels =
                Array.from(
                    root.querySelectorAll(
                        '[data-sheet-panel]'
                    )
                );

            const activateSheet = (
                sheetIndex,
                focusTab = false
            ) => {

                tabs.forEach(
                    (tab) => {
                        const active =
                            tab.dataset.sheetTab
                            === sheetIndex;

                        tab.setAttribute(
                            'aria-selected',
                            active
                                ? 'true'
                                : 'false'
                        );

                        tab.setAttribute(
                            'tabindex',
                            active
                                ? '0'
                                : '-1'
                        );

                        if (active) {
                            tab.scrollIntoView({
                                behavior:
                                    reduceMotion
                                        ? 'auto'
                                        : 'smooth',

                                block:
                                    'nearest',

                                inline:
                                    'nearest',
                            });

                            if (focusTab) {
                                tab.focus({
                                    preventScroll:
                                        true,
                                });
                            }
                        }
                    }
                );

                panels.forEach(
                    (panel) => {

                        const active =
                            panel.dataset.sheetPanel
                            === sheetIndex;

                        panel.classList.toggle(
                            'hidden',
                            !active
                        );

                        if (active) {

                            panel.classList.remove(
                                'analytics-panel-enter'
                            );

                            /*
                            | Force reflow supaya animation bisa diputar
                            | kembali saat pindah sheet.
                            */

                            void panel.offsetWidth;

                            panel.classList.add(
                                'analytics-panel-enter'
                            );
                        }
                    }
                );
            };

            tabs.forEach(
                (tab, index) => {

                    tab.setAttribute(
                        'tabindex',
                        tab.getAttribute(
                            'aria-selected'
                        ) === 'true'
                            ? '0'
                            : '-1'
                    );

                    tab.addEventListener(
                        'click',
                        () => {
                            activateSheet(
                                tab.dataset.sheetTab
                            );
                        }
                    );

                    tab.addEventListener(
                        'keydown',
                        (event) => {

                            const handledKeys = [
                                'ArrowLeft',
                                'ArrowRight',
                                'Home',
                                'End',
                            ];

                            if (
                                !handledKeys.includes(
                                    event.key
                                )
                            ) {
                                return;
                            }

                            event.preventDefault();

                            let nextIndex = index;

                            if (event.key === 'Home') {
                                nextIndex = 0;
                            }

                            if (event.key === 'End') {
                                nextIndex =
                                    tabs.length - 1;
                            }

                            if (event.key === 'ArrowRight') {
                                nextIndex =
                                    (
                                        index + 1
                                    )
                                    % tabs.length;
                            }

                            if (event.key === 'ArrowLeft') {
                                nextIndex =
                                    (
                                        index - 1
                                        + tabs.length
                                    )
                                    % tabs.length;
                            }

                            const nextTab =
                                tabs[nextIndex];

                            if (nextTab) {
                                activateSheet(
                                    nextTab.dataset.sheetTab,
                                    true
                                );
                            }
                        }
                    );
                }
            );


            /*
            |--------------------------------------------------------------------------
            | Counter
            |--------------------------------------------------------------------------
            */

            const formatter =
                new Intl.NumberFormat(
                    'id-ID'
                );

            root.querySelectorAll(
                '.analytics-counter'
            ).forEach(
                (element) => {

                    const target =
                        Number(
                            element.dataset.value
                            ?? 0
                        );

                    if (
                        !Number.isFinite(target)
                        || target < 0
                    ) {
                        return;
                    }

                    if (reduceMotion) {
                        element.textContent =
                            formatter.format(target);

                        return;
                    }

                    const duration = 650;
                    const started =
                        performance.now();

                    const animate = (time) => {

                        const progress =
                            Math.min(
                                (time - started)
                                / duration,
                                1
                            );

                        const eased =
                            1
                            - Math.pow(
                                1 - progress,
                                3
                            );

                        element.textContent =
                            formatter.format(
                                Math.round(
                                    target * eased
                                )
                            );

                        if (progress < 1) {
                            requestAnimationFrame(
                                animate
                            );
                        }
                    };

                    requestAnimationFrame(
                        animate
                    );
                }
            );



            /*
            |--------------------------------------------------------------------------
            | Column Statistics Search / Filter / Expand
            |--------------------------------------------------------------------------
            */

            root.querySelectorAll(
                '[data-column-statistics-root]'
            ).forEach(
                (statisticsRoot) => {

                    const search =
                        statisticsRoot.querySelector(
                            '[data-column-search]'
                        );

                    const cards =
                        Array.from(
                            statisticsRoot.querySelectorAll(
                                '[data-column-stat-card]'
                            )
                        );

                    const filters =
                        Array.from(
                            statisticsRoot.querySelectorAll(
                                '[data-column-type-filter]'
                            )
                        );

                    const visibleCount =
                        statisticsRoot.querySelector(
                            '[data-visible-column-count]'
                        );

                    const noResult =
                        statisticsRoot.querySelector(
                            '[data-column-no-result]'
                        );

                    const expandAll =
                        statisticsRoot.querySelector(
                            '[data-expand-all-columns]'
                        );

                    const collapseAll =
                        statisticsRoot.querySelector(
                            '[data-collapse-all-columns]'
                        );

                    let activeType =
                        'all';

                    const normalizeColumnText =
                        (value) =>
                            String(
                                value
                                ?? ''
                            )
                                .toLocaleLowerCase(
                                    'id-ID'
                                )
                                .trim();

                    const applyColumnFilter =
                        () => {

                            const keyword =
                                normalizeColumnText(
                                    search?.value
                                    ?? ''
                                );

                            let count =
                                0;

                            cards.forEach(
                                (card) => {

                                    const matchesSearch =
                                        keyword === ''
                                        || normalizeColumnText(
                                            card.dataset
                                                .columnSearchText
                                        ).includes(
                                            keyword
                                        );

                                    const matchesType =
                                        activeType === 'all'
                                        || card.dataset
                                            .columnType
                                            === activeType;

                                    const show =
                                        matchesSearch
                                        && matchesType;

                                    card.classList.toggle(
                                        'hidden',
                                        !show
                                    );

                                    if (show) {
                                        count++;
                                    }
                                }
                            );

                            if (visibleCount) {
                                visibleCount.textContent =
                                    formatter.format(
                                        count
                                    );
                            }

                            if (noResult) {
                                noResult.classList.toggle(
                                    'hidden',
                                    count !== 0
                                );
                            }
                        };

                    search?.addEventListener(
                        'input',
                        applyColumnFilter
                    );

                    filters.forEach(
                        (filter) => {

                            filter.addEventListener(
                                'click',
                                () => {

                                    activeType =
                                        filter.dataset
                                            .columnTypeFilter
                                        ?? 'all';

                                    filters.forEach(
                                        (button) => {
                                            button.classList.toggle(
                                                'is-active',
                                                button === filter
                                            );
                                        }
                                    );

                                    applyColumnFilter();
                                }
                            );
                        }
                    );

                    expandAll?.addEventListener(
                        'click',
                        () => {
                            cards.forEach(
                                (card) => {
                                    if (
                                        !card.classList.contains(
                                            'hidden'
                                        )
                                    ) {
                                        card.open =
                                            true;
                                    }
                                }
                            );
                        }
                    );

                    collapseAll?.addEventListener(
                        'click',
                        () => {
                            cards.forEach(
                                (card) => {
                                    card.open =
                                        false;
                                }
                            );
                        }
                    );
                }
            );


            /*
            |--------------------------------------------------------------------------
            | Chart Data
            |--------------------------------------------------------------------------
            */

            const dataElement =
                document.getElementById(
                    'document-analytics-chart-data'
                );

            if (!dataElement) {
                return;
            }

            let chartData = [];

            try {
                chartData =
                    JSON.parse(
                        dataElement.textContent
                    );
            } catch (error) {
                console.error(
                    'BMC QualityHub Document Analytics: data chart tidak valid.',
                    error
                );

                return;
            }

            const colors = [
                '#2563eb',
                '#06b6d4',
                '#7c3aed',
                '#10b981',
                '#f59e0b',
                '#ef4444',
                '#6366f1',
                '#64748b',
                '#14b8a6',
                '#f97316',
            ];

            const escapeHtml = (value) => {
                const element =
                    document.createElement('div');

                element.textContent =
                    String(value ?? '');

                return element.innerHTML;
            };

            const formatNumber = (value) => {

                const numeric =
                    Number(value);

                if (!Number.isFinite(numeric)) {
                    return '0';
                }

                return new Intl.NumberFormat(
                    'id-ID',
                    {
                        maximumFractionDigits: 2,
                    }
                ).format(numeric);
            };

            const emptyChart = (
                element,
                message
            ) => {
                element.innerHTML = `
                    <div class="analytics-chart-empty">
                        <div>
                            <div
                                class="mx-auto
                                       h-10 w-10
                                       rounded-2xl
                                       bg-slate-100
                                       flex items-center justify-center
                                       text-slate-400"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="18"
                                    height="18"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M3 3v18h18"/>
                                    <path d="m7 16 4-5 4 3 5-7"/>
                                </svg>
                            </div>

                            <p class="mt-3">
                                ${escapeHtml(message)}
                            </p>
                        </div>
                    </div>
                `;
            };


            /*
            |--------------------------------------------------------------------------
            | Donut Chart
            |--------------------------------------------------------------------------
            */

            const donutMarkup = (
                chart
            ) => {

                const labels =
                    Array.isArray(
                        chart.labels
                    )
                        ? chart.labels
                        : [];

                const values =
                    Array.isArray(
                        chart.values
                    )
                        ? chart.values.map(
                            (value) =>
                                Math.max(
                                    0,
                                    Number(value)
                                    || 0
                                )
                        )
                        : [];

                const total =
                    values.reduce(
                        (sum, value) =>
                            sum + value,
                        0
                    );

                if (
                    labels.length === 0
                    || total <= 0
                ) {
                    return '';
                }

                const radius =
                    58;

                const circumference =
                    2
                    * Math.PI
                    * radius;

                let consumed =
                    0;

                const segments =
                    values.map(
                        (value, index) => {

                            const length =
                                (
                                    value
                                    / total
                                )
                                * circumference;

                            const offset =
                                -consumed;

                            consumed +=
                                length;

                            const percent =
                                total > 0
                                    ? (
                                        value
                                        / total
                                        * 100
                                    )
                                    : 0;

                            return `
                                <circle
                                    class="analytics-donut-segment"
                                    cx="84"
                                    cy="84"
                                    r="${radius}"
                                    fill="none"
                                    stroke="${colors[index % colors.length]}"
                                    stroke-width="20"
                                    stroke-dasharray="${length} ${circumference - length}"
                                    stroke-dashoffset="${offset}"
                                    transform="rotate(-90 84 84)"
                                >
                                    <title>
                                        ${escapeHtml(labels[index])}
                                        ·
                                        ${formatNumber(value)}
                                        ·
                                        ${percent.toFixed(1)}%
                                    </title>
                                </circle>
                            `;
                        }
                    )
                    .join('');

                const legend =
                    labels.map(
                        (label, index) => {

                            const value =
                                values[index]
                                ?? 0;

                            const percent =
                                total > 0
                                    ? (
                                        value
                                        / total
                                        * 100
                                    )
                                    : 0;

                            return `
                                <div
                                    class="grid
                                           grid-cols-[minmax(0,1fr)_auto]
                                           items-center
                                           gap-3
                                           rounded-lg
                                           px-2
                                           py-1.5
                                           transition
                                           hover:bg-slate-50"
                                >
                                    <div
                                        class="min-w-0
                                               flex
                                               items-center
                                               gap-2"
                                    >
                                        <span
                                            class="h-2.5
                                                   w-2.5
                                                   flex-shrink-0
                                                   rounded-full"
                                            style="
                                                background:
                                                    ${colors[index % colors.length]};
                                            "
                                        ></span>

                                        <span
                                            class="truncate
                                                   text-xs
                                                   text-slate-500"
                                            title="${escapeHtml(label)}"
                                        >
                                            ${escapeHtml(label)}
                                        </span>
                                    </div>

                                    <span
                                        class="flex-shrink-0
                                               text-xs
                                               font-bold
                                               text-slate-800"
                                    >
                                        ${formatNumber(value)}
                                        <small
                                            class="ml-1
                                                   font-medium
                                                   text-slate-400"
                                        >
                                            ${percent.toFixed(0)}%
                                        </small>
                                    </span>
                                </div>
                            `;
                        }
                    )
                    .join('');

                return `
                    <div class="analytics-chart-block">
                        <div
                            class="flex
                                   items-start
                                   justify-between
                                   gap-3"
                        >
                            <div class="min-w-0">
                                <p
                                    class="truncate
                                           text-sm
                                           font-bold
                                           text-slate-900"
                                    title="${escapeHtml(chart.title)}"
                                >
                                    ${escapeHtml(chart.title)}
                                </p>

                                <p
                                    class="mt-1
                                           text-xs
                                           leading-relaxed
                                           text-slate-400"
                                >
                                    ${escapeHtml(chart.subtitle ?? '')}
                                </p>
                            </div>

                            <span
                                class="rounded-lg
                                       bg-violet-50
                                       px-2
                                       py-1
                                       text-[9px]
                                       font-bold
                                       uppercase
                                       tracking-wider
                                       text-violet-700"
                            >
                                Distribusi
                            </span>
                        </div>

                        <div
                            class="mt-4
                                   grid
                                   items-center
                                   gap-5
                                   sm:grid-cols-[178px_minmax(0,1fr)]"
                        >
                            <svg
                                viewBox="0 0 168 168"
                                class="analytics-donut-svg
                                       mx-auto
                                       h-[168px]
                                       w-[168px]"
                                role="img"
                                aria-label="${escapeHtml(chart.title)}"
                            >
                                <circle
                                    cx="84"
                                    cy="84"
                                    r="${radius}"
                                    fill="none"
                                    stroke="#eef2f7"
                                    stroke-width="20"
                                />

                                ${segments}

                                <text
                                    x="84"
                                    y="80"
                                    text-anchor="middle"
                                    fill="#0f172a"
                                    font-size="20"
                                    font-weight="800"
                                    font-family="inherit"
                                >
                                    ${formatNumber(total)}
                                </text>

                                <text
                                    x="84"
                                    y="99"
                                    text-anchor="middle"
                                    fill="#94a3b8"
                                    font-size="9"
                                    font-weight="700"
                                    font-family="inherit"
                                >
                                    TOTAL
                                </text>
                            </svg>

                            <div
                                class="max-h-[190px]
                                       space-y-0.5
                                       overflow-auto
                                       pr-1"
                            >
                                ${legend}
                            </div>
                        </div>
                    </div>
                `;
            };


            /*
            |--------------------------------------------------------------------------
            | Premium Numeric Range
            |--------------------------------------------------------------------------
            */

            const numericMarkup = (
                chart
            ) => {

                const labels =
                    Array.isArray(
                        chart.labels
                    )
                        ? chart.labels
                        : [];

                const values =
                    Array.isArray(
                        chart.values
                    )
                        ? chart.values.map(
                            (value) =>
                                Number(value)
                                || 0
                        )
                        : [];

                if (
                    labels.length < 3
                    || values.length < 3
                ) {
                    return '';
                }

                const min =
                    values[0]
                    ?? 0;

                const average =
                    values[1]
                    ?? 0;

                const max =
                    values[2]
                    ?? 0;

                const range =
                    max - min;

                const averagePosition =
                    range === 0
                        ? 50
                        : Math.max(
                            0,
                            Math.min(
                                100,
                                (
                                    (
                                        average
                                        - min
                                    )
                                    / range
                                )
                                * 100
                            )
                        );

                return `
                    <div class="analytics-chart-block">
                        <div
                            class="flex
                                   items-start
                                   justify-between
                                   gap-3"
                        >
                            <div class="min-w-0">
                                <p
                                    class="truncate
                                           text-sm
                                           font-bold
                                           text-slate-900"
                                    title="${escapeHtml(chart.title)}"
                                >
                                    ${escapeHtml(chart.title)}
                                </p>

                                <p
                                    class="mt-1
                                           text-xs
                                           leading-relaxed
                                           text-slate-400"
                                >
                                    ${escapeHtml(chart.subtitle ?? '')}
                                </p>
                            </div>

                            <span
                                class="rounded-lg
                                       bg-blue-50
                                       px-2
                                       py-1
                                       text-[9px]
                                       font-bold
                                       uppercase
                                       tracking-wider
                                       text-blue-700"
                            >
                                Numeric
                            </span>
                        </div>

                        <div
                            class="mt-5
                                   grid
                                   grid-cols-3
                                   gap-2.5"
                        >
                            <div
                                class="rounded-xl
                                       bg-slate-50
                                       p-3"
                            >
                                <p
                                    class="text-[9px]
                                           font-semibold
                                           uppercase
                                           tracking-wider
                                           text-slate-400"
                                >
                                    Minimum
                                </p>

                                <p
                                    class="mt-1
                                           break-all
                                           text-sm
                                           font-bold
                                           text-slate-800"
                                >
                                    ${formatNumber(min)}
                                </p>
                            </div>

                            <div
                                class="rounded-xl
                                       bg-blue-50
                                       p-3"
                            >
                                <p
                                    class="text-[9px]
                                           font-semibold
                                           uppercase
                                           tracking-wider
                                           text-blue-400"
                                >
                                    Rata-rata
                                </p>

                                <p
                                    class="mt-1
                                           break-all
                                           text-sm
                                           font-bold
                                           text-blue-900"
                                >
                                    ${formatNumber(average)}
                                </p>
                            </div>

                            <div
                                class="rounded-xl
                                       bg-slate-50
                                       p-3"
                            >
                                <p
                                    class="text-[9px]
                                           font-semibold
                                           uppercase
                                           tracking-wider
                                           text-slate-400"
                                >
                                    Maksimum
                                </p>

                                <p
                                    class="mt-1
                                           break-all
                                           text-sm
                                           font-bold
                                           text-slate-800"
                                >
                                    ${formatNumber(max)}
                                </p>
                            </div>
                        </div>

                        <div class="mt-5">
                            <div
                                class="mb-2
                                       flex
                                       items-center
                                       justify-between
                                       gap-3"
                            >
                                <span
                                    class="text-[10px]
                                           font-medium
                                           text-slate-400"
                                >
                                    Posisi rata-rata dalam rentang
                                </span>

                                <span
                                    class="text-[10px]
                                           font-bold
                                           text-blue-600"
                                >
                                    ${formatNumber(average)}
                                </span>
                            </div>

                            <div class="analytics-range-track">
                                <span
                                    class="analytics-range-marker"
                                    style="left:${averagePosition}%"
                                    title="Rata-rata: ${formatNumber(average)}"
                                ></span>
                            </div>

                            <div
                                class="mt-2
                                       flex
                                       items-center
                                       justify-between
                                       gap-3
                                       text-[10px]
                                       text-slate-400"
                            >
                                <span>${formatNumber(min)}</span>
                                <span>${formatNumber(max)}</span>
                            </div>
                        </div>
                    </div>
                `;
            };


            /*
            |--------------------------------------------------------------------------
            | Render
            |--------------------------------------------------------------------------
            */

            chartData.forEach(
                (sheetData) => {

                    const sheetIndex =
                        String(
                            sheetData.sheet_index
                        );

                    const categoryZone =
                        root.querySelector(
                            `[data-category-chart-zone="${sheetIndex}"]`
                        );

                    const numericZone =
                        root.querySelector(
                            `[data-numeric-chart-zone="${sheetIndex}"]`
                        );

                    if (categoryZone) {

                        const charts =
                            Array.isArray(
                                sheetData.categorical
                            )
                                ? sheetData.categorical
                                : [];

                        if (charts.length === 0) {
                            emptyChart(
                                categoryZone,
                                'Belum ada kolom kategori yang cocok untuk diagram.'
                            );
                        } else {
                            categoryZone.innerHTML =
                                charts
                                    .map(
                                        donutMarkup
                                    )
                                    .join('');
                        }
                    }

                    if (numericZone) {

                        const charts =
                            Array.isArray(
                                sheetData.numeric
                            )
                                ? sheetData.numeric
                                : [];

                        if (charts.length === 0) {
                            emptyChart(
                                numericZone,
                                'Belum ada kolom numerik yang cocok untuk grafik.'
                            );
                        } else {
                            numericZone.innerHTML =
                                charts
                                    .map(
                                        numericMarkup
                                    )
                                    .join('');
                        }
                    }
                }
            );

        })();
    </script>


    {{-- ========================================================= --}}
    {{-- AUTO REFRESH — PENDING / PROCESSING --}}
    {{-- ========================================================= --}}

    @if(in_array($analyticsStatus, ['pending', 'processing'], true))

        <script>
            (() => {
                'use strict';

                const refreshKey =
                    'smartfind-analytics-refresh-'
                    + @json((int) $document->id)
                    + '-rev-'
                    + @json($analyticsRevisionNumber);

                const maxAttempts = 12;

                const currentAttempts =
                    Number(
                        sessionStorage.getItem(
                            refreshKey
                        ) ?? 0
                    );

                if (
                    Number.isFinite(currentAttempts)
                    && currentAttempts < maxAttempts
                ) {
                    sessionStorage.setItem(
                        refreshKey,
                        String(
                            currentAttempts + 1
                        )
                    );

                    window.setTimeout(
                        () => {
                            window.location.reload();
                        },
                        2500
                    );
                }
            })();
        </script>

    @elseif($analyticsStatus === 'ready' || $analyticsStatus === 'failed')

        <script>
            (() => {
                'use strict';

                const refreshKey =
                    'smartfind-analytics-refresh-'
                    + @json((int) $document->id)
                    + '-rev-'
                    + @json($analyticsRevisionNumber);

                sessionStorage.removeItem(
                    refreshKey
                );
            })();
        </script>

    @endif

</section>
