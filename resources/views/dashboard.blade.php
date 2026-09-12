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
        class="space-y-7"
    >

        {{-- ========================================================= --}}
        {{-- HERO / DASHBOARD HEADER --}}
        {{-- ========================================================= --}}

        <section
            class="analytics-enter relative overflow-hidden
                   rounded-[28px]
                   bg-gradient-to-br from-blue-950 via-blue-900 to-blue-700
                   shadow-xl shadow-blue-950/10"
        >

            <div
                class="absolute -right-24 -top-28
                       h-80 w-80 rounded-full
                       bg-white/[0.06]"
            ></div>

            <div
                class="absolute right-20 -bottom-40
                       h-72 w-72 rounded-full
                       bg-blue-300/[0.08]"
            ></div>

            <div
                class="absolute left-[42%] top-0
                       h-full w-px
                       bg-gradient-to-b from-transparent via-white/10 to-transparent
                       hidden xl:block"
            ></div>

            <div
                class="relative z-10
                       grid gap-7
                       xl:grid-cols-[1.25fr_0.75fr]
                       p-6 sm:p-8"
            >

                <div>

                    <div
                        class="inline-flex items-center gap-2
                               rounded-full
                               border border-white/10
                               bg-white/[0.08]
                               px-3 py-1.5
                               text-xs font-semibold
                               text-blue-100"
                    >
                        <span
                            class="h-2 w-2 rounded-full
                                   bg-emerald-400
                                   shadow-[0_0_0_4px_rgba(52,211,153,0.12)]"
                        ></span>

                        Analitik data real BMC QualityHub
                    </div>

                    <p
                        class="mt-5
                               text-sm font-medium
                               text-blue-200"
                    >
                        Selamat datang kembali,
                    </p>

                    <h1
                        class="mt-1
                               text-3xl sm:text-4xl
                               font-bold tracking-tight
                               text-white"
                    >
                        {{ Auth::user()->name }}
                    </h1>

                    <p
                        class="mt-3
                               max-w-2xl
                               text-sm sm:text-base
                               leading-relaxed
                               text-blue-100/75"
                    >
                        Pantau dokumen, revisi, aktivitas, kategori,
                        departemen, dan jenis file dari satu dashboard
                        yang terhubung langsung dengan database BMC QualityHub.
                    </p>


                    {{-- GLOBAL SEARCH --}}

                    <form
                        method="GET"
                        action="{{ route('documents.index') }}"
                        class="mt-6
                               flex flex-col sm:flex-row
                               gap-2
                               max-w-2xl"
                    >

                        <div class="relative flex-1">

                            <div
                                class="absolute inset-y-0 left-0
                                       pl-4
                                       flex items-center
                                       pointer-events-none
                                       text-slate-400"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="19"
                                    height="19"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <circle cx="11" cy="11" r="8"/>
                                    <path d="m21 21-4.3-4.3"/>
                                </svg>
                            </div>

                            <input
                                type="text"
                                name="search"
                                autocomplete="off"
                                placeholder="Cari dokumen, nomor part, kode, atau kata kunci..."
                                class="w-full
                                       rounded-2xl
                                       border-0
                                       bg-white
                                       pl-12 pr-4 py-3.5
                                       text-sm text-slate-900
                                       placeholder:text-slate-400
                                       shadow-xl shadow-blue-950/10
                                       focus:ring-4 focus:ring-blue-300/30"
                            >

                        </div>

                        <button
                            type="submit"
                            class="rounded-2xl
                                   bg-white/10
                                   border border-white/10
                                   px-5 py-3.5
                                   text-sm font-semibold
                                   text-white
                                   backdrop-blur-sm
                                   hover:bg-white/20
                                   transition"
                        >
                            Cari Dokumen
                        </button>

                    </form>

                </div>


                {{-- PERIOD PANEL --}}

                <div
                    class="rounded-3xl
                           border border-white/10
                           bg-white/[0.08]
                           p-5 sm:p-6
                           backdrop-blur-sm"
                >

                    <div
                        class="flex items-start justify-between gap-4"
                    >

                        <div>

                            <p
                                class="text-xs font-semibold
                                       uppercase tracking-[0.18em]
                                       text-blue-200/80"
                            >
                                Periode Analitik
                            </p>

                            <p
                                class="mt-2
                                       text-2xl font-bold
                                       text-white"
                            >
                                {{ $periodLabel }}
                            </p>

                            <p
                                class="mt-1
                                       text-xs
                                       text-blue-100/65"
                            >
                                {{ $periodDateLabel }}
                            </p>

                        </div>

                        <div
                            class="h-11 w-11
                                   rounded-2xl
                                   bg-white/10
                                   flex items-center justify-center
                                   text-blue-100"
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
                                <path d="M8 2v4"/>
                                <path d="M16 2v4"/>
                                <rect width="18" height="18" x="3" y="4" rx="2"/>
                                <path d="M3 10h18"/>
                            </svg>
                        </div>

                    </div>

                    <form
                        method="GET"
                        action="{{ route('dashboard') }}"
                        class="mt-5"
                    >
                        <label
                            for="dashboard-period"
                            class="sr-only"
                        >
                            Pilih periode analitik
                        </label>

                        <select
                            id="dashboard-period"
                            name="period"
                            data-auto-submit
                            class="w-full
                                   rounded-xl
                                   border-white/10
                                   bg-blue-950/40
                                   text-sm text-white
                                   focus:border-blue-300
                                   focus:ring-blue-300"
                        >
                            @foreach([
                                7 => '7 Hari Terakhir',
                                30 => '30 Hari Terakhir',
                                90 => '90 Hari Terakhir',
                                365 => '1 Tahun Terakhir',
                            ] as $value => $label)

                                <option
                                    value="{{ $value }}"
                                    @selected($period === $value)
                                    class="text-slate-900"
                                >
                                    {{ $label }}
                                </option>

                            @endforeach
                        </select>
                    </form>


                    <div
                        class="mt-5
                               grid grid-cols-2 gap-3"
                    >

                        <div
                            class="rounded-2xl
                                   bg-white/[0.07]
                                   p-3"
                        >
                            <p
                                class="text-[11px]
                                       text-blue-100/65"
                            >
                                Dokumen baru
                            </p>

                            <p
                                class="mt-1
                                       text-xl font-bold
                                       text-white"
                            >
                                {{ number_format($newDocumentsInPeriod) }}
                            </p>
                        </div>

                        <div
                            class="rounded-2xl
                                   bg-white/[0.07]
                                   p-3"
                        >
                            <p
                                class="text-[11px]
                                       text-blue-100/65"
                            >
                                Aktivitas
                            </p>

                            <p
                                class="mt-1
                                       text-xl font-bold
                                       text-white"
                            >
                                {{ number_format($activityInPeriod) }}
                            </p>
                        </div>

                    </div>

                </div>

            </div>

        </section>


        {{-- ========================================================= --}}
        {{-- KPI CARDS --}}
        {{-- ========================================================= --}}

        <section
            class="grid grid-cols-1 sm:grid-cols-2
                   xl:grid-cols-3 2xl:grid-cols-6
                   gap-4"
        >

            {{-- ACTIVE DOCUMENTS --}}

            <article
                class="analytics-enter analytics-card
                       group
                       rounded-3xl
                       border border-slate-200/70
                       bg-white
                       p-5
                       shadow-sm"
            >

                <div class="flex items-start justify-between">

                    <div>

                        <p class="text-sm font-medium text-slate-500">
                            Dokumen Aktif
                        </p>

                        <p
                            class="mt-3
                                   text-3xl font-bold
                                   tracking-tight
                                   text-slate-900
                                   analytics-counter"
                            data-value="{{ $totalDocuments }}"
                        >
                            0
                        </p>

                    </div>

                    <div
                        class="h-11 w-11
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
                    class="mt-4
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
                       group
                       rounded-3xl
                       border border-slate-200/70
                       bg-white
                       p-5
                       shadow-sm"
            >

                <div class="flex items-start justify-between">

                    <div>

                        <p class="text-sm font-medium text-slate-500">
                            Dokumen Arsip
                        </p>

                        <p
                            class="mt-3
                                   text-3xl font-bold
                                   tracking-tight
                                   text-slate-900
                                   analytics-counter"
                            data-value="{{ $archivedDocuments }}"
                        >
                            0
                        </p>

                    </div>

                    <div
                        class="h-11 w-11
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

                <p class="mt-4 text-xs text-slate-400">
                    Tetap tersimpan tanpa hard delete
                </p>

            </article>


            {{-- REVISIONS --}}

            <article
                class="analytics-enter analytics-card
                       group
                       rounded-3xl
                       border border-slate-200/70
                       bg-white
                       p-5
                       shadow-sm"
            >

                <div class="flex items-start justify-between">

                    <div>

                        <p class="text-sm font-medium text-slate-500">
                            Total Revisi
                        </p>

                        <p
                            class="mt-3
                                   text-3xl font-bold
                                   tracking-tight
                                   text-slate-900
                                   analytics-counter"
                            data-value="{{ $totalRevisions }}"
                        >
                            0
                        </p>

                    </div>

                    <div
                        class="h-11 w-11
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

                <p class="mt-4 text-xs text-slate-400">
                    Menghitung Revisi 1 dan seterusnya
                </p>

            </article>


            {{-- DEPARTMENTS --}}

            <article
                class="analytics-enter analytics-card
                       group
                       rounded-3xl
                       border border-slate-200/70
                       bg-white
                       p-5
                       shadow-sm"
            >

                <div class="flex items-start justify-between">

                    <div>

                        <p class="text-sm font-medium text-slate-500">
                            Departemen Aktif
                        </p>

                        <p
                            class="mt-3
                                   text-3xl font-bold
                                   tracking-tight
                                   text-slate-900
                                   analytics-counter"
                            data-value="{{ $totalDepartments }}"
                        >
                            0
                        </p>

                    </div>

                    <div
                        class="h-11 w-11
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

                <p class="mt-4 text-xs text-slate-400">
                    {{ number_format($totalCategories) }} kategori aktif
                </p>

            </article>


            {{-- USERS --}}

            <article
                class="analytics-enter analytics-card
                       group
                       rounded-3xl
                       border border-slate-200/70
                       bg-white
                       p-5
                       shadow-sm"
            >

                <div class="flex items-start justify-between">

                    <div>

                        <p class="text-sm font-medium text-slate-500">
                            Pengguna Aktif
                        </p>

                        <p
                            class="mt-3
                                   text-3xl font-bold
                                   tracking-tight
                                   text-slate-900
                                   analytics-counter"
                            data-value="{{ $activeUsers }}"
                        >
                            0
                        </p>

                    </div>

                    <div
                        class="h-11 w-11
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

                <p class="mt-4 text-xs text-slate-400">
                    {{ number_format($pendingUsers) }} menunggu persetujuan
                </p>

            </article>


            {{-- ACTIVITY PERIOD --}}

            <article
                class="analytics-enter analytics-card
                       group
                       rounded-3xl
                       border border-slate-200/70
                       bg-white
                       p-5
                       shadow-sm"
            >

                <div class="flex items-start justify-between">

                    <div>

                        <p class="text-sm font-medium text-slate-500">
                            Aktivitas {{ $periodLabel }}
                        </p>

                        <p
                            class="mt-3
                                   text-3xl font-bold
                                   tracking-tight
                                   text-slate-900
                                   analytics-counter"
                            data-value="{{ $activityInPeriod }}"
                        >
                            0
                        </p>

                    </div>

                    <div
                        class="h-11 w-11
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

                <p class="mt-4 text-xs text-slate-400">
                    Aktivitas tercatat pada periode aktif
                </p>

            </article>

        </section>


        {{-- ========================================================= --}}
        {{-- SECONDARY SUMMARY --}}
        {{-- ========================================================= --}}

        <section
            class="analytics-enter
                   grid gap-4
                   md:grid-cols-3"
        >

            <div
                class="rounded-3xl
                       border border-slate-200/70
                       bg-white
                       p-5
                       shadow-sm
                       flex items-center gap-4"
            >

                <div
                    class="h-12 w-12
                           rounded-2xl
                           bg-emerald-50
                           text-emerald-700
                           flex items-center justify-center"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="21"
                        height="21"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M7 3h10"/>
                        <path d="M7 7h10"/>
                        <path d="M5 11h14v10H5z"/>
                    </svg>
                </div>

                <div>
                    <p class="text-xs font-medium text-slate-400">
                        Spreadsheet Aktif
                    </p>

                    <p class="mt-1 text-xl font-bold text-slate-900">
                        {{ number_format($spreadsheetDocuments) }}
                    </p>

                    <p class="mt-1 text-xs text-slate-400">
                        XLSX, XLS, dan CSV
                    </p>
                </div>

            </div>


            <div
                class="rounded-3xl
                       border border-slate-200/70
                       bg-white
                       p-5
                       shadow-sm
                       flex items-center gap-4"
            >

                <div
                    class="h-12 w-12
                           rounded-2xl
                           bg-blue-50
                           text-blue-700
                           flex items-center justify-center"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="21"
                        height="21"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <ellipse cx="12" cy="5" rx="8" ry="3"/>
                        <path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/>
                        <path d="M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/>
                    </svg>
                </div>

                <div>
                    <p class="text-xs font-medium text-slate-400">
                        Ukuran Dokumen Aktif
                    </p>

                    <p class="mt-1 text-xl font-bold text-slate-900">
                        {{ $formatBytes($totalStorageBytes) }}
                    </p>

                    <p class="mt-1 text-xs text-slate-400">
                        Berdasarkan metadata file
                    </p>
                </div>

            </div>


            <div
                class="rounded-3xl
                       border border-slate-200/70
                       bg-white
                       p-5
                       shadow-sm
                       flex items-center gap-4"
            >

                <div
                    class="h-12 w-12
                           rounded-2xl
                           bg-violet-50
                           text-violet-700
                           flex items-center justify-center"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="21"
                        height="21"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M3 12h18"/>
                        <path d="M12 3v18"/>
                    </svg>
                </div>

                <div>
                    <p class="text-xs font-medium text-slate-400">
                        Dokumen Baru
                    </p>

                    <p class="mt-1 text-xl font-bold text-slate-900">
                        {{ number_format($newDocumentsInPeriod) }}
                    </p>

                    <p class="mt-1 text-xs text-slate-400">
                        {{ $growthLabel }}
                    </p>
                </div>

            </div>

        </section>


        {{-- ========================================================= --}}
        {{-- MAIN ANALYTICS --}}
        {{-- ========================================================= --}}

        <section
            class="grid gap-5
                   2xl:grid-cols-3"
        >

            {{-- TREND --}}

            <article
                class="analytics-enter
                       2xl:col-span-2
                       rounded-3xl
                       border border-slate-200/70
                       bg-white
                       shadow-sm
                       overflow-hidden"
            >

                <div
                    class="p-5 sm:p-6
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
                            Pergerakan data harian selama {{ $periodLabel }}.
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
                        class="chart-stage min-h-[310px]"
                        aria-label="Grafik tren dokumen dan aktivitas"
                    ></div>

                </div>

            </article>


            {{-- CATEGORY DONUT --}}

            <article
                class="analytics-enter
                       rounded-3xl
                       border border-slate-200/70
                       bg-white
                       shadow-sm
                       overflow-hidden"
            >

                <div
                    class="p-5 sm:p-6
                           border-b border-slate-100"
                >
                    <h2 class="font-bold text-slate-900">
                        Dokumen per Kategori
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Komposisi seluruh dokumen aktif.
                    </p>
                </div>

                <div class="p-5 sm:p-6">

                    <div
                        id="category-donut-chart"
                        class="chart-stage min-h-[240px]"
                        aria-label="Diagram komposisi dokumen per kategori"
                    ></div>

                    <div
                        id="category-donut-legend"
                        class="mt-4 space-y-2"
                    ></div>

                </div>

            </article>

        </section>


        {{-- ========================================================= --}}
        {{-- DISTRIBUTION GRID --}}
        {{-- ========================================================= --}}

        <section
            class="grid gap-5
                   xl:grid-cols-2"
        >

            {{-- DEPARTMENT --}}

            <article
                class="analytics-enter
                       rounded-3xl
                       border border-slate-200/70
                       bg-white
                       shadow-sm"
            >

                <div
                    class="p-5 sm:p-6
                           flex items-start justify-between gap-4
                           border-b border-slate-100"
                >
                    <div>
                        <h2 class="font-bold text-slate-900">
                            Distribusi Departemen
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Jumlah dokumen aktif berdasarkan departemen.
                        </p>
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

                <div class="p-5 sm:p-6">
                    <div
                        id="department-bar-chart"
                        class="space-y-4"
                    ></div>
                </div>

            </article>


            {{-- FILE TYPES --}}

            <article
                class="analytics-enter
                       rounded-3xl
                       border border-slate-200/70
                       bg-white
                       shadow-sm"
            >

                <div
                    class="p-5 sm:p-6
                           border-b border-slate-100"
                >
                    <h2 class="font-bold text-slate-900">
                        Distribusi Jenis File
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Format file yang terindeks pada dokumen aktif.
                    </p>
                </div>

                <div
                    class="p-5 sm:p-6
                           grid gap-5
                           md:grid-cols-[0.9fr_1.1fr]
                           items-center"
                >

                    <div
                        id="filetype-donut-chart"
                        class="chart-stage min-h-[220px]"
                        aria-label="Diagram distribusi jenis file"
                    ></div>

                    <div
                        id="filetype-donut-legend"
                        class="space-y-2"
                    ></div>

                </div>

            </article>

        </section>


        {{-- ========================================================= --}}
        {{-- ACTIVITY + TOP DOCUMENTS --}}
        {{-- ========================================================= --}}

        <section
            class="grid gap-5
                   xl:grid-cols-2"
        >

            {{-- ACTIVITY BREAKDOWN --}}

            <article
                class="analytics-enter
                       rounded-3xl
                       border border-slate-200/70
                       bg-white
                       shadow-sm"
            >

                <div
                    class="p-5 sm:p-6
                           border-b border-slate-100"
                >
                    <h2 class="font-bold text-slate-900">
                        Komposisi Aktivitas
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Aktivitas sistem selama {{ $periodLabel }}.
                    </p>
                </div>

                <div class="p-5 sm:p-6">
                    <div
                        id="activity-bar-chart"
                        class="space-y-4"
                    ></div>
                </div>

            </article>


            {{-- TOP DOCUMENTS --}}

            <article
                class="analytics-enter
                       rounded-3xl
                       border border-slate-200/70
                       bg-white
                       shadow-sm"
            >

                <div
                    class="p-5 sm:p-6
                           border-b border-slate-100"
                >
                    <h2 class="font-bold text-slate-900">
                        Dokumen Paling Sering Diakses
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Berdasarkan buka, lihat, dan unduh selama {{ $periodLabel }}.
                    </p>
                </div>

                <div class="p-3">

                    @forelse($topAccessedDocuments as $index => $document)

                        <a
                            href="{{ route('documents.show', $document->id) }}"
                            class="group
                                   flex items-center gap-4
                                   rounded-2xl
                                   px-4 py-3.5
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

        </section>


        {{-- ========================================================= --}}
        {{-- RECENT DOCUMENTS + RECENT ACTIVITY --}}
        {{-- ========================================================= --}}

        <section
            class="grid gap-5
                   xl:grid-cols-3"
        >

            {{-- RECENT DOCUMENTS --}}

            <article
                class="analytics-enter
                       xl:col-span-2
                       rounded-3xl
                       border border-slate-200/70
                       bg-white
                       shadow-sm
                       overflow-hidden"
            >

                <div
                    class="p-5 sm:p-6
                           flex items-center justify-between gap-4
                           border-b border-slate-100"
                >

                    <div>
                        <h2 class="font-bold text-slate-900">
                            Dokumen Terbaru
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Dokumen aktif yang paling baru terindeks.
                        </p>
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
                                   flex items-center gap-4
                                   rounded-2xl
                                   px-4 py-4
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


            {{-- RECENT ACTIVITY --}}

            <article
                class="analytics-enter
                       rounded-3xl
                       border border-slate-200/70
                       bg-white
                       shadow-sm
                       overflow-hidden"
            >

                <div
                    class="p-5 sm:p-6
                           border-b border-slate-100"
                >
                    <h2 class="font-bold text-slate-900">
                        Aktivitas Terbaru
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Jejak aktivitas terbaru dalam sistem.
                    </p>
                </div>


                <div class="p-5 space-y-5">

                    @forelse($recentActivities as $activity)

                        <div class="flex gap-3">

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
                                    class="text-sm
                                           leading-relaxed
                                           text-slate-700"
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


