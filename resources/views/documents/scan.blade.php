<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

        {{-- PAGE HEADER --}}
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-blue-600">Document Management</p>

                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">
                    Scan / Capture Berkas QC
                </h1>

                <p class="mt-1 max-w-2xl text-sm text-slate-500">
                    Digitalisasi berkas QC langsung ke BMC QualityHub.
                </p>
            </div>
        </div>

        {{-- SUCCESS MESSAGE --}}
        @if (session('success'))
            <div
                x-data="{ show: true }"
                x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0 -translate-y-1"
                class="mb-6 flex items-start justify-between gap-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 shadow-sm"
            >
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 6 9 17l-5-5"/>
                        </svg>
                    </div>

                    <div>
                        <p class="text-sm font-bold text-emerald-900">
                            Dokumen berhasil disimpan
                        </p>
                        <p class="mt-1 text-sm text-emerald-700">
                            {{ session('success') }}
                        </p>

                        @if (session('saved_document_id'))
                            <div class="mt-2 flex flex-wrap items-center gap-3">
                                @if (session('saved_document_title'))
                                    <span class="text-xs font-medium text-emerald-800">
                                        {{ session('saved_document_title') }}
                                    </span>
                                @endif

                                <a
                                    href="{{ route('documents.show', session('saved_document_id')) }}"
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-800 underline decoration-emerald-300 underline-offset-4 hover:text-emerald-950"
                                >
                                    Lihat dokumen
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M5 12h14"/>
                                        <path d="m13 6 6 6-6 6"/>
                                    </svg>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <button
                    type="button"
                    @click="show = false"
                    class="rounded-lg p-1 text-emerald-600 transition hover:bg-emerald-100 hover:text-emerald-900"
                    aria-label="Tutup notifikasi"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"/>
                        <path d="m6 6 12 12"/>
                    </svg>
                </button>
            </div>
        @endif

        {{-- VALIDATION ERROR --}}
        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- MAIN FORM --}}
        <form
            id="scanDocumentForm"
            method="POST"
            action="{{ route('documents.scan.store') }}"
            enctype="multipart/form-data"
            class="grid gap-6 lg:grid-cols-[1.05fr_.95fr]"
        >
            @csrf
            <input id="captureSource" type="hidden" name="capture_source" value="{{ old('capture_source', 'upload') }}">

            {{-- File final yang benar-benar dikirim ke backend. JS menjaga urutan files[]. --}}
            <input
                id="scanFilesInput"
                type="file"
                name="files[]"
                accept="image/*"
                multiple
                class="hidden"
            >

            {{-- ========================================================= --}}
            {{-- LEFT : CAPTURE --}}
            {{-- ========================================================= --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-sm font-semibold text-slate-900">
                        Capture Berkas
                    </h2>

                    <p class="mt-1 text-xs text-slate-500">
                        Gunakan kamera perangkat atau pilih foto dari galeri.
                    </p>
                </div>

                <div class="p-5">

                    {{-- PREVIEW --}}
                    <div
                        id="previewBox"
                        class="relative flex min-h-[360px] items-center justify-center overflow-hidden rounded-xl border border-dashed border-slate-300 bg-slate-50"
                    >

                        {{-- EMPTY STATE --}}
                        <div id="emptyState" class="px-6 text-center">

                            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">

                                <svg
                                    class="h-7 w-7"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M6.75 7.5h1.5l1.1-1.65a1.5 1.5 0 0 1 1.248-.666h2.804a1.5 1.5 0 0 1 1.248.666l1.1 1.65h1.5A2.25 2.25 0 0 1 20.5 9.75v7.5a2.25 2.25 0 0 1-2.25 2.25h-12A2.25 2.25 0 0 1 4 17.25v-7.5A2.25 2.25 0 0 1 6.25 7.5Z"
                                    />

                                    <circle
                                        cx="12.25"
                                        cy="13.5"
                                        r="3.25"
                                    />
                                </svg>

                            </div>

                            <p class="text-sm font-semibold text-slate-700">
                                Belum ada berkas
                            </p>

                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                Ambil foto dokumen QC atau pilih gambar yang sudah ada.
                            </p>

                        </div>

                        {{-- IMAGE PREVIEW --}}
                        <img
                            id="imagePreview"
                            class="hidden max-h-[520px] w-full object-contain"
                            alt="Preview hasil scan"
                        >

                    </div>


                    {{-- ================================================= --}}
                    {{-- EDITING TOOLBAR --}}
                    {{-- ================================================= --}}
                    <div id="editToolbar" class="mt-4 hidden rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Edit Hasil Scan</p>
                                <p class="mt-1 text-xs text-slate-500">Rapikan foto sebelum disimpan ke QualityHub.</p>
                            </div>
                            <button type="button" id="retakeButton" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7.5V5.25A1.25 1.25 0 0 1 5.25 4h2.25M20 16.5v2.25A1.25 1.25 0 0 1 18.75 20H16.5M5.2 15.5A7 7 0 0 0 18.6 17M18.8 8.5A7 7 0 0 0 5.4 7"/><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 4v3.5H4M16.5 20v-3.5H20"/></svg>
                                Ambil Ulang
                            </button>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                            <button type="button" data-edit-action="rotate-left" class="edit-action">↶ <span>Putar Kiri</span></button>
                            <button type="button" data-edit-action="rotate-right" class="edit-action">↷ <span>Putar Kanan</span></button>
                            <button type="button" data-edit-action="grayscale" class="edit-action">◐ <span>Grayscale</span></button>
                            <button type="button" data-edit-action="reset" class="edit-action">↺ <span>Reset</span></button>
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <label class="block">
                                <div class="mb-1 flex justify-between text-xs font-semibold text-slate-600"><span>Kecerahan</span><span id="brightnessValue">100%</span></div>
                                <input id="brightnessRange" type="range" min="60" max="150" value="100" class="w-full accent-blue-600">
                            </label>
                            <label class="block">
                                <div class="mb-1 flex justify-between text-xs font-semibold text-slate-600"><span>Kontras</span><span id="contrastValue">100%</span></div>
                                <input id="contrastRange" type="range" min="60" max="160" value="100" class="w-full accent-blue-600">
                            </label>
                            <label class="block">
                                <div class="mb-1 flex justify-between text-xs font-semibold text-slate-600"><span>Zoom</span><span id="zoomValue">100%</span></div>
                                <input id="zoomRange" type="range" min="100" max="160" value="100" class="w-full accent-blue-600">
                            </label>
                            <label class="block">
                                <div class="mb-1 flex justify-between text-xs font-semibold text-slate-600"><span>Crop / Potong</span><span id="cropValue">0%</span></div>
                                <input id="cropRange" type="range" min="0" max="25" value="0" class="w-full accent-blue-600">
                            </label>
                        </div>

                        <button type="button" id="applyEditButton" class="mt-4 w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50">
                            Terapkan Perubahan ke Berkas
                        </button>
                    </div>

                    {{-- ================================================= --}}
                    {{-- MULTI PHOTO QUEUE --}}
                    {{-- ================================================= --}}
                    <div id="photoQueueWrapper" class="mt-4 hidden rounded-2xl border border-blue-100 bg-blue-50/60 p-4">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Foto dalam Berkas</p>
                                <p id="photoQueueHint" class="mt-1 text-xs text-slate-500">Urutan foto menjadi urutan halaman PDF.</p>
                            </div>

                            <span id="photoQueueCount" class="inline-flex w-fit items-center rounded-full bg-blue-100 px-2.5 py-1 text-xs font-bold text-blue-700">
                                0 foto
                            </span>
                        </div>

                        <div id="photoQueue" class="mt-3 grid gap-2 sm:grid-cols-2"></div>
                    </div>


                    {{-- ================================================= --}}
                    {{-- CAMERA / GALLERY BUTTON --}}
                    {{-- ================================================= --}}
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">

                        {{-- AMBIL FOTO --}}
                        <button
                            type="button"
                            id="openCameraButton"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold !text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        >

                            <svg
                                class="h-5 w-5 text-white"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6.75 7.5h1.5l1.1-1.65a1.5 1.5 0 0 1 1.248-.666h2.804a1.5 1.5 0 0 1 1.248.666l1.1 1.65h1.5A2.25 2.25 0 0 1 20.5 9.75v7.5a2.25 2.25 0 0 1-2.25 2.25h-12A2.25 2.25 0 0 1 4 17.25v-7.5A2.25 2.25 0 0 1 6.25 7.5Z"
                                />

                                <circle
                                    cx="12.25"
                                    cy="13.5"
                                    r="3.25"
                                />
                            </svg>

                            <span class="text-white">
                                Ambil Foto
                            </span>

                        </button>


                        {{-- PILIH FOTO --}}
                        <label
                            for="uploadInput"
                            class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700"
                        >

                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3.75 7.5A2.25 2.25 0 0 1 6 5.25h3l1.5 1.5h7.5a2.25 2.25 0 0 1 2.25 2.25v8.25A2.25 2.25 0 0 1 18 19.5H6a2.25 2.25 0 0 1-2.25-2.25V7.5Z"
                                />
                            </svg>

                            Pilih Foto

                            <input
                                id="uploadInput"
                                type="file"
                                accept="image/*"
                                multiple
                                class="hidden"
                            >

                        </label>

                    </div>


                    <p class="mt-3 text-center text-xs text-slate-400">
                        Format: JPG, JPEG, PNG, WEBP, GIF, BMP, TIF, TIFF · Maks. 20 MB/foto · Maks. 10 foto
                    </p>

                    <p class="mt-1 text-center text-[11px] font-medium text-blue-600">
                        Jika memilih 2 foto atau lebih, server otomatis menggabungkannya menjadi 1 PDF multi-halaman.
                    </p>

                </div>
            </section>


            {{-- ========================================================= --}}
            {{-- RIGHT : DOCUMENT INFORMATION --}}
            {{-- ========================================================= --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-5 py-4">

                    <h2 class="text-sm font-semibold text-slate-900">
                        Informasi Berkas
                    </h2>

                    <p class="mt-1 text-xs text-slate-500">
                        Lengkapi metadata agar berkas mudah dicari kembali.
                    </p>

                </div>


                <div class="space-y-4 p-5">

                    {{-- JUDUL --}}
                    <div>

                        <label
                            for="title"
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Judul Dokumen
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            id="title"
                            name="title"
                            value="{{ old('title') }}"
                            required
                            class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Contoh: INSTRUKSI PERUBAHAN TYPE (RETOOLING)"
                        >

                    </div>


                    {{-- NAMA FILE --}}
                    <div>

                        <label
                            for="file_name"
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Nama File
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            id="file_name"
                            name="file_name"
                            value="{{ old('file_name') }}"
                            required
                            class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Contoh: instruksi-perubahan-type"
                        >

                        <p class="mt-1 text-xs text-slate-400">
                            Ekstensi gambar akan ditambahkan otomatis.
                        </p>

                    </div>


                    {{-- CATEGORY --}}
                    <div>

                        <label
                            for="category_id"
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Kategori
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            id="category_id"
                            name="category_id"
                            required
                            class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                            <option value="">
                                Pilih kategori
                            </option>

                            @foreach ($categories as $category)

                                <option
                                    value="{{ $category->id }}"
                                    data-category-name="{{ strtolower($category->name) }}"
                                    @selected(old('category_id') == $category->id)
                                >
                                    {{ $category->name }}
                                    {{ $category->department ? ' · '.$category->department->name : '' }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- ===================================================== --}}
                    {{-- JENIS DOKUMEN + TANGGAL --}}
                    {{-- ===================================================== --}}
                    <div class="grid gap-4 sm:grid-cols-2">

                        <div>
                            <label
                                for="scan_type"
                                class="mb-1.5 block text-sm font-medium text-slate-700"
                            >
                                Jenis Dokumen Scan
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                id="scan_type"
                                name="scan_type"
                                required
                                class="block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="Retooling" @selected(old('scan_type', 'Retooling') === 'Retooling')>
                                    Instruksi Perubahan Type / Retooling
                                </option>
                                <option value="Laporan Hasil Produksi - Axle Assy" @selected(old('scan_type') === 'Laporan Hasil Produksi - Axle Assy')>
                                    Laporan Hasil Produksi - Axle Assy
                                </option>
                                <option value="Laporan Hasil Produksi - Brake Assy" @selected(old('scan_type') === 'Laporan Hasil Produksi - Brake Assy')>
                                    Laporan Hasil Produksi - Brake Assy
                                </option>
                                <option value="Lainnya" @selected(old('scan_type') === 'Lainnya')>
                                    Dokumen QC Lainnya
                                </option>
                            </select>

                            <p class="mt-1 text-xs text-slate-400">
                                Pilih format dokumen agar metadata yang tampil sesuai berkas QC.
                            </p>
                        </div>

                        <div>
                            <label
                                for="scan_date"
                                id="scanDateLabel"
                                class="mb-1.5 block text-sm font-medium text-slate-700"
                            >
                                Tanggal
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="scan_date"
                                type="date"
                                name="scan_date"
                                value="{{ old('scan_date', now()->format('Y-m-d')) }}"
                                required
                                class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                    </div>


                    {{-- CUSTOM / OTHER SCAN TYPE --}}
                    <div id="customScanTypeWrapper" class="hidden">
                        <label
                            for="scan_type_other"
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Nama Jenis Dokumen QC
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            id="scan_type_other"
                            name="scan_type_other"
                            value="{{ old('scan_type_other') }}"
                            maxlength="50"
                            class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Contoh: Checksheet Final Inspection"
                        >
                    </div>


                    {{-- ===================================================== --}}
                    {{-- AXLE ASSY PRODUCTION CHECKSHEET --}}
                    {{-- ===================================================== --}}
                    <div
                        id="axleAssyFields"
                        class="hidden overflow-visible rounded-2xl border border-blue-200 bg-gradient-to-br from-blue-50 to-white"
                    >
                        <div class="border-b border-blue-100 px-4 py-3">
                            <div class="flex items-start gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-sm">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16M7 8h10M7 16h10"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">
                                        Laporan Hasil Produksi - Axle Assy
                                    </p>
                                    <p class="mt-0.5 text-xs leading-5 text-slate-500">
                                        Pilih Type dan posisi sesuai tanda pada checksheet fisik.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 p-4">
                            <div>
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Type Axle
                                </p>

                                <div class="grid gap-2 sm:grid-cols-2">
                                    @forelse ($axleTypes as $axleType)
                                        <label data-radio-card class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm font-medium text-slate-700 shadow-sm transition hover:border-blue-300 hover:bg-blue-50/60">
                                            <input
                                                type="radio"
                                                name="axle_type_id"
                                                value="{{ $axleType->id }}"
                                                data-master-name="{{ $axleType->name }}"
                                                @checked((string) old('axle_type_id') === (string) $axleType->id)
                                                class="peer sr-only"
                                            >

                                            <span
                                                data-radio-indicator
                                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 border-slate-300 bg-white text-white transition peer-focus-visible:ring-2 peer-focus-visible:ring-blue-300 peer-focus-visible:ring-offset-2 peer-checked:border-blue-600 peer-checked:bg-blue-600"
                                                aria-hidden="true"
                                            >
                                                <svg class="h-3 w-3 opacity-0 transition peer-checked:opacity-100" viewBox="0 0 20 20" fill="none">
                                                    <path d="M4.5 10.5 8 14l7.5-8" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </span>

                                            <span>{{ $axleType->name }}</span>
                                        </label>
                                    @empty
                                        <p class="text-sm text-slate-500">Master Type Axle aktif belum tersedia.</p>
                                    @endforelse
                                </div>

                                @error('axle_type_id')
                                    <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Posisi
                                </p>

                                <div class="grid grid-cols-2 gap-2">
                                    @forelse ($axlePositions as $axlePosition)
                                        <label data-radio-card class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-300 hover:bg-blue-50/60">
                                            <input
                                                type="radio"
                                                name="axle_position_id"
                                                value="{{ $axlePosition->id }}"
                                                data-master-name="{{ $axlePosition->name }}"
                                                @checked((string) old('axle_position_id') === (string) $axlePosition->id)
                                                class="peer sr-only"
                                            >

                                            <span
                                                data-radio-indicator
                                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 border-slate-300 bg-white text-white transition peer-focus-visible:ring-2 peer-focus-visible:ring-blue-300 peer-focus-visible:ring-offset-2 peer-checked:border-blue-600 peer-checked:bg-blue-600"
                                                aria-hidden="true"
                                            >
                                                <svg class="h-3 w-3 opacity-0 transition peer-checked:opacity-100" viewBox="0 0 20 20" fill="none">
                                                    <path d="M4.5 10.5 8 14l7.5-8" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </span>

                                            <span>{{ $axlePosition->name }}</span>
                                        </label>
                                    @empty
                                        <p class="text-sm text-slate-500">Master Posisi Axle aktif belum tersedia.</p>
                                    @endforelse
                                </div>

                                @error('axle_position_id')
                                    <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>


                    {{-- ===================================================== --}}
                    {{-- BRAKE ASSY PRODUCTION CHECKSHEET --}}
                    {{-- ===================================================== --}}
                    <div
                        id="brakeAssyFields"
                        class="hidden overflow-visible rounded-2xl border border-blue-200 bg-gradient-to-br from-blue-50 to-white"
                    >
                        <div class="border-b border-blue-100 px-4 py-3">
                            <div class="flex items-start gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-sm">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M5 12h14M7 17h10"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">
                                        Laporan Hasil Produksi - Brake Assy
                                    </p>
                                    <p class="mt-0.5 text-xs leading-5 text-slate-500">
                                        Part No dipilih dari master produksi Brake Assy.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 p-4">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Prefix Part No
                                </label>

                                <div
                                    id="brakePartPrefix"
                                    class="flex items-center rounded-xl border border-slate-200 bg-slate-100 px-3 py-3 text-sm font-bold tracking-wide text-slate-700"
                                >
                                    -
                                </div>
                            </div>

                            <div
                                id="brakeMasterField"
                                data-brake-master="1"
                                data-brake-parts-url="{{ route('documents.scan.master.brake-part-numbers') }}"
                            >
                                <label
                                    for="brakePartSearch"
                                    class="mb-1.5 block text-sm font-medium text-slate-700"
                                >
                                    Variant Part No
                                    <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">
                                    <input
                                        id="brakePartSearch"
                                        type="search"
                                        autocomplete="off"
                                        placeholder="Memuat Variant Part No..."
                                        disabled
                                        class="block w-full rounded-xl border-slate-300 bg-white pr-10 text-sm shadow-sm disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 focus:border-blue-500 focus:ring-blue-500"
                                        role="combobox"
                                        aria-autocomplete="list"
                                        aria-expanded="false"
                                        aria-controls="brakePartOptions"
                                        aria-required="true"
                                    >

                                    <span aria-hidden="true" class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">⌄</span>

                                    <input
                                        type="hidden"
                                        id="brake_part_number_id"
                                        name="brake_part_number_id"
                                        value="{{ old('brake_part_number_id') }}"
                                    >

                                    <div
                                        id="brakePartOptions"
                                        class="absolute left-0 right-0 top-full z-50 mt-1 hidden max-h-60 overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl"
                                        role="listbox"
                                    ></div>
                                </div>

                                @error('brake_part_number_id')
                                    <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div
                                id="brakePartPreview"
                                class="hidden rounded-xl border border-blue-100 bg-white px-3 py-2.5 text-xs text-blue-800"
                            ></div>
                        </div>
                    </div>


                    {{-- ===================================================== --}}
                    {{-- BMC RETOOLING MASTER FIELDS V2                       --}}
                    {{-- ===================================================== --}}

                    {{-- SHIFT: hanya 3 nilai valid --}}
                    <div class="grid gap-4 sm:grid-cols-2">

                        <div>
                            <label
                                for="shift"
                                class="mb-1.5 block text-sm font-medium text-slate-700"
                            >
                                Shift
                                <span id="shiftRequiredIndicator" class="hidden text-red-500">*</span>
                                <span id="shiftOptionalIndicator" class="hidden text-xs font-normal text-slate-400">(Opsional)</span>
                            </label>

                            <select
                                id="shift"
                                name="shift"
                                class="block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">
                                    Pilih Shift
                                </option>

                                @foreach(['1', '2', '3'] as $shiftOption)
                                    <option
                                        value="{{ $shiftOption }}"
                                        @selected(old('shift') === $shiftOption)
                                    >
                                        Shift {{ $shiftOption }}
                                    </option>
                                @endforeach
                            </select>

                            @error('shift')
                                <p class="mt-1.5 text-xs font-medium text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- LINE MANUAL: tetap dipakai Dokumen QC Lainnya --}}
                        <div id="manualLineField" class="hidden">

                            <label
                                for="line"
                                class="mb-1.5 block text-sm font-medium text-slate-700"
                            >
                                Line
                            </label>

                            <input
                                id="line"
                                name="line"
                                value="{{ old('line') }}"
                                class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Contoh: MOP 3"
                            >

                        </div>

                        {{-- LINE MASTER: Retooling, Axle, dan Brake --}}
                        <div
                            id="retoolingLineField"
                            data-retooling-master="1"
                            data-lines-url="{{ route('documents.scan.master.lines') }}"
                            data-products-url-base="{{ url('/documents/scan/master/lines') }}"
                            class="hidden"
                        >
                            <label
                                for="retoolingLineSearch"
                                class="mb-1.5 block text-sm font-medium text-slate-700"
                            >
                                Line
                                <span id="lineRequiredIndicator" class="text-red-500">*</span>
                                <span id="lineOptionalIndicator" class="hidden text-xs font-normal text-slate-400">(Opsional)</span>
                            </label>

                            <div class="relative">

                                <input
                                    id="retoolingLineSearch"
                                    type="search"
                                    autocomplete="off"
                                    placeholder="Cari atau pilih Line..."
                                    class="block w-full rounded-xl border-slate-300 bg-white pr-10 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    role="combobox"
                                    aria-autocomplete="list"
                                    aria-expanded="false"
                                    aria-controls="retoolingLineOptions"
                                >

                                <span aria-hidden="true" class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">⌄</span>

                                <input
                                    type="hidden"
                                    id="production_line_id"
                                    name="production_line_id"
                                    value="{{ old('production_line_id') }}"
                                >

                                <div
                                    id="retoolingLineOptions"
                                    class="absolute left-0 right-0 top-full z-50 mt-1 hidden max-h-60 overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl"
                                    role="listbox"
                                ></div>

                            </div>

                            <p class="mt-1 text-xs text-slate-500">
                                Pilihan Line berasal dari master database produksi.
                            </p>

                            <button
                                id="lineClearButton"
                                type="button"
                                class="mt-1 hidden text-xs font-semibold text-blue-700 hover:text-blue-900"
                            >
                                Kosongkan pilihan Line
                            </button>

                            @error('production_line_id')
                                <p class="mt-1.5 text-xs font-medium text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                    </div>

                    {{-- FROM TYPE + TO TYPE MANUAL: untuk Dokumen QC Lainnya --}}
                    <div
                        id="retoolingFields"
                        class="hidden grid gap-4 sm:grid-cols-2"
                    >

                        <div>
                            <label
                                for="from_type"
                                class="mb-1.5 block text-sm font-medium text-slate-700"
                            >
                                Dari Type
                            </label>

                            <input
                                id="from_type"
                                name="from_type"
                                value="{{ old('from_type') }}"
                                class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Type sebelumnya"
                            >
                        </div>

                        <div>
                            <label
                                for="to_type"
                                class="mb-1.5 block text-sm font-medium text-slate-700"
                            >
                                Ke Type
                            </label>

                            <input
                                id="to_type"
                                name="to_type"
                                value="{{ old('to_type') }}"
                                class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Type berikutnya"
                            >
                        </div>

                    </div>

                    {{-- FROM TYPE + TO TYPE MASTER: khusus Retooling --}}
                    <div
                        id="retoolingMasterTypeFields"
                        class="hidden grid gap-4 sm:grid-cols-2"
                    >

                        <div>

                            <label
                                for="retoolingFromSearch"
                                class="mb-1.5 block text-sm font-medium text-slate-700"
                            >
                                Dari Type
                                <span class="text-red-500">*</span>
                            </label>

                            <div class="relative">

                                <input
                                    id="retoolingFromSearch"
                                    type="search"
                                    autocomplete="off"
                                    placeholder="Pilih Line terlebih dahulu"
                                    disabled
                                    class="block w-full rounded-xl border-slate-300 bg-white pr-10 text-sm shadow-sm disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 focus:border-blue-500 focus:ring-blue-500"
                                    role="combobox"
                                    aria-autocomplete="list"
                                    aria-expanded="false"
                                    aria-controls="retoolingFromOptions"
                                >

                                <span aria-hidden="true" class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">⌄</span>

                                <input
                                    type="hidden"
                                    id="from_casting_id"
                                    name="from_casting_id"
                                    value="{{ old('from_casting_id') }}"
                                >

                                <div
                                    id="retoolingFromOptions"
                                    class="absolute left-0 right-0 top-full z-50 mt-1 hidden max-h-60 overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl"
                                    role="listbox"
                                ></div>

                            </div>

                            <div
                                id="retoolingFromCycleTime"
                                class="mt-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-600"
                            >
                                Cycle Time: -
                            </div>

                            @error('from_casting_id')
                                <p class="mt-1.5 text-xs font-medium text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>

                            <label
                                for="retoolingToSearch"
                                class="mb-1.5 block text-sm font-medium text-slate-700"
                            >
                                Ke Type
                                <span class="text-red-500">*</span>
                            </label>

                            <div class="relative">

                                <input
                                    id="retoolingToSearch"
                                    type="search"
                                    autocomplete="off"
                                    placeholder="Pilih Line terlebih dahulu"
                                    disabled
                                    class="block w-full rounded-xl border-slate-300 bg-white pr-10 text-sm shadow-sm disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 focus:border-blue-500 focus:ring-blue-500"
                                    role="combobox"
                                    aria-autocomplete="list"
                                    aria-expanded="false"
                                    aria-controls="retoolingToOptions"
                                >

                                <span aria-hidden="true" class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">⌄</span>

                                <input
                                    type="hidden"
                                    id="to_casting_id"
                                    name="to_casting_id"
                                    value="{{ old('to_casting_id') }}"
                                >

                                <div
                                    id="retoolingToOptions"
                                    class="absolute left-0 right-0 top-full z-50 mt-1 hidden max-h-60 overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl"
                                    role="listbox"
                                ></div>

                            </div>

                            <div
                                id="retoolingToCycleTime"
                                class="mt-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-600"
                            >
                                Cycle Time: -
                            </div>

                            @error('to_casting_id')
                                <p class="mt-1.5 text-xs font-medium text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                    </div>


                    {{-- JAM --}}
                    <div>
                        <label
                            for="scan_time"
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Jam
                            <span id="timeOptionalIndicator" class="hidden text-xs font-normal text-slate-400">(Opsional)</span>
                        </label>

                        <div class="relative">
                            <input
                                id="scan_time"
                                type="{{ old('scan_type', 'Retooling') === 'Retooling' ? 'text' : 'time' }}"
                                name="scan_time"
                                value="{{ old('scan_time') }}"
                                placeholder="Ketik atau pilih Jam (HH:mm)..."
                                pattern="(?:[01][0-9]|2[0-3]):[0-5][0-9]"
                                maxlength="5"
                                aria-describedby="retoolingTimeHint"
                                class="block w-full rounded-xl border-slate-300 bg-white pr-10 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                            <span
                                id="retoolingTimeIndicator"
                                aria-hidden="true"
                                class="pointer-events-none absolute inset-y-0 right-3 hidden items-center text-slate-400"
                            >⌄</span>

                            <div
                                id="retoolingTimeOptions"
                                class="absolute left-0 right-0 top-full z-50 mt-1 hidden max-h-60 overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl"
                                role="listbox"
                            ></div>
                        </div>

                        <p
                            id="retoolingTimeHint"
                            class="mt-1 hidden text-xs text-slate-500"
                        >
                            Ketik format 24 jam HH:mm atau pilih saran waktu.
                        </p>

                        @error('scan_time')
                            <p class="mt-1.5 text-xs font-medium text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>


                    {{-- SELECTED FILE --}}
                    <div
                        id="selectedFile"
                        class="hidden rounded-xl border border-blue-100 bg-blue-50 px-3 py-2.5 text-xs text-blue-800"
                    ></div>


                    {{-- SAVE --}}
                    <button
                        id="saveButton"
                        type="submit"
                        disabled
                        class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-45"
                    >
                        Simpan Berkas QC
                    </button>

                </div>

            </section>

        </form>
    </div>


    {{-- ================================================================ --}}
    {{-- CAMERA MODAL --}}
    {{-- ================================================================ --}}
    <div
        id="cameraModal"
        class="fixed inset-0 z-[100] hidden bg-slate-950/90 backdrop-blur-sm"
    >

        <div class="flex min-h-full items-center justify-center p-4">

            <div class="w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-2xl">

                {{-- CAMERA HEADER --}}
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">

                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">
                            Ambil Foto Berkas
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Posisikan berkas QC di dalam area kamera.
                        </p>
                    </div>

                    <button
                        type="button"
                        id="closeCameraButton"
                        class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                        aria-label="Tutup kamera"
                    >
                        ✕
                    </button>

                </div>


                {{-- CAMERA AREA --}}
                <div class="relative bg-black">

                    <video
                        id="cameraVideo"
                        autoplay
                        playsinline
                        muted
                        class="block aspect-video w-full object-cover"
                    ></video>

                    {{-- CAMERA LOADING --}}
                    <div
                        id="cameraLoading"
                        class="absolute inset-0 flex items-center justify-center bg-slate-950"
                    >
                        <div class="text-center text-white">

                            <div class="mx-auto mb-3 h-8 w-8 animate-spin rounded-full border-2 border-white/30 border-t-white"></div>

                            <p class="text-sm font-medium">
                                Mengaktifkan kamera...
                            </p>

                        </div>
                    </div>

                </div>


                {{-- CAMERA FOOTER --}}
                <div class="border-t border-slate-200 bg-white px-5 py-4">

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                        <div class="min-w-0 flex-1">
                            <label
                                for="cameraSelect"
                                class="mb-1 block text-xs font-semibold text-slate-600"
                            >
                                Kamera
                            </label>

                            <select
                                id="cameraSelect"
                                disabled
                                class="block w-full rounded-xl border-slate-300 bg-white text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Mendeteksi kamera...</option>
                            </select>
                        </div>

                        <div class="flex items-center justify-center gap-3">

                            <button
                                type="button"
                                id="switchCameraButton"
                                disabled
                                class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                Ganti Kamera
                            </button>

                            <button
                                type="button"
                                id="captureButton"
                                disabled
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-blue-600 text-white shadow-lg transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-40"
                                aria-label="Ambil foto"
                            >
                                <span class="h-5 w-5 rounded-full border-2 border-white"></span>
                            </button>

                        </div>

                    </div>

                    <p
                        id="cameraStatus"
                        class="mt-2 text-center text-xs text-slate-400"
                    >
                        Kamera akan digunakan langsung. Pilih kamera jika perangkat memiliki lebih dari satu kamera.
                    </p>

                </div>

            </div>

        </div>

    </div>


    {{-- HIDDEN CAMERA INPUT --}}
    <input
        id="cameraInput"
        type="file"
        accept="image/*"
        capture="environment"
        class="hidden"
    >


<style nonce="{{ $cspNonce }}">
    .edit-action { display:inline-flex; align-items:center; justify-content:center; gap:.4rem; border:1px solid #cbd5e1; background:#fff; color:#334155; border-radius:.75rem; padding:.65rem .75rem; font-size:.75rem; font-weight:700; transition:.2s; }
    .edit-action:hover { border-color:#93c5fd; background:#eff6ff; color:#1d4ed8; }
</style>

    {{-- ================================================================ --}}
    {{-- JAVASCRIPT --}}
    {{-- ================================================================ --}}
    <script nonce="{{ $cspNonce }}">
        (() => {

            const openCameraButton = document.getElementById('openCameraButton');
            const closeCameraButton = document.getElementById('closeCameraButton');
            const switchCameraButton = document.getElementById('switchCameraButton');
            const captureButton = document.getElementById('captureButton');

            const cameraModal = document.getElementById('cameraModal');
            const cameraVideo = document.getElementById('cameraVideo');
            const cameraLoading = document.getElementById('cameraLoading');
            const cameraSelect = document.getElementById('cameraSelect');
            const cameraStatus = document.getElementById('cameraStatus');

            const cameraInput = document.getElementById('cameraInput');
            const uploadInput = document.getElementById('uploadInput');
            const scanFilesInput = document.getElementById('scanFilesInput');
            const scanDocumentForm = document.getElementById('scanDocumentForm');

            const preview = document.getElementById('imagePreview');
            const emptyState = document.getElementById('emptyState');

            const selectedFile = document.getElementById('selectedFile');
            const saveButton = document.getElementById('saveButton');
            const photoQueueWrapper = document.getElementById('photoQueueWrapper');
            const photoQueue = document.getElementById('photoQueue');
            const photoQueueCount = document.getElementById('photoQueueCount');
            const photoQueueHint = document.getElementById('photoQueueHint');
            const editToolbar = document.getElementById('editToolbar');
            const retakeButton = document.getElementById('retakeButton');
            const applyEditButton = document.getElementById('applyEditButton');
            const brightnessRange = document.getElementById('brightnessRange');
            const contrastRange = document.getElementById('contrastRange');
            const zoomRange = document.getElementById('zoomRange');
            const cropRange = document.getElementById('cropRange');
            const brightnessValue = document.getElementById('brightnessValue');
            const contrastValue = document.getElementById('contrastValue');
            const zoomValue = document.getElementById('zoomValue');
            const cropValue = document.getElementById('cropValue');

            // ============================================================
            // DOCUMENT TYPE / CHECKSHEET METADATA
            // ============================================================
            const scanType = document.getElementById('scan_type');
            const customScanTypeWrapper = document.getElementById('customScanTypeWrapper');
            const customScanType = document.getElementById('scan_type_other');
            const scanDate = document.getElementById('scan_date');
            const scanDateLabel = document.getElementById('scanDateLabel');
            const axleAssyFields = document.getElementById('axleAssyFields');
            const brakeAssyFields = document.getElementById('brakeAssyFields');
            const brakePartSearch = document.getElementById('brakePartSearch');
            const brakePartNumberId = document.getElementById('brake_part_number_id');
            const brakePartPreview = document.getElementById('brakePartPreview');
            const retoolingFields = document.getElementById('retoolingFields');
            const shiftRequiredIndicator = document.getElementById('shiftRequiredIndicator');
            const shiftOptionalIndicator = document.getElementById('shiftOptionalIndicator');
            const timeOptionalIndicator = document.getElementById('timeOptionalIndicator');
            const scanTime = document.getElementById('scan_time');
            const titleInput = document.getElementById('title');
            const fileNameInput = document.getElementById('file_name');
            const categoryInput = document.getElementById('category_id');
            const captureSourceInput = document.getElementById('captureSource');

            const AXLE_SCAN = 'Laporan Hasil Produksi - Axle Assy';
            const BRAKE_SCAN = 'Laporan Hasil Produksi - Brake Assy';
            const OTHER_SCAN = 'Lainnya';

            let lastAutoTitle = '';
            let lastAutoFileName = '';


            let cameraStream = null;
            let cameraDevices = [];
            let currentDeviceId = '';
            let cameraStarting = false;

            // ============================================================
            // MOBILE CAMERA MODE
            // ============================================================
            // Di HP/tablet, terutama saat QualityHub dibuka melalui HTTP LAN,
            // getUserMedia() dapat diblokir karena bukan secure context.
            // Untuk perangkat mobile kita gunakan file input + capture=environment
            // agar kamera native (kamera belakang) terbuka langsung.
            const isMobileCameraDevice = (() => {
                const ua = navigator.userAgent || navigator.vendor || window.opera || '';
                const mobileUa = /Android|iPhone|iPad|iPod|Mobile|IEMobile|Opera Mini/i.test(ua);
                const iPadDesktopMode = navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1;
                const coarsePointer = window.matchMedia && window.matchMedia('(pointer: coarse)').matches;
                const compactScreen = Math.min(window.screen.width || 9999, window.screen.height || 9999) <= 1024;

                return mobileUa || iPadDesktopMode || (coarsePointer && compactScreen);
            })();

            function openNativeMobileCamera() {
                closeCamera();

                // selectedItems menyimpan foto yang sudah dipilih, jadi input chooser
                // aman dikosongkan agar file yang sama dapat dipilih lagi.
                cameraInput.value = '';
                cameraInput.click();
            }
            const MAX_PHOTOS = 10;
            const MAX_TOTAL_BYTES = 60 * 1024 * 1024;

            let selectedItems = [];
            let activePhotoIndex = -1;
            let replaceOnNextCapture = false;
            let queueObjectUrls = [];

            let originalFile = null;
            let originalImage = null;
            let editState = { rotation: 0, brightness: 100, contrast: 100, grayscale: 0, zoom: 100, crop: 0 };
            let objectUrl = null;


            // ============================================================
            // PRODUCTION CHECKSHEET UI
            // ============================================================
            function setRequiredForGroup(selector, required) {
                document.querySelectorAll(selector).forEach(input => {
                    input.required = required;
                    input.setAttribute('aria-required', String(required));
                });
            }

            function selectedRadioMasterName(name) {
                return document.querySelector(`input[name="${name}"]:checked`)?.dataset.masterName || '';
            }

            // ============================================================
            // ROBUST RADIO CARD HANDLER
            // ============================================================
            // Beberapa browser dapat hanya memberi focus ring saat area card
            // diklik. Handler ini memastikan seluruh card benar-benar memilih
            // radio, lalu langsung memperbarui tampilan + metadata otomatis.
            function refreshRadioCards() {
                document.querySelectorAll('[data-radio-card]').forEach(card => {
                    const radio = card.querySelector('input[type="radio"]');
                    const active = !!radio?.checked;

                    // Selected state dibuat halus. Fokus visual utama ada pada
                    // indikator bulat biru + check putih, bukan seluruh card.
                    card.classList.toggle('border-blue-300', active);
                    card.classList.toggle('bg-blue-50/40', active);
                    card.classList.toggle('text-blue-900', active);

                    const indicator = card.querySelector('[data-radio-indicator]');
                    const checkIcon = indicator?.querySelector('svg');

                    if (indicator) {
                        indicator.classList.toggle('border-blue-600', active);
                        indicator.classList.toggle('bg-blue-600', active);
                        indicator.classList.toggle('border-slate-300', !active);
                        indicator.classList.toggle('bg-white', !active);
                    }

                    if (checkIcon) {
                        checkIcon.classList.toggle('opacity-100', active);
                        checkIcon.classList.toggle('opacity-0', !active);
                    }
                });
            }

            document.querySelectorAll('[data-radio-card]').forEach(card => {
                const radio = card.querySelector('input[type="radio"]');

                if (!radio) {
                    return;
                }

                card.addEventListener('click', event => {
                    if (radio.disabled) {
                        return;
                    }

                    // Klik langsung ke lingkaran radio tetap pakai perilaku native.
                    if (event.target === radio) {
                        return;
                    }

                    // Klik teks / area card dipastikan memilih radio ini.
                    event.preventDefault();

                    if (!radio.checked) {
                        radio.checked = true;
                        radio.dispatchEvent(new Event('change', { bubbles: true }));
                    } else {
                        refreshRadioCards();
                    }

                    radio.focus({ preventScroll: true });
                });

                radio.addEventListener('change', refreshRadioCards);
            });

            function slugify(value) {
                return String(value || '')
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }

            function setAutoValue(input, value, type = 'title') {
                if (!input || !value) {
                    return;
                }

                const previous = type === 'title' ? lastAutoTitle : lastAutoFileName;

                // Jangan menimpa input jika user sudah mengetik nama sendiri.
                if (input.value.trim() === '' || input.value === previous) {
                    input.value = value;

                    if (type === 'title') {
                        lastAutoTitle = value;
                    } else {
                        lastAutoFileName = value;
                    }
                }
            }

            function findChecksheetCategory() {
                if (!categoryInput) {
                    return null;
                }

                return Array.from(categoryInput.options).find(option => {
                    const name = (option.dataset.categoryName || option.textContent || '')
                        .toLowerCase()
                        .replace(/\s+/g, ' ')
                        .trim();

                    return name.includes('checksheet') || name.includes('check sheet');
                }) || null;
            }

            function autoSelectChecksheetCategory() {
                if (!categoryInput || categoryInput.value) {
                    return;
                }

                const checksheet = findChecksheetCategory();

                if (checksheet) {
                    categoryInput.value = checksheet.value;
                    categoryInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }

            function refreshProductionPreview() {
                const type = scanType?.value || '';
                const date = scanDate?.value || '';

                if (type === 'Retooling') {
                    const autoTitle = 'INSTRUKSI PERUBAHAN TYPE (RETOOLING)';
                    setAutoValue(titleInput, autoTitle, 'title');
                    setAutoValue(fileNameInput, slugify(autoTitle), 'file');
                }

                if (type === AXLE_SCAN) {
                    const axleType = selectedRadioMasterName('axle_type_id');
                    const axleSide = selectedRadioMasterName('axle_position_id');

                    const titleParts = [
                        'LAPORAN HASIL PRODUKSI - AXLE ASSY',
                        axleType,
                        axleSide,
                        date
                    ].filter(Boolean);

                    const autoTitle = titleParts.join(' - ');
                    setAutoValue(titleInput, autoTitle, 'title');
                    setAutoValue(fileNameInput, slugify(autoTitle), 'file');
                }

                if (type === BRAKE_SCAN) {
                    const fullPartNumber = brakePartSearch?.dataset.displayName || '';

                    if (brakePartPreview) {
                        brakePartPreview.classList.toggle('hidden', !fullPartNumber);
                        brakePartPreview.textContent = fullPartNumber
                            ? `Part No tersimpan: ${fullPartNumber}`
                            : '';
                    }

                    const titleParts = [
                        'LAPORAN HASIL PRODUKSI - BRAKE ASSY',
                        fullPartNumber,
                        date
                    ].filter(Boolean);

                    const autoTitle = titleParts.join(' - ');
                    setAutoValue(titleInput, autoTitle, 'title');
                    setAutoValue(fileNameInput, slugify(autoTitle), 'file');
                }
            }

            function syncDocumentTypeUI() {
                if (!scanType) {
                    return;
                }

                const type = scanType.value;
                const isAxle = type === AXLE_SCAN;
                const isBrake = type === BRAKE_SCAN;
                const isProduction = isAxle || isBrake;
                const isRetooling = type === 'Retooling';
                const isOther = type === OTHER_SCAN;

                customScanTypeWrapper?.classList.toggle('hidden', !isOther);
                axleAssyFields?.classList.toggle('hidden', !isAxle);
                brakeAssyFields?.classList.toggle('hidden', !isBrake);
                retoolingFields?.classList.toggle('hidden', isRetooling || isProduction);

                if (customScanType) {
                    customScanType.required = isOther;
                    customScanType.disabled = !isOther;
                }

                setRequiredForGroup('input[name="axle_type_id"]', isAxle);
                setRequiredForGroup('input[name="axle_position_id"]', isAxle);

                document.querySelectorAll('input[name="axle_type_id"], input[name="axle_position_id"]').forEach(input => {
                    input.disabled = !isAxle;
                });

                if (brakePartNumberId) {
                    brakePartNumberId.required = isBrake;
                    brakePartNumberId.disabled = !isBrake;
                }

                shiftRequiredIndicator?.classList.toggle('hidden', !isRetooling);
                shiftOptionalIndicator?.classList.toggle('hidden', !isProduction);
                timeOptionalIndicator?.classList.toggle('hidden', !isProduction);
                scanTime?.setAttribute('aria-required', 'false');

                scanDateLabel.innerHTML = isProduction
                    ? 'Tanggal Produksi <span class="text-red-500">*</span>'
                    : 'Tanggal <span class="text-red-500">*</span>';

                if (isProduction) {
                    autoSelectChecksheetCategory();
                }

                if (type === 'Retooling') {
                    if (!titleInput.value.trim()) {
                        titleInput.placeholder = 'Contoh: INSTRUKSI PERUBAHAN TYPE (RETOOLING)';
                    }
                    if (!fileNameInput.value.trim()) {
                        fileNameInput.placeholder = 'Contoh: instruksi-perubahan-type';
                    }
                } else if (isProduction) {
                    titleInput.placeholder = 'Judul otomatis mengikuti type / part no dan tanggal';
                    fileNameInput.placeholder = 'Nama file otomatis mengikuti metadata produksi';
                } else {
                    titleInput.placeholder = 'Masukkan judul dokumen QC';
                    fileNameInput.placeholder = 'Masukkan nama file';
                }

                refreshProductionPreview();
                refreshRadioCards();
            }

            scanType?.addEventListener('change', syncDocumentTypeUI);
            scanDate?.addEventListener('change', refreshProductionPreview);
            brakePartNumberId?.addEventListener('change', refreshProductionPreview);

            document.querySelectorAll('input[name="axle_type_id"], input[name="axle_position_id"]').forEach(input => {
                input.addEventListener('change', () => {
                    refreshRadioCards();
                    refreshProductionPreview();
                });
            });

            // Restore tampilan yang benar setelah validation error / reload.
            syncDocumentTypeUI();


            // ============================================================
            // CAMERA SUPPORT CHECK
            // ============================================================
            function hasCameraSupport() {

                return !!(
                    navigator.mediaDevices &&
                    typeof navigator.mediaDevices.getUserMedia === 'function'
                );

            }


            // ============================================================
            // CAMERA ERROR MESSAGE
            // ============================================================
            function getCameraErrorMessage(error) {

                if (!error) {
                    return 'Kamera tidak dapat diakses.';
                }

                switch (error.name) {

                    case 'NotAllowedError':
                    case 'PermissionDeniedError':
                        return 'Izin kamera ditolak. Izinkan akses kamera untuk browser ini, lalu coba lagi.';

                    case 'NotFoundError':
                    case 'DevicesNotFoundError':
                        return 'Tidak ada kamera yang terdeteksi pada perangkat ini.';

                    case 'NotReadableError':
                    case 'TrackStartError':
                        return 'Kamera sedang digunakan aplikasi lain atau tidak dapat dibaca. Tutup aplikasi lain yang memakai kamera lalu coba lagi.';

                    case 'OverconstrainedError':
                        return 'Kamera yang dipilih tidak dapat digunakan dengan pengaturan saat ini. Silakan pilih kamera lain.';

                    case 'SecurityError':
                        return 'Browser memblokir akses kamera pada halaman ini. Gunakan localhost/127.0.0.1 atau HTTPS dan izinkan kamera.';

                    default:
                        return 'Kamera tidak dapat diakses. Pastikan kamera tersedia dan izin kamera sudah diberikan kepada browser.';
                }

            }


            // ============================================================
            // UPDATE CAMERA STATUS
            // ============================================================
            function setCameraStatus(message, isError = false) {

                cameraStatus.textContent = message;

                cameraStatus.classList.toggle('text-red-500', isError);
                cameraStatus.classList.toggle('text-slate-400', !isError);

            }


            // ============================================================
            // STOP CAMERA
            // ============================================================
            function stopCamera() {

                if (cameraStream) {

                    cameraStream.getTracks().forEach(track => {
                        track.stop();
                    });

                    cameraStream = null;
                }

                cameraVideo.srcObject = null;

                captureButton.disabled = true;

            }


            // ============================================================
            // GET ALL VIDEO DEVICES
            // ============================================================
            async function getCameraDevices() {

                if (!navigator.mediaDevices || typeof navigator.mediaDevices.enumerateDevices !== 'function') {
                    cameraDevices = [];
                    return [];
                }

                const devices = await navigator.mediaDevices.enumerateDevices();

                cameraDevices = devices.filter(device => device.kind === 'videoinput');

                return cameraDevices;

            }


            // ============================================================
            // CAMERA LABEL
            // ============================================================
            function getCameraLabel(device, index) {

                if (device.label && device.label.trim() !== '') {
                    return device.label;
                }

                return `Kamera ${index + 1}`;

            }


            // ============================================================
            // RENDER CAMERA LIST
            // ============================================================
            function renderCameraList() {

                cameraSelect.innerHTML = '';

                if (!cameraDevices.length) {

                    const option = document.createElement('option');

                    option.value = '';
                    option.textContent = 'Tidak ada kamera terdeteksi';

                    cameraSelect.appendChild(option);

                    cameraSelect.disabled = true;
                    switchCameraButton.disabled = true;

                    return;
                }


                cameraDevices.forEach((device, index) => {

                    const option = document.createElement('option');

                    option.value = device.deviceId;
                    option.textContent = getCameraLabel(device, index);

                    if (device.deviceId === currentDeviceId) {
                        option.selected = true;
                    }

                    cameraSelect.appendChild(option);

                });


                cameraSelect.disabled = cameraDevices.length <= 1;
                switchCameraButton.disabled = cameraDevices.length <= 1;

            }


            // ============================================================
            // FIND INITIAL CAMERA
            // ============================================================
            function chooseInitialCamera() {

                if (!cameraDevices.length) {
                    return '';
                }


                // Jika kamera yang sedang aktif masih tersedia, pertahankan.
                if (
                    currentDeviceId &&
                    cameraDevices.some(device => device.deviceId === currentDeviceId)
                ) {
                    return currentDeviceId;
                }


                // Prioritaskan kamera belakang jika browser memberikan label.
                const environmentCamera = cameraDevices.find(device => {

                    const label = (device.label || '').toLowerCase();

                    return (
                        label.includes('back') ||
                        label.includes('rear') ||
                        label.includes('environment') ||
                        label.includes('belakang')
                    );

                });


                if (environmentCamera) {
                    return environmentCamera.deviceId;
                }


                return cameraDevices[0].deviceId;

            }


            // ============================================================
            // START CAMERA
            // ============================================================
            async function startCamera(deviceId = '') {

                if (cameraStarting) {
                    return;
                }


                cameraStarting = true;

                stopCamera();

                cameraLoading.classList.remove('hidden');
                cameraModal.classList.remove('hidden');

                captureButton.disabled = true;
                cameraSelect.disabled = true;
                switchCameraButton.disabled = true;

                setCameraStatus('Meminta akses kamera...');


                if (!hasCameraSupport()) {

                    cameraStarting = false;

                    cameraLoading.classList.add('hidden');

                    setCameraStatus(
                        'Browser ini tidak mendukung akses kamera langsung.',
                        true
                    );

                    return;
                }


                try {

                    /*
                     * Saat belum memilih device tertentu, minta kamera
                     * belakang sebagai preferensi. Ini tidak mengunci
                     * perangkat karena daftar semua kamera akan diambil
                     * setelah izin diberikan.
                     */
                    const videoConstraints = deviceId
                        ? {
                            deviceId: {
                                exact: deviceId
                            },
                            width: {
                                ideal: 1920
                            },
                            height: {
                                ideal: 1080
                            }
                        }
                        : {
                            facingMode: {
                                ideal: 'environment'
                            },
                            width: {
                                ideal: 1920
                            },
                            height: {
                                ideal: 1080
                            }
                        };


                    try {

                        cameraStream = await navigator.mediaDevices.getUserMedia({
                            video: videoConstraints,
                            audio: false
                        });

                    } catch (firstError) {

                        /*
                         * Beberapa webcam laptop tidak menerima
                         * constraint tertentu. Coba akses kamera tanpa
                         * constraint agar kamera tetap dapat digunakan.
                         */
                        if (
                            firstError.name === 'OverconstrainedError' ||
                            firstError.name === 'NotFoundError'
                        ) {

                            cameraStream = await navigator.mediaDevices.getUserMedia({
                                video: true,
                                audio: false
                            });

                        } else {

                            throw firstError;

                        }

                    }


                    cameraVideo.srcObject = cameraStream;

                    await cameraVideo.play();


                    /*
                     * Setelah izin diberikan, enumerateDevices() akan
                     * mengembalikan label kamera yang sebenarnya.
                     */
                    await getCameraDevices();


                    /*
                     * Ambil device ID kamera yang benar-benar sedang
                     * digunakan dari track jika tersedia.
                     */
                    const activeTrack =
                        cameraStream.getVideoTracks()[0];

                    const activeSettings =
                        activeTrack && typeof activeTrack.getSettings === 'function'
                            ? activeTrack.getSettings()
                            : {};

                    currentDeviceId =
                        activeSettings.deviceId ||
                        deviceId ||
                        chooseInitialCamera();


                    renderCameraList();


                    /*
                     * Jika currentDeviceId belum ditemukan di daftar,
                     * tetap pilih kamera pertama yang tersedia.
                     */
                    if (
                        !cameraDevices.some(
                            device => device.deviceId === currentDeviceId
                        )
                    ) {

                        currentDeviceId = chooseInitialCamera();

                        renderCameraList();

                    }


                    cameraLoading.classList.add('hidden');

                    captureButton.disabled = false;

                    cameraSelect.disabled = cameraDevices.length <= 1;
                    switchCameraButton.disabled = cameraDevices.length <= 1;


                    if (cameraDevices.length > 1) {

                        setCameraStatus(
                            `${cameraDevices.length} kamera tersedia. Pilih kamera dari daftar atau tekan "Ganti Kamera".`
                        );

                    } else {

                        setCameraStatus(
                            'Kamera aktif. Posisikan berkas di dalam area kamera.'
                        );

                    }

                } catch (error) {

                    console.error('Camera error:', error);

                    stopCamera();

                    cameraLoading.classList.add('hidden');

                    setCameraStatus(
                        getCameraErrorMessage(error),
                        true
                    );

                    /*
                     * JANGAN membuka file picker di sini.
                     * Tombol Ambil Foto khusus untuk kamera.
                     */
                    captureButton.disabled = true;

                    cameraSelect.disabled = true;
                    switchCameraButton.disabled = true;

                } finally {

                    cameraStarting = false;

                }

            }


            // ============================================================
            // CLOSE CAMERA
            // ============================================================
            function closeCamera() {

                stopCamera();

                cameraModal.classList.add('hidden');

                cameraLoading.classList.add('hidden');

                cameraSelect.disabled = true;
                switchCameraButton.disabled = true;

                setCameraStatus(
                    'Kamera akan digunakan langsung. Pilih kamera jika perangkat memiliki lebih dari satu kamera.'
                );

            }


            // ============================================================
            // SHOW IMAGE PREVIEW
            // ============================================================
            function resetEditor() {
                editState = { rotation: 0, brightness: 100, contrast: 100, grayscale: 0, zoom: 100, crop: 0 };
                brightnessRange.value = 100;
                contrastRange.value = 100;
                zoomRange.value = 100;
                cropRange.value = 0;
                brightnessValue.textContent = '100%';
                contrastValue.textContent = '100%';
                zoomValue.textContent = '100%';
                cropValue.textContent = '0%';
                preview.style.filter = 'none';
                preview.style.transform = 'none';
                preview.style.clipPath = 'inset(0%)';
            }


            function renderEditorPreview() {
                if (!originalImage) return;
                preview.style.filter = `brightness(${editState.brightness}%) contrast(${editState.contrast}%) grayscale(${editState.grayscale}%)`;
                preview.style.transform = `rotate(${editState.rotation}deg) scale(${editState.zoom / 100})`;
                preview.style.clipPath = `inset(${editState.crop}% ${editState.crop}% ${editState.crop}% ${editState.crop}% round 10px)`;
                brightnessValue.textContent = `${editState.brightness}%`;
                contrastValue.textContent = `${editState.contrast}%`;
                zoomValue.textContent = `${editState.zoom}%`;
                cropValue.textContent = `${editState.crop}%`;
            }


            function showPreview(file) {
                if (!file || !file.type.startsWith('image/')) {
                    alert('File yang dipilih harus berupa gambar.');
                    return;
                }

                originalFile = file;
                resetEditor();

                if (objectUrl) URL.revokeObjectURL(objectUrl);
                objectUrl = URL.createObjectURL(file);
                preview.src = objectUrl;
                preview.classList.remove('hidden');
                emptyState.classList.add('hidden');
                editToolbar.classList.remove('hidden');
                // Ringkasan jumlah file dikelola oleh updateMultiPhotoState().

                originalImage = new Image();
                originalImage.onload = () => renderEditorPreview();
                originalImage.onerror = () => { originalImage = null; alert('Gambar tidak dapat dibaca untuk proses edit.'); };
                originalImage.src = objectUrl;
            }


            async function applyEditsToFile() {
                if (!originalFile || !originalImage) return;
                applyEditButton.disabled = true;
                applyEditButton.textContent = 'Memproses gambar...';

                try {
                    const img = originalImage;
                    const radians = (editState.rotation * Math.PI) / 180;
                    const sin = Math.abs(Math.sin(radians));
                    const cos = Math.abs(Math.cos(radians));
                    const rotatedWidth = Math.ceil(img.width * cos + img.height * sin);
                    const rotatedHeight = Math.ceil(img.width * sin + img.height * cos);
                    const cropX = Math.floor(rotatedWidth * editState.crop / 100);
                    const cropY = Math.floor(rotatedHeight * editState.crop / 100);
                    const outW = Math.max(1, rotatedWidth - cropX * 2);
                    const outH = Math.max(1, rotatedHeight - cropY * 2);

                    const canvas = document.createElement('canvas');
                    canvas.width = outW;
                    canvas.height = outH;
                    const ctx = canvas.getContext('2d');
                    if (!ctx) throw new Error('Canvas tidak tersedia.');

                    ctx.save();
                    ctx.translate(rotatedWidth / 2 - cropX, rotatedHeight / 2 - cropY);
                    ctx.rotate(radians);
                    ctx.filter = `brightness(${editState.brightness}%) contrast(${editState.contrast}%) grayscale(${editState.grayscale}%)`;
                    const zoom = editState.zoom / 100;
                    ctx.drawImage(img, -(img.width * zoom) / 2, -(img.height * zoom) / 2, img.width * zoom, img.height * zoom);
                    ctx.restore();

                    const outputType = ['image/png', 'image/webp', 'image/jpeg'].includes(originalFile.type) ? originalFile.type : 'image/jpeg';
                    const quality = outputType === 'image/png' ? undefined : 0.94;
                    const blob = await new Promise((resolve, reject) => canvas.toBlob(b => b ? resolve(b) : reject(new Error('Gagal membuat file hasil edit.')), outputType, quality));
                    const base = originalFile.name.replace(/\.[^.]+$/, '').replace(/[^a-zA-Z0-9_-]+/g, '-').replace(/^-+|-+$/g, '') || 'scan-qc';
                    const ext = outputType === 'image/png' ? 'png' : outputType === 'image/webp' ? 'webp' : 'jpg';
                    const editedFile = new File([blob], `${base}-edited.${ext}`, { type: outputType, lastModified: Date.now() });

                    if (activePhotoIndex < 0 || !selectedItems[activePhotoIndex]) {
                        throw new Error('Foto aktif tidak ditemukan.');
                    }

                    selectedItems[activePhotoIndex] = {
                        ...selectedItems[activePhotoIndex],
                        file: editedFile,
                    };

                    updateMultiPhotoState();
                    showPreview(editedFile);
                } catch (error) {
                    console.error('Image edit error:', error);
                    alert(error.message || 'Gagal memproses gambar.');
                } finally {
                    applyEditButton.disabled = false;
                    applyEditButton.textContent = 'Terapkan Perubahan ke Berkas';
                }
            }


            // ============================================================
            // MULTI PHOTO MANAGER
            // ============================================================
            function releaseQueueObjectUrls() {
                queueObjectUrls.forEach(url => URL.revokeObjectURL(url));
                queueObjectUrls = [];
            }


            function totalSelectedBytes() {
                return selectedItems.reduce((sum, item) => sum + (item.file?.size || 0), 0);
            }


            function syncFilesToForm() {
                try {
                    const transfer = new DataTransfer();
                    selectedItems.forEach(item => transfer.items.add(item.file));
                    scanFilesInput.files = transfer.files;
                    return scanFilesInput.files.length === selectedItems.length;
                } catch (error) {
                    console.error('Unable to sync scan files:', error);
                    return false;
                }
            }


            function refreshCaptureSource() {
                const sources = new Set(selectedItems.map(item => item.source));

                if (sources.size > 1) {
                    captureSourceInput.value = 'mixed';
                } else if (sources.size === 1) {
                    captureSourceInput.value = Array.from(sources)[0];
                } else {
                    captureSourceInput.value = 'upload';
                }
            }


            function createQueueAction(label, title, disabled, handler) {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = label;
                button.title = title;
                button.disabled = disabled;
                button.className = 'inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-slate-200 bg-white px-2 text-xs font-bold text-slate-600 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-35';
                button.addEventListener('click', event => {
                    event.stopPropagation();
                    handler();
                });
                return button;
            }


            function renderPhotoQueue() {
                releaseQueueObjectUrls();
                photoQueue.replaceChildren();

                const count = selectedItems.length;
                photoQueueCount.textContent = `${count} foto`;
                photoQueueWrapper.classList.toggle('hidden', count === 0);
                photoQueueHint.textContent = count > 1
                    ? 'Urutan foto di bawah menjadi urutan halaman PDF.'
                    : 'Tambahkan foto lain jika berkas QC memiliki lebih dari satu halaman.';

                selectedItems.forEach((item, index) => {
                    const card = document.createElement('div');
                    card.className = `flex cursor-pointer items-center gap-3 rounded-xl border p-2.5 transition ${index === activePhotoIndex ? 'border-blue-400 bg-white shadow-sm' : 'border-blue-100 bg-white/70 hover:border-blue-300'}`;
                    card.addEventListener('click', () => setActivePhoto(index));

                    const image = document.createElement('img');
                    const url = URL.createObjectURL(item.file);
                    queueObjectUrls.push(url);
                    image.src = url;
                    image.alt = `Foto ${index + 1}`;
                    image.className = 'h-14 w-14 shrink-0 rounded-lg border border-slate-200 bg-slate-50 object-cover';

                    const body = document.createElement('div');
                    body.className = 'min-w-0 flex-1';

                    const title = document.createElement('p');
                    title.className = 'truncate text-xs font-bold text-slate-800';
                    title.textContent = `Halaman ${index + 1} · ${item.file.name}`;

                    const meta = document.createElement('p');
                    meta.className = 'mt-1 text-[11px] text-slate-500';
                    meta.textContent = `${(item.file.size / 1024 / 1024).toFixed(2)} MB · ${item.source === 'camera' ? 'Kamera' : 'Galeri'}`;

                    body.append(title, meta);

                    const actions = document.createElement('div');
                    actions.className = 'flex shrink-0 items-center gap-1';
                    actions.append(
                        createQueueAction('←', 'Geser ke halaman sebelumnya', index === 0, () => movePhoto(index, -1)),
                        createQueueAction('→', 'Geser ke halaman berikutnya', index === count - 1, () => movePhoto(index, 1)),
                        createQueueAction('×', 'Hapus foto', false, () => removePhoto(index)),
                    );

                    card.append(image, body, actions);
                    photoQueue.append(card);
                });
            }


            function updateMultiPhotoState() {
                refreshCaptureSource();
                const synced = syncFilesToForm();
                renderPhotoQueue();

                const count = selectedItems.length;
                const totalMb = totalSelectedBytes() / 1024 / 1024;

                if (count === 0) {
                    selectedFile.classList.add('hidden');
                    selectedFile.textContent = '';
                    saveButton.disabled = true;
                    return;
                }

                selectedFile.classList.remove('hidden');
                selectedFile.textContent = count === 1
                    ? `1 foto dipilih · ${selectedItems[0].file.name} · ${totalMb.toFixed(2)} MB`
                    : `${count} foto dipilih · ${totalMb.toFixed(2)} MB · akan digabung menjadi 1 PDF`;

                saveButton.disabled = !synced;
            }


            function setActivePhoto(index) {
                if (!selectedItems[index]) return;
                activePhotoIndex = index;
                showPreview(selectedItems[index].file);
                renderPhotoQueue();
            }


            function addPhotos(files, source, replaceActive = false) {
                const incoming = Array.from(files || []).filter(file => file && file.type.startsWith('image/'));

                if (incoming.length === 0) {
                    alert('File yang dipilih harus berupa gambar.');
                    return false;
                }

                const replacing = replaceActive && activePhotoIndex >= 0;
                const extraCount = replacing ? Math.max(0, incoming.length - 1) : incoming.length;

                if (selectedItems.length + extraCount > MAX_PHOTOS) {
                    alert(`Maksimal ${MAX_PHOTOS} foto dalam satu berkas QC.`);
                    return false;
                }

                const projectedBytes = totalSelectedBytes()
                    - (replacing ? (selectedItems[activePhotoIndex]?.file?.size || 0) : 0)
                    + incoming.reduce((sum, file) => sum + file.size, 0);

                if (projectedBytes > MAX_TOTAL_BYTES) {
                    alert('Total ukuran foto melebihi 60 MB. Kurangi jumlah atau ukuran foto.');
                    return false;
                }

                let targetIndex;

                if (replacing) {
                    selectedItems[activePhotoIndex] = { file: incoming[0], source };
                    targetIndex = activePhotoIndex;

                    incoming.slice(1).forEach(file => selectedItems.push({ file, source }));
                } else {
                    incoming.forEach(file => selectedItems.push({ file, source }));
                    targetIndex = selectedItems.length - 1;
                }

                updateMultiPhotoState();
                setActivePhoto(targetIndex);
                return true;
            }


            function removePhoto(index) {
                if (!selectedItems[index]) return;
                selectedItems.splice(index, 1);

                if (selectedItems.length === 0) {
                    activePhotoIndex = -1;
                    originalFile = null;
                    originalImage = null;
                    if (objectUrl) {
                        URL.revokeObjectURL(objectUrl);
                        objectUrl = null;
                    }
                    preview.removeAttribute('src');
                    preview.classList.add('hidden');
                    emptyState.classList.remove('hidden');
                    editToolbar.classList.add('hidden');
                    updateMultiPhotoState();
                    return;
                }

                activePhotoIndex = Math.min(index, selectedItems.length - 1);
                updateMultiPhotoState();
                setActivePhoto(activePhotoIndex);
            }


            function movePhoto(index, direction) {
                const target = index + direction;
                if (!selectedItems[index] || target < 0 || target >= selectedItems.length) return;

                [selectedItems[index], selectedItems[target]] = [selectedItems[target], selectedItems[index]];

                if (activePhotoIndex === index) activePhotoIndex = target;
                else if (activePhotoIndex === target) activePhotoIndex = index;

                updateMultiPhotoState();
                if (activePhotoIndex >= 0) setActivePhoto(activePhotoIndex);
            }


            // ============================================================
            // EDIT CONTROLS
            // ============================================================
            document.querySelectorAll('[data-edit-action]').forEach(button => {
                button.addEventListener('click', () => {
                    const action = button.dataset.editAction;
                    if (action === 'rotate-left') editState.rotation -= 90;
                    if (action === 'rotate-right') editState.rotation += 90;
                    if (action === 'grayscale') editState.grayscale = editState.grayscale ? 0 : 100;
                    if (action === 'reset') resetEditor();
                    renderEditorPreview();
                });
            });

            brightnessRange.addEventListener('input', e => { editState.brightness = Number(e.target.value); renderEditorPreview(); });
            contrastRange.addEventListener('input', e => { editState.contrast = Number(e.target.value); renderEditorPreview(); });
            zoomRange.addEventListener('input', e => { editState.zoom = Number(e.target.value); renderEditorPreview(); });
            cropRange.addEventListener('input', e => { editState.crop = Number(e.target.value); renderEditorPreview(); });
            applyEditButton.addEventListener('click', applyEditsToFile);
            retakeButton.addEventListener('click', () => {
                resetEditor();
                replaceOnNextCapture = activePhotoIndex >= 0;

                if (isMobileCameraDevice) {
                    openNativeMobileCamera();
                    return;
                }

                startCamera();
            });


            // ============================================================
            // CAMERA CAPTURE
            // ============================================================
            captureButton.addEventListener('click', () => {

                if (
                    !cameraStream ||
                    !cameraVideo.videoWidth ||
                    !cameraVideo.videoHeight
                ) {
                    return;
                }


                const canvas = document.createElement('canvas');

                canvas.width = cameraVideo.videoWidth;
                canvas.height = cameraVideo.videoHeight;


                const context = canvas.getContext('2d');

                if (!context) {
                    return;
                }


                context.drawImage(
                    cameraVideo,
                    0,
                    0,
                    canvas.width,
                    canvas.height
                );


                captureButton.disabled = true;

                canvas.toBlob(
                    (blob) => {

                        if (!blob) {

                            captureButton.disabled = false;

                            setCameraStatus(
                                'Foto gagal dibuat. Silakan coba lagi.',
                                true
                            );

                            return;
                        }


                        const now = new Date();

                        const timestamp =
                            now.getFullYear() +
                            '-' +
                            String(now.getMonth() + 1).padStart(2, '0') +
                            '-' +
                            String(now.getDate()).padStart(2, '0') +
                            '_' +
                            String(now.getHours()).padStart(2, '0') +
                            '-' +
                            String(now.getMinutes()).padStart(2, '0') +
                            '-' +
                            String(now.getSeconds()).padStart(2, '0');


                        const file = new File(
                            [blob],
                            `scan-qc-${timestamp}.jpg`,
                            {
                                type: 'image/jpeg',
                                lastModified: Date.now()
                            }
                        );


                        const shouldReplace = replaceOnNextCapture;
                        replaceOnNextCapture = false;

                        if (!addPhotos([file], 'camera', shouldReplace)) {
                            captureButton.disabled = false;
                            return;
                        }

                        closeCamera();

                    },
                    'image/jpeg',
                    0.92
                );

            });


            // ============================================================
            // SWITCH CAMERA
            // ============================================================
            switchCameraButton.addEventListener('click', async () => {

                if (cameraDevices.length < 2 || cameraStarting) {
                    return;
                }


                const currentIndex = cameraDevices.findIndex(
                    device => device.deviceId === currentDeviceId
                );


                const nextIndex =
                    currentIndex >= 0
                        ? (currentIndex + 1) % cameraDevices.length
                        : 0;


                const nextDevice =
                    cameraDevices[nextIndex];


                if (!nextDevice) {
                    return;
                }


                currentDeviceId = nextDevice.deviceId;

                cameraSelect.value = currentDeviceId;

                await startCamera(currentDeviceId);

            });


            // ============================================================
            // SELECT CAMERA
            // ============================================================
            cameraSelect.addEventListener('change', async () => {

                const selectedDeviceId = cameraSelect.value;

                if (!selectedDeviceId || cameraStarting) {
                    return;
                }


                currentDeviceId = selectedDeviceId;

                await startCamera(currentDeviceId);

            });


            // ============================================================
            // OPEN CAMERA BUTTON
            // ============================================================
            openCameraButton.addEventListener('click', () => {

                replaceOnNextCapture = false;

                if (isMobileCameraDevice) {
                    openNativeMobileCamera();
                    return;
                }

                startCamera();

            });


            // ============================================================
            // CLOSE BUTTON
            // ============================================================
            closeCameraButton.addEventListener('click', () => {

                replaceOnNextCapture = false;
                closeCamera();

            });


            // ============================================================
            // CLICK OUTSIDE MODAL
            // ============================================================
            cameraModal.addEventListener('click', (event) => {

                if (event.target === cameraModal) {

                    closeCamera();

                }

            });


            // ============================================================
            // ESC TO CLOSE
            // ============================================================
            document.addEventListener('keydown', (event) => {

                if (
                    event.key === 'Escape' &&
                    !cameraModal.classList.contains('hidden')
                ) {

                    closeCamera();

                }

            });


            // ============================================================
            // CAMERA INPUT
            // ============================================================
            cameraInput.addEventListener('change', () => {

                const file = cameraInput.files[0];

                if (!file) {
                    return;
                }

                if (!file.type.startsWith('image/')) {
                    alert('Hasil kamera harus berupa gambar.');
                    cameraInput.value = '';
                    return;
                }

                const shouldReplace = replaceOnNextCapture;
                replaceOnNextCapture = false;
                addPhotos([file], 'camera', shouldReplace);
                cameraInput.value = '';

            });


            // ============================================================
            // GALLERY INPUT
            // ============================================================
            uploadInput.addEventListener('change', () => {

                const files = Array.from(uploadInput.files || []);

                if (files.length === 0) {
                    return;
                }

                addPhotos(files, 'upload', false);

                // Memungkinkan user memilih file yang sama lagi sebagai halaman lain.
                uploadInput.value = '';

            });


            // ============================================================
            // FINAL FORM SUBMIT
            // ============================================================
            scanDocumentForm.addEventListener('submit', event => {
                if (selectedItems.length === 0) {
                    event.preventDefault();
                    alert('Pilih minimal satu foto berkas QC.');
                    return;
                }

                if (!syncFilesToForm()) {
                    event.preventDefault();
                    alert('Daftar foto tidak dapat disiapkan untuk upload. Coba pilih ulang foto.');
                    return;
                }

                saveButton.disabled = true;
                saveButton.textContent = selectedItems.length > 1
                    ? 'Menggabungkan foto & menyimpan...'
                    : 'Menyimpan berkas...';
            });


            // ============================================================
            // CLEANUP CAMERA WHEN LEAVING PAGE
            // ============================================================
            window.addEventListener('beforeunload', () => {

                stopCamera();
                releaseQueueObjectUrls();
                if (objectUrl) URL.revokeObjectURL(objectUrl);

            });


        })();
    </script>

    {{-- ========================================================= --}}
    {{-- BMC QUALITYHUB RETOOLING MASTER SCRIPT V2                --}}
    {{-- CSP-safe searchable dropdown tanpa jQuery / Select2      --}}
    {{-- ========================================================= --}}

    <script nonce="{{ $cspNonce }}">
        (() => {

            const masterRoot =
                document.querySelector(
                    '[data-retooling-master="1"]'
                );

            const brakeMasterRoot =
                document.querySelector(
                    '[data-brake-master="1"]'
                );

            if (!masterRoot) {
                return;
            }

            const RETOOLING =
                'Retooling';

            const AXLE_SCAN =
                'Laporan Hasil Produksi - Axle Assy';

            const BRAKE_SCAN =
                'Laporan Hasil Produksi - Brake Assy';

            const scanType =
                document.getElementById('scan_type');

            const shift =
                document.getElementById('shift');

            const scanTime =
                document.getElementById('scan_time');

            const retoolingTimeOptions =
                document.getElementById('retoolingTimeOptions');

            const retoolingTimeIndicator =
                document.getElementById('retoolingTimeIndicator');

            const retoolingTimeHint =
                document.getElementById('retoolingTimeHint');

            const manualLineField =
                document.getElementById('manualLineField');

            const manualLine =
                document.getElementById('line');

            const retoolingLineField =
                document.getElementById('retoolingLineField');

            const lineSearch =
                document.getElementById('retoolingLineSearch');

            const lineOptions =
                document.getElementById('retoolingLineOptions');

            const lineId =
                document.getElementById('production_line_id');

            const lineRequiredIndicator =
                document.getElementById('lineRequiredIndicator');

            const lineOptionalIndicator =
                document.getElementById('lineOptionalIndicator');

            const lineClearButton =
                document.getElementById('lineClearButton');

            const manualTypeFields =
                document.getElementById('retoolingFields');

            const manualFrom =
                document.getElementById('from_type');

            const manualTo =
                document.getElementById('to_type');

            const masterTypeFields =
                document.getElementById('retoolingMasterTypeFields');

            const fromSearch =
                document.getElementById('retoolingFromSearch');

            const fromOptions =
                document.getElementById('retoolingFromOptions');

            const fromId =
                document.getElementById('from_casting_id');

            const fromCycleTime =
                document.getElementById('retoolingFromCycleTime');

            const toSearch =
                document.getElementById('retoolingToSearch');

            const toOptions =
                document.getElementById('retoolingToOptions');

            const toId =
                document.getElementById('to_casting_id');

            const toCycleTime =
                document.getElementById('retoolingToCycleTime');

            const brakePartSearch =
                document.getElementById('brakePartSearch');

            const brakePartOptions =
                document.getElementById('brakePartOptions');

            const brakePartId =
                document.getElementById('brake_part_number_id');

            const brakePartPrefix =
                document.getElementById('brakePartPrefix');

            const brakePartPreview =
                document.getElementById('brakePartPreview');

            const linesUrl =
                masterRoot.dataset.linesUrl;

            const productsUrlBase =
                masterRoot.dataset.productsUrlBase;

            const brakePartsUrl =
                brakeMasterRoot?.dataset.brakePartsUrl || '';

            const oldLineId =
                String(
                    lineId?.value || ''
                );

            const oldFromId =
                String(
                    fromId?.value || ''
                );

            const oldToId =
                String(
                    toId?.value || ''
                );

            const oldBrakePartId =
                String(
                    brakePartId?.value || ''
                );

            const timeSuggestions =
                Array.from(
                    { length: 48 },
                    (_, index) => {
                        const hours =
                            String(Math.floor(index / 2))
                                .padStart(2, '0');

                        const minutes =
                            index % 2 === 0
                                ? '00'
                                : '30';

                        return `${hours}:${minutes}`;
                    }
                );

            let lines = [];
            let products = [];
            let brakeParts = [];

            let selectedLine = null;
            let selectedFrom = null;
            let selectedTo = null;
            let selectedBrakePart = null;

            let linesLoaded = false;
            let linesLoading = false;
            let productRequestId = 0;
            let brakePartsLoaded = false;
            let brakePartsLoading = false;

            function normalize(value) {

                return String(value || '')
                    .trim()
                    .toLowerCase()
                    .replace(/[\s_]+/g, '');
            }

            function isRetooling() {

                return scanType?.value
                    === RETOOLING;
            }

            function isProduction() {

                const value =
                    scanType?.value || '';

                return value === AXLE_SCAN
                    || value === BRAKE_SCAN;
            }

            function closeDropdown(
                element,
                input
            ) {

                element?.classList.add(
                    'hidden'
                );

                if (input?.hasAttribute('aria-controls')) {
                    input.setAttribute(
                        'aria-expanded',
                        'false'
                    );
                } else {
                    input?.removeAttribute(
                        'aria-expanded'
                    );
                }

                input?.removeAttribute(
                    'aria-activedescendant'
                );
            }

            function openDropdown(
                element,
                input
            ) {

                [
                    [lineOptions, lineSearch],
                    [fromOptions, fromSearch],
                    [toOptions, toSearch],
                    [brakePartOptions, brakePartSearch],
                    [retoolingTimeOptions, scanTime],
                ].forEach(([otherElement, otherInput]) => {
                    if (otherElement !== element) {
                        closeDropdown(otherElement, otherInput);
                    }
                });

                element?.classList.remove(
                    'hidden'
                );

                input?.setAttribute(
                    'aria-expanded',
                    'true'
                );
            }

            function emptyMessage(
                text
            ) {

                const element =
                    document.createElement(
                        'div'
                    );

                element.className =
                    'px-3 py-3 text-sm text-slate-400';

                element.textContent =
                    text;

                element.setAttribute(
                    'role',
                    'status'
                );

                element.setAttribute(
                    'aria-live',
                    'polite'
                );

                return element;
            }

            function createOption(
                primary,
                secondary,
                callback,
                dropdown,
                input,
                index,
                selected
            ) {

                const button =
                    document.createElement(
                        'button'
                    );

                button.type =
                    'button';

                button.id =
                    `${dropdown.id}-option-${index}`;

                button.tabIndex = -1;

                button.className =
                    'flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-blue-50 hover:text-blue-800 focus:bg-blue-50 focus:text-blue-800 focus:outline-none';

                button.setAttribute(
                    'role',
                    'option'
                );

                button.setAttribute(
                    'aria-selected',
                    String(selected)
                );

                if (selected) {
                    button.classList.add(
                        'bg-blue-50',
                        'text-blue-800'
                    );
                }

                const main =
                    document.createElement(
                        'span'
                    );

                main.className =
                    'min-w-0 flex-1 truncate font-semibold';

                main.textContent =
                    primary;

                const meta =
                    document.createElement(
                        'span'
                    );

                meta.className =
                    'shrink-0 text-xs font-medium text-slate-400';

                meta.textContent =
                    secondary || '';

                button.append(
                    main,
                    meta
                );

                button.addEventListener(
                    'click',
                    callback
                );

                button.addEventListener(
                    'mousedown',
                    event => event.preventDefault()
                );

                button.addEventListener(
                    'mouseenter',
                    () => setActiveOption(
                        dropdown,
                        input,
                        index
                    )
                );

                return button;
            }

            function setActiveOption(
                dropdown,
                input,
                index
            ) {
                const options =
                    Array.from(
                        dropdown.querySelectorAll('[role="option"]')
                    );

                options.forEach((option, optionIndex) => {
                    option.classList.toggle(
                        'bg-blue-100',
                        optionIndex === index
                    );
                    option.classList.toggle(
                        'text-blue-900',
                        optionIndex === index
                    );
                });

                const active = options[index];
                if (active) {
                    input.setAttribute(
                        'aria-activedescendant',
                        active.id
                    );
                    active.scrollIntoView({ block: 'nearest' });
                } else {
                    input.removeAttribute(
                        'aria-activedescendant'
                    );
                }
            }

            function attachKeyboard(
                input,
                dropdown,
                renderAll,
                isEnabled = () => true
            ) {
                input.addEventListener('keydown', event => {
                    if (!isEnabled()) {
                        return;
                    }

                    if (event.key === 'Escape') {
                        if (!dropdown.classList.contains('hidden')) {
                            event.preventDefault();
                            closeDropdown(dropdown, input);
                        }
                        return;
                    }

                    if (!['ArrowDown', 'ArrowUp', 'Enter'].includes(event.key)) {
                        return;
                    }

                    const isOpen = !dropdown.classList.contains('hidden');
                    if (event.key === 'Enter' && !isOpen) {
                        return;
                    }

                    event.preventDefault();

                    if (!isOpen) {
                        renderAll();
                        openDropdown(dropdown, input);
                    }

                    const options =
                        Array.from(
                            dropdown.querySelectorAll('[role="option"]')
                        );
                    if (options.length === 0) {
                        return;
                    }

                    const current = options.findIndex(option =>
                        option.id === input.getAttribute('aria-activedescendant')
                    );

                    if (event.key === 'Enter') {
                        options[current]?.click();
                        return;
                    }

                    const next = current < 0
                        ? (event.key === 'ArrowDown' ? 0 : options.length - 1)
                        : (current + (event.key === 'ArrowDown' ? 1 : -1)
                            + options.length) % options.length;

                    setActiveOption(dropdown, input, next);
                });

                input.addEventListener('click', () => {
                    if (!isEnabled()) {
                        return;
                    }

                    if (dropdown.classList.contains('hidden')) {
                        renderAll();
                        openDropdown(dropdown, input);
                    }
                });

                input.addEventListener('blur', () => {
                    closeDropdown(dropdown, input);
                });
            }

            function filterItems(
                items,
                search
            ) {

                const query =
                    normalize(
                        search
                    );

                if (query === '') {
                    return items;
                }

                return items.filter(item => {

                    const text =
                        normalize(
                            [
                                item.code,
                                item.name,
                                item.text,
                                item.prefix,
                                item.variant,
                                item.display_name,
                            ].join(' ')
                        );

                    return text.includes(
                        query
                    );
                });
            }

            function renderLines(search = lineSearch.value) {

                lineOptions.replaceChildren();
                lineSearch.removeAttribute('aria-activedescendant');

                const filtered =
                    filterItems(
                        lines,
                        search
                    );

                if (filtered.length === 0) {

                    lineOptions.append(
                        emptyMessage(
                            lines.length === 0
                                ? 'Tidak ada Line aktif.'
                                : 'Line tidak ditemukan.'
                        )
                    );

                    return;
                }

                filtered.forEach((line, index) => {

                    lineOptions.append(
                        createOption(
                            line.code,
                            line.name,
                            () => {
                                selectLine(
                                    line,
                                    false
                                );
                            },
                            lineOptions,
                            lineSearch,
                            index,
                            String(line.id) === lineId.value
                        )
                    );
                });
            }

            function availableProductsFor(searchInput) {

                const oppositeId =
                    String(
                        searchInput === fromSearch
                            ? toId.value
                            : fromId.value
                    );

                if (oppositeId === '') {
                    return products;
                }

                return products.filter(
                    product =>
                        String(product.id) !== oppositeId
                );
            }

            function renderProducts(
                dropdown,
                searchInput,
                selectCallback,
                search = searchInput.value
            ) {

                dropdown.replaceChildren();
                searchInput.removeAttribute('aria-activedescendant');

                const availableProducts =
                    availableProductsFor(
                        searchInput
                    );

                const filtered =
                    filterItems(
                        availableProducts,
                        search
                    );

                if (filtered.length === 0) {

                    dropdown.append(
                        emptyMessage(
                            products.length === 0
                                ? 'Tidak ada Type untuk Line ini.'
                                : availableProducts.length === 0
                                    ? 'Tidak ada Type lain yang dapat dipilih.'
                                : 'Type tidak ditemukan.'
                        )
                    );

                    return;
                }

                filtered.forEach((product, index) => {

                    dropdown.append(
                        createOption(
                            product.code,
                            `CT ${product.cycle_time}`,
                            () => {
                                selectCallback(
                                    product
                                );
                            },
                            dropdown,
                            searchInput,
                            index,
                            String(product.id) === (searchInput === fromSearch
                                ? fromId.value
                                : toId.value)
                        )
                    );
                });
            }

            function isValidTime(value) {
                return /^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/
                    .test(String(value || ''));
            }

            function validateRetoolingTime() {
                if (
                    !isRetooling()
                    || scanTime.value === ''
                    || isValidTime(scanTime.value)
                ) {
                    scanTime.setCustomValidity('');
                    return true;
                }

                scanTime.setCustomValidity(
                    'Gunakan format Jam HH:mm yang valid, misalnya 08:30.'
                );

                return false;
            }

            function selectTimeSuggestion(value) {
                scanTime.value = value;
                scanTime.setCustomValidity('');

                closeDropdown(
                    retoolingTimeOptions,
                    scanTime
                );

                scanTime.dispatchEvent(
                    new Event('change', { bubbles: true })
                );
            }

            function renderTimeSuggestions(
                search = scanTime.value
            ) {
                retoolingTimeOptions.replaceChildren();
                scanTime.removeAttribute('aria-activedescendant');

                const query =
                    String(search || '');

                const filtered =
                    timeSuggestions.filter(
                        value => value.includes(query)
                    );

                if (filtered.length === 0) {
                    retoolingTimeOptions.append(
                        emptyMessage(
                            isValidTime(query)
                                ? `${query} dapat digunakan. Tekan Enter atau Tab.`
                                : 'Ketik Jam dengan format HH:mm.'
                        )
                    );

                    return;
                }

                filtered.forEach((value, index) => {
                    retoolingTimeOptions.append(
                        createOption(
                            value,
                            '',
                            () => selectTimeSuggestion(value),
                            retoolingTimeOptions,
                            scanTime,
                            index,
                            value === scanTime.value
                        )
                    );
                });
            }

            function syncTimeMode(retooling) {
                if (retooling) {
                    scanTime.type = 'text';
                    scanTime.placeholder =
                        'Ketik atau pilih Jam (HH:mm)...';
                    scanTime.autocomplete = 'off';
                    scanTime.setAttribute('role', 'combobox');
                    scanTime.setAttribute('aria-autocomplete', 'list');
                    scanTime.setAttribute(
                        'aria-controls',
                        'retoolingTimeOptions'
                    );
                    scanTime.setAttribute('aria-expanded', 'false');
                    retoolingTimeIndicator.classList.remove('hidden');
                    retoolingTimeIndicator.classList.add('flex');
                    retoolingTimeHint.classList.remove('hidden');

                    return;
                }

                closeDropdown(
                    retoolingTimeOptions,
                    scanTime
                );

                scanTime.type = 'time';
                scanTime.placeholder = '';
                scanTime.removeAttribute('autocomplete');
                scanTime.removeAttribute('role');
                scanTime.removeAttribute('aria-autocomplete');
                scanTime.removeAttribute('aria-controls');
                scanTime.removeAttribute('aria-expanded');
                scanTime.setCustomValidity('');
                retoolingTimeIndicator.classList.add('hidden');
                retoolingTimeIndicator.classList.remove('flex');
                retoolingTimeHint.classList.add('hidden');
            }

            function renderBrakeParts(
                search = brakePartSearch.value
            ) {

                brakePartOptions.replaceChildren();
                brakePartSearch.removeAttribute('aria-activedescendant');

                const filtered =
                    filterItems(
                        brakeParts,
                        search
                    );

                if (filtered.length === 0) {

                    brakePartOptions.append(
                        emptyMessage(
                            brakeParts.length === 0
                                ? 'Tidak ada Part Number Brake aktif.'
                                : 'Variant Part No tidak ditemukan.'
                        )
                    );

                    return;
                }

                filtered.forEach((partNumber, index) => {

                    brakePartOptions.append(
                        createOption(
                            partNumber.variant,
                            partNumber.prefix,
                            () => selectBrakePart(partNumber),
                            brakePartOptions,
                            brakePartSearch,
                            index,
                            String(partNumber.id) === brakePartId.value
                        )
                    );
                });
            }

            function resetBrakePart(
                preserveSearch = false,
                emitChange = true
            ) {

                selectedBrakePart = null;
                brakePartId.value = '';

                if (!preserveSearch) {
                    brakePartSearch.value = '';
                }

                delete brakePartSearch.dataset.displayName;
                brakePartPrefix.textContent = '-';
                brakePartPreview.textContent = '';
                brakePartPreview.classList.add('hidden');

                if (emitChange) {
                    brakePartId.dispatchEvent(
                        new Event('change', { bubbles: true })
                    );
                }
            }

            function selectBrakePart(
                partNumber
            ) {

                selectedBrakePart = partNumber;
                brakePartId.value = String(partNumber.id);
                brakePartSearch.value = partNumber.variant;
                brakePartSearch.dataset.displayName = partNumber.display_name;
                brakePartPrefix.textContent = partNumber.prefix;
                brakePartPreview.textContent =
                    `Part No tersimpan: ${partNumber.display_name}`;
                brakePartPreview.classList.remove('hidden');

                closeDropdown(
                    brakePartOptions,
                    brakePartSearch
                );

                brakePartId.dispatchEvent(
                    new Event('change', { bubbles: true })
                );
            }

            async function loadBrakeParts() {

                if (
                    !brakeMasterRoot
                    || brakePartsLoaded
                    || brakePartsLoading
                ) {
                    return;
                }

                brakePartsLoading = true;
                brakePartSearch.disabled = true;
                brakePartSearch.placeholder =
                    'Memuat Variant Part No...';

                try {

                    const response =
                        await fetch(
                            brakePartsUrl,
                            {
                                headers: {
                                    'Accept':
                                        'application/json',
                                    'X-Requested-With':
                                        'XMLHttpRequest',
                                },

                                credentials:
                                    'same-origin',
                            }
                        );

                    if (!response.ok) {
                        throw new Error(
                            `HTTP ${response.status}`
                        );
                    }

                    const payload =
                        await response.json();

                    brakeParts =
                        Array.isArray(payload.results)
                            ? payload.results
                            : [];

                    brakePartsLoaded = true;
                    brakePartSearch.disabled =
                        scanType?.value !== BRAKE_SCAN;
                    brakePartSearch.placeholder =
                        'Cari atau pilih Variant Part No...';

                    const oldPartNumber =
                        brakeParts.find(
                            partNumber =>
                                String(partNumber.id)
                                === oldBrakePartId
                        );

                    if (oldPartNumber) {
                        selectBrakePart(oldPartNumber);
                    } else {
                        resetBrakePart();
                    }

                } catch (error) {

                    console.error(
                        'Gagal memuat Part Number Brake.',
                        error
                    );

                    brakePartSearch.disabled = true;
                    brakePartSearch.placeholder =
                        'Gagal memuat Variant Part No';
                } finally {
                    brakePartsLoading = false;
                }
            }

            function resetFrom() {

                selectedFrom = null;

                fromId.value = '';
                fromSearch.value = '';

                fromCycleTime.textContent =
                    'Cycle Time: -';
            }

            function resetTo() {

                selectedTo = null;

                toId.value = '';
                toSearch.value = '';

                toCycleTime.textContent =
                    'Cycle Time: -';
            }

            function selectFrom(
                product
            ) {

                if (
                    String(toId.value)
                    === String(product.id)
                ) {
                    resetTo();
                }

                selectedFrom =
                    product;

                fromId.value =
                    String(product.id);

                fromSearch.value =
                    product.code;

                fromCycleTime.textContent =
                    `Cycle Time: ${product.cycle_time}`;

                closeDropdown(
                    fromOptions,
                    fromSearch
                );
            }

            function selectTo(
                product
            ) {

                if (
                    String(fromId.value)
                    === String(product.id)
                ) {
                    resetFrom();
                }

                selectedTo =
                    product;

                toId.value =
                    String(product.id);

                toSearch.value =
                    product.code;

                toCycleTime.textContent =
                    `Cycle Time: ${product.cycle_time}`;

                closeDropdown(
                    toOptions,
                    toSearch
                );
            }

            async function loadProducts(
                productionLineId,
                restoreOld = false
            ) {
                const requestId = ++productRequestId;

                products = [];

                fromOptions.replaceChildren();
                toOptions.replaceChildren();

                if (!productionLineId) {

                    fromSearch.disabled = true;
                    toSearch.disabled = true;

                    fromSearch.placeholder =
                        'Pilih Line terlebih dahulu';

                    toSearch.placeholder =
                        'Pilih Line terlebih dahulu';

                    return;
                }

                fromSearch.disabled = true;
                toSearch.disabled = true;

                fromSearch.placeholder =
                    'Memuat Type...';

                toSearch.placeholder =
                    'Memuat Type...';

                try {

                    const response =
                        await fetch(
                            `${productsUrlBase}/${encodeURIComponent(productionLineId)}/products`,
                            {
                                headers: {
                                    'Accept':
                                        'application/json',
                                    'X-Requested-With':
                                        'XMLHttpRequest',
                                },

                                credentials:
                                    'same-origin',
                            }
                        );

                    if (!response.ok) {

                        throw new Error(
                            `HTTP ${response.status}`
                        );
                    }

                    const payload =
                        await response.json();
                    if (
                        requestId !== productRequestId
                        || String(lineId.value) !== String(productionLineId)
                    ) {
                        return;
                    }

                    products =
                        Array.isArray(
                            payload.results
                        )
                            ? payload.results
                            : [];

                    fromSearch.disabled = false;
                    toSearch.disabled = false;

                    fromSearch.placeholder =
                        'Cari atau pilih Dari Type...';

                    toSearch.placeholder =
                        'Cari atau pilih Ke Type...';

                    if (restoreOld) {

                        const oldFrom =
                            products.find(
                                product =>
                                    String(product.id)
                                    === oldFromId
                            );

                        const oldTo =
                            products.find(
                                product =>
                                    String(product.id)
                                    === oldToId
                            );

                        if (oldFrom) {
                            selectFrom(
                                oldFrom
                            );
                        } else {
                            resetFrom();
                        }

                        if (
                            oldTo
                            && String(oldTo.id)
                                !== String(fromId.value)
                        ) {
                            selectTo(
                                oldTo
                            );
                        } else {
                            resetTo();
                        }
                    }

                } catch (error) {
                    if (
                        requestId !== productRequestId
                        || String(lineId.value) !== String(productionLineId)
                    ) {
                        return;
                    }

                    console.error(
                        'Gagal memuat Type Retooling.',
                        error
                    );

                    fromSearch.disabled = true;
                    toSearch.disabled = true;

                    fromSearch.placeholder =
                        'Gagal memuat Type';

                    toSearch.placeholder =
                        'Gagal memuat Type';
                }
            }

            async function selectLine(
                line,
                restoreOld = false
            ) {

                const previous =
                    String(
                        selectedLine?.id
                        || lineId.value
                        || ''
                    );

                selectedLine =
                    line;

                lineId.value =
                    String(line.id);

                lineSearch.value =
                    line.code;

                closeDropdown(
                    lineOptions,
                    lineSearch
                );

                if (
                    !restoreOld
                    && previous !== String(line.id)
                ) {

                    resetFrom();
                    resetTo();
                }

                await loadProducts(
                    line.id,
                    restoreOld
                );
            }

            async function loadLines() {

                if (linesLoaded || linesLoading) {
                    return;
                }
                linesLoading = true;

                lineSearch.disabled = true;

                lineSearch.placeholder =
                    'Memuat Line...';

                try {

                    const response =
                        await fetch(
                            linesUrl,
                            {
                                headers: {
                                    'Accept':
                                        'application/json',
                                    'X-Requested-With':
                                        'XMLHttpRequest',
                                },

                                credentials:
                                    'same-origin',
                            }
                        );

                    if (!response.ok) {

                        throw new Error(
                            `HTTP ${response.status}`
                        );
                    }

                    const payload =
                        await response.json();

                    lines =
                        Array.isArray(
                            payload.results
                        )
                            ? payload.results
                            : [];

                    linesLoaded = true;

                    lineSearch.disabled =
                        !isRetooling()
                        && !isProduction();

                    lineSearch.placeholder =
                        'Cari atau pilih Line...';

                    if (oldLineId !== '') {

                        const oldLine =
                            lines.find(
                                line =>
                                    String(line.id)
                                    === oldLineId
                            );

                        if (oldLine) {

                            await selectLine(
                                oldLine,
                                true
                            );
                        } else {
                            lineId.value = '';
                            resetFrom();
                            resetTo();
                            await loadProducts(null);
                        }
                    } else {
                        resetFrom();
                        resetTo();
                        await loadProducts(null);
                    }

                } catch (error) {

                    console.error(
                        'Gagal memuat Line produksi.',
                        error
                    );

                    lineSearch.disabled = true;

                    lineSearch.placeholder =
                        'Gagal memuat Line';
                } finally {
                    linesLoading = false;
                }
            }

            function clearDirtyProductionState() {
                if (shift) {
                    shift.value = '';
                    shift.required = false;
                    shift.setAttribute('aria-required', 'false');
                }

                if (scanTime) {
                    scanTime.value = '';
                }

                if (manualLine) {
                    manualLine.value = '';
                }

                if (lineId) {
                    lineId.value = '';
                    lineId.required = false;
                }

                if (lineSearch) {
                    lineSearch.value = '';
                }

                if (fromId) {
                    fromId.value = '';
                }

                if (fromSearch) {
                    fromSearch.value = '';
                }

                if (toId) {
                    toId.value = '';
                }

                if (toSearch) {
                    toSearch.value = '';
                }

                if (fromCycleTime) {
                    fromCycleTime.textContent = 'Cycle Time: -';
                }

                if (toCycleTime) {
                    toCycleTime.textContent = 'Cycle Time: -';
                }

                if (brakePartId) {
                    brakePartId.value = '';
                    brakePartId.required = false;
                    brakePartId.disabled = true;
                }

                if (brakePartSearch) {
                    brakePartSearch.value = '';
                    brakePartSearch.disabled = true;
                    delete brakePartSearch.dataset.displayName;
                }

                if (brakePartPrefix) {
                    brakePartPrefix.textContent = '-';
                }

                if (brakePartPreview) {
                    brakePartPreview.textContent = '';
                    brakePartPreview.classList.add('hidden');
                }

                selectedLine = null;
                selectedFrom = null;
                selectedTo = null;
                selectedBrakePart = null;
                products = [];
                fromOptions.replaceChildren();
                toOptions.replaceChildren();
                brakePartOptions.replaceChildren();
            }

            function clearBrakePartState() {
                selectedBrakePart = null;
                brakePartId.value = '';
                brakePartSearch.value = '';
                delete brakePartSearch.dataset.displayName;
                brakePartPrefix.textContent = '-';
                brakePartPreview.textContent = '';
                brakePartPreview.classList.add('hidden');
                brakePartId.required = false;
                brakePartId.disabled = true;
                brakePartSearch.disabled = true;
                brakePartId.dispatchEvent(new Event('change', { bubbles: true }));
            }

            function syncMode() {

                const retooling =
                    isRetooling();

                const production =
                    isProduction();

                const masterLineMode =
                    retooling
                    || production;

                if (!retooling && !production && !scanType?.value) {
                    clearDirtyProductionState();
                }

                if (!retooling) {
                    if (shift) {
                        shift.value = '';
                    }
                }

                if (!masterLineMode) {
                    if (manualLine) {
                        manualLine.value = '';
                    }
                    if (lineId) {
                        lineId.value = '';
                    }
                    if (lineSearch) {
                        lineSearch.value = '';
                    }
                    if (fromId) {
                        fromId.value = '';
                    }
                    if (toId) {
                        toId.value = '';
                    }
                    if (fromSearch) {
                        fromSearch.value = '';
                    }
                    if (toSearch) {
                        toSearch.value = '';
                    }
                }

                if (!retooling && !production) {
                    if (scanTime) {
                        scanTime.value = '';
                    }
                }

                syncTimeMode(retooling);

                if (!retooling && !production) {
                    clearDirtyProductionState();
                } else if (production && scanType?.value !== BRAKE_SCAN) {
                    clearBrakePartState();
                }

                /*
                 * Shift tetap tersedia untuk workflow lama, tetapi diwajibkan
                 * oleh backend hanya ketika Retooling.
                 */
                shift.required =
                    retooling;

                shift.setAttribute(
                    'aria-required',
                    String(retooling)
                );

                /*
                 * Line manual dipertahankan untuk Dokumen QC Lainnya.
                 */
                manualLineField.classList.toggle(
                    'hidden',
                    masterLineMode
                );

                manualLine.disabled =
                    masterLineMode;

                /*
                 * Line master dipakai Retooling, Axle, dan Brake.
                 */
                retoolingLineField.classList.toggle(
                    'hidden',
                    !masterLineMode
                );

                lineId.disabled =
                    !masterLineMode;

                lineId.required =
                    retooling;

                lineSearch.disabled =
                    !masterLineMode
                    || !linesLoaded;

                lineSearch.setAttribute(
                    'aria-required',
                    String(retooling)
                );

                lineRequiredIndicator.classList.toggle(
                    'hidden',
                    !retooling
                );

                lineOptionalIndicator.classList.toggle(
                    'hidden',
                    !production
                );

                lineClearButton.classList.toggle(
                    'hidden',
                    !production
                );

                lineClearButton.disabled =
                    !production;

                /*
                 * Input Type manual tetap untuk Dokumen QC Lainnya.
                 * Production mengikuti perilaku lama: disembunyikan.
                 */
                const showManualTypes =
                    !retooling
                    && !production;

                manualTypeFields.classList.toggle(
                    'hidden',
                    !showManualTypes
                );

                manualFrom.disabled =
                    !showManualTypes;

                manualTo.disabled =
                    !showManualTypes;

                /*
                 * Type master hanya Retooling.
                 */
                masterTypeFields.classList.toggle(
                    'hidden',
                    !retooling
                );

                fromId.disabled =
                    !retooling;

                toId.disabled =
                    !retooling;

                fromId.required =
                    retooling;

                toId.required =
                    retooling;

                fromSearch.setAttribute(
                    'aria-required',
                    String(retooling)
                );

                toSearch.setAttribute(
                    'aria-required',
                    String(retooling)
                );

                if (masterLineMode) {

                    loadLines();

                    if (
                        retooling
                        && !lineId.value
                    ) {

                        fromSearch.disabled = true;
                        toSearch.disabled = true;
                    }

                } else {

                    closeDropdown(
                        lineOptions,
                        lineSearch
                    );

                    closeDropdown(
                        fromOptions,
                        fromSearch
                    );

                    closeDropdown(
                        toOptions,
                        toSearch
                    );
                }

                const brake =
                    scanType?.value === BRAKE_SCAN;

                if (brakePartId && brakePartSearch) {
                    brakePartId.disabled = !brake;
                    brakePartId.required = brake;
                    brakePartSearch.setAttribute(
                        'aria-required',
                        String(brake)
                    );

                    if (brake) {
                        brakePartSearch.disabled =
                            !brakePartsLoaded;
                        loadBrakeParts();
                    } else {
                        brakePartSearch.disabled = true;
                        closeDropdown(
                            brakePartOptions,
                            brakePartSearch
                        );
                    }
                }
            }

            lineSearch.addEventListener(
                'focus',
                () => {

                    renderLines('');

                    if (lineId.value) {
                        lineSearch.select();
                    }

                    openDropdown(
                        lineOptions,
                        lineSearch
                    );
                }
            );

            lineSearch.addEventListener(
                'input',
                () => {

                    if (
                        selectedLine
                        && lineSearch.value
                            !== selectedLine.code
                    ) {

                        selectedLine = null;

                        lineId.value = '';

                        resetFrom();
                        resetTo();

                        loadProducts(
                            null
                        );
                    }

                    renderLines();

                    openDropdown(
                        lineOptions,
                        lineSearch
                    );
                }
            );

            lineClearButton.addEventListener(
                'click',
                () => {

                    selectedLine = null;
                    lineId.value = '';
                    lineSearch.value = '';

                    resetFrom();
                    resetTo();
                    loadProducts(null);

                    closeDropdown(
                        lineOptions,
                        lineSearch
                    );
                }
            );

            scanTime.addEventListener(
                'focus',
                () => {
                    if (!isRetooling()) {
                        return;
                    }

                    renderTimeSuggestions('');

                    openDropdown(
                        retoolingTimeOptions,
                        scanTime
                    );
                }
            );

            scanTime.addEventListener(
                'input',
                () => {
                    if (!isRetooling()) {
                        return;
                    }

                    scanTime.setCustomValidity('');
                    renderTimeSuggestions();

                    openDropdown(
                        retoolingTimeOptions,
                        scanTime
                    );
                }
            );

            scanTime.addEventListener(
                'change',
                validateRetoolingTime
            );

            scanTime.addEventListener(
                'blur',
                validateRetoolingTime
            );

            scanTime.addEventListener(
                'keydown',
                event => {
                    if (
                        !isRetooling()
                        || event.key !== 'Enter'
                        || retoolingTimeOptions.classList.contains('hidden')
                        || scanTime.hasAttribute('aria-activedescendant')
                    ) {
                        return;
                    }

                    event.preventDefault();

                    if (validateRetoolingTime()) {
                        closeDropdown(
                            retoolingTimeOptions,
                            scanTime
                        );
                    } else {
                        scanTime.reportValidity();
                    }
                }
            );

            brakePartSearch.addEventListener(
                'focus',
                () => {

                    renderBrakeParts('');

                    if (brakePartId.value) {
                        brakePartSearch.select();
                    }

                    openDropdown(
                        brakePartOptions,
                        brakePartSearch
                    );
                }
            );

            brakePartSearch.addEventListener(
                'input',
                () => {

                    if (
                        selectedBrakePart
                        && brakePartSearch.value
                            !== selectedBrakePart.variant
                    ) {
                        resetBrakePart(true);
                    }

                    renderBrakeParts();

                    openDropdown(
                        brakePartOptions,
                        brakePartSearch
                    );
                }
            );

            fromSearch.addEventListener(
                'focus',
                () => {

                    renderProducts(
                        fromOptions,
                        fromSearch,
                        selectFrom,
                        ''
                    );

                    if (fromId.value) {
                        fromSearch.select();
                    }

                    openDropdown(
                        fromOptions,
                        fromSearch
                    );
                }
            );

            fromSearch.addEventListener(
                'input',
                () => {

                    if (
                        selectedFrom
                        && fromSearch.value
                            !== selectedFrom.code
                    ) {

                        selectedFrom = null;

                        fromId.value = '';

                        fromCycleTime.textContent =
                            'Cycle Time: -';
                    }

                    renderProducts(
                        fromOptions,
                        fromSearch,
                        selectFrom
                    );

                    openDropdown(
                        fromOptions,
                        fromSearch
                    );
                }
            );

            toSearch.addEventListener(
                'focus',
                () => {

                    renderProducts(
                        toOptions,
                        toSearch,
                        selectTo,
                        ''
                    );

                    if (toId.value) {
                        toSearch.select();
                    }

                    openDropdown(
                        toOptions,
                        toSearch
                    );
                }
            );

            toSearch.addEventListener(
                'input',
                () => {

                    if (
                        selectedTo
                        && toSearch.value
                            !== selectedTo.code
                    ) {

                        selectedTo = null;

                        toId.value = '';

                        toCycleTime.textContent =
                            'Cycle Time: -';
                    }

                    renderProducts(
                        toOptions,
                        toSearch,
                        selectTo
                    );

                    openDropdown(
                        toOptions,
                        toSearch
                    );
                }
            );

            attachKeyboard(
                lineSearch,
                lineOptions,
                () => renderLines('')
            );

            attachKeyboard(
                fromSearch,
                fromOptions,
                () => renderProducts(fromOptions, fromSearch, selectFrom, '')
            );

            attachKeyboard(
                toSearch,
                toOptions,
                () => renderProducts(toOptions, toSearch, selectTo, '')
            );

            attachKeyboard(
                brakePartSearch,
                brakePartOptions,
                () => renderBrakeParts('')
            );

            attachKeyboard(
                scanTime,
                retoolingTimeOptions,
                () => renderTimeSuggestions(''),
                () => isRetooling()
            );

            document.addEventListener(
                'click',
                event => {

                    if (
                        !lineSearch.contains(event.target)
                        && !lineOptions.contains(event.target)
                    ) {

                        closeDropdown(
                            lineOptions,
                            lineSearch
                        );
                    }

                    if (
                        !fromSearch.contains(event.target)
                        && !fromOptions.contains(event.target)
                    ) {

                        closeDropdown(
                            fromOptions,
                            fromSearch
                        );
                    }

                    if (
                        !toSearch.contains(event.target)
                        && !toOptions.contains(event.target)
                    ) {

                        closeDropdown(
                            toOptions,
                            toSearch
                        );
                    }

                    if (
                        !brakePartSearch.contains(event.target)
                        && !brakePartOptions.contains(event.target)
                    ) {

                        closeDropdown(
                            brakePartOptions,
                            brakePartSearch
                        );
                    }

                    if (
                        !scanTime.contains(event.target)
                        && !retoolingTimeOptions.contains(event.target)
                    ) {

                        closeDropdown(
                            retoolingTimeOptions,
                            scanTime
                        );
                    }
                }
            );

            document.addEventListener(
                'keydown',
                event => {

                    if (event.key !== 'Escape') {
                        return;
                    }

                    closeDropdown(
                        lineOptions,
                        lineSearch
                    );

                    closeDropdown(
                        fromOptions,
                        fromSearch
                    );

                    closeDropdown(
                        toOptions,
                        toSearch
                    );

                    closeDropdown(
                        brakePartOptions,
                        brakePartSearch
                    );

                    closeDropdown(
                        retoolingTimeOptions,
                        scanTime
                    );
                }
            );

            scanType?.addEventListener(
                'change',
                syncMode
            );

            syncMode();
        })();
    </script>

</x-app-layout>x

