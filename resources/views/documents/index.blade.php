<x-app-layout>

    @php
        $hasActiveFilters =
            request()->filled('search') ||
            request()->filled('department') ||
            request()->filled('category') ||
            request()->filled('extension') ||
            request()->filled('document_type') ||
            request()->filled('date_from') ||
            request()->filled('date_to');
    @endphp


    {{-- ========================================================= --}}
    {{-- PAGE HEADER --}}
    {{-- ========================================================= --}}

    <section
        class="flex
               flex-col
               lg:flex-row
               lg:items-center
               lg:justify-between
               gap-5
               mb-8"
    >

        <div>

            <div
                class="inline-flex
                       items-center
                       gap-2
                       mb-2
                       text-sm
                       font-semibold
                       text-blue-700"
            >
                <span
                    class="w-2
                           h-2
                           rounded-full
                           bg-blue-600"
                ></span>

                Document Management
            </div>


            <h1
                class="text-3xl
                       sm:text-4xl
                       font-bold
                       tracking-tight
                       text-slate-900"
            >
                All Documents
            </h1>


            <p
                class="mt-2
                       text-sm
                       sm:text-base
                       text-slate-500"
            >
                Cari, atur, dan akses dokumen internal BMC
                dari satu ruang kerja terpusat.
            </p>

        </div>



        {{-- HEADER ACTIONS --}}

        <div
            class="flex
                   flex-col
                   sm:flex-row
                   gap-3"
        >

            {{-- MY FAVORITES --}}

            @if(Route::has('favorites.index'))

                <a
                    href="{{ route('favorites.index') }}"

                    class="inline-flex
                           items-center
                           justify-center
                           gap-2

                           rounded-xl

                           border
                           border-amber-200

                           bg-amber-50

                           px-5
                           py-3

                           text-sm
                           font-semibold
                           text-amber-700

                           hover:bg-amber-100
                           hover:border-amber-300

                           transition"
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
                        <path
                            d="m12 17.27
                               5.18 3.04
                               -1.4-5.89
                               4.58-3.96
                               -6.03-.52
                               L12 4.38
                               9.67 9.94
                               l-6.03.52
                               4.58 3.96
                               -1.4 5.89Z"
                        />
                    </svg>

                    Favorit Saya

                </a>

            @endif



            {{-- ADD DOCUMENT --}}

            @if(Auth::user()->isAdmin())

                <a
                    href="{{ route('documents.create') }}"

                    class="inline-flex
                           items-center
                           justify-center
                           gap-2

                           rounded-xl

                           bg-blue-800

                           px-5
                           py-3

                           text-sm
                           font-semibold
                           text-white

                           shadow-lg
                           shadow-blue-900/10

                           hover:bg-blue-900
                           hover:-translate-y-0.5

                           transition
                           duration-200"
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
                        <path d="M12 5v14"/>
                        <path d="M5 12h14"/>
                    </svg>

                    Tambah Dokumen

                </a>

            @endif

        </div>

    </section>



    {{-- ========================================================= --}}
    {{-- SUCCESS NOTIFICATION --}}
    {{-- ========================================================= --}}

    @if(session('success'))

        <div
            x-data="{ show: true }"
            x-show="show"

            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"

            class="mb-6

                   flex
                   items-start
                   justify-between
                   gap-4

                   rounded-2xl

                   border
                   border-emerald-200

                   bg-emerald-50

                   px-5
                   py-4"
        >

            <div class="flex items-start gap-3">

                <div
                    class="w-9
                           h-9

                           rounded-xl

                           bg-emerald-100
                           text-emerald-700

                           flex
                           items-center
                           justify-center

                           flex-shrink-0"
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
                        <path d="M20 6 9 17l-5-5"/>
                    </svg>

                </div>


                <div>

                    <p
                        class="text-sm
                               font-bold
                               text-emerald-900"
                    >
                        Berhasil
                    </p>

                    <p
                        class="mt-1
                               text-sm
                               text-emerald-700"
                    >
                        {{ session('success') }}
                    </p>

                </div>

            </div>


            <button
                type="button"

                @click="show = false"

                class="text-emerald-600
                       hover:text-emerald-900
                       transition"

                aria-label="Tutup notifikasi"
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
                    <path d="M18 6 6 18"/>
                    <path d="m6 6 12 12"/>
                </svg>

            </button>

        </div>

    @endif



    {{-- ========================================================= --}}
    {{-- VALIDATION ERROR --}}
    {{-- ========================================================= --}}

    @if($errors->any())

        <div
            class="mb-6

                   rounded-2xl

                   border
                   border-red-200

                   bg-red-50

                   px-5
                   py-4"
        >

            <div class="flex gap-3">

                <div
                    class="w-9
                           h-9
                           rounded-xl
                           bg-red-100
                           text-red-700
                           flex
                           items-center
                           justify-center
                           flex-shrink-0"
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
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 8v4"/>
                        <path d="M12 16h.01"/>
                    </svg>

                </div>


                <div>

                    <p
                        class="text-sm
                               font-bold
                               text-red-900"
                    >
                        Kesalahan Filter Pencarian
                    </p>

                    <p
                        class="mt-1
                               text-sm
                               text-red-700"
                    >
                        {{ $errors->first() }}
                    </p>

                </div>

            </div>

        </div>

    @endif



    {{-- ========================================================= --}}
    {{-- QUICK STATS --}}
    {{-- ========================================================= --}}

    <section
        class="grid
               grid-cols-1
               sm:grid-cols-2
               xl:grid-cols-3
               gap-4
               mb-6"
    >


        {{-- DOCUMENT COUNT --}}

        <div
            class="bg-white

                   rounded-2xl

                   border
                   border-slate-200/70

                   p-5

                   shadow-sm"
        >

            <div class="flex items-center justify-between">

                <div>

                    <p
                        class="text-sm
                               font-medium
                               text-slate-500"
                    >
                        {{ $hasActiveFilters
                            ? 'Dokumen Sesuai'
                            : 'Dokumen Terindeks'
                        }}
                    </p>


                    <p
                        class="mt-2
                               text-3xl
                               font-bold
                               text-slate-900"
                    >
                        {{ number_format($documents->total()) }}
                    </p>


                    <p
                        class="mt-2
                               text-xs
                               text-slate-400"
                    >
                        {{ $hasActiveFilters
                            ? 'Hasil yang sesuai dengan filter saat ini'
                            : 'Dokumen aktif yang tersedia di QualityHub'
                        }}
                    </p>

                </div>


                <div
                    class="w-12
                           h-12

                           rounded-2xl

                           bg-blue-50
                           text-blue-700

                           flex
                           items-center
                           justify-center"
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
                        <path
                            d="M14.5 2H6
                               a2 2 0 0 0-2 2
                               v16
                               a2 2 0 0 0 2 2
                               h12
                               a2 2 0 0 0 2-2
                               V7.5Z"
                        />

                        <polyline points="14 2 14 8 20 8"/>
                    </svg>

                </div>

            </div>

        </div>



        {{-- STORAGE --}}

        <div
            class="bg-white

                   rounded-2xl

                   border
                   border-slate-200/70

                   p-5

                   shadow-sm"
        >

            <div class="flex items-center justify-between">

                <div>

                    <p
                        class="text-sm
                               font-medium
                               text-slate-500"
                    >
                        Penyimpanan Dokumen
                    </p>


                    <p
                        class="mt-2
                               text-lg
                               font-bold
                               text-slate-900"
                    >
                        Penyimpanan Internal
                    </p>


                    <p
                        class="mt-2
                               text-xs
                               text-slate-400"
                    >
                        File hanya dapat diakses melalui rute yang memerlukan autentikasi
                    </p>

                </div>


                <div
                    class="w-12
                           h-12

                           rounded-2xl

                           bg-emerald-50
                           text-emerald-700

                           flex
                           items-center
                           justify-center"
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
                        <rect
                            width="18"
                            height="11"
                            x="3"
                            y="11"
                            rx="2"
                        />

                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>

                </div>

            </div>

        </div>



        {{-- SOURCE --}}

        <div
            class="bg-white

                   rounded-2xl

                   border
                   border-slate-200/70

                   p-5

                   shadow-sm

                   sm:col-span-2
                   xl:col-span-1"
        >

            <div class="flex items-center justify-between">

                <div>

                    <p
                        class="text-sm
                               font-medium
                               text-slate-500"
                    >
                        Sumber Data Saat Ini
                    </p>


                    <p
                        class="mt-2
                               text-lg
                               font-bold
                               text-slate-900"
                    >
                        Pengembangan Lokal
                    </p>


                    <p
                        class="mt-2
                               text-xs
                               text-slate-400"
                    >
                        Integrasi server BMC akan dikonfigurasi kemudian
                    </p>

                </div>


                <div
                    class="w-12
                           h-12

                           rounded-2xl

                           bg-indigo-50
                           text-indigo-700

                           flex
                           items-center
                           justify-center"
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

                        <ellipse
                            cx="12"
                            cy="5"
                            rx="9"
                            ry="3"
                        />

                        <path
                            d="M3 5v14
                               c0 1.7 4 3 9 3
                               s9-1.3 9-3V5"
                        />

                        <path
                            d="M3 12
                               c0 1.7 4 3 9 3
                               s9-1.3 9-3"
                        />

                    </svg>

                </div>

            </div>

        </div>

    </section>



    {{-- ========================================================= --}}
    {{-- AI SMART SEARCH --}}
    {{-- ========================================================= --}}

    <section
        x-data="aiSmartSearch"
        class="mb-6"
        aria-labelledby="ai-smart-search-title"
    >
        <div class="rounded-2xl border border-indigo-200/70 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">
                        <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M12 3a4 4 0 0 0-4 4v1a4 4 0 0 0-2 7.5A4 4 0 0 0 10 21h2"/>
                            <path d="M12 3a4 4 0 0 1 4 4v1a4 4 0 0 1 2 7.5A4 4 0 0 1 14 21h-2"/>
                            <path d="M9 8h6M8 13h8M12 8v13"/>
                        </svg>
                    </div>

                    <div>
                        <h2 id="ai-smart-search-title" class="font-bold text-slate-900">
                            AI Smart Search
                        </h2>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Cari dokumen menggunakan bahasa sehari-hari dari metadata QualityHub.
                        </p>
                    </div>
                </div>

                <span class="inline-flex w-fit items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700">
                    <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                    AI Lokal
                </span>
            </div>

            <form class="mt-5" @submit.prevent="performAiSearch()">
                <label for="ai-smart-search-query" class="block text-sm font-semibold text-slate-700">
                    Permintaan pencarian
                </label>

                <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                    <input
                        id="ai-smart-search-query"
                        x-model="query"
                        type="search"
                        maxlength="500"
                        autocomplete="off"
                        required
                        placeholder="Contoh: cari dokumen retooling line 2 bulan September"
                        aria-describedby="ai-smart-search-help ai-smart-search-status"
                        :aria-invalid="error ? 'true' : 'false'"
                        class="min-w-0 flex-1 rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500"
                    >

                    <button
                        type="submit"
                        :disabled="loading"
                        :aria-busy="loading"
                        aria-controls="ai-smart-search-results"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
                    >
                        <svg x-show="loading" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true" x-cloak>
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                        </svg>
                        <span x-text="loading ? 'Mencari...' : 'Cari dengan AI'"></span>
                    </button>
                </div>

                <p id="ai-smart-search-help" class="mt-2 text-xs text-slate-500">
                    Contoh: “cari axle assy posisi LH terbaru” atau “dokumen brake assy part number 402 502”.
                </p>
            </form>

            <p id="ai-smart-search-status" class="sr-only" role="status" aria-live="polite" aria-atomic="true" x-text="statusMessage"></p>

            <div x-show="loading" class="mt-4 rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-700" role="status" x-cloak>
                AI lokal sedang memahami permintaan dan mengurutkan metadata dokumen.
            </div>

            <div x-show="error || fallback" class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3" role="alert" x-cloak>
                <p
                    class="text-sm font-semibold text-amber-800"
                    x-text="fallback ? 'AI Smart Search lokal sedang tidak tersedia.' : error"
                ></p>
                <p x-show="fallback" class="mt-1 text-xs text-amber-700" x-text="message"></p>
                <p x-show="error" class="mt-1 text-xs text-amber-700">
                    Pencarian QualityHub biasa di bawah tetap dapat digunakan.
                </p>
                <a
                    x-show="fallbackUrl"
                    :href="fallbackUrl"
                    class="mt-3 inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-xs font-semibold text-amber-800 transition hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                >
                    Cari dengan pencarian biasa
                </a>
            </div>

            <div id="ai-smart-search-results" x-show="searched && !loading && !error" :aria-busy="loading" class="mt-5" x-cloak>
                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800" x-text="fallback ? 'Hasil pencarian biasa' : 'Hasil AI Smart Search'"></h3>
                        <p x-show="message && !fallback" class="mt-0.5 text-xs text-slate-500" x-text="message"></p>
                    </div>
                    <span class="text-xs font-semibold text-indigo-700" x-text="results.length + ' dokumen'"></span>
                </div>

                <div x-show="results.length === 0" class="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-5 py-6 text-center">
                    <p class="text-sm font-semibold text-slate-700">Dokumen yang sesuai tidak ditemukan</p>
                    <p class="mt-1 text-xs text-slate-500">Coba deskripsi yang lebih singkat atau gunakan pencarian biasa.</p>
                </div>

                <div x-show="results.length > 0" class="mt-3 divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200">
                    <template x-for="document in results" :key="document.id">
                        <a
                            :href="document.url"
                            class="group flex items-start gap-3 bg-white px-4 py-4 transition hover:bg-indigo-50/50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="min-w-0 truncate text-sm font-semibold text-slate-800" x-text="document.title"></p>
                                    <span x-show="document.extension" class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500" x-text="document.extension"></span>
                                </div>
                                <p x-show="document.explanation" class="mt-1 text-xs font-medium text-indigo-700" x-text="document.explanation"></p>
                                <p x-show="document.metadata" class="mt-1 text-xs text-slate-500" x-text="document.metadata"></p>
                            </div>

                            <svg class="mt-1 flex-shrink-0 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-indigo-600" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </section>


    {{-- ========================================================= --}}
    {{-- SMART SEARCH ENGINE --}}
    <div
        id="document-search"
        class="scroll-mt-24"
        aria-hidden="true"
    ></div>

    {{-- ========================================================= --}}

    <section
        x-data="documentSearch"

        class="mb-6"
    >

        <div
            class="bg-white

                   rounded-2xl

                   border
                   border-slate-200/70

                   shadow-sm

                   p-5"
        >


            {{-- ================================================= --}}
            {{-- SEARCH HEADER --}}
            {{-- ================================================= --}}

            <div
                class="flex
                       flex-col
                       lg:flex-row
                       lg:items-center
                       lg:justify-between
                       gap-3

                       mb-5"
            >

                <div>

                    <div
                        class="flex
                               items-center
                               gap-2"
                    >

                        <div
                            class="w-9
                                   h-9

                                   rounded-xl

                                   bg-blue-50
                                   text-blue-700

                                   flex
                                   items-center
                                   justify-center"
                        >

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
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

                        </div>


                        <div>

                            <h2
                                class="font-bold
                                       text-slate-900"
                            >
                                Pencarian QualityHub
                            </h2>


                            <p
                                class="mt-0.5
                                       text-xs
                                       text-slate-500"
                            >
                                Cari berdasarkan metadata dokumen,
                                kategori, dan departemen.
                            </p>

                        </div>

                    </div>

                </div>


                <div
                    class="inline-flex
                           items-center
                           gap-2

                           text-xs
                           font-medium
                           text-emerald-700"
                >

                    <span
                        class="relative
                               flex
                               h-2
                               w-2"
                    >

                        <span
                            class="absolute
                                   inline-flex
                                   h-full
                                   w-full
                                   animate-ping
                                   rounded-full
                                   bg-emerald-400
                                   opacity-60"
                        ></span>

                        <span
                            class="relative
                                   inline-flex
                                   h-2
                                   w-2
                                   rounded-full
                                   bg-emerald-500"
                        ></span>

                    </span>

                    Mesin Pencarian Aktif

                </div>

            </div>



            {{-- ================================================= --}}
            {{-- MAIN SEARCH ROW --}}
            {{-- ================================================= --}}

            <div
                class="flex
                       flex-col
                       xl:flex-row
                       gap-3"
            >


                {{-- SEARCH INPUT --}}

                <div class="relative flex-1">

                    <div
                        class="absolute
                               inset-y-0
                               left-0

                               pl-4

                               flex
                               items-center

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
                        x-model="search"

                        @input.debounce.180ms="performSearch()"

                        @focus="
                            if (hasLiveCriteria()) {
                                performSearch()
                            }
                        "

                        @keydown.escape="open = false"

                        @keydown.enter.prevent="submitFilters()"

                        type="search"

                        autocomplete="off"

                        placeholder="Contoh: Axle Assy, RH, Brake 402 / 502, Instruksi Perubahan..."

                        class="w-full

                               rounded-xl

                               border-slate-200

                               bg-slate-50

                               pl-12
                               pr-12
                               py-3

                               text-sm

                               placeholder:text-slate-400

                               focus:border-blue-500
                               focus:ring-blue-500"
                    >



                    {{-- LOADING --}}

                    <div
                        x-show="loading"

                        class="absolute
                               right-4
                               top-1/2
                               -translate-y-1/2"

                        x-cloak
                    >

                        <svg
                            class="animate-spin
                                   w-5
                                   h-5
                                   text-blue-600"

                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >

                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>

                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"
                            ></path>

                        </svg>

                    </div>



                    {{-- ========================================= --}}
                    {{-- LIVE SEARCH RESULTS --}}
                    {{-- ========================================= --}}

                    <div
                        x-show="
                            open &&
                            hasLiveCriteria()
                        "

                        @click.outside="open = false"

                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"

                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"

                        class="absolute
                               z-50

                               left-0
                               right-0
                               top-full

                               mt-2

                               bg-white

                               rounded-2xl

                               border
                               border-slate-200

                               shadow-2xl
                               shadow-slate-900/10

                               overflow-hidden"

                        x-cloak
                    >


                        {{-- RESULT HEADER --}}

                        <div
                            class="px-5
                                   py-3

                                   flex
                                   items-center
                                   justify-between
                                   gap-3

                                   bg-slate-50

                                   border-b
                                   border-slate-100"
                        >

                            <div>

                                <p
                                    class="text-xs
                                           font-semibold
                                           text-slate-600"
                                >
                                    Hasil Pencarian QualityHub
                                </p>


                                <p
                                    class="mt-0.5
                                           text-[11px]
                                           text-slate-400"
                                >
                                    Maksimal 20 dokumen ditampilkan
                                </p>

                            </div>


                            <span
                                class="text-xs
                                       font-medium
                                       text-slate-500"

                                x-text="
                                    limited
                                        ? shown + ' dari ' + count + ' hasil'
                                        : count + ' hasil'
                                "
                            ></span>

                        </div>



                        {{-- SEARCH ERROR --}}

                        <div
                            x-show="error"

                            class="border-b
                                   border-red-100

                                   bg-red-50

                                   px-5
                                   py-3

                                   text-xs
                                   text-red-700"

                            x-cloak
                        >
                            <span x-text="error"></span>
                        </div>



                        {{-- RESULTS --}}

                        <div
                            class="max-h-[460px]
                                   overflow-y-auto"
                        >

                            <template
                                x-for="document in results"
                                :key="document.id"
                            >

                                <a
                                    :href="document.url"

                                    class="group/result

                                           flex
                                           items-start
                                           gap-3

                                           px-5
                                           py-4

                                           border-b
                                           border-slate-100

                                           hover:bg-blue-50/50

                                           transition"
                                >


                                    {{-- FILE ICON --}}

                                    <div
                                        class="w-10
                                               h-10

                                               rounded-xl

                                               bg-blue-50
                                               text-blue-700

                                               flex
                                               items-center
                                               justify-center

                                               flex-shrink-0

                                               group-hover/result:bg-blue-100

                                               transition"
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
                                            <path
                                                d="M14.5 2H6
                                                   a2 2 0 0 0-2 2
                                                   v16
                                                   a2 2 0 0 0 2 2
                                                   h12
                                                   a2 2 0 0 0 2-2
                                                   V7.5Z"
                                            />

                                            <polyline
                                                points="14 2 14 8 20 8"
                                            />
                                        </svg>

                                    </div>



                                    {{-- RESULT INFORMATION --}}

                                    <div class="min-w-0 flex-1">

                                        <div
                                            class="flex
                                                   items-center
                                                   gap-2"
                                        >

                                            <p
                                                class="font-semibold
                                                       text-sm
                                                       text-slate-800
                                                       truncate"

                                                x-text="document.title"
                                            ></p>


                                            <span
                                                class="rounded-md

                                                       bg-slate-100

                                                       px-2
                                                       py-0.5

                                                       text-[10px]
                                                       font-bold
                                                       text-slate-500"

                                                x-text="document.extension"
                                            ></span>



                                            {{-- FAVORITE INDICATOR --}}

                                            <template
                                                x-if="document.is_favorited"
                                            >

                                                <span
                                                    class="inline-flex
                                                           items-center

                                                           text-amber-500"

                                                    title="Tersimpan di Favorit"
                                                >

                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        width="14"
                                                        height="14"
                                                        viewBox="0 0 24 24"
                                                        fill="currentColor"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                    >
                                                        <path
                                                            d="m12 17.27
                                                               5.18 3.04
                                                               -1.4-5.89
                                                               4.58-3.96
                                                               -6.03-.52
                                                               L12 4.38
                                                               9.67 9.94
                                                               l-6.03.52
                                                               4.58 3.96
                                                               -1.4 5.89Z"
                                                        />
                                                    </svg>

                                                </span>

                                            </template>

                                        </div>



                                        {{-- CODE / PART NUMBER --}}

                                        <p
                                            class="mt-1
                                                   text-xs
                                                   text-slate-400
                                                   truncate"

                                            x-text="
                                                [
                                                    document.document_code,
                                                    document.part_number
                                                ]
                                                .filter(Boolean)
                                                .join(' • ')
                                                || document.file_name
                                            "
                                        ></p>


                                        <template x-if="document.qc_type_label || document.qc_detail">
                                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                                <span
                                                    x-show="document.qc_type_label"
                                                    class="rounded-md bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-700"
                                                    x-text="document.qc_type_label"
                                                ></span>

                                                <span
                                                    x-show="document.qc_detail"
                                                    class="text-[11px] font-medium text-slate-600"
                                                    x-text="document.qc_detail"
                                                ></span>

                                                <span
                                                    x-show="document.production_date"
                                                    class="text-[11px] text-slate-400"
                                                    x-text="'• Produksi ' + document.production_date"
                                                ></span>
                                            </div>
                                        </template>



                                        {{-- CATEGORY INFORMATION --}}

                                        <div
                                            class="mt-2

                                                   flex
                                                   flex-wrap
                                                   items-center
                                                   gap-2"
                                        >

                                            <span
                                                class="text-[11px]
                                                       font-medium
                                                       text-blue-700"

                                                x-text="
                                                    document.category
                                                    || 'Tanpa Kategori'
                                                "
                                            ></span>


                                            <span
                                                class="text-[11px]
                                                       text-slate-300"
                                            >
                                                •
                                            </span>


                                            <span
                                                class="text-[11px]
                                                       text-slate-500"

                                                x-text="
                                                    document.department
                                                    || '-'
                                                "
                                            ></span>


                                            <template x-if="document.date">

                                                <span
                                                    class="text-[11px]
                                                           text-slate-400"

                                                    x-text="
                                                        '• ' + document.date
                                                    "
                                                ></span>

                                            </template>

                                        </div>

                                    </div>



                                    {{-- ARROW --}}

                                    <div
                                        class="mt-2
                                               text-slate-300

                                               group-hover/result:text-blue-600
                                               group-hover/result:translate-x-0.5

                                               transition"
                                    >

                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="16"
                                            height="16"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path d="m9 18 6-6-6-6"/>
                                        </svg>

                                    </div>

                                </a>

                            </template>



                            {{-- EMPTY RESULT --}}

                            <div
                                x-show="
                                    !loading &&
                                    !error &&
                                    results.length === 0
                                "

                                class="px-6
                                       py-10
                                       text-center"

                                x-cloak
                            >

                                <div
                                    class="w-12
                                           h-12

                                           mx-auto

                                           rounded-xl

                                           bg-slate-100
                                           text-slate-400

                                           flex
                                           items-center
                                           justify-center"
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
                                        <circle cx="11" cy="11" r="8"/>
                                        <path d="m21 21-4.3-4.3"/>
                                    </svg>

                                </div>


                                <p
                                    class="mt-3
                                           text-sm
                                           font-semibold
                                           text-slate-700"
                                >
                                    Dokumen tidak ditemukan
                                </p>


                                <p
                                    class="mt-1
                                           text-xs
                                           text-slate-400"
                                >
                                    Coba gunakan kata kunci atau kombinasi filter lain.
                                </p>

                            </div>

                        </div>



                        {{-- RESULT FOOTER --}}

                        <div
                            x-show="
                                !loading &&
                                !error &&
                                count > 0
                            "

                            class="bg-slate-50

                                   border-t
                                   border-slate-100

                                   p-3"

                            x-cloak
                        >

                            <button
                                type="button"

                                @click="submitFilters()"

                                class="w-full

                                       rounded-xl

                                       px-4
                                       py-2.5

                                       text-xs
                                       font-semibold
                                       text-blue-700

                                       hover:bg-blue-100/70

                                       transition"
                            >
                                Lihat semua dokumen yang sesuai
                            </button>

                        </div>

                    </div>

                </div>



                {{-- DEPARTMENT FILTER --}}

                <select
                    x-model="department"

                    @change="performSearch()"

                    class="rounded-xl

                           border-slate-200

                           bg-slate-50

                           px-4
                           py-3

                           text-sm
                           text-slate-700

                           focus:border-blue-500
                           focus:ring-blue-500"
                >

                    <option value="">
                        Semua Departemen
                    </option>


                    @foreach($departments as $department)

                        <option
                            value="{{ $department->id }}"
                        >
                            {{ $department->name }}
                        </option>

                    @endforeach

                </select>



                {{-- CATEGORY FILTER --}}

                <select
                    x-model="category"

                    @change="performSearch()"

                    class="rounded-xl

                           border-slate-200

                           bg-slate-50

                           px-4
                           py-3

                           text-sm
                           text-slate-700

                           focus:border-blue-500
                           focus:ring-blue-500"
                >

                    <option value="">
                        Semua Kategori
                    </option>


                    @foreach($categories as $category)

                        <option
                            value="{{ $category->id }}"
                        >
                            {{ $category->name }}
                        </option>

                    @endforeach

                </select>



                {{-- FILE TYPE FILTER --}}

                <select
                    x-model="extension"

                    @change="performSearch()"

                    class="rounded-xl

                           border-slate-200

                           bg-slate-50

                           px-4
                           py-3

                           text-sm
                           text-slate-700

                           focus:border-blue-500
                           focus:ring-blue-500"
                >

                    <option value="">
                        Semua Jenis File
                    </option>


                    @foreach($extensions as $extension)

                        <option
                            value="{{ $extension }}"
                        >
                            {{ strtoupper($extension) }}
                        </option>

                    @endforeach

                </select>

            </div>



            {{-- ================================================= --}}
            {{-- QUICK FIND — DOKUMEN QC --}}
            {{-- ================================================= --}}

            <div class="mt-4 rounded-xl border border-blue-100 bg-blue-50/50 p-3">
                <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-700">
                            Temukan Cepat
                        </p>
                        <p class="mt-0.5 text-[11px] text-slate-500">
                            Klik jenis atau metadata QC tanpa perlu menebak kata pencarian.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="button"
                            @click="quickType('Retooling')"
                            class="rounded-lg border border-blue-200 bg-white px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">
                            Instruksi Perubahan
                        </button>

                        <button type="button"
                            @click="quickType('Laporan Hasil Produksi - Axle Assy')"
                            class="rounded-lg border border-blue-200 bg-white px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">
                            Axle Assy
                        </button>

                        <button type="button"
                            @click="quickType('Laporan Hasil Produksi - Brake Assy')"
                            class="rounded-lg border border-blue-200 bg-white px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">
                            Brake Assy
                        </button>

                        <button type="button"
                            @click="quickSearch('Module Assy')"
                            class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                            Module Assy
                        </button>

                        <button type="button"
                            @click="quickSearch('LH')"
                            class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                            LH
                        </button>

                        <button type="button"
                            @click="quickSearch('RH')"
                            class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                            RH
                        </button>

                        <button type="button"
                            @click="quickSearch('402 / 502')"
                            class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                            402 / 502
                        </button>
                    </div>
                </div>
            </div>


            {{-- ================================================= --}}
            {{-- ADVANCED FILTER --}}
            {{-- ================================================= --}}

            <div
                class="mt-4

                       grid
                       sm:grid-cols-2
                       xl:grid-cols-5

                       gap-3"
            >


                {{-- DOCUMENT TYPE QC --}}

                <div>
                    <label
                        for="smartfind-document-type"
                        class="block mb-1.5 text-xs font-semibold text-slate-500"
                    >
                        Jenis Dokumen QC
                    </label>

                    <select
                        id="smartfind-document-type"
                        x-model="documentType"
                        @change="performSearch()"
                        class="w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="">Semua Jenis Dokumen</option>

                        @foreach($scanTypes as $scanTypeValue => $scanTypeLabel)
                            <option value="{{ $scanTypeValue }}">
                                {{ $scanTypeLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>


                {{-- DATE FROM --}}

                <div>

                    <label
                        for="smartfind-date-from"

                        class="block
                               mb-1.5

                               text-xs
                               font-semibold
                               text-slate-500"
                    >
                        Tanggal Mulai
                    </label>


                    <input
                        id="smartfind-date-from"

                        x-model="dateFrom"

                        @change="performSearch()"

                        :max="dateTo || null"

                        type="date"

                        class="w-full

                               rounded-xl

                               border-slate-200

                               bg-slate-50

                               px-4
                               py-2.5

                               text-sm

                               focus:border-blue-500
                               focus:ring-blue-500"
                    >

                </div>



                {{-- DATE TO --}}

                <div>

                    <label
                        for="smartfind-date-to"

                        class="block
                               mb-1.5

                               text-xs
                               font-semibold
                               text-slate-500"
                    >
                        Tanggal Akhir
                    </label>


                    <input
                        id="smartfind-date-to"

                        x-model="dateTo"

                        @change="performSearch()"

                        :min="dateFrom || null"

                        type="date"

                        class="w-full

                               rounded-xl

                               border-slate-200

                               bg-slate-50

                               px-4
                               py-2.5

                               text-sm

                               focus:border-blue-500
                               focus:ring-blue-500"
                    >

                </div>



                {{-- APPLY --}}

                <div class="sm:self-end">

                    <button
                        type="button"

                        @click="submitFilters()"

                        class="w-full

                               inline-flex
                               items-center
                               justify-center
                               gap-2

                               rounded-xl

                               bg-blue-800

                               px-5
                               py-2.5

                               text-sm
                               font-semibold
                               text-white

                               hover:bg-blue-900

                               transition"
                    >

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="16"
                            height="16"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M4 21v-7"/>
                            <path d="M4 10V3"/>
                            <path d="M12 21v-9"/>
                            <path d="M12 8V3"/>
                            <path d="M20 21v-5"/>
                            <path d="M20 12V3"/>
                        </svg>

                        Terapkan Filter

                    </button>

                </div>



                {{-- RESET --}}

                <div class="sm:self-end">

                    <button
                        type="button"

                        @click="resetSearch()"

                        class="w-full

                               inline-flex
                               items-center
                               justify-center
                               gap-2

                               rounded-xl

                               border
                               border-slate-200

                               bg-white

                               px-5
                               py-2.5

                               text-sm
                               font-semibold
                               text-slate-600

                               hover:bg-slate-50

                               transition"
                    >

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="15"
                            height="15"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M3 12a9 9 0 1 0 3-6.7L3 8"/>
                            <path d="M3 3v5h5"/>
                        </svg>

                        Atur Ulang Pencarian

                    </button>

                </div>

            </div>



            {{-- ================================================= --}}
            {{-- SEARCH ERROR --}}
            {{-- ================================================= --}}

            <div
                x-show="error"

                class="mt-4

                       rounded-xl

                       border
                       border-red-200

                       bg-red-50

                       px-4
                       py-3

                       text-xs
                       font-medium
                       text-red-700"

                x-cloak
            >
                <span x-text="error"></span>
            </div>



            {{-- ================================================= --}}
            {{-- SEARCH STATUS --}}
            {{-- ================================================= --}}

            <div
                class="mt-4

                       flex
                       flex-wrap
                       items-center
                       gap-2

                       text-xs
                       text-slate-400"
            >

                <span
                    class="w-2
                           h-2
                           rounded-full
                           bg-emerald-500"
                ></span>

                Pencarian metadata real-time aktif

                <span>•</span>

                <span>
                    Judul
                </span>

                <span>•</span>

                <span>
                    Nama File
                </span>

                <span>•</span>

                <span>
                    Kode Dokumen
                </span>

                <span>•</span>

                <span>
                    Nomor Part
                </span>

                <span>•</span>

                <span>
                    Jenis QC / Type / Posisi / Varian
                </span>

                <span>•</span>

                <span>
                    Kata Kunci
                </span>

                <span>•</span>

                <span>
                    Kategori
                </span>

                <span>•</span>

                <span>
                    Departemen
                </span>

            </div>

        </div>

    </section>



    {{-- ========================================================= --}}
    {{-- DOCUMENT LIBRARY --}}
    {{-- ========================================================= --}}

    <section
        class="bg-white

               rounded-2xl

               border
               border-slate-200/70

               shadow-sm

               overflow-hidden"
    >


        {{-- ===================================================== --}}
        {{-- LIBRARY HEADER --}}
        {{-- ===================================================== --}}

        <div
            class="px-6
                   py-5

                   border-b
                   border-slate-100

                   flex
                   flex-col
                   sm:flex-row

                   sm:items-center
                   sm:justify-between

                   gap-4"
        >

            <div>

                <div
                    class="flex
                           items-center
                           gap-2"
                >

                    <h2
                        class="font-bold
                               text-slate-900"
                    >
                        Pustaka Dokumen
                    </h2>


                    @if($hasActiveFilters)

                        <span
                            class="rounded-lg

                                   bg-blue-50

                                   px-2
                                   py-1

                                   text-[10px]
                                   font-bold
                                   uppercase
                                   text-blue-700"
                        >
                            Terfilter
                        </span>

                    @endif

                </div>


                <p
                    class="mt-1
                           text-sm
                           text-slate-500"
                >

                    @if($hasActiveFilters)

                        {{ number_format($documents->total()) }}
                        dokumen yang sesuai.

                    @else

                        {{ number_format($documents->total()) }}
                        dokumen saat ini terindeks.

                    @endif

                </p>

            </div>



            <div
                class="flex
                       items-center
                       gap-3"
            >

                @if($hasActiveFilters)

                    <a
                        href="{{ route('documents.index') }}"

                        class="text-xs
                               font-semibold
                               text-blue-700
                               hover:text-blue-900"
                    >
                        Hapus Filter
                    </a>

                @endif


                <div
                    class="hidden
                           sm:flex
                           items-center
                           gap-2

                           text-xs
                           font-medium
                           text-emerald-700"
                >

                    <span
                        class="w-2
                               h-2
                               rounded-full
                               bg-emerald-500"
                    ></span>

                    Penyimpanan Internal Siap

                </div>

            </div>

        </div>



        {{-- ===================================================== --}}
        {{-- DOCUMENT TABLE --}}
        {{-- ===================================================== --}}

        @if($documents->count())

            <div class="hidden lg:flex items-center justify-between gap-3 px-6 py-3 border-b border-slate-100 bg-slate-50/40">
                <p class="text-[11px] text-slate-400">
                    Tampilan tabel dioptimalkan agar judul dokumen tetap terbaca. Geser horizontal hanya jika layar lebih sempit.
                </p>
                <span class="inline-flex flex-shrink-0 items-center gap-1.5 text-[11px] font-medium text-slate-500">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14"/>
                        <path d="m15 8 4 4-4 4"/>
                        <path d="m9 16-4-4 4-4"/>
                    </svg>
                    Responsive Table
                </span>
            </div>

            {{-- MOBILE / TABLET CARD VIEW --}}
            <div class="lg:hidden divide-y divide-slate-100">
                @foreach($documents as $document)
                    @php
                        $extension = strtolower($document->file_extension ?? '');
                        $scanMetadata = $scanMetadataByDocument->get($document->id);
                        $scanType = $scanMetadata?->scan_type;

                        $qcTypeLabel = match($scanType) {
                            'Retooling' => 'Instruksi Perubahan',
                            'Laporan Hasil Produksi - Axle Assy' => 'Axle Assy',
                            'Laporan Hasil Produksi - Brake Assy' => 'Brake Assy',
                            default => null,
                        };

                        $qcDetail = match($scanType) {
                            'Retooling' => implode(' → ', array_filter([
                                $scanMetadata?->from_type,
                                $scanMetadata?->to_type,
                            ])) ?: null,
                            'Laporan Hasil Produksi - Axle Assy' => implode(' • ', array_filter([
                                $scanMetadata?->axle_assy_type,
                                $scanMetadata?->axle_side,
                            ])) ?: null,
                            'Laporan Hasil Produksi - Brake Assy' =>
                                $scanMetadata?->brake_part_number
                                ?: implode(' ', array_filter([
                                    $scanMetadata?->brake_part_prefix,
                                    $scanMetadata?->brake_part_variant,
                                ]))
                                ?: null,
                            default => null,
                        };

                        $productionDate = $scanMetadata?->production_date
                            ? \Carbon\Carbon::parse($scanMetadata->production_date)
                                ->locale('id')
                                ->translatedFormat('d M Y')
                            : null;

                        $fileIconStyle = match($extension) {
                            'pdf' => 'bg-red-50 text-red-700',
                            'xls', 'xlsx', 'csv' => 'bg-emerald-50 text-emerald-700',
                            'doc', 'docx' => 'bg-blue-50 text-blue-700',
                            'txt' => 'bg-slate-100 text-slate-600',
                            default => 'bg-indigo-50 text-indigo-700',
                        };
                    @endphp

                    <article class="p-4 sm:p-5 hover:bg-blue-50/30 transition">
                        <div class="flex items-start gap-3">
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0 {{ $fileIconStyle }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5Z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                </svg>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-start gap-2">
                                    <a
                                        href="{{ route('documents.show', $document) }}"
                                        class="min-w-0 flex-1 text-sm font-bold leading-snug text-slate-900 hover:text-blue-700 transition"
                                    >
                                        {{ $document->title }}
                                    </a>

                                    @if((bool) $document->is_favorited)
                                        <span class="mt-0.5 flex-shrink-0 text-amber-500" title="Tersimpan di Favorit">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2">
                                                <path d="m12 17.27 5.18 3.04-1.4-5.89 4.58-3.96-6.03-.52L12 4.38 9.67 9.94l-6.03.52 4.58 3.96-1.4 5.89Z"/>
                                            </svg>
                                        </span>
                                    @endif
                                </div>

                                <p class="mt-1 text-xs text-slate-400">
                                    {{ $document->document_code ?? 'Tidak ada kode dokumen' }}
                                </p>

                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                        {{ $document->category?->name ?? 'Tanpa Kategori' }}
                                    </span>
                                    <span class="text-xs text-slate-400">
                                        {{ $document->category?->department?->name ?? '-' }}
                                    </span>
                                    @if($qcTypeLabel)
                                        <span class="inline-flex rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                                            {{ $qcTypeLabel }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Detail QC</p>
                                <p class="mt-1 text-xs font-semibold text-slate-700 break-words">
                                    {{ $qcDetail ?? 'Dokumen Umum' }}
                                </p>
                                @if($productionDate)
                                    <p class="mt-1 text-[11px] text-slate-400">Produksi: {{ $productionDate }}</p>
                                @endif
                            </div>

                            <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Nomor Part</p>
                                <p class="mt-1 text-xs font-semibold text-slate-700 break-words">
                                    {{ $document->part_number ?? '-' }}
                                </p>
                            </div>

                            <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Tanggal Dokumen</p>
                                <p class="mt-1 text-xs font-semibold text-slate-700">
                                    {{ $document->document_date ? $document->document_date->locale('id')->translatedFormat('d M Y') : '-' }}
                                </p>
                            </div>

                            <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">File</p>
                                <div class="mt-1 flex items-center gap-2">
                                    <span class="inline-flex rounded-md bg-white px-2 py-0.5 text-[10px] font-bold uppercase text-slate-600 shadow-sm">
                                        {{ $document->file_extension ?: 'FILE' }}
                                    </span>
                                    @if($document->file_size)
                                        <span class="text-[11px] text-slate-400">
                                            @if($document->file_size >= 1048576)
                                                {{ number_format($document->file_size / 1048576, 2) }} MB
                                            @elseif($document->file_size >= 1024)
                                                {{ number_format($document->file_size / 1024, 1) }} KB
                                            @else
                                                {{ number_format($document->file_size) }} B
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-end gap-2">
                            @if(Route::has('favorites.store') && Route::has('favorites.destroy'))
                                <x-favorite-button
                                    :document="$document"
                                    :favorited="(bool) $document->is_favorited"
                                    :compact="true"
                                />
                            @endif

                            <a
                                href="{{ route('documents.show', $document) }}"
                                class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-blue-50 px-4 py-2.5 text-xs font-semibold text-blue-700 hover:bg-blue-100 transition"
                            >
                                Lihat
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="m9 18 6-6-6-6"/>
                                </svg>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- DESKTOP TABLE VIEW --}}
            <div
                id="document-library-table-scroll"
                class="hidden lg:block overflow-x-auto overscroll-x-contain"
            >
                <table
                    class="w-full
                           min-w-[1040px]
                           xl:min-w-0
                           table-fixed
                           text-left"
                >
                    <colgroup>
                        <col data-qh-width="27">
                        <col data-qh-width="12">
                        <col data-qh-width="18">
                        <col data-qh-width="12">
                        <col data-qh-width="10">
                        <col data-qh-width="14">
                        <col data-qh-width="7">
                    </colgroup>


                    {{-- TABLE HEAD --}}

                    <thead class="bg-slate-50/80">

                        <tr
                            class="text-[11px]
                                   uppercase
                                   tracking-wider
                                   text-slate-500"
                        >

                            <th
                                class="sticky left-0 z-20
                                       bg-slate-50
                                       px-5 py-4
                                       font-semibold
                                       shadow-[1px_0_0_0_rgba(226,232,240,0.8)]"
                            >
                                Dokumen
                            </th>


                            <th
                                class="px-6
                                       py-4
                                       font-semibold"
                            >
                                Kategori
                            </th>


                            <th
                                class="px-6
                                       py-4
                                       font-semibold"
                            >
                                Jenis / Detail QC
                            </th>


                            <th
                                class="px-6
                                       py-4
                                       font-semibold"
                            >
                                Nomor Part
                            </th>


                            <th
                                class="px-6
                                       py-4
                                       font-semibold"
                            >
                                Tanggal Dokumen
                            </th>


                            <th
                                class="px-6
                                       py-4
                                       font-semibold"
                            >
                                File
                            </th>


                            <th
                                class="sticky right-0 z-20
                                       bg-slate-50
                                       px-4 py-4
                                       font-semibold
                                       text-right
                                       shadow-[-1px_0_0_0_rgba(226,232,240,0.8)]"
                            >
                                Aksi
                            </th>

                        </tr>

                    </thead>



                    {{-- TABLE BODY --}}

                    <tbody
                        class="divide-y
                               divide-slate-100"
                    >

                        @foreach($documents as $document)

                            @php

                                $extension = strtolower(
                                    $document->file_extension
                                    ?? ''
                                );

                                $scanMetadata =
                                    $scanMetadataByDocument->get($document->id);

                                $scanType =
                                    $scanMetadata?->scan_type;

                                $qcTypeLabel = match($scanType) {
                                    'Retooling' =>
                                        'Instruksi Perubahan',

                                    'Laporan Hasil Produksi - Axle Assy' =>
                                        'Axle Assy',

                                    'Laporan Hasil Produksi - Brake Assy' =>
                                        'Brake Assy',

                                    default =>
                                        null,
                                };

                                $qcDetail = match($scanType) {
                                    'Retooling' =>
                                        implode(' → ', array_filter([
                                            $scanMetadata?->from_type,
                                            $scanMetadata?->to_type,
                                        ])) ?: null,

                                    'Laporan Hasil Produksi - Axle Assy' =>
                                        implode(' • ', array_filter([
                                            $scanMetadata?->axle_assy_type,
                                            $scanMetadata?->axle_side,
                                        ])) ?: null,

                                    'Laporan Hasil Produksi - Brake Assy' =>
                                        $scanMetadata?->brake_part_number
                                        ?: implode(' ', array_filter([
                                            $scanMetadata?->brake_part_prefix,
                                            $scanMetadata?->brake_part_variant,
                                        ]))
                                        ?: null,

                                    default =>
                                        null,
                                };

                                $productionDate =
                                    $scanMetadata?->production_date
                                        ? \Carbon\Carbon::parse(
                                            $scanMetadata->production_date
                                        )
                                            ->locale('id')
                                            ->translatedFormat('d M Y')
                                        : null;

                                $fileIconStyle = match($extension) {
                                    'pdf' =>
                                        'bg-red-50 text-red-700',

                                    'xls',
                                    'xlsx',
                                    'csv' =>
                                        'bg-emerald-50 text-emerald-700',

                                    'doc',
                                    'docx' =>
                                        'bg-blue-50 text-blue-700',

                                    'txt' =>
                                        'bg-slate-100 text-slate-600',

                                    default =>
                                        'bg-indigo-50 text-indigo-700',
                                };

                            @endphp


                            <tr
                                class="group
                                       hover:bg-blue-50/30
                                       transition"
                            >


                                {{-- ========================================= --}}
                                {{-- DOCUMENT --}}
                                {{-- ========================================= --}}

                                <td class="sticky left-0 z-10 bg-white px-5 py-4 group-hover:bg-blue-50/70 transition shadow-[1px_0_0_0_rgba(241,245,249,0.95)]">

                                    <div
                                        class="flex
                                               items-center
                                               gap-3"
                                    >

                                        <div
                                            class="w-11
                                                   h-11

                                                   rounded-xl

                                                   flex
                                                   items-center
                                                   justify-center

                                                   flex-shrink-0

                                                   {{ $fileIconStyle }}

                                                   transition"
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
                                                <path
                                                    d="M14.5 2H6
                                                       a2 2 0 0 0-2 2
                                                       v16
                                                       a2 2 0 0 0 2 2
                                                       h12
                                                       a2 2 0 0 0 2-2
                                                       V7.5Z"
                                                />

                                                <polyline
                                                    points="14 2 14 8 20 8"
                                                />
                                            </svg>

                                        </div>



                                        <div class="min-w-0">

                                            <div
                                                class="flex
                                                       items-center
                                                       gap-2"
                                            >

                                                <a
                                                    href="{{ route(
                                                        'documents.show',
                                                        $document
                                                    ) }}"

                                                    class="font-semibold
                                                           text-sm
                                                           leading-snug
                                                           text-slate-900
                                                           whitespace-normal
                                                           break-words
                                                           hover:text-blue-700
                                                           transition"
                                                >
                                                    {{ $document->title }}
                                                </a>



                                                {{-- SAVED INDICATOR --}}

                                                @if((bool) $document->is_favorited)

                                                    <span
                                                        class="inline-flex
                                                               flex-shrink-0
                                                               text-amber-500"

                                                        title="Tersimpan di Favorit"
                                                    >

                                                        <svg
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            width="14"
                                                            height="14"
                                                            viewBox="0 0 24 24"
                                                            fill="currentColor"
                                                            stroke="currentColor"
                                                            stroke-width="2"
                                                        >
                                                            <path
                                                                d="m12 17.27
                                                                   5.18 3.04
                                                                   -1.4-5.89
                                                                   4.58-3.96
                                                                   -6.03-.52
                                                                   L12 4.38
                                                                   9.67 9.94
                                                                   l-6.03.52
                                                                   4.58 3.96
                                                                   -1.4 5.89Z"
                                                            />
                                                        </svg>

                                                    </span>

                                                @endif

                                            </div>



                                            <p
                                                class="mt-1

                                                       text-xs
                                                       text-slate-400

                                                       max-w-full
                                                       truncate"
                                            >
                                                {{
                                                    $document->document_code
                                                    ?? 'Tidak ada kode dokumen'
                                                }}
                                            </p>

                                        </div>

                                    </div>

                                </td>



                                {{-- ========================================= --}}
                                {{-- CATEGORY --}}
                                {{-- ========================================= --}}

                                <td class="px-4 py-4">

                                    <span
                                        class="inline-flex

                                               rounded-lg

                                               bg-blue-50

                                               px-2.5
                                               py-1

                                               text-xs
                                               font-semibold
                                               text-blue-700"
                                    >
                                        {{
                                            $document->category?->name
                                            ?? 'Tanpa Kategori'
                                        }}
                                    </span>


                                    <p
                                        class="mt-1
                                               text-xs
                                               text-slate-400"
                                    >
                                        {{
                                            $document
                                                ->category
                                                ?->department
                                                ?->name
                                            ?? '-'
                                        }}
                                    </p>

                                </td>



                                {{-- ========================================= --}}
                                {{-- JENIS / DETAIL QC --}}
                                {{-- ========================================= --}}

                                <td class="px-4 py-4">
                                    @if($qcTypeLabel)
                                        <span
                                            class="inline-flex rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700"
                                        >
                                            {{ $qcTypeLabel }}
                                        </span>

                                        @if($qcDetail)
                                            <p
                                                class="mt-1 max-w-full whitespace-normal break-words text-xs font-medium text-slate-600"
                                                title="{{ $qcDetail }}"
                                            >
                                                {{ $qcDetail }}
                                            </p>
                                        @endif

                                        @if($productionDate)
                                            <p class="mt-1 text-[11px] text-slate-400">
                                                Produksi: {{ $productionDate }}
                                            </p>
                                        @endif
                                    @else
                                        <span class="text-xs text-slate-400">
                                            Dokumen Umum
                                        </span>
                                    @endif
                                </td>



                                {{-- ========================================= --}}
                                {{-- PART NUMBER --}}
                                {{-- ========================================= --}}

                                <td class="px-4 py-4">

                                    <span
                                        class="text-sm
                                               font-medium
                                               text-slate-700"
                                    >
                                        {{ $document->part_number ?? '-' }}
                                    </span>

                                </td>



                                {{-- ========================================= --}}
                                {{-- DATE --}}
                                {{-- ========================================= --}}

                                <td class="px-4 py-4">

                                    <p
                                        class="text-sm
                                               text-slate-600"
                                    >
                                        {{
                                            $document->document_date
                                                ? $document->document_date->locale('id')->translatedFormat('d M Y')
                                                : '-'
                                        }}
                                    </p>

                                </td>



                                {{-- ========================================= --}}
                                {{-- FILE --}}
                                {{-- ========================================= --}}

                                <td class="px-4 py-4">

                                    <div
                                        class="flex
                                               items-center
                                               gap-2"
                                    >

                                        <span
                                            class="inline-flex

                                                   rounded-lg

                                                   bg-slate-100

                                                   px-2.5
                                                   py-1

                                                   text-[11px]
                                                   font-bold
                                                   uppercase
                                                   text-slate-600"
                                        >
                                            {{ $document->file_extension ?: 'FILE' }}
                                        </span>



                                        @if($document->file_size)

                                            <span
                                                class="text-xs
                                                       text-slate-400"
                                            >

                                                @if($document->file_size >= 1073741824)

                                                    {{
                                                        number_format(
                                                            $document->file_size / 1073741824,
                                                            2
                                                        )
                                                    }} GB

                                                @elseif($document->file_size >= 1048576)

                                                    {{
                                                        number_format(
                                                            $document->file_size / 1048576,
                                                            2
                                                        )
                                                    }} MB

                                                @elseif($document->file_size >= 1024)

                                                    {{
                                                        number_format(
                                                            $document->file_size / 1024,
                                                            1
                                                        )
                                                    }} KB

                                                @else

                                                    {{
                                                        number_format(
                                                            $document->file_size
                                                        )
                                                    }} B

                                                @endif

                                            </span>

                                        @endif

                                    </div>



                                    <p
                                        class="mt-1

                                               max-w-full
                                               truncate

                                               text-xs
                                               text-slate-400"

                                        title="{{ $document->file_name }}"
                                    >
                                        {{ $document->file_name }}
                                    </p>

                                </td>



                                {{-- ========================================= --}}
                                {{-- ACTION --}}
                                {{-- ========================================= --}}

                                <td
                                    class="sticky right-0 z-10
                                           bg-white
                                           px-4 py-4
                                           text-right
                                           group-hover:bg-blue-50/70
                                           transition
                                           shadow-[-1px_0_0_0_rgba(241,245,249,0.95)]"
                                >

                                    <div
                                        class="flex
                                               items-center
                                               justify-end
                                               gap-2"
                                    >


                                        {{-- FAVORITE --}}

                                        @if(
                                            Route::has('favorites.store')
                                            && Route::has('favorites.destroy')
                                        )

                                            <x-favorite-button
                                                :document="$document"
                                                :favorited="(bool) $document->is_favorited"
                                                :compact="true"
                                            />

                                        @endif



                                        {{-- VIEW --}}

                                        <a
                                            href="{{ route(
                                                'documents.show',
                                                $document
                                            ) }}"

                                            class="inline-flex
                                                   items-center
                                                   justify-center
                                                   gap-1.5

                                                   rounded-lg

                                                   bg-blue-50

                                                   px-3
                                                   py-2

                                                   text-xs
                                                   font-semibold
                                                   text-blue-700

                                                   hover:bg-blue-100

                                                   transition"
                                        >

                                            Lihat

                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="14"
                                                height="14"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path d="m9 18 6-6-6-6"/>
                                            </svg>

                                        </a>

                                    </div>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>



            {{-- ================================================= --}}
            {{-- PAGINATION --}}
            {{-- ================================================= --}}

            @if($documents->hasPages())

                <div
                    class="px-6
                           py-4

                           border-t
                           border-slate-100"
                >
                    {{ $documents->links() }}
                </div>

            @endif



        @else


            {{-- ================================================= --}}
            {{-- EMPTY STATE --}}
            {{-- ================================================= --}}

            <div
                class="py-20
                       px-6
                       text-center"
            >

                <div
                    class="w-16
                           h-16

                           mx-auto

                           rounded-2xl

                           {{
                               $hasActiveFilters
                                   ? 'bg-slate-100 text-slate-400'
                                   : 'bg-blue-50 text-blue-700'
                           }}

                           flex
                           items-center
                           justify-center"
                >

                    @if($hasActiveFilters)

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="28"
                            height="28"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.3-4.3"/>
                        </svg>

                    @else

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="28"
                            height="28"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                d="M14.5 2H6
                                   a2 2 0 0 0-2 2
                                   v16
                                   a2 2 0 0 0 2 2
                                   h12
                                   a2 2 0 0 0 2-2
                                   V7.5Z"
                            />

                            <polyline
                                points="14 2 14 8 20 8"
                            />
                        </svg>

                    @endif

                </div>



                <h3
                    class="mt-5
                           text-xl
                           font-bold
                           text-slate-800"
                >

                    @if($hasActiveFilters)

                        Tidak ada dokumen yang sesuai

                    @else

                        Belum ada dokumen yang terindeks

                    @endif

                </h3>



                <p
                    class="mt-2

                           max-w-md
                           mx-auto

                           text-sm
                           leading-relaxed
                           text-slate-500"
                >

                    @if($hasActiveFilters)

                        QualityHub tidak menemukan dokumen aktif
                        yang sesuai dengan kata kunci atau filter yang dipilih.

                    @else

                        Mulai membangun pustaka dokumen BMC QualityHub
                        dengan mendaftarkan dokumen internal pertama.

                    @endif

                </p>



                <div
                    class="mt-6

                           flex
                           flex-wrap
                           items-center
                           justify-center
                           gap-3"
                >

                    @if($hasActiveFilters)

                        <a
                            href="{{ route('documents.index') }}"

                            class="inline-flex
                                   items-center
                                   justify-center
                                   gap-2

                                   rounded-xl

                                   border
                                   border-slate-200

                                   bg-white

                                   px-5
                                   py-3

                                   text-sm
                                   font-semibold
                                   text-slate-600

                                   hover:bg-slate-50

                                   transition"
                        >

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M3 12a9 9 0 1 0 3-6.7L3 8"/>
                                <path d="M3 3v5h5"/>
                            </svg>

                            Hapus Filter

                        </a>


                    @elseif(Auth::user()->isAdmin())

                        <a
                            href="{{ route('documents.create') }}"

                            class="inline-flex
                                   items-center
                                   gap-2

                                   rounded-xl

                                   bg-blue-800

                                   px-5
                                   py-3

                                   text-sm
                                   font-semibold
                                   text-white

                                   hover:bg-blue-900
                                   hover:-translate-y-0.5

                                   transition"
                        >

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="17"
                                height="17"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M12 5v14"/>
                                <path d="M5 12h14"/>
                            </svg>

                            Tambah Dokumen Pertama

                        </a>

                    @endif

                </div>

            </div>

        @endif

    </section>



    {{-- ========================================================= --}}
    {{-- SMART SEARCH JAVASCRIPT --}}
    {{-- ========================================================= --}}

    <script nonce="{{ $cspNonce }}">
        function aiSmartSearch() {

            const endpoint = @js(route('documents.search.ai'));
            const normalSearchUrl = @js(route('documents.index'));

            const text = (value, fallback = '') => {
                if (!['string', 'number'].includes(typeof value)) {
                    return fallback;
                }

                return String(value).trim() || fallback;
            };

            const localUrl = (value) => {
                if (typeof value !== 'string' || value.trim() === '') {
                    return '';
                }

                try {
                    const parsed = new URL(value, window.location.origin);

                    return parsed.origin === window.location.origin
                        ? parsed.pathname + parsed.search + parsed.hash
                        : '';
                } catch (error) {
                    return '';
                }
            };

            const keywordFallbackUrl = (query) => {
                try {
                    const parsed = new URL(normalSearchUrl, window.location.origin);
                    parsed.searchParams.set('search', query.slice(0, 160));

                    return localUrl(parsed.toString());
                } catch (error) {
                    return '#document-search';
                }
            };

            const normalizeResult = (result) => {
                if (result === null || typeof result !== 'object') {
                    return null;
                }

                const id = Number(result.id ?? result.document_id);
                const url = localUrl(result.url);

                if (!Number.isInteger(id) || id < 1 || url === '') {
                    return null;
                }

                const matchedBy = Array.isArray(result.matched_by)
                    ? result.matched_by.map((item) => text(item)).filter(Boolean)
                    : [];

                const explanation = text(
                    result.explanation ?? result.match_explanation
                ) || (
                    matchedBy.length > 0
                        ? 'Cocok dengan: ' + matchedBy.join(' • ')
                        : ''
                );

                const metadata = [
                    text(result.qc_type_label ?? result.scan_type),
                    text(result.qc_detail),
                    text(result.category),
                    text(result.department),
                    text(result.date),
                ].filter(Boolean).join(' • ');

                return {
                    id,
                    url,
                    title: text(
                        result.title,
                        text(result.file_name, 'Dokumen QualityHub')
                    ),
                    extension: text(result.extension).toUpperCase(),
                    explanation,
                    metadata,
                };
            };

            return {
                query: '',
                results: [],
                loading: false,
                searched: false,
                fallback: false,
                fallbackUrl: '',
                message: '',
                error: '',
                statusMessage: '',
                requestController: null,
                requestId: 0,

                async performAiSearch() {
                    const cleanQuery = this.query.trim();

                    if (cleanQuery === '') {
                        this.searched = false;
                        this.error = 'Masukkan permintaan pencarian terlebih dahulu.';
                        this.statusMessage = this.error;
                        return;
                    }

                    if (this.requestController) {
                        this.requestController.abort();
                    }

                    const controller = new AbortController();
                    const currentRequestId = ++this.requestId;

                    this.requestController = controller;
                    this.loading = true;
                    this.searched = true;
                    this.fallback = false;
                    this.fallbackUrl = '';
                    this.results = [];
                    this.message = '';
                    this.error = '';
                    this.statusMessage = 'AI lokal sedang mencari dokumen.';

                    try {
                        const csrfToken = document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content');

                        if (!csrfToken) {
                            throw new Error(
                                'Sesi pencarian tidak valid. Muat ulang halaman lalu coba kembali.'
                            );
                        }

                        const response = await fetch(endpoint, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ query: cleanQuery }),
                            signal: controller.signal,
                        });

                        let data = null;

                        try {
                            data = await response.json();
                        } catch (parseError) {
                            throw new Error(
                                'Respons AI Smart Search lokal tidak valid.'
                            );
                        }

                        if (currentRequestId !== this.requestId) {
                            return;
                        }

                        if (data === null || typeof data !== 'object') {
                            throw new Error(
                                'Respons AI Smart Search lokal tidak valid.'
                            );
                        }

                        this.fallbackUrl = cleanQuery.length <= 160
                            ? localUrl(data.fallback_url)
                            : '';
                        this.fallbackUrl = this.fallbackUrl
                            || keywordFallbackUrl(cleanQuery);

                        if (!response.ok) {
                            this.error = text(
                                data.message,
                                'AI Smart Search lokal sedang tidak tersedia.'
                            );
                            this.statusMessage = this.error;
                            return;
                        }

                        if (!Array.isArray(data.results)) {
                            throw new Error(
                                'Respons AI Smart Search lokal tidak valid.'
                            );
                        }

                        this.fallback = data.fallback === true
                            || data.available === false
                            || text(data.mode) === 'keyword_fallback';
                        this.results = data.results
                            .map(normalizeResult)
                            .filter(Boolean);
                        this.message = text(
                            data.message,
                            this.fallback
                                ? 'Hasil berikut berasal dari pencarian metadata biasa.'
                                : 'Dokumen diurutkan berdasarkan metadata yang paling relevan.'
                        );
                        this.statusMessage = this.fallback
                            ? 'AI lokal tidak tersedia. '
                                + this.results.length
                                + ' hasil pencarian biasa ditampilkan.'
                            : this.results.length
                                + ' hasil AI Smart Search ditemukan.';
                    } catch (error) {
                        if (error instanceof Error && error.name === 'AbortError') {
                            return;
                        }

                        if (currentRequestId !== this.requestId) {
                            return;
                        }

                        const safeMessage = error instanceof Error
                            && (
                                error.message.startsWith('Respons AI')
                                || error.message.startsWith('Sesi pencarian')
                            )
                                ? error.message
                                : 'AI Smart Search lokal sedang tidak tersedia.';

                        this.fallback = false;
                        this.results = [];
                        this.fallbackUrl = keywordFallbackUrl(cleanQuery);
                        this.error = safeMessage;
                        this.statusMessage = safeMessage;
                    } finally {
                        if (currentRequestId === this.requestId) {
                            this.loading = false;
                            this.requestController = null;
                        }
                    }
                },
            };
        }

        function documentSearch() {

            return {

                /*
                |--------------------------------------------------------------------------
                | SEARCH STATE
                |--------------------------------------------------------------------------
                */

                search:
                    @js(request('search', '')),

                category:
                    @js((string) request('category', '')),

                department:
                    @js((string) request('department', '')),

                extension:
                    @js(request('extension', '')),

                documentType:
                    @js(request('document_type', '')),

                dateFrom:
                    @js(request('date_from', '')),

                dateTo:
                    @js(request('date_to', '')),


                /*
                |--------------------------------------------------------------------------
                | RESULT STATE
                |--------------------------------------------------------------------------
                */

                results: [],

                count: 0,

                shown: 0,

                limited: false,

                loading: false,

                open: false,

                error: '',


                /*
                |--------------------------------------------------------------------------
                | REQUEST MANAGEMENT
                |--------------------------------------------------------------------------
                */

                requestController: null,

                requestId: 0,


                /*
                |--------------------------------------------------------------------------
                | CHECK WHETHER SEARCH / FILTER EXISTS
                |--------------------------------------------------------------------------
                */

                hasLiveCriteria() {

                    return Boolean(
                        this.search.trim()
                        || this.category
                        || this.department
                        || this.extension
                        || this.documentType
                        || this.dateFrom
                        || this.dateTo
                    );

                },


                /*
                |--------------------------------------------------------------------------
                | VALIDATE DATE RANGE
                |--------------------------------------------------------------------------
                */

                validateDates() {

                    if (
                        this.dateFrom
                        && this.dateTo
                        && this.dateTo < this.dateFrom
                    ) {

                        this.error =
                            'Tanggal akhir tidak boleh lebih awal dari tanggal mulai.';

                        return false;

                    }


                    this.error = '';

                    return true;

                },


                /*
                |--------------------------------------------------------------------------
                | LIVE SEARCH
                |--------------------------------------------------------------------------
                */

                async performSearch() {

                    /*
                    |--------------------------------------------------------------------------
                    | No Search Criteria
                    |--------------------------------------------------------------------------
                    */

                    if (!this.hasLiveCriteria()) {

                        if (this.requestController) {
                            this.requestController.abort();
                        }


                        this.requestId++;

                        this.results = [];

                        this.count = 0;

                        this.shown = 0;

                        this.limited = false;

                        this.loading = false;

                        this.open = false;

                        this.error = '';

                        return;

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Client-Side Date Validation
                    |--------------------------------------------------------------------------
                    */

                    if (!this.validateDates()) {

                        this.results = [];

                        this.count = 0;

                        this.shown = 0;

                        this.limited = false;

                        this.open = true;

                        return;

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Cancel Previous Request
                    |--------------------------------------------------------------------------
                    */

                    if (this.requestController) {

                        this.requestController.abort();

                    }


                    const controller =
                        new AbortController();


                    this.requestController =
                        controller;


                    const currentRequestId =
                        ++this.requestId;


                    this.loading = true;

                    this.open = true;

                    this.error = '';


                    try {

                        /*
                        |--------------------------------------------------------------------------
                        | URL Parameters
                        |--------------------------------------------------------------------------
                        */

                        const params =
                            new URLSearchParams();


                        const cleanSearch =
                            this.search.trim();


                        if (cleanSearch) {

                            params.set(
                                'search',
                                cleanSearch
                            );

                        }


                        if (this.category) {

                            params.set(
                                'category',
                                this.category
                            );

                        }


                        if (this.department) {

                            params.set(
                                'department',
                                this.department
                            );

                        }


                        if (this.extension) {

                            params.set(
                                'extension',
                                this.extension
                            );

                        }


                        if (this.documentType) {

                            params.set(
                                'document_type',
                                this.documentType
                            );

                        }


                        if (this.dateFrom) {

                            params.set(
                                'date_from',
                                this.dateFrom
                            );

                        }


                        if (this.dateTo) {

                            params.set(
                                'date_to',
                                this.dateTo
                            );

                        }



                        /*
                        |--------------------------------------------------------------------------
                        | Fetch
                        |--------------------------------------------------------------------------
                        */

                        const response =
                            await fetch(
                                "{{ route('documents.search.live') }}"
                                + '?'
                                + params.toString(),
                                {
                                    headers: {
                                        'Accept':
                                            'application/json',

                                        'X-Requested-With':
                                            'XMLHttpRequest',
                                    },

                                    signal:
                                        controller.signal,
                                }
                            );



                        /*
                        |--------------------------------------------------------------------------
                        | HTTP Error
                        |--------------------------------------------------------------------------
                        */

                        if (!response.ok) {

                            let message =
                                'Permintaan pencarian QualityHub gagal.';


                            try {

                                const errorData =
                                    await response.json();


                                if (errorData.message) {

                                    message =
                                        errorData.message;

                                }

                            } catch (parseError) {

                                // Gunakan pesan default.

                            }


                            throw new Error(
                                message
                            );

                        }



                        /*
                        |--------------------------------------------------------------------------
                        | JSON
                        |--------------------------------------------------------------------------
                        */

                        const data =
                            await response.json();



                        /*
                        |--------------------------------------------------------------------------
                        | Ignore Stale Response
                        |--------------------------------------------------------------------------
                        */

                        if (
                            currentRequestId
                            !== this.requestId
                        ) {

                            return;

                        }



                        /*
                        |--------------------------------------------------------------------------
                        | Update Results
                        |--------------------------------------------------------------------------
                        */

                        this.results =
                            data.results ?? [];


                        this.count =
                            Number(
                                data.count ?? 0
                            );


                        this.shown =
                            Number(
                                data.shown
                                ?? this.results.length
                            );


                        this.limited =
                            Boolean(
                                data.limited ?? false
                            );


                    } catch (error) {

                        /*
                        |--------------------------------------------------------------------------
                        | Ignore Aborted Request
                        |--------------------------------------------------------------------------
                        */

                        if (
                            error.name
                            === 'AbortError'
                        ) {

                            return;

                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Ignore Old Request Errors
                        |--------------------------------------------------------------------------
                        */

                        if (
                            currentRequestId
                            !== this.requestId
                        ) {

                            return;

                        }


                        console.error(
                            'Kesalahan Pencarian BMC QualityHub:',
                            error
                        );


                        this.results = [];

                        this.count = 0;

                        this.shown = 0;

                        this.limited = false;

                        this.error =
                            error.message
                            || 'Dokumen tidak dapat dicari.';


                    } finally {

                        /*
                        |--------------------------------------------------------------------------
                        | Only Current Request Can Stop Loader
                        |--------------------------------------------------------------------------
                        */

                        if (
                            currentRequestId
                            === this.requestId
                        ) {

                            this.loading = false;

                        }

                    }

                },


                /*
                |--------------------------------------------------------------------------
                | QUICK FIND HELPERS
                |--------------------------------------------------------------------------
                */

                quickType(type) {

                    this.search = '';
                    this.documentType = type;
                    this.performSearch();

                },


                quickSearch(term) {

                    this.documentType = '';
                    this.search = term;
                    this.performSearch();

                },


                /*
                |--------------------------------------------------------------------------
                | APPLY FILTERS TO DOCUMENT LIBRARY
                |--------------------------------------------------------------------------
                */

                submitFilters() {

                    if (!this.validateDates()) {

                        this.open = true;

                        return;

                    }


                    const params =
                        new URLSearchParams();


                    const cleanSearch =
                        this.search.trim();


                    if (cleanSearch) {

                        params.set(
                            'search',
                            cleanSearch
                        );

                    }


                    if (this.category) {

                        params.set(
                            'category',
                            this.category
                        );

                    }


                    if (this.department) {

                        params.set(
                            'department',
                            this.department
                        );

                    }


                    if (this.extension) {

                        params.set(
                            'extension',
                            this.extension
                        );

                    }


                    if (this.documentType) {

                        params.set(
                            'document_type',
                            this.documentType
                        );

                    }


                    if (this.dateFrom) {

                        params.set(
                            'date_from',
                            this.dateFrom
                        );

                    }


                    if (this.dateTo) {

                        params.set(
                            'date_to',
                            this.dateTo
                        );

                    }


                    const query =
                        params.toString();


                    window.location.href =
                        "{{ route('documents.index') }}"
                        + (
                            query
                                ? '?' + query
                                : ''
                        );

                },


                /*
                |--------------------------------------------------------------------------
                | RESET SEARCH
                |--------------------------------------------------------------------------
                */

                resetSearch() {

                    if (this.requestController) {

                        this.requestController.abort();

                    }


                    this.requestId++;


                    this.search = '';

                    this.category = '';

                    this.department = '';

                    this.extension = '';

                    this.documentType = '';

                    this.dateFrom = '';

                    this.dateTo = '';


                    this.results = [];

                    this.count = 0;

                    this.shown = 0;

                    this.limited = false;

                    this.loading = false;

                    this.open = false;

                    this.error = '';


                    window.location.href =
                        "{{ route('documents.index') }}";

                },

            };

        }
    
        /*
        |--------------------------------------------------------------------------
        | CSP-SAFE ALPINE REGISTRATION
        |--------------------------------------------------------------------------
        |
        | @alpinejs/csp tidak membaca arbitrary global function dari x-data.
        | Register provider secara resmi sebelum Alpine.start().
        |
        */
        document.addEventListener('alpine:init', () => {
            Alpine.data('aiSmartSearch', aiSmartSearch);
            Alpine.data('documentSearch', documentSearch);
        });
</script>

</x-app-layout>e
