<x-app-layout>

    @php
        $formatBytes = function ($bytes) {
            $bytes = (int) $bytes;

            if ($bytes <= 0) {
                return '0 B';
            }

            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $power = min(
                (int) floor(log($bytes, 1024)),
                count($units) - 1
            );

            return number_format(
                $bytes / (1024 ** $power),
                $power === 0 ? 0 : 1,
                ',',
                '.'
            ) . ' ' . $units[$power];
        };

        $activityDescription = function ($activity) {
            $description =
                $activity->description
                ?? ucfirst(
                    str_replace(
                        '_',
                        ' ',
                        $activity->action
                    )
                );

            return str_replace(
                [
                    ' uploaded REV ',
                    ' uploaded document ',
                    ' viewed document ',
                    ' opened current revision REV ',
                    ' downloaded current revision REV ',
                    ' opened REV ',
                    ' downloaded REV ',
                    ' updated metadata for document ',
                    ' archived document ',
                    ' restored document ',
                    ' added document ',
                    ' removed document ',
                    ' to Favorites',
                    ' from Favorites',
                    ' created category ',
                    ' updated category ',
                    ' activated category ',
                    ' deactivated category ',
                    ' approved account ',
                    ' rejected account ',
                    ' activated account ',
                    ' disabled account ',
                    ' created department ',
                    ' updated department ',
                    ' activated department ',
                    ' deactivated department ',
                    ' for document ',
                    ' as REV ',
                ],
                [
                    ' mengunggah Revisi ',
                    ' mengunggah dokumen ',
                    ' melihat dokumen ',
                    ' membuka revisi saat ini Revisi ',
                    ' mengunduh revisi saat ini Revisi ',
                    ' membuka Revisi ',
                    ' mengunduh Revisi ',
                    ' memperbarui metadata dokumen ',
                    ' mengarsipkan dokumen ',
                    ' memulihkan dokumen ',
                    ' menambahkan dokumen ',
                    ' menghapus dokumen ',
                    ' ke Favorit',
                    ' dari Favorit',
                    ' membuat kategori ',
                    ' memperbarui kategori ',
                    ' mengaktifkan kategori ',
                    ' menonaktifkan kategori ',
                    ' menyetujui akun ',
                    ' menolak akun ',
                    ' mengaktifkan akun ',
                    ' menonaktifkan akun ',
                    ' membuat departemen ',
                    ' memperbarui departemen ',
                    ' mengaktifkan departemen ',
                    ' menonaktifkan departemen ',
                    ' untuk dokumen ',
                    ' sebagai Revisi ',
                ],
                $description
            );
        };

        $growthLabel = is_null($documentGrowth)
            ? 'Periode sebelumnya belum memiliki dokumen'
            : (
                $documentGrowth > 0
                    ? '+' . number_format($documentGrowth, 1, ',', '.') . '% dari periode sebelumnya'
                    : (
                        $documentGrowth < 0
                            ? number_format($documentGrowth, 1, ',', '.') . '% dari periode sebelumnya'
                            : 'Stabil dibanding periode sebelumnya'
                    )
            );
    @endphp


    <div
        id="analytics-dashboard"
        class="mx-auto max-w-[1300px] space-y-4 pb-4"
    >

        {{-- ========================================================= --}}
        {{-- MINIMAL DASHBOARD HEADER --}}
        {{-- ========================================================= --}}

        {{-- BMC signature navy/blue welcome banner --}}
        <section class="analytics-enter relative overflow-hidden rounded-2xl border border-blue-800/60 bg-gradient-to-r from-blue-950 via-blue-900 to-blue-700 p-4 text-white shadow-sm sm:p-5" data-qh-dashboard-header>
            <div class="pointer-events-none absolute -right-16 -top-28 h-64 w-64 rounded-full bg-white/5" aria-hidden="true"></div>
            <div class="relative z-10">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-blue-100">BMC QUALITYHUB</p>
                        <h1 class="mt-1 !text-[21px] font-extrabold leading-tight tracking-tight text-white">Selamat datang, {{ Auth::user()->name }}</h1>
                        <p class="mt-1.5 text-xs font-medium text-blue-100">{{ $periodDateLabel }}</p>
                    </div>
                    <form method="GET" action="{{ route('dashboard') }}" class="shrink-0">
                        <label for="dashboard-period" class="sr-only">Pilih periode analitik</label>
                        <select id="dashboard-period" name="period" data-auto-submit
                            class="min-h-[42px] w-full rounded-xl border-white/30 bg-white px-3 pr-9 text-sm font-semibold text-blue-900 shadow-sm focus:border-blue-300 focus:ring-blue-300 sm:w-auto">
                            @foreach([
                                7 => '7 Hari Terakhir',
                                30 => '30 Hari Terakhir',
                                90 => '90 Hari Terakhir',
                                365 => '1 Tahun Terakhir',
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <form method="GET" action="{{ route('documents.index') }}" role="search" aria-label="Pencarian dokumen" class="mt-4 flex flex-col gap-2 sm:flex-row">
                    <div class="relative min-w-0 flex-1">
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        <label for="dashboard-global-search" class="sr-only">Cari dokumen</label>
                        <input id="dashboard-global-search" name="search" type="search" autocomplete="off"
                            placeholder="Cari dokumen, kode, nomor part..."
                            class="block min-h-[42px] w-full rounded-xl border border-white/80 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-500 shadow-sm focus:border-blue-300 focus:ring-blue-300">
                    </div>
                    <button type="submit" class="min-h-[42px] rounded-xl border border-white bg-white px-5 text-sm font-bold text-blue-900 shadow-sm transition hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Cari Dokumen</button>
                </form>
            </div>
        </section>


        {{-- ========================================================= --}}
        {{-- KPI CARDS --}}
        {{-- ========================================================= --}}

        <section
            class="grid grid-cols-2 gap-3 md:grid-cols-3" data-qh-dashboard-kpi
        >

            {{-- ACTIVE DOCUMENTS --}}

            <article
                class="analytics-enter analytics-card
                       group flex min-h-[132px] flex-col justify-between
                       rounded-2xl
                       border border-slate-200/70
                       bg-white
                       p-4
                       shadow-sm"
            >

                <div class="flex items-start justify-between">

                    <div>

                        <p class="text-xs font-semibold text-slate-500">
                            Dokumen Aktif
                        </p>

                        <p
                            class="mt-2
                                   text-[26px] font-bold
                                   tracking-tight
                                   text-slate-900
                                   analytics-counter"
                            data-value="{{ $totalDocuments }}"
                        >
                            0
                        </p>

                    </div>

                    <div
                        class="h-9 w-9
                               rounded-2xl
                               bg-blue-50
                               text-blue-700
                               flex items-center justify-center
                               transition
                               group-hover:scale-105"
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
                            <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5Z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                    </div>

                </div>

                <div
                    class="mt-2
                           flex items-center gap-2
                           text-xs"
                >
                    <span
                        class="inline-flex h-2 w-2
                               rounded-full
                               bg-emerald-500"
                    ></span>

                    <span class="text-slate-400">
                        Terindeks dan dapat diakses
                    </span>
                </div>

            </article>


            {{-- ARCHIVED --}}

            <article
                class="analytics-enter analytics-card
                       group flex min-h-[132px] flex-col justify-between
                       rounded-2xl
                       border border-slate-200/70
                       bg-white
                       p-4
                       shadow-sm"
            >

                <div class="flex items-start justify-between">

                    <div>

                        <p class="text-xs font-semibold text-slate-500">
                            Dokumen Arsip
                        </p>

                        <p
                            class="mt-2
                                   text-[26px] font-bold
                                   tracking-tight
                                   text-slate-900
                                   analytics-counter"
                            data-value="{{ $archivedDocuments }}"
                        >
                            0
                        </p>

                    </div>

                    <div
                        class="h-9 w-9
                               rounded-2xl
                               bg-amber-50
                               text-amber-700
                               flex items-center justify-center
                               transition
                               group-hover:scale-105"
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
                            <rect width="20" height="5" x="2" y="3" rx="1"/>
                            <path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"/>
                            <path d="M10 12h4"/>
                        </svg>
                    </div>

                </div>

                <p class="mt-2 text-xs text-slate-400">
                    Tetap tersimpan tanpa hard delete
                </p>

            </article>


            {{-- REVISIONS --}}

            <article
                class="analytics-enter analytics-card
                       group flex min-h-[132px] flex-col justify-between
                       rounded-2xl
                       border border-slate-200/70
                       bg-white
                       p-4
                       shadow-sm"
            >

                <div class="flex items-start justify-between">

                    <div>

                        <p class="text-xs font-semibold text-slate-500">
                            Total Revisi
                        </p>

                        <p
                            class="mt-2
                                   text-[26px] font-bold
                                   tracking-tight
                                   text-slate-900
                                   analytics-counter"
                            data-value="{{ $totalRevisions }}"
                        >
                            0
                        </p>

                    </div>

                    <div
                        class="h-9 w-9
                               rounded-2xl
                               bg-violet-50
                               text-violet-700
                               flex items-center justify-center
                               transition
                               group-hover:scale-105"
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
                            <path d="M3 12a9 9 0 1 0 3-6.7L3 8"/>
                            <path d="M3 3v5h5"/>
                        </svg>
                    </div>

                </div>

                <p class="mt-2 text-xs text-slate-400">
                    Menghitung Revisi 1 dan seterusnya
                </p>

            </article>


            {{-- DEPARTMENTS --}}

            <article
                class="analytics-enter analytics-card
                       group flex min-h-[132px] flex-col justify-between
                       rounded-2xl
                       border border-slate-200/70
                       bg-white
                       p-4
                       shadow-sm"
            >

                <div class="flex items-start justify-between">

                    <div>

                        <p class="text-xs font-semibold text-slate-500">
                            Departemen Aktif
                        </p>

                        <p
                            class="mt-2
                                   text-[26px] font-bold
                                   tracking-tight
                                   text-slate-900
                                   analytics-counter"
                            data-value="{{ $totalDepartments }}"
                        >
                            0
                        </p>

                    </div>

                    <div
                        class="h-9 w-9
                               rounded-2xl
                               bg-indigo-50
                               text-indigo-700
                               flex items-center justify-center
                               transition
                               group-hover:scale-105"
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
                            <rect width="16" height="20" x="4" y="2" rx="2"/>
                            <path d="M9 22v-4h6v4"/>
                            <path d="M8 6h.01"/>
                            <path d="M12 6h.01"/>
                            <path d="M16 6h.01"/>
                        </svg>
                    </div>

                </div>

                <p class="mt-2 text-xs text-slate-400">
                    {{ number_format($totalCategories) }} kategori aktif
                </p>

            </article>


            {{-- USERS --}}

            <article
                class="analytics-enter analytics-card
                       group flex min-h-[132px] flex-col justify-between
                       rounded-2xl
                       border border-slate-200/70
                       bg-white
                       p-4
                       shadow-sm"
            >

                <div class="flex items-start justify-between">

                    <div>

                        <p class="text-xs font-semibold text-slate-500">
                            Pengguna Aktif
                        </p>

                        <p
                            class="mt-2
                                   text-[26px] font-bold
                                   tracking-tight
                                   text-slate-900
                                   analytics-counter"
                            data-value="{{ $activeUsers }}"
                        >
                            0
                        </p>

                    </div>

                    <div
                        class="h-9 w-9
                               rounded-2xl
                               bg-emerald-50
                               text-emerald-700
                               flex items-center justify-center
                               transition
                               group-hover:scale-105"
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
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="m16 11 2 2 4-4"/>
                        </svg>
                    </div>

                </div>

                <p class="mt-2 text-xs text-slate-400">
                    {{ number_format($pendingUsers) }} menunggu persetujuan
                </p>

            </article>


            {{-- ACTIVITY PERIOD --}}

            <article
                class="analytics-enter analytics-card
                       group flex min-h-[132px] flex-col justify-between
                       rounded-2xl
                       border border-slate-200/70
                       bg-white
                       p-4
                       shadow-sm"
            >

                <div class="flex items-start justify-between">

                    <div>

                        <p class="text-xs font-semibold text-slate-500">
                            Aktivitas {{ $periodLabel }}
                        </p>

                        <p
                            class="mt-2
                                   text-[26px] font-bold
                                   tracking-tight
                                   text-slate-900
                                   analytics-counter"
                            data-value="{{ $activityInPeriod }}"
                        >
                            0
                        </p>

                    </div>

                    <div
                        class="h-9 w-9
                               rounded-2xl
                               bg-cyan-50
                               text-cyan-700
                               flex items-center justify-center
                               transition
                               group-hover:scale-105"
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
                            <path d="M3 3v18h18"/>
                            <path d="m7 16 4-5 4 3 5-7"/>
                        </svg>
                    </div>

                </div>

                <p class="mt-2 text-xs text-slate-400">
                    Aktivitas tercatat pada periode aktif
                </p>

            </article>
            {{-- Supporting metrics use the same layout as the other six KPIs. --}}
            <article class="analytics-enter analytics-card group flex min-h-[132px] flex-col justify-between rounded-2xl border border-slate-200/70 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0"><p class="text-xs font-semibold text-slate-500">Spreadsheet Aktif</p><p class="mt-2 text-[26px] font-bold tracking-tight text-slate-900">{{ number_format($spreadsheetDocuments) }}</p></div>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700" aria-hidden="true"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 3h10M7 7h10M5 11h14v10H5z"/></svg></span>
                </div>
                <p class="mt-2 text-xs text-slate-500">XLSX, XLS, CSV</p>
            </article>
            <article class="analytics-enter analytics-card group flex min-h-[132px] flex-col justify-between rounded-2xl border border-slate-200/70 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0"><p class="text-xs font-semibold text-slate-500">Ukuran Dokumen Aktif</p><p class="mt-2 text-[26px] font-bold tracking-tight text-slate-900">{{ $formatBytes($totalStorageBytes) }}</p></div>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700" aria-hidden="true"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/></svg></span>
                </div>
                <p class="mt-2 text-xs text-slate-500">Berdasarkan metadata file</p>
            </article>
            <article class="analytics-enter analytics-card group flex min-h-[132px] flex-col justify-between rounded-2xl border border-slate-200/70 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0"><p class="text-xs font-semibold text-slate-500">Dokumen Baru</p><p class="mt-2 text-[26px] font-bold tracking-tight text-slate-900">{{ number_format($newDocumentsInPeriod) }}</p></div>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-700" aria-hidden="true"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M12 3v18"/></svg></span>
                </div>
                <p class="mt-2 break-words text-xs text-slate-500">{{ $growthLabel }}</p>
            </article>
        </section>


        {{-- ========================================================= --}}
        {{-- SUPPORTING KPIS INTEGRATED INTO MAIN GRID --}}
        {{-- ========================================================= --}}




        {{-- ========================================================= --}}
        {{-- MAIN ANALYTICS --}}
        {{-- ========================================================= --}}


        {{-- MAIN ANALYTICS: trend retains original SVG renderer --}}
        <section class="grid grid-cols-1 gap-4" data-qh-dashboard-trend>
            <article
                class="analytics-enter
                       2xl:col-span-2
                       rounded-2xl
                       border border-slate-200/70
                       bg-white
                       shadow-sm
                       overflow-hidden"
            >

                <div
                    class="p-4 sm:p-5
                           flex flex-col sm:flex-row
                           sm:items-center
                           justify-between
                           gap-3
                           border-b border-slate-100"
                >

                    <div>

                        <div class="flex items-center gap-2">

                            <span
                                class="h-2.5 w-2.5
                                       rounded-full
                                       bg-blue-600"
                            ></span>

                            <h2 class="font-bold text-slate-900">
                                Tren Dokumen & Aktivitas
                            </h2>

                        </div>

                        <p class="mt-1 text-sm text-slate-500">
                            Tren selama {{ $periodLabel }}.
                        </p>

                    </div>

                    <div
                        class="flex flex-wrap items-center gap-4
                               text-xs text-slate-500"
                    >
                        <span class="inline-flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                            Dokumen baru
                        </span>

                        <span class="inline-flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-cyan-500"></span>
                            Aktivitas
                        </span>
                    </div>

                </div>

                <div class="p-4 sm:p-6">

                    <div
                        id="trend-chart"
                        class="chart-stage min-h-[270px]"
                        aria-label="Grafik tren dokumen dan aktivitas"
                    ></div>

                </div>

            </article>
        </section>

                {{-- Balanced independent columns keep short analytics from leaving empty grid rows. --}}
        <section class="grid grid-cols-1 items-start gap-4 lg:grid-cols-2" data-qh-dashboard-distributions>
            <div class="flex min-w-0 flex-col gap-4">
            <article
                class="analytics-enter
                       rounded-2xl
                       border border-slate-200/70
                       bg-white
                       shadow-sm
                       overflow-hidden"
            >

                <div
                    class="p-4 sm:p-5
                           border-b border-slate-100"
                >
                    <h2 class="font-bold text-slate-900">
                        Dokumen per Kategori
                    </h2>


                </div>

                <div class="grid grid-cols-1 items-center gap-4 p-4 sm:grid-cols-[0.9fr_1.1fr] sm:p-5">

                    <div
                        id="category-donut-chart"
                        class="chart-stage min-h-[200px]"
                        aria-label="Diagram komposisi dokumen per kategori"
                    ></div>

                    <div
                        id="category-donut-legend"
                        class="min-w-0 space-y-1.5"
                    ></div>

                </div>

            </article>
            <article
                class="analytics-enter
                       rounded-2xl
                       border border-slate-200/70
                       bg-white
                       shadow-sm"
            >

                <div
                    class="p-4 sm:p-5
                           flex items-start justify-between gap-4
                           border-b border-slate-100"
                >
                    <div>
                        <h2 class="font-bold text-slate-900">
                            Distribusi Departemen
                        </h2>


                    </div>

                    <span
                        class="rounded-xl
                               bg-indigo-50
                               px-3 py-1.5
                               text-xs font-semibold
                               text-indigo-700"
                    >
                        {{ number_format($totalDepartments) }} aktif
                    </span>
                </div>

                <div class="p-4 sm:p-5">
                    <div
                        id="department-bar-chart"
                        class="space-y-3"
                    ></div>
                </div>

            </article>
                {{-- Document access ranking, aligned beneath the shorter department panel. --}}
                <div class="min-w-0" data-qh-dashboard-documents>
            <article
                class="analytics-enter min-w-0
                       rounded-2xl
                       border border-slate-200/70
                       bg-white
                       shadow-sm"
            >

                <div
                    class="p-4 sm:p-5
                           border-b border-slate-100"
                >
                    <h2 class="font-bold text-slate-900">
                        Dokumen Paling Sering Diakses
                    </h2>


                </div>

                <div class="p-3">

                    @forelse($topAccessedDocuments as $index => $document)

                        <a
                            href="{{ route('documents.show', $document->id) }}"
                            class="group
                                   flex items-center gap-3
                                   rounded-2xl
                                   px-3 py-3
                                   hover:bg-slate-50
                                   transition"
                        >

                            <div
                                class="h-10 w-10
                                       rounded-xl
                                       bg-slate-100
                                       flex-shrink-0
                                       flex items-center justify-center
                                       text-sm font-bold
                                       text-slate-600
                                       group-hover:bg-blue-50
                                       group-hover:text-blue-700
                                       transition"
                            >
                                {{ $index + 1 }}
                            </div>

                            <div class="min-w-0 flex-1">

                                <p
                                    class="text-sm font-semibold
                                           text-slate-800
                                           truncate"
                                >
                                    {{ $document->title }}
                                </p>

                                <p
                                    class="mt-1
                                           text-xs
                                           text-slate-400
                                           truncate"
                                >
                                    {{ $document->document_code ?: 'Tanpa kode dokumen' }}
                                </p>

                            </div>

                            <div class="text-right flex-shrink-0">

                                <p
                                    class="text-lg font-bold
                                           text-slate-900"
                                >
                                    {{ number_format($document->access_count) }}
                                </p>

                                <p class="text-[11px] text-slate-400">
                                    akses
                                </p>

                            </div>

                        </a>

                    @empty

                        <div
                            class="py-12
                                   text-center"
                        >
                            <div
                                class="mx-auto
                                       h-12 w-12
                                       rounded-2xl
                                       bg-slate-100
                                       flex items-center justify-center
                                       text-slate-400"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="22"
                                    height="22"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M3 3v18h18"/>
                                    <path d="m7 16 4-5 4 3 5-7"/>
                                </svg>
                            </div>

                            <p
                                class="mt-3
                                       text-sm font-semibold
                                       text-slate-600"
                            >
                                Belum ada data akses
                            </p>

                            <p
                                class="mt-1
                                       text-xs
                                       text-slate-400"
                            >
                                Data akan muncul setelah dokumen digunakan.
                            </p>
                        </div>

                    @endforelse

                </div>

            </article>
                </div>
            </div>
            <div class="flex min-w-0 flex-col gap-4">
            <article
                class="analytics-enter
                       rounded-2xl
                       border border-slate-200/70
                       bg-white
                       shadow-sm"
            >

                <div
                    class="p-4 sm:p-5
                           border-b border-slate-100"
                >
                    <h2 class="font-bold text-slate-900">
                        Distribusi Jenis File
                    </h2>


                </div>

                <div
                    class="p-4 sm:p-5
                           grid gap-5
                           sm:grid-cols-[0.9fr_1.1fr]
                           items-center"
                >

                    <div
                        id="filetype-donut-chart"
                        class="chart-stage min-h-[200px]"
                        aria-label="Diagram distribusi jenis file"
                    ></div>

                    <div
                        id="filetype-donut-legend"
                        class="space-y-2"
                    ></div>

                </div>

            </article>
            <article
                class="analytics-enter
                       rounded-2xl
                       border border-slate-200/70
                       bg-white
                       shadow-sm"
            >

                <div
                    class="p-4 sm:p-5
                           border-b border-slate-100"
                >
                    <h2 class="font-bold text-slate-900">
                        Komposisi Aktivitas
                    </h2>


                </div>

                <div class="p-4 sm:p-5">
                    <div
                        id="activity-bar-chart"
                        class="space-y-3"
                    ></div>
                </div>

            </article>
                {{-- Recently indexed documents, aligned beneath activity distribution. --}}
                <div class="min-w-0" data-qh-dashboard-documents>
            <article
                class="analytics-enter min-w-0
                       rounded-2xl
                       border border-slate-200/70
                       bg-white
                       shadow-sm
                       overflow-hidden"
            >

                <div
                    class="p-4 sm:p-5
                           flex items-center justify-between gap-4
                           border-b border-slate-100"
                >

                    <div>
                        <h2 class="font-bold text-slate-900">
                            Dokumen Terbaru
                        </h2>


                    </div>

                    <a
                        href="{{ route('documents.index') }}"
                        class="text-sm font-semibold
                               text-blue-700
                               hover:text-blue-900"
                    >
                        Lihat Semua
                    </a>

                </div>


                <div class="p-3">

                    @forelse($recentDocuments as $document)

                        <a
                            href="{{ route('documents.show', $document) }}"
                            class="group
                                   flex items-center gap-3
                                   rounded-2xl
                                   px-3 py-3
                                   hover:bg-slate-50
                                   transition"
                        >

                            <div
                                class="h-11 w-11
                                       rounded-xl
                                       bg-blue-50
                                       text-blue-700
                                       flex-shrink-0
                                       flex items-center justify-center"
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
                                    <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5Z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                </svg>
                            </div>


                            <div class="min-w-0 flex-1">

                                <p
                                    class="text-sm font-semibold
                                           text-slate-800
                                           truncate
                                           group-hover:text-blue-800"
                                >
                                    {{ $document->title }}
                                </p>

                                <div
                                    class="mt-1.5
                                           flex flex-wrap items-center gap-2
                                           text-xs text-slate-400"
                                >
                                    <span class="truncate">
                                        {{ $document->category?->name ?? 'Tanpa kategori' }}
                                    </span>

                                    <span>•</span>

                                    <span class="truncate">
                                        {{ $document->category?->department?->name ?? 'Tanpa departemen' }}
                                    </span>

                                    <span>•</span>

                                    <span>
                                        {{ strtoupper($document->file_extension ?? 'FILE') }}
                                    </span>
                                </div>

                            </div>


                            <div
                                class="hidden sm:block
                                       text-right flex-shrink-0"
                            >

                                <p
                                    class="text-xs font-medium
                                           text-slate-500"
                                >
                                    {{ $document->created_at->locale('id')->translatedFormat('d M Y') }}
                                </p>

                                <p
                                    class="mt-1
                                           text-xs
                                           text-slate-400"
                                >
                                    Revisi {{ $document->current_revision }}
                                </p>

                            </div>

                        </a>

                    @empty

                        <div class="py-14 text-center">

                            <p
                                class="text-sm font-semibold
                                       text-slate-600"
                            >
                                Belum ada dokumen
                            </p>

                            <p
                                class="mt-1 text-xs
                                       text-slate-400"
                            >
                                Dokumen akan muncul setelah diunggah.
                            </p>

                        </div>

                    @endforelse

                </div>

            </article>
                </div>
            </div>
        </section>

        {{-- Recent audit activity --}}
        <section class="grid grid-cols-1 gap-4" data-qh-dashboard-activities>
            <article
                class="analytics-enter
                       rounded-2xl
                       border border-slate-200/70
                       bg-white
                       shadow-sm
                       overflow-hidden"
            >

                <div
                    class="p-4 sm:p-5
                           border-b border-slate-100"
                >
                    <h2 class="font-bold text-slate-900">
                        Aktivitas Terbaru
                    </h2>


                </div>


                <div class="divide-y divide-slate-100 p-3 sm:p-4">

                    @forelse($recentActivities as $activity)

                        <div class="flex min-w-0 gap-3 rounded-lg px-2 py-2.5 transition hover:bg-slate-50">

                            <div
                                class="mt-1
                                       h-8 w-8
                                       rounded-full
                                       bg-blue-50
                                       flex-shrink-0
                                       flex items-center justify-center"
                            >
                                <span
                                    class="h-2 w-2
                                           rounded-full
                                           bg-blue-600"
                                ></span>
                            </div>

                            <div class="min-w-0">

                                <p
                                    class="line-clamp-2 text-sm leading-5 text-slate-700"
                                >
                                    {{ $activityDescription($activity) }}
                                </p>

                                <p
                                    class="mt-1
                                           text-xs
                                           text-slate-400"
                                >
                                    {{ $activity->created_at->locale('id')->diffForHumans() }}
                                </p>

                            </div>

                        </div>

                    @empty

                        <div class="py-10 text-center">

                            <p
                                class="text-sm font-medium
                                       text-slate-600"
                            >
                                Belum ada aktivitas
                            </p>

                            <p
                                class="mt-1
                                       text-xs
                                       text-slate-400"
                            >
                                Aktivitas sistem akan tercatat di sini.
                            </p>

                        </div>

                    @endforelse

                </div>

            </article>
        </section>

    </div>


    {{-- ============================================================= --}}
    {{-- DATA SOURCE UNTUK CHART — DIBUAT LANGSUNG DARI DATABASE --}}
    {{-- ============================================================= --}}

    <script nonce="{{ $cspNonce }}"
        id="dashboard-chart-data"
        type="application/json"
    >@json($chartData)</script>


    {{-- ============================================================= --}}
    {{-- DASHBOARD ANIMATION + NATIVE SVG CHART ENGINE --}}
    {{-- ============================================================= --}}

    <style nonce="{{ $cspNonce }}">
        #analytics-dashboard .analytics-enter {
            opacity: 0;
            transform: translateY(12px);
            animation: smartfindFadeUp 0.55s ease forwards;
        }

        #analytics-dashboard .analytics-enter:nth-child(2) {
            animation-delay: 0.05s;
        }

        #analytics-dashboard .analytics-enter:nth-child(3) {
            animation-delay: 0.10s;
        }

        #analytics-dashboard .analytics-enter:nth-child(4) {
            animation-delay: 0.15s;
        }

        #analytics-dashboard .analytics-enter:nth-child(5) {
            animation-delay: 0.20s;
        }

        #analytics-dashboard .analytics-card {
            transition:
                transform 0.25s ease,
                box-shadow 0.25s ease,
                border-color 0.25s ease;
        }

        #analytics-dashboard .analytics-card:hover {
            transform: translateY(-4px);
            border-color: rgba(59, 130, 246, 0.20);
            box-shadow:
                0 16px 35px
                rgba(15, 23, 42, 0.07);
        }

        #analytics-dashboard .chart-stage {
            position: relative;
            width: 100%;
        }

        #analytics-dashboard .smartfind-tooltip {
            position: absolute;
            z-index: 30;
            pointer-events: none;
            transform: translate(-50%, calc(-100% - 10px));
            padding: 8px 10px;
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.94);
            color: white;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.4;
            white-space: nowrap;
            box-shadow:
                0 10px 30px
                rgba(15, 23, 42, 0.18);
            opacity: 0;
            transition: opacity 0.15s ease;
        }

        #analytics-dashboard .smartfind-chart-bar {
            transform-origin: left center;
            animation: smartfindBarGrow 0.75s ease both;
        }

        @keyframes smartfindFadeUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes smartfindBarGrow {
            from {
                transform: scaleX(0);
                opacity: 0.45;
            }

            to {
                transform: scaleX(1);
                opacity: 1;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            #analytics-dashboard .analytics-enter,
            #analytics-dashboard .smartfind-chart-bar {
                animation: none !important;
                opacity: 1 !important;
                transform: none !important;
            }

            #analytics-dashboard .analytics-card {
                transition: none !important;
            }
        }
    </style>


    <script nonce="{{ $cspNonce }}">
        (() => {
            'use strict';

            const root =
                document.getElementById(
                    'analytics-dashboard'
                );

            const dataElement =
                document.getElementById(
                    'dashboard-chart-data'
                );

            if (!root || !dataElement) {
                return;
            }

            let data = {};

            try {
                data =
                    JSON.parse(
                        dataElement.textContent
                    );
            } catch (error) {
                console.error(
                    'Data dashboard BMC QualityHub tidak valid.',
                    error
                );

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Helpers
            |--------------------------------------------------------------------------
            */

            const numberFormatter =
                new Intl.NumberFormat(
                    'id-ID'
                );

            const colors = [
                '#2563eb',
                '#06b6d4',
                '#7c3aed',
                '#10b981',
                '#f59e0b',
                '#ef4444',
                '#6366f1',
                '#64748b',
            ];

            const safeNumber = (value) => {
                const number =
                    Number(value);

                return Number.isFinite(number)
                    ? number
                    : 0;
            };

            const escapeHtml = (value) => {
                const div =
                    document.createElement('div');

                div.textContent =
                    String(value ?? '');

                return div.innerHTML;
            };

            const emptyState = (
                element,
                message = 'Belum ada data untuk ditampilkan.'
            ) => {
                element.innerHTML = `
                    <div
                        class="min-h-[180px]
                               flex items-center justify-center
                               text-center"
                    >
                        <div>
                            <div
                                class="mx-auto
                                       h-11 w-11
                                       rounded-2xl
                                       bg-slate-100
                                       flex items-center justify-center
                                       text-slate-400"
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
                                    <path d="M3 3v18h18"/>
                                    <path d="m7 16 4-5 4 3 5-7"/>
                                </svg>
                            </div>

                            <p
                                class="mt-3
                                       text-xs font-medium
                                       text-slate-400"
                            >
                                ${escapeHtml(message)}
                            </p>
                        </div>
                    </div>
                `;
            };


            /*
            |--------------------------------------------------------------------------
            | Counter Animation
            |--------------------------------------------------------------------------
            */

            const animateCounters = () => {

                const counters =
                    root.querySelectorAll(
                        '.analytics-counter'
                    );

                const reduceMotion =
                    window.matchMedia(
                        '(prefers-reduced-motion: reduce)'
                    ).matches;

                counters.forEach((counter) => {

                    const target =
                        Math.max(
                            0,
                            safeNumber(
                                counter.dataset.value
                            )
                        );

                    if (reduceMotion) {
                        counter.textContent =
                            numberFormatter.format(
                                target
                            );

                        return;
                    }

                    const duration = 700;
                    const startTime =
                        performance.now();

                    const frame = (now) => {

                        const progress =
                            Math.min(
                                (now - startTime)
                                / duration,
                                1
                            );

                        const eased =
                            1
                            - Math.pow(
                                1 - progress,
                                3
                            );

                        counter.textContent =
                            numberFormatter.format(
                                Math.round(
                                    target * eased
                                )
                            );

                        if (progress < 1) {
                            requestAnimationFrame(
                                frame
                            );
                        }
                    };

                    requestAnimationFrame(
                        frame
                    );
                });
            };


            /*
            |--------------------------------------------------------------------------
            | SVG Line / Area Chart
            |--------------------------------------------------------------------------
            */

            const renderTrendChart = () => {

                const element =
                    document.getElementById(
                        'trend-chart'
                    );

                if (!element) {
                    return;
                }

                const labels =
                    Array.isArray(
                        data?.trend?.labels
                    )
                        ? data.trend.labels
                        : [];

                const documentValues =
                    Array.isArray(
                        data?.trend?.documents
                    )
                        ? data.trend.documents.map(
                            safeNumber
                        )
                        : [];

                const activityValues =
                    Array.isArray(
                        data?.trend?.activities
                    )
                        ? data.trend.activities.map(
                            safeNumber
                        )
                        : [];

                if (
                    labels.length === 0
                    || (
                        Math.max(
                            ...documentValues,
                            0
                        ) === 0
                        && Math.max(
                            ...activityValues,
                            0
                        ) === 0
                    )
                ) {
                    emptyState(
                        element,
                        'Belum ada tren pada periode ini.'
                    );

                    return;
                }

                const width = 1000;
                const height = 310;
                const left = 46;
                const right = 18;
                const top = 18;
                const bottom = 42;

                const plotWidth =
                    width - left - right;

                const plotHeight =
                    height - top - bottom;

                const maxValue =
                    Math.max(
                        ...documentValues,
                        ...activityValues,
                        1
                    );

                const niceMax =
                    Math.max(
                        1,
                        Math.ceil(
                            maxValue * 1.15
                        )
                    );

                const count =
                    Math.max(
                        labels.length,
                        1
                    );

                const xFor = (index) => {
                    if (count <= 1) {
                        return left
                            + plotWidth / 2;
                    }

                    return left
                        + (
                            index
                            / (count - 1)
                        ) * plotWidth;
                };

                const yFor = (value) =>
                    top
                    + plotHeight
                    - (
                        safeNumber(value)
                        / niceMax
                    ) * plotHeight;

                const pointsFor = (values) =>
                    values
                        .map(
                            (value, index) =>
                                `${xFor(index)},${yFor(value)}`
                        )
                        .join(' ');

                const areaFor = (values) => {

                    if (values.length === 0) {
                        return '';
                    }

                    const points =
                        values.map(
                            (value, index) =>
                                `${xFor(index)},${yFor(value)}`
                        );

                    return [
                        `${xFor(0)},${top + plotHeight}`,
                        ...points,
                        `${xFor(values.length - 1)},${top + plotHeight}`,
                    ].join(' ');
                };

                const gridLines = 4;

                let grid = '';

                for (
                    let index = 0;
                    index <= gridLines;
                    index++
                ) {
                    const ratio =
                        index / gridLines;

                    const y =
                        top
                        + ratio * plotHeight;

                    const labelValue =
                        Math.round(
                            niceMax
                            * (1 - ratio)
                        );

                    grid += `
                        <line
                            x1="${left}"
                            y1="${y}"
                            x2="${width - right}"
                            y2="${y}"
                            stroke="#e2e8f0"
                            stroke-width="1"
                            stroke-dasharray="4 5"
                        />

                        <text
                            x="${left - 10}"
                            y="${y + 4}"
                            text-anchor="end"
                            fill="#94a3b8"
                            font-size="11"
                            font-family="inherit"
                        >
                            ${labelValue}
                        </text>
                    `;
                }

                const labelStep =
                    Math.max(
                        1,
                        Math.ceil(
                            labels.length / 7
                        )
                    );

                let xLabels = '';

                labels.forEach(
                    (label, index) => {

                        const shouldShow =
                            index === 0
                            || index
                                === labels.length - 1
                            || index % labelStep === 0;

                        if (!shouldShow) {
                            return;
                        }

                        xLabels += `
                            <text
                                x="${xFor(index)}"
                                y="${height - 11}"
                                text-anchor="middle"
                                fill="#94a3b8"
                                font-size="10"
                                font-family="inherit"
                            >
                                ${escapeHtml(label)}
                            </text>
                        `;
                    }
                );

                element.innerHTML = `
                    <div class="relative w-full">

                        <svg
                            viewBox="0 0 ${width} ${height}"
                            preserveAspectRatio="none"
                            class="w-full h-[310px]
                                   overflow-visible"
                            role="img"
                        >

                            <defs>
                                <linearGradient
                                    id="documentAreaGradient"
                                    x1="0"
                                    x2="0"
                                    y1="0"
                                    y2="1"
                                >
                                    <stop
                                        offset="0%"
                                        stop-color="#2563eb"
                                        stop-opacity="0.24"
                                    />

                                    <stop
                                        offset="100%"
                                        stop-color="#2563eb"
                                        stop-opacity="0.01"
                                    />
                                </linearGradient>

                                <linearGradient
                                    id="activityAreaGradient"
                                    x1="0"
                                    x2="0"
                                    y1="0"
                                    y2="1"
                                >
                                    <stop
                                        offset="0%"
                                        stop-color="#06b6d4"
                                        stop-opacity="0.17"
                                    />

                                    <stop
                                        offset="100%"
                                        stop-color="#06b6d4"
                                        stop-opacity="0.01"
                                    />
                                </linearGradient>
                            </defs>

                            ${grid}

                            <polygon
                                points="${areaFor(documentValues)}"
                                fill="url(#documentAreaGradient)"
                            />

                            <polygon
                                points="${areaFor(activityValues)}"
                                fill="url(#activityAreaGradient)"
                            />

                            <polyline
                                points="${pointsFor(documentValues)}"
                                fill="none"
                                stroke="#2563eb"
                                stroke-width="3"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                vector-effect="non-scaling-stroke"
                            />

                            <polyline
                                points="${pointsFor(activityValues)}"
                                fill="none"
                                stroke="#06b6d4"
                                stroke-width="3"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                vector-effect="non-scaling-stroke"
                            />

                            ${xLabels}

                            <line
                                id="trend-hover-line"
                                x1="0"
                                x2="0"
                                y1="${top}"
                                y2="${top + plotHeight}"
                                stroke="#64748b"
                                stroke-width="1"
                                stroke-dasharray="3 4"
                                opacity="0"
                            />

                            <circle
                                id="trend-hover-doc"
                                r="5"
                                fill="#ffffff"
                                stroke="#2563eb"
                                stroke-width="3"
                                opacity="0"
                            />

                            <circle
                                id="trend-hover-act"
                                r="5"
                                fill="#ffffff"
                                stroke="#06b6d4"
                                stroke-width="3"
                                opacity="0"
                            />

                            <rect
                                id="trend-hover-surface"
                                x="${left}"
                                y="${top}"
                                width="${plotWidth}"
                                height="${plotHeight}"
                                fill="transparent"
                                data-qh-cursor="crosshair"
                            />

                        </svg>

                        <div
                            id="trend-tooltip"
                            class="smartfind-tooltip"
                        ></div>

                    </div>
                `;

                const surface =
                    element.querySelector(
                        '#trend-hover-surface'
                    );

                const line =
                    element.querySelector(
                        '#trend-hover-line'
                    );

                const docPoint =
                    element.querySelector(
                        '#trend-hover-doc'
                    );

                const activityPoint =
                    element.querySelector(
                        '#trend-hover-act'
                    );

                const tooltip =
                    element.querySelector(
                        '#trend-tooltip'
                    );

                if (
                    !surface
                    || !line
                    || !docPoint
                    || !activityPoint
                    || !tooltip
                ) {
                    return;
                }

                surface.addEventListener(
                    'mousemove',
                    (event) => {

                        const rect =
                            surface
                                .ownerSVGElement
                                .getBoundingClientRect();

                        const relativeX =
                            event.clientX
                            - rect.left;

                        const viewX =
                            relativeX
                            * (
                                width
                                / rect.width
                            );

                        const rawIndex =
                            count <= 1
                                ? 0
                                : Math.round(
                                    (
                                        viewX - left
                                    )
                                    / plotWidth
                                    * (count - 1)
                                );

                        const index =
                            Math.max(
                                0,
                                Math.min(
                                    count - 1,
                                    rawIndex
                                )
                            );

                        const x =
                            xFor(index);

                        const documentY =
                            yFor(
                                documentValues[index]
                                ?? 0
                            );

                        const activityY =
                            yFor(
                                activityValues[index]
                                ?? 0
                            );

                        line.setAttribute(
                            'x1',
                            x
                        );

                        line.setAttribute(
                            'x2',
                            x
                        );

                        line.setAttribute(
                            'opacity',
                            '1'
                        );

                        docPoint.setAttribute(
                            'cx',
                            x
                        );

                        docPoint.setAttribute(
                            'cy',
                            documentY
                        );

                        docPoint.setAttribute(
                            'opacity',
                            '1'
                        );

                        activityPoint.setAttribute(
                            'cx',
                            x
                        );

                        activityPoint.setAttribute(
                            'cy',
                            activityY
                        );

                        activityPoint.setAttribute(
                            'opacity',
                            '1'
                        );

                        const screenX =
                            (
                                x / width
                            ) * rect.width;

                        const minY =
                            Math.min(
                                documentY,
                                activityY
                            );

                        const screenY =
                            (
                                minY / height
                            ) * rect.height;

                        tooltip.style.left =
                            `${screenX}px`;

                        tooltip.style.top =
                            `${screenY}px`;

                        tooltip.innerHTML = `
                            <div>${escapeHtml(labels[index] ?? '')}</div>
                            <div data-qh-margin-top="4" data-qh-color="#bfdbfe">
                                Dokumen:
                                ${numberFormatter.format(documentValues[index] ?? 0)}
                            </div>
                            <div data-qh-color="#a5f3fc">
                                Aktivitas:
                                ${numberFormatter.format(activityValues[index] ?? 0)}
                            </div>
                        `;

                        tooltip.style.opacity =
                            '1';
                    }
                );

                surface.addEventListener(
                    'mouseleave',
                    () => {
                        line.setAttribute(
                            'opacity',
                            '0'
                        );

                        docPoint.setAttribute(
                            'opacity',
                            '0'
                        );

                        activityPoint.setAttribute(
                            'opacity',
                            '0'
                        );

                        tooltip.style.opacity =
                            '0';
                    }
                );
            };


            /*
            |--------------------------------------------------------------------------
            | SVG Donut
            |--------------------------------------------------------------------------
            */

            const renderDonut = (
                chartId,
                legendId,
                labels,
                values
            ) => {

                const chartElement =
                    document.getElementById(
                        chartId
                    );

                const legendElement =
                    document.getElementById(
                        legendId
                    );

                if (
                    !chartElement
                    || !legendElement
                ) {
                    return;
                }

                labels =
                    Array.isArray(labels)
                        ? labels
                        : [];

                values =
                    Array.isArray(values)
                        ? values.map(
                            safeNumber
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
                    emptyState(
                        chartElement
                    );

                    legendElement.innerHTML =
                        '';

                    return;
                }

                const radius = 76;
                const circumference =
                    2 * Math.PI * radius;

                let consumed = 0;

                const segments =
                    values.map(
                        (value, index) => {

                            const length =
                                (
                                    value / total
                                ) * circumference;

                            const offset =
                                -consumed;

                            consumed += length;

                            return `
                                <circle
                                    cx="110"
                                    cy="110"
                                    r="${radius}"
                                    fill="none"
                                    stroke="${colors[index % colors.length]}"
                                    stroke-width="26"
                                    stroke-linecap="butt"
                                    stroke-dasharray="${length} ${circumference - length}"
                                    stroke-dashoffset="${offset}"
                                    transform="rotate(-90 110 110)"
                                >
                                    <title>
                                        ${escapeHtml(labels[index])}: ${numberFormatter.format(value)}
                                    </title>
                                </circle>
                            `;
                        }
                    )
                    .join('');

                chartElement.innerHTML = `
                    <svg
                        viewBox="0 0 220 220"
                        class="mx-auto
                               h-[220px]
                               w-[220px]
                               max-w-full"
                        role="img"
                    >
                        <circle
                            cx="110"
                            cy="110"
                            r="${radius}"
                            fill="none"
                            stroke="#f1f5f9"
                            stroke-width="26"
                        />

                        ${segments}

                        <text
                            x="110"
                            y="104"
                            text-anchor="middle"
                            fill="#0f172a"
                            font-size="30"
                            font-weight="800"
                            font-family="inherit"
                        >
                            ${numberFormatter.format(total)}
                        </text>

                        <text
                            x="110"
                            y="128"
                            text-anchor="middle"
                            fill="#94a3b8"
                            font-size="11"
                            font-weight="600"
                            font-family="inherit"
                        >
                            TOTAL
                        </text>
                    </svg>
                `;

                legendElement.innerHTML =
                    labels
                        .map(
                            (label, index) => {

                                const value =
                                    values[index]
                                    ?? 0;

                                const percentage =
                                    total > 0
                                        ? (
                                            value
                                            / total
                                            * 100
                                        )
                                        : 0;

                                return `
                                    <div
                                        class="flex items-center
                                               justify-between
                                               gap-3
                                               text-xs"
                                    >
                                        <div
                                            class="min-w-0
                                                   flex items-center
                                                   gap-2"
                                        >
                                            <span
                                                class="h-2.5 w-2.5
                                                       rounded-full
                                                       flex-shrink-0"
                                                data-qh-background="${colors[index % colors.length]}"
                                            ></span>

                                            <span
                                                class="truncate
                                                       text-slate-500"
                                            >
                                                ${escapeHtml(label)}
                                            </span>
                                        </div>

                                        <div
                                            class="flex-shrink-0
                                                   font-semibold
                                                   text-slate-700"
                                        >
                                            ${numberFormatter.format(value)}
                                            <span class="text-slate-400">
                                                · ${percentage.toFixed(0)}%
                                            </span>
                                        </div>
                                    </div>
                                `;
                            }
                        )
                        .join('');
            };


            /*
            |--------------------------------------------------------------------------
            | Horizontal Bars
            |--------------------------------------------------------------------------
            */

            const renderHorizontalBars = (
                elementId,
                labels,
                values,
                color = '#2563eb'
            ) => {

                const element =
                    document.getElementById(
                        elementId
                    );

                if (!element) {
                    return;
                }

                labels =
                    Array.isArray(labels)
                        ? labels
                        : [];

                values =
                    Array.isArray(values)
                        ? values.map(
                            safeNumber
                        )
                        : [];

                const maxValue =
                    Math.max(
                        ...values,
                        0
                    );

                if (
                    labels.length === 0
                    || maxValue <= 0
                ) {
                    emptyState(
                        element
                    );

                    return;
                }

                element.innerHTML =
                    labels
                        .map(
                            (label, index) => {

                                const value =
                                    values[index]
                                    ?? 0;

                                const width =
                                    Math.max(
                                        value > 0
                                            ? 4
                                            : 0,
                                        (
                                            value
                                            / maxValue
                                        ) * 100
                                    );

                                return `
                                    <div>
                                        <div
                                            class="mb-2
                                                   flex items-center
                                                   justify-between
                                                   gap-3"
                                        >
                                            <span
                                                class="min-w-0
                                                       truncate
                                                       text-xs font-medium
                                                       text-slate-600"
                                            >
                                                ${escapeHtml(label)}
                                            </span>

                                            <span
                                                class="flex-shrink-0
                                                       text-xs font-bold
                                                       text-slate-800"
                                            >
                                                ${numberFormatter.format(value)}
                                            </span>
                                        </div>

                                        <div
                                            class="h-2.5
                                                   overflow-hidden
                                                   rounded-full
                                                   bg-slate-100"
                                        >
                                            <div
                                                class="smartfind-chart-bar
                                                       h-full
                                                       rounded-full"
                                                data-qh-width="${width}" data-qh-background="${color}" data-qh-animation-delay="${index * 70}"
                                            ></div>
                                        </div>
                                    </div>
                                `;
                            }
                        )
                        .join('');
            };


            /*
            |--------------------------------------------------------------------------
            | Render Semua
            |--------------------------------------------------------------------------
            */

            const renderAll = () => {

                animateCounters();

                renderTrendChart();

                renderDonut(
                    'category-donut-chart',
                    'category-donut-legend',
                    data?.categories?.labels,
                    data?.categories?.values
                );

                renderHorizontalBars(
                    'department-bar-chart',
                    data?.departments?.labels,
                    data?.departments?.values,
                    '#4f46e5'
                );

                renderDonut(
                    'filetype-donut-chart',
                    'filetype-donut-legend',
                    data?.fileTypes?.labels,
                    data?.fileTypes?.values
                );

                renderHorizontalBars(
                    'activity-bar-chart',
                    data?.activities?.labels,
                    data?.activities?.values,
                    '#0891b2'
                );
            };

            if (
                document.readyState === 'loading'
            ) {
                document.addEventListener(
                    'DOMContentLoaded',
                    renderAll,
                    {
                        once: true,
                    }
                );
            } else {
                renderAll();
            }

        })();
    </script>

</x-app-layout>


