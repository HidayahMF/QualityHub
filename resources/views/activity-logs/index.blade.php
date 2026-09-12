<x-app-layout>

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <section
        class="flex flex-col
               xl:flex-row
               xl:items-center
               xl:justify-between
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

                Keamanan & Audit

            </div>


            <h1
                class="text-3xl
                       sm:text-4xl
                       font-bold
                       tracking-tight
                       text-slate-900"
            >
                Activity Log
            </h1>


            <p
                class="mt-2
                       text-sm
                       sm:text-base
                       text-slate-500"
            >
                Pantau aktivitas penting yang dilakukan di dalam
                BMC QualityHub.
            </p>

        </div>


        <div
            class="inline-flex
                   items-center
                   gap-2

                   rounded-xl

                   border
                   border-emerald-200

                   bg-emerald-50

                   px-4
                   py-2.5

                   text-sm
                   font-semibold
                   text-emerald-700"
        >

            <span
                class="relative
                       flex
                       h-2.5
                       w-2.5"
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
                           h-2.5
                           w-2.5
                           rounded-full
                           bg-emerald-500"
                ></span>

            </span>

            Jejak Audit Aktif

        </div>

    </section>



    {{-- ========================================================= --}}
    {{-- STATISTICS --}}
    {{-- ========================================================= --}}

    <section
        class="grid
               grid-cols-1
               sm:grid-cols-2
               xl:grid-cols-4
               gap-5
               mb-6"
    >


        {{-- TOTAL --}}

        <div
            class="bg-white
                   rounded-2xl
                   border border-slate-200/70
                   shadow-sm
                   p-5"
        >

            <div
                class="flex
                       items-center
                       justify-between"
            >

                <div>

                    <p
                        class="text-sm
                               font-medium
                               text-slate-500"
                    >
                        Total Aktivitas
                    </p>

                    <p
                        class="mt-2
                               text-3xl
                               font-bold
                               text-slate-900"
                    >
                        {{ number_format($statistics['total']) }}
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
                        <path d="M12 8v4l3 3"/>
                        <circle cx="12" cy="12" r="9"/>
                    </svg>

                </div>

            </div>

        </div>



        {{-- TODAY --}}

        <div
            class="bg-white
                   rounded-2xl
                   border border-slate-200/70
                   shadow-sm
                   p-5"
        >

            <div
                class="flex
                       items-center
                       justify-between"
            >

                <div>

                    <p
                        class="text-sm
                               font-medium
                               text-slate-500"
                    >
                        Hari Ini
                    </p>

                    <p
                        class="mt-2
                               text-3xl
                               font-bold
                               text-emerald-700"
                    >
                        {{ number_format($statistics['today']) }}
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
                        <path d="M8 2v4"/>
                        <path d="M16 2v4"/>
                        <rect
                            width="18"
                            height="18"
                            x="3"
                            y="4"
                            rx="2"
                        />
                        <path d="M3 10h18"/>
                    </svg>

                </div>

            </div>

        </div>



        {{-- DOCUMENT ACTIVITY --}}

        <div
            class="bg-white
                   rounded-2xl
                   border border-slate-200/70
                   shadow-sm
                   p-5"
        >

            <div
                class="flex
                       items-center
                       justify-between"
            >

                <div>

                    <p
                        class="text-sm
                               font-medium
                               text-slate-500"
                    >
                        Aktivitas Dokumen
                    </p>

                    <p
                        class="mt-2
                               text-3xl
                               font-bold
                               text-indigo-700"
                    >
                        {{ number_format(
                            $statistics['document_activity']
                        ) }}
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



        {{-- USER MANAGEMENT --}}

        <div
            class="bg-white
                   rounded-2xl
                   border border-slate-200/70
                   shadow-sm
                   p-5"
        >

            <div
                class="flex
                       items-center
                       justify-between"
            >

                <div>

                    <p
                        class="text-sm
                               font-medium
                               text-slate-500"
                    >
                        Aktivitas Akun
                    </p>

                    <p
                        class="mt-2
                               text-3xl
                               font-bold
                               text-amber-700"
                    >
                        {{ number_format(
                            $statistics['user_management']
                        ) }}
                    </p>

                </div>


                <div
                    class="w-12
                           h-12
                           rounded-2xl
                           bg-amber-50
                           text-amber-700
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
                            d="M16 21v-2
                               a4 4 0 0 0-4-4H6
                               a4 4 0 0 0-4 4v2"
                        />
                        <circle cx="9" cy="7" r="4"/>
                        <path d="m17 11 2 2 4-4"/>
                    </svg>

                </div>

            </div>

        </div>

    </section>



    {{-- ========================================================= --}}
    {{-- FILTER PANEL --}}
    {{-- ========================================================= --}}

    <form
        method="GET"
        action="{{ route('activity-logs.index') }}"

        class="bg-white
               rounded-2xl
               border border-slate-200/70
               shadow-sm
               p-5
               mb-6"
    >

        <div
            class="flex
                   items-center
                   justify-between
                   gap-4
                   mb-5"
        >

            <div>

                <h2
                    class="font-bold
                           text-slate-900"
                >
                    Pencarian & Filter Audit
                </h2>

                <p
                    class="mt-1
                           text-sm
                           text-slate-500"
                >
                    Temukan aktivitas berdasarkan pengguna, aksi, dokumen,
                    alamat IP, atau tanggal.
                </p>

            </div>


            @if(request()->hasAny([
                'search',
                'user_id',
                'action',
                'document_id',
                'ip_address',
                'date_from',
                'date_to'
            ]))

                <a
                    href="{{ route('activity-logs.index') }}"

                    class="hidden
                           sm:inline-flex
                           text-xs
                           font-semibold
                           text-red-600
                           hover:text-red-700"
                >
                    Hapus semua filter
                </a>

            @endif

        </div>



        <div
            class="grid
                   md:grid-cols-2
                   xl:grid-cols-4
                   gap-4"
        >


            {{-- GLOBAL SEARCH --}}

            <div class="md:col-span-2">

                <label
                    for="search"
                    class="block
                           mb-2
                           text-xs
                           font-semibold
                           text-slate-500"
                >
                    Pencarian
                </label>


                <div class="relative">

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
                            width="18"
                            height="18"
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
                        id="search"
                        type="text"
                        name="search"

                        value="{{ request('search') }}"

                        placeholder="Pengguna, dokumen, aksi, deskripsi..."

                        class="w-full
                               rounded-xl
                               border-slate-200
                               bg-slate-50
                               pl-11
                               pr-4
                               py-3
                               text-sm
                               focus:border-blue-500
                               focus:ring-blue-500"
                    >

                </div>

            </div>



            {{-- USER --}}

            <div>

                <label
                    for="user_id"
                    class="block
                           mb-2
                           text-xs
                           font-semibold
                           text-slate-500"
                >
                    Pengguna
                </label>


                <select
                    id="user_id"
                    name="user_id"

                    class="w-full
                           rounded-xl
                           border-slate-200
                           bg-slate-50
                           px-4
                           py-3
                           text-sm
                           focus:border-blue-500
                           focus:ring-blue-500"
                >

                    <option value="">
                        Semua Pengguna
                    </option>


                    @foreach($users as $user)

                        <option
                            value="{{ $user->id }}"

                            @selected(
                                (string) request('user_id')
                                === (string) $user->id
                            )
                        >
                            {{ $user->name }}
                        </option>

                    @endforeach

                </select>

            </div>



            {{-- ACTION --}}

            <div>

                <label
                    for="action"
                    class="block
                           mb-2
                           text-xs
                           font-semibold
                           text-slate-500"
                >
                    Aksi
                </label>


                @php
                    /*
                    |--------------------------------------------------------------------------
                    | Label aksi Activity Log
                    |--------------------------------------------------------------------------
                    | Nilai teknis/raw action TIDAK diubah.
                    | Yang diubah hanya label tampilan agar konsisten Bahasa Indonesia.
                    */
                    $actionLabels = [
                        'activate_category' => 'Aktifkan Kategori',
                        'activate_department' => 'Aktifkan Departemen',
                        'activate_user' => 'Aktifkan Pengguna',
                        'add_favorite' => 'Tambah Favorit',
                        'approve_user' => 'Setujui Pengguna',
                        'archive_document' => 'Arsipkan Dokumen',
                        'create_category' => 'Tambah Kategori',
                        'create_department' => 'Tambah Departemen',
                        'deactivate_category' => 'Nonaktifkan Kategori',
                        'deactivate_department' => 'Nonaktifkan Departemen',
                        'deactivate_user' => 'Nonaktifkan Pengguna',
                        'download_document' => 'Unduh Dokumen',
                        'download_document_revision' => 'Unduh Revisi Dokumen',
                        'open_document' => 'Buka Dokumen',
                        'open_document_revision' => 'Buka Revisi Dokumen',
                        'reject_user' => 'Tolak Pengguna',
                        'remove_favorite' => 'Hapus Favorit',
                        'restore_document' => 'Pulihkan Dokumen',
                        'update_category' => 'Perbarui Kategori',
                        'update_department' => 'Perbarui Departemen',
                        'update_document' => 'Perbarui Dokumen',
                        'upload_document' => 'Unggah Dokumen',
                        'upload_document_revision' => 'Unggah Revisi Dokumen',
                        'view_document' => 'Lihat Dokumen',
                    ];

                    $sortedActions = collect($actions)
                        ->sortBy(
                            fn ($action) => strtolower(
                                $actionLabels[$action]
                                ?? ucwords(str_replace('_', ' ', $action))
                            )
                        )
                        ->values();
                @endphp


                <select
                    id="action"
                    name="action"

                    class="w-full
                           rounded-xl
                           border-slate-200
                           bg-slate-50
                           px-4
                           py-3
                           text-sm
                           focus:border-blue-500
                           focus:ring-blue-500"
                >

                    <option value="">
                        Semua Aksi
                    </option>


                    @foreach($sortedActions as $action)

                        <option
                            value="{{ $action }}"

                            @selected(
                                request('action') === $action
                            )
                        >
                            {{ $actionLabels[$action]
                                ?? ucwords(str_replace('_', ' ', $action))
                            }}
                        </option>

                    @endforeach

                </select>

            </div>



            {{-- DOCUMENT --}}

            <div>

                <label
                    for="document_id"
                    class="block
                           mb-2
                           text-xs
                           font-semibold
                           text-slate-500"
                >
                    Dokumen
                </label>


                <select
                    id="document_id"
                    name="document_id"

                    class="w-full
                           rounded-xl
                           border-slate-200
                           bg-slate-50
                           px-4
                           py-3
                           text-sm
                           focus:border-blue-500
                           focus:ring-blue-500"
                >

                    <option value="">
                        Semua Dokumen
                    </option>


                    @foreach($documents as $document)

                        <option
                            value="{{ $document->id }}"

                            @selected(
                                (string) request('document_id')
                                === (string) $document->id
                            )
                        >
                            {{ $document->title }}
                        </option>

                    @endforeach

                </select>

            </div>



            {{-- IP ADDRESS --}}

            <div>

                <label
                    for="ip_address"
                    class="block
                           mb-2
                           text-xs
                           font-semibold
                           text-slate-500"
                >
                    Alamat IP
                </label>


                <input
                    id="ip_address"
                    type="text"
                    name="ip_address"

                    value="{{ request('ip_address') }}"

                    placeholder="Contoh: 127.0.0.1"

                    class="w-full
                           rounded-xl
                           border-slate-200
                           bg-slate-50
                           px-4
                           py-3
                           text-sm
                           focus:border-blue-500
                           focus:ring-blue-500"
                >

            </div>



            {{-- DATE FROM --}}

            <div>

                <label
                    for="date_from"
                    class="block
                           mb-2
                           text-xs
                           font-semibold
                           text-slate-500"
                >
                    Tanggal Mulai
                </label>


                <input
                    id="date_from"
                    type="date"
                    name="date_from"

                    value="{{ request('date_from') }}"

                    class="w-full
                           rounded-xl
                           border-slate-200
                           bg-slate-50
                           px-4
                           py-3
                           text-sm
                           focus:border-blue-500
                           focus:ring-blue-500"
                >

            </div>



            {{-- DATE TO --}}

            <div>

                <label
                    for="date_to"
                    class="block
                           mb-2
                           text-xs
                           font-semibold
                           text-slate-500"
                >
                    Tanggal Akhir
                </label>


                <input
                    id="date_to"
                    type="date"
                    name="date_to"

                    value="{{ request('date_to') }}"

                    class="w-full
                           rounded-xl
                           border-slate-200
                           bg-slate-50
                           px-4
                           py-3
                           text-sm
                           focus:border-blue-500
                           focus:ring-blue-500"
                >

            </div>

        </div>



        {{-- BUTTONS --}}

        <div
            class="mt-5
                   flex
                   flex-wrap
                   gap-3"
        >

            <button
                type="submit"

                class="inline-flex
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


            <a
                href="{{ route('activity-logs.index') }}"

                class="inline-flex
                       items-center
                       justify-center

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
                Atur Ulang
            </a>

        </div>

    </form>



    {{-- ========================================================= --}}
    {{-- ACTIVITY TABLE --}}
    {{-- ========================================================= --}}

    <section
        class="bg-white
               rounded-2xl
               border border-slate-200/70
               shadow-sm
               overflow-hidden"
    >


        {{-- TABLE HEADER --}}

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
                   gap-2"
        >

            <div>

                <h2
                    class="font-bold
                           text-slate-900"
                >
                    Catatan Audit
                </h2>

                <p
                    class="mt-1
                           text-sm
                           text-slate-500"
                >
                    {{ number_format($logs->total()) }}
                    catatan aktivitas ditemukan.
                </p>

            </div>


            <div
                class="text-xs
                       text-slate-400"
            >
                Aktivitas terbaru ditampilkan terlebih dahulu
            </div>

        </div>



        @if($logs->count())


            <div class="overflow-x-auto">

                <table class="w-full text-left">


                    {{-- TABLE HEADER --}}

                    <thead class="bg-slate-50/80">

                        <tr
                            class="text-[11px]
                                   uppercase
                                   tracking-wider
                                   text-slate-500"
                        >

                            <th
                                class="px-6
                                       py-4
                                       font-semibold"
                            >
                                Pengguna
                            </th>

                            <th
                                class="px-6
                                       py-4
                                       font-semibold"
                            >
                                Aksi
                            </th>

                            <th
                                class="px-6
                                       py-4
                                       font-semibold"
                            >
                                Target / Dokumen
                            </th>

                            <th
                                class="px-6
                                       py-4
                                       font-semibold"
                            >
                                Alamat IP
                            </th>

                            <th
                                class="px-6
                                       py-4
                                       font-semibold"
                            >
                                Waktu
                            </th>

                            <th
                                class="px-6
                                       py-4
                                       font-semibold
                                       text-right"
                            >
                                Detail
                            </th>

                        </tr>

                    </thead>



                    <tbody
                        class="divide-y
                               divide-slate-100"
                    >


                        @foreach($logs as $log)

                            @php

                                $actionStyle = match($log->action) {
                                    'upload_document_revision' =>
                                        'bg-indigo-50 text-indigo-700',

                                    'open_document_revision' =>
                                        'bg-violet-50 text-violet-700',

                                    'download_document_revision' =>
                                        'bg-cyan-50 text-cyan-700',

                                    'update_document' =>
                                        'bg-blue-50 text-blue-700',

                                    'archive_document' =>
                                        'bg-amber-50 text-amber-700',

                                    'restore_document' =>
                                        'bg-emerald-50 text-emerald-700',

                                    'add_favorite' =>
                                        'bg-amber-50 text-amber-700',

                                    'remove_favorite' =>
                                        'bg-rose-50 text-rose-700',

                                    'upload_document' =>
                                        'bg-blue-50 text-blue-700',

                                    'view_document' =>
                                        'bg-indigo-50 text-indigo-700',

                                    'open_document' =>
                                        'bg-cyan-50 text-cyan-700',

                                    'download_document' =>
                                        'bg-emerald-50 text-emerald-700',

                                    'approve_user',
                                    'activate_user' =>
                                        'bg-emerald-50 text-emerald-700',

                                    'reject_user' =>
                                        'bg-red-50 text-red-700',

                                    'deactivate_user' =>
                                        'bg-amber-50 text-amber-700',

                                    'create_category',
                                    'create_department',
                                    'update_category',
                                    'update_department' =>
                                        'bg-blue-50 text-blue-700',

                                    'activate_category',
                                    'activate_department' =>
                                        'bg-emerald-50 text-emerald-700',

                                    'deactivate_category',
                                    'deactivate_department' =>
                                        'bg-amber-50 text-amber-700',

                                    default =>
                                        'bg-slate-100 text-slate-600',
                                };


                                $metadata = is_array($log->metadata)
                                    ? $log->metadata
                                    : [];

                            @endphp


                            <tr
                                x-data="{ expanded: false }"

                                class="hover:bg-slate-50/70
                                       transition"
                            >


                                {{-- USER --}}

                                <td
                                    class="px-6
                                           py-4
                                           align-top"
                                >

                                    <div
                                        class="flex
                                               items-center
                                               gap-3"
                                    >

                                        <div
                                            class="w-10
                                                   h-10
                                                   rounded-xl
                                                   bg-blue-100
                                                   text-blue-800
                                                   flex
                                                   items-center
                                                   justify-center
                                                   font-bold
                                                   flex-shrink-0"
                                        >

                                            {{ strtoupper(
                                                substr(
                                                    $log->user?->name
                                                        ?? 'S',
                                                    0,
                                                    1
                                                )
                                            ) }}

                                        </div>


                                        <div class="min-w-0">

                                            <p
                                                class="text-sm
                                                       font-semibold
                                                       text-slate-900"
                                            >
                                                {{ $log->user?->name ?? 'Sistem' }}
                                            </p>


                                            <p
                                                class="mt-1
                                                       text-xs
                                                       text-slate-400"
                                            >
                                                {{ $log->user?->email ?? 'Aktivitas sistem' }}
                                            </p>

                                        </div>

                                    </div>

                                </td>



                                {{-- ACTION --}}

                                <td
                                    class="px-6
                                           py-4
                                           align-top"
                                >

                                    <span
                                        class="inline-flex
                                               rounded-lg

                                               px-2.5
                                               py-1

                                               text-xs
                                               font-semibold

                                               {{ $actionStyle }}"
                                    >
                                        {{ $actionLabels[$log->action]
                                            ?? ucwords(str_replace('_', ' ', $log->action))
                                        }}
                                    </span>


                                    @if($log->description)

                                        @php
                                            $actorName = $log->user?->name ?? 'Sistem';
                                            $targetDocumentTitle =
                                                $log->document?->title
                                                ?? ($metadata['document_title'] ?? null);

                                            $revisionNumber =
                                                $metadata['new_revision']
                                                ?? $metadata['revision_number']
                                                ?? $metadata['current_revision']
                                                ?? null;

                                            $activityDescription = match($log->action) {
                                                'upload_document_revision' =>
                                                    $targetDocumentTitle
                                                        ? $actorName
                                                            . ' mengunggah Revisi '
                                                            . ($revisionNumber ?? '-')
                                                            . ' untuk dokumen "'
                                                            . $targetDocumentTitle
                                                            . '".'
                                                        : $actorName
                                                            . ' mengunggah revisi dokumen.',

                                                'open_document_revision' =>
                                                    $targetDocumentTitle
                                                        ? $actorName
                                                            . ' membuka Revisi '
                                                            . ($revisionNumber ?? '-')
                                                            . ' dari dokumen "'
                                                            . $targetDocumentTitle
                                                            . '".'
                                                        : $actorName
                                                            . ' membuka revisi dokumen.',

                                                'download_document_revision' =>
                                                    $targetDocumentTitle
                                                        ? $actorName
                                                            . ' mengunduh Revisi '
                                                            . ($revisionNumber ?? '-')
                                                            . ' dari dokumen "'
                                                            . $targetDocumentTitle
                                                            . '".'
                                                        : $actorName
                                                            . ' mengunduh revisi dokumen.',

                                                'upload_document' =>
                                                    $targetDocumentTitle
                                                        ? $actorName
                                                            . ' mengunggah dokumen "'
                                                            . $targetDocumentTitle
                                                            . '".'
                                                        : $actorName
                                                            . ' mengunggah dokumen.',

                                                'view_document' =>
                                                    $targetDocumentTitle
                                                        ? $actorName
                                                            . ' melihat dokumen "'
                                                            . $targetDocumentTitle
                                                            . '".'
                                                        : $actorName
                                                            . ' melihat dokumen.',

                                                'open_document' =>
                                                    $targetDocumentTitle
                                                        ? $actorName
                                                            . ' membuka dokumen "'
                                                            . $targetDocumentTitle
                                                            . '".'
                                                        : $actorName
                                                            . ' membuka dokumen.',

                                                'download_document' =>
                                                    $targetDocumentTitle
                                                        ? $actorName
                                                            . ' mengunduh dokumen "'
                                                            . $targetDocumentTitle
                                                            . '".'
                                                        : $actorName
                                                            . ' mengunduh dokumen.',

                                                'update_document' =>
                                                    $targetDocumentTitle
                                                        ? $actorName
                                                            . ' memperbarui metadata dokumen "'
                                                            . $targetDocumentTitle
                                                            . '".'
                                                        : $actorName
                                                            . ' memperbarui metadata dokumen.',

                                                'archive_document' =>
                                                    $targetDocumentTitle
                                                        ? $actorName
                                                            . ' mengarsipkan dokumen "'
                                                            . $targetDocumentTitle
                                                            . '".'
                                                        : $actorName
                                                            . ' mengarsipkan dokumen.',

                                                'restore_document' =>
                                                    $targetDocumentTitle
                                                        ? $actorName
                                                            . ' memulihkan dokumen "'
                                                            . $targetDocumentTitle
                                                            . '".'
                                                        : $actorName
                                                            . ' memulihkan dokumen.',

                                                'add_favorite' =>
                                                    $targetDocumentTitle
                                                        ? $actorName
                                                            . ' menambahkan dokumen "'
                                                            . $targetDocumentTitle
                                                            . '" ke Favorit.'
                                                        : $actorName
                                                            . ' menambahkan dokumen ke Favorit.',

                                                'remove_favorite' =>
                                                    $targetDocumentTitle
                                                        ? $actorName
                                                            . ' menghapus dokumen "'
                                                            . $targetDocumentTitle
                                                            . '" dari Favorit.'
                                                        : $actorName
                                                            . ' menghapus dokumen dari Favorit.',

                                                'approve_user' =>
                                                    $actorName
                                                        . ' menyetujui akun pengguna '
                                                        . ($metadata['target_name'] ?? '') . '.',

                                                'reject_user' =>
                                                    $actorName
                                                        . ' menolak akun pengguna '
                                                        . ($metadata['target_name'] ?? '') . '.',

                                                'activate_user' =>
                                                    $actorName
                                                        . ' mengaktifkan akun pengguna '
                                                        . ($metadata['target_name'] ?? '') . '.',

                                                'deactivate_user' =>
                                                    $actorName
                                                        . ' menonaktifkan akun pengguna '
                                                        . ($metadata['target_name'] ?? '') . '.',

                                                default =>
                                                    $log->description,
                                            };
                                        @endphp

                                        <p
                                            class="mt-2
                                                   max-w-[280px]
                                                   text-xs
                                                   leading-relaxed
                                                   text-slate-500"
                                        >
                                            {{ $activityDescription }}
                                        </p>

                                    @endif

                                </td>



                                {{-- DOCUMENT --}}

                                <td
                                    class="px-6
                                           py-4
                                           align-top"
                                >

                                    @if($log->document)

                                        <a
                                            href="{{ route(
                                                'documents.show',
                                                $log->document
                                            ) }}"

                                            class="block
                                                   max-w-[240px]
                                                   text-sm
                                                   font-semibold
                                                   text-blue-700
                                                   hover:text-blue-900
                                                   hover:underline"
                                        >
                                            {{ $log->document->title }}
                                        </a>


                                        <p
                                            class="mt-1
                                                   text-xs
                                                   text-slate-400"
                                        >
                                            {{ $log->document->document_code
                                                ?? 'Tidak ada kode dokumen'
                                            }}
                                        </p>


                                        @if(
                                            $log->document->category
                                            && $log->document->category->department
                                        )

                                            <p
                                                class="mt-1
                                                       text-[11px]
                                                       text-slate-400"
                                            >
                                                {{ $log->document->category->department->name }}
                                                ·
                                                {{ $log->document->category->name }}
                                            </p>

                                        @endif

                                    @elseif(
                                        isset(
                                            $metadata['target_name']
                                        )
                                    )

                                        <p
                                            class="text-sm
                                                   font-semibold
                                                   text-slate-700"
                                        >
                                            {{ $metadata['target_name'] }}
                                        </p>


                                        <p
                                            class="mt-1
                                                   text-xs
                                                   text-slate-400"
                                        >
                                            Akun Pengguna
                                        </p>

                                    @else

                                        <span
                                            class="text-sm
                                                   text-slate-400"
                                        >
                                            -
                                        </span>

                                    @endif

                                </td>



                                {{-- IP --}}

                                <td
                                    class="px-6
                                           py-4
                                           align-top"
                                >

                                    <code
                                        class="inline-flex
                                               rounded-lg
                                               bg-slate-100
                                               px-2.5
                                               py-1.5
                                               text-xs
                                               text-slate-600"
                                    >
                                        {{ $log->ip_address ?? '-' }}
                                    </code>

                                </td>



                                {{-- TIME --}}

                                <td
                                    class="px-6
                                           py-4
                                           align-top"
                                >

                                    <p
                                        class="text-sm
                                               font-semibold
                                               text-slate-700"
                                    >
                                        {{ $log->created_at->locale('id')->translatedFormat(
                                            'd M Y'
                                        ) }}
                                    </p>


                                    <p
                                        class="mt-1
                                               text-xs
                                               text-slate-400"
                                    >
                                        {{ $log->created_at->format(
                                            'H:i:s'
                                        ) }}
                                    </p>

                                </td>



                                {{-- DETAILS --}}

                                <td
                                    class="px-6
                                           py-4
                                           text-right
                                           align-top"
                                >

                                    <button
                                        type="button"

                                        @click="
                                            expanded = !expanded
                                        "

                                        class="inline-flex
                                               items-center
                                               gap-1.5

                                               rounded-lg

                                               px-3
                                               py-2

                                               text-xs
                                               font-semibold
                                               text-slate-600

                                               hover:bg-slate-100

                                               transition"
                                    >

                                        <span
                                            x-text="
                                                expanded
                                                    ? 'Sembunyikan'
                                                    : 'Detail'
                                            "
                                        ></span>


                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="14"
                                            height="14"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"

                                            class="transition-transform"

                                            :class="
                                                expanded
                                                    ? 'rotate-180'
                                                    : ''
                                            "
                                        >
                                            <path d="m6 9 6 6 6-6"/>
                                        </svg>

                                    </button>



                                    {{-- EXPANDED DETAIL --}}

                                    <div
                                        x-show="expanded"
                                        x-collapse

                                        class="mt-3

                                               min-w-[280px]

                                               rounded-xl

                                               border
                                               border-slate-200

                                               bg-slate-50

                                               p-4

                                               text-left"

                                        x-cloak
                                    >

                                        <p
                                            class="text-[11px]
                                                   uppercase
                                                   tracking-wider
                                                   font-semibold
                                                   text-slate-400"
                                        >
                                            Agen Pengguna
                                        </p>


                                        <p
                                            class="mt-1
                                                   text-xs
                                                   leading-relaxed
                                                   text-slate-600
                                                   break-all"
                                        >
                                            {{ $log->user_agent ?? '-' }}
                                        </p>



                                        @if(count($metadata))

                                            <div
                                                class="my-3
                                                       border-t
                                                       border-slate-200"
                                            ></div>


                                            <p
                                                class="text-[11px]
                                                       uppercase
                                                       tracking-wider
                                                       font-semibold
                                                       text-slate-400"
                                            >
                                                Metadata
                                            </p>


                                            <dl
                                                class="mt-2
                                                       space-y-2"
                                            >

                                                @foreach(
                                                    $metadata
                                                    as $key => $value
                                                )

                                                    <div>

                                                        <dt
                                                            class="text-[11px]
                                                                   font-semibold
                                                                   text-slate-500"
                                                        >
                                                            {{ ucwords(
                                                                str_replace(
                                                                    '_',
                                                                    ' ',
                                                                    $key
                                                                )
                                                            ) }}
                                                        </dt>


                                                        <dd
                                                            class="mt-0.5
                                                                   text-xs
                                                                   text-slate-700
                                                                   break-all"
                                                        >

                                                            @if(is_array($value))

                                                                {{ json_encode(
                                                                    $value
                                                                ) }}

                                                            @elseif(is_bool($value))

                                                                {{ $value
                                                                    ? 'ya'
                                                                    : 'tidak'
                                                                }}

                                                            @else

                                                                {{ $value ?? '-' }}

                                                            @endif

                                                        </dd>

                                                    </div>

                                                @endforeach

                                            </dl>

                                        @endif

                                    </div>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>



            {{-- PAGINATION --}}

            @if($logs->hasPages())

                <div
                    class="px-6
                           py-4
                           border-t
                           border-slate-100"
                >
                    {{ $logs->links() }}
                </div>

            @endif


        @else


            {{-- EMPTY STATE --}}

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
                           bg-slate-100
                           text-slate-400
                           flex
                           items-center
                           justify-center"
                >

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="27"
                        height="27"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M12 8v4l3 3"/>
                        <circle cx="12" cy="12" r="9"/>
                    </svg>

                </div>


                <h3
                    class="mt-5
                           text-lg
                           font-bold
                           text-slate-700"
                >
                    Tidak ada catatan aktivitas yang ditemukan
                </h3>


                <p
                    class="mt-2
                           text-sm
                           text-slate-400"
                >
                    Coba ubah filter pencarian yang dipilih.
                </p>


                <a
                    href="{{ route('activity-logs.index') }}"

                    class="mt-5
                           inline-flex
                           rounded-xl
                           bg-blue-800
                           px-5
                           py-2.5
                           text-sm
                           font-semibold
                           text-white
                           hover:bg-blue-900"
                >
                    Atur Ulang Filter
                </a>

            </div>

        @endif

    </section>

</x-app-layout>