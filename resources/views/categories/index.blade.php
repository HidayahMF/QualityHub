<x-app-layout>

    {{-- HEADER --}}
    <section
        class="flex flex-col
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
                    class="w-2 h-2
                           rounded-full
                           bg-blue-600"
                ></span>

                Konfigurasi Dokumen
            </div>

            <h1
                class="text-3xl
                       sm:text-4xl
                       font-bold
                       tracking-tight
                       text-slate-900"
            >
                Categories Management
            </h1>

            <p
                class="mt-2
                       text-sm
                       sm:text-base
                       text-slate-500"
            >
                Kelola kategori dokumen yang digunakan oleh
                BMC QualityHub.
            </p>

        </div>


        <a
            href="{{ route('categories.create') }}"

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
                <path d="M12 5v14"/>
                <path d="M5 12h14"/>
            </svg>

            Tambah Kategori
        </a>

    </section>


    {{-- SUCCESS --}}
    @php
        /*
        |--------------------------------------------------------------------------
        | Success Message Localization
        |--------------------------------------------------------------------------
        | Kompatibilitas untuk pesan legacy dari controller.
        | Jika controller sudah mengirim Bahasa Indonesia, pesan dibiarkan apa adanya.
        */
        $categorySuccessMessage = session('success');

        $categorySuccessMessage = match($categorySuccessMessage) {
            'Category successfully created.' =>
                'Kategori berhasil ditambahkan.',

            'Category successfully updated.' =>
                'Kategori berhasil diperbarui.',

            'Category successfully deactivated.' =>
                'Kategori berhasil dinonaktifkan.',

            'Category successfully activated.' =>
                'Kategori berhasil diaktifkan.',

            default =>
                $categorySuccessMessage,
        };
    @endphp

    @if(session('success'))

        <div
            class="mb-6
                   flex
                   items-center
                   gap-4
                   rounded-2xl
                   border
                   border-emerald-200
                   bg-emerald-50
                   px-5
                   py-4"
        >

            <div>
                <p class="text-sm font-bold text-emerald-900">
                    Berhasil
                </p>

                <p class="mt-1 text-sm text-emerald-700">
                    {{ $categorySuccessMessage }}
                </p>
            </div>


        </div>

    @endif


    @if($errors->has('category'))

        <div
            class="mb-6
                   rounded-2xl
                   border
                   border-red-200
                   bg-red-50
                   px-5
                   py-4"
            role="alert"
        >
            <p class="text-sm font-bold text-red-900">
                Tindakan tidak dapat diproses
            </p>

            <p class="mt-1 text-sm text-red-700">
                {{ $errors->first('category') }}
            </p>
        </div>

    @endif


    {{-- STATISTICS --}}
    <section
        class="qh-categories-stats
               grid
               grid-cols-1
               sm:grid-cols-2
               xl:grid-cols-4
               gap-5
               mb-6"
    >

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">
                Total Kategori
            </p>

            <p class="mt-2 text-3xl font-bold text-slate-900">
                {{ number_format($statistics['total']) }}
            </p>
        </div>


        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">
                Aktif
            </p>

            <p class="mt-2 text-3xl font-bold text-emerald-700">
                {{ number_format($statistics['active']) }}
            </p>
        </div>


        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">
                Tidak Aktif
            </p>

            <p class="mt-2 text-3xl font-bold text-slate-600">
                {{ number_format($statistics['inactive']) }}
            </p>
        </div>


        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">
                Digunakan oleh Dokumen
            </p>

            <p class="mt-2 text-3xl font-bold text-blue-700">
                {{ number_format($statistics['used']) }}
            </p>
        </div>

    </section>


    {{-- FILTER --}}
    <form
        method="GET"
        action="{{ route('categories.index') }}"

        class="qh-categories-filter
               mb-6
               bg-white
               rounded-2xl
               border
               border-slate-200
               shadow-sm
               p-5"
    >

        <div
            class="grid
                   md:grid-cols-2
                   xl:grid-cols-4
                   gap-3"
        >

            <div class="xl:col-span-2">

                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari kategori, departemen, slug..."

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


            <select
                name="department_id"

                class="rounded-xl
                       border-slate-200
                       bg-slate-50
                       px-4
                       py-3
                       text-sm"
            >

                <option value="">
                    Semua Departemen
                </option>

                @foreach($departments as $department)

                    <option
                        value="{{ $department->id }}"
                        @selected(
                            (string) request('department_id')
                            === (string) $department->id
                        )
                    >
                        {{ $department->name }}
                    </option>

                @endforeach

            </select>


            <select
                name="status"

                class="rounded-xl
                       border-slate-200
                       bg-slate-50
                       px-4
                       py-3
                       text-sm"
            >

                <option value="">
                    Semua Status
                </option>

                <option
                    value="active"
                    @selected(request('status') === 'active')
                >
                    Aktif
                </option>

                <option
                    value="inactive"
                    @selected(request('status') === 'inactive')
                >
                    Tidak Aktif
                </option>

            </select>

        </div>


        <div class="mt-4 flex gap-3">

            <button
                type="submit"

                class="rounded-xl
                       bg-blue-800
                       px-5
                       py-2.5
                       text-sm
                       font-semibold
                       text-white
                       hover:bg-blue-900"
            >
                Terapkan Filter
            </button>


            <a
                href="{{ route('categories.index') }}"

                class="rounded-xl
                       border
                       border-slate-200
                       px-5
                       py-2.5
                       text-sm
                       font-semibold
                       text-slate-600
                       hover:bg-slate-50"
            >
                Atur Ulang
            </a>

        </div>

    </form>


    {{-- TABLE --}}
    <section
        class="qh-categories-table
               bg-white
               rounded-2xl
               border
               border-slate-200
               shadow-sm
               overflow-hidden"
    >

        <div
            class="px-6
                   py-5
                   border-b
                   border-slate-100"
        >
            <h2 class="font-bold text-slate-900">
                Kategori Dokumen
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                {{ $categories->total() }} kategori ditemukan.
            </p>
        </div>


        @if($categories->count())

            <div class="overflow-x-auto">

                <table class="w-full text-left">

                    <thead class="bg-slate-50">

                        <tr
                            class="text-[11px]
                                   uppercase
                                   tracking-wider
                                   text-slate-500"
                        >
                            <th class="px-6 py-4">
                                Kategori
                            </th>

                            <th class="px-6 py-4">
                                Departemen
                            </th>

                            <th class="px-6 py-4">
                                Dokumen
                            </th>

                            <th class="px-6 py-4">
                                Status
                            </th>

                            <th class="px-6 py-4 text-right">
                                Aksi
                            </th>
                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-100">

                        @foreach($categories as $category)

                            <tr class="hover:bg-slate-50">

                                {{-- CATEGORY --}}
                                <td class="px-6 py-4">

                                    <div class="flex items-center gap-3">

                                        <div
                                            class="w-10
                                                   h-10
                                                   rounded-xl
                                                   bg-blue-50
                                                   text-blue-700
                                                   flex
                                                   items-center
                                                   justify-center"
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
                                                <rect
                                                    width="7"
                                                    height="7"
                                                    x="3"
                                                    y="3"
                                                    rx="1"
                                                />
                                                <rect
                                                    width="7"
                                                    height="7"
                                                    x="14"
                                                    y="3"
                                                    rx="1"
                                                />
                                                <rect
                                                    width="7"
                                                    height="7"
                                                    x="3"
                                                    y="14"
                                                    rx="1"
                                                />
                                                <rect
                                                    width="7"
                                                    height="7"
                                                    x="14"
                                                    y="14"
                                                    rx="1"
                                                />
                                            </svg>
                                        </div>


                                        <div>

                                            <p
                                                class="text-sm
                                                       font-semibold
                                                       text-slate-900"
                                            >
                                                {{ $category->name }}
                                            </p>

                                            <p
                                                class="mt-1
                                                       text-xs
                                                       text-slate-400"
                                            >
                                                {{ $category->slug }}
                                            </p>

                                            @if($category->description)

                                                <p
                                                    class="mt-1
                                                           max-w-[300px]
                                                           truncate
                                                           text-xs
                                                           text-slate-500"
                                                >
                                                    {{ $category->description }}
                                                </p>

                                            @endif

                                        </div>

                                    </div>

                                </td>


                                {{-- DEPARTMENT --}}
                                <td class="px-6 py-4">

                                    <p
                                        class="text-sm
                                               font-semibold
                                               text-slate-700"
                                    >
                                        {{ $category->department?->name ?? '-' }}
                                    </p>

                                    <p
                                        class="mt-1
                                               text-xs
                                               text-slate-400"
                                    >
                                        {{ $category->department?->code ?? '-' }}
                                    </p>

                                </td>


                                {{-- DOCUMENT COUNT --}}
                                <td class="px-6 py-4">

                                    <button
                                        type="button"
                                        data-category-documents-trigger
                                        data-category-documents-url="{{ route(
                                            'categories.documents.index',
                                            $category,
                                            false
                                        ) }}"
                                        data-category-name="{{ $category->name }}"
                                        data-category-document-count="{{ $category->documents_count }}"
                                        aria-haspopup="dialog"
                                        aria-label="Lihat {{ $category->documents_count }} dokumen pada kategori {{ $category->name }}"
                                        class="inline-flex
                                               items-center
                                               gap-1.5
                                               rounded-lg
                                               bg-blue-50
                                               px-3
                                               py-1.5
                                               text-xs
                                               font-semibold
                                               text-blue-700
                                               transition
                                               hover:bg-blue-100
                                               focus:outline-none
                                               focus:ring-2
                                               focus:ring-blue-500
                                               focus:ring-offset-2"
                                    >
                                        {{ $category->documents_count }}
                                        dokumen

                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="14"
                                            height="14"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            aria-hidden="true"
                                        >
                                            <path d="m9 18 6-6-6-6" />
                                        </svg>
                                    </button>

                                </td>


                                {{-- STATUS --}}
                                <td class="px-6 py-4">

                                    @if($category->is_active)

                                        <span
                                            class="inline-flex
                                                   items-center
                                                   gap-1.5
                                                   rounded-lg
                                                   bg-emerald-50
                                                   px-2.5
                                                   py-1
                                                   text-xs
                                                   font-semibold
                                                   text-emerald-700"
                                        >
                                            <span
                                                class="w-1.5
                                                       h-1.5
                                                       rounded-full
                                                       bg-emerald-500"
                                            ></span>

                                            Aktif
                                        </span>

                                    @else

                                        <span
                                            class="inline-flex
                                                   items-center
                                                   gap-1.5
                                                   rounded-lg
                                                   bg-slate-100
                                                   px-2.5
                                                   py-1
                                                   text-xs
                                                   font-semibold
                                                   text-slate-600"
                                        >
                                            <span
                                                class="w-1.5
                                                       h-1.5
                                                       rounded-full
                                                       bg-slate-400"
                                            ></span>

                                            Tidak Aktif
                                        </span>

                                    @endif

                                </td>


                                {{-- ACTION --}}
                                <td class="px-6 py-4">

                                    <div
                                        class="flex
                                               justify-end
                                               gap-2"
                                    >

                                        <a
                                            href="{{ route(
                                                'categories.edit',
                                                $category
                                            ) }}"

                                            class="rounded-lg
                                                   bg-blue-50
                                                   px-3
                                                   py-2
                                                   text-xs
                                                   font-semibold
                                                   text-blue-700
                                                   hover:bg-blue-100"
                                        >
                                            Ubah
                                        </a>


                                        @if($category->is_active)

                                            <button
                                                type="button"
                                                x-on:click.prevent="$dispatch('open-modal', '{{ (int) $category->documents_count === 0
                                                    ? 'delete-category-' . $category->id
                                                    : 'deactivate-category-' . $category->id
                                                }}')"
                                                class="rounded-lg
                                                       px-3
                                                       py-2
                                                       text-xs
                                                       font-semibold
                                                       {{ (int) $category->documents_count === 0
                                                           ? 'bg-red-50 text-red-700 hover:bg-red-100'
                                                           : 'bg-amber-50 text-amber-700 hover:bg-amber-100'
                                                       }}"
                                            >
                                                {{ (int) $category->documents_count === 0
                                                    ? 'Hapus'
                                                    : 'Nonaktifkan'
                                                }}
                                            </button>

                                        @else

                                            <form
                                                method="POST"
                                                action="{{ route(
                                                    'categories.activate',
                                                    $category
                                                ) }}"
                                            >
                                                @csrf
                                                @method('PATCH')

                                                <button
                                                    type="submit"
                                                    class="rounded-lg
                                                           bg-emerald-50
                                                           px-3
                                                           py-2
                                                           text-xs
                                                           font-semibold
                                                           text-emerald-700
                                                           hover:bg-emerald-100"
                                                >
                                                    Aktifkan Kembali
                                                </button>
                                            </form>

                                        @endif

                                    </div>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>


            @if($categories->hasPages())

                <div
                    class="px-6
                           py-4
                           border-t
                           border-slate-100"
                >
                    {{ $categories->links() }}
                </div>

            @endif

        @else

            <div class="py-16 text-center">

                <p class="font-semibold text-slate-700">
                    Tidak ada kategori yang ditemukan.
                </p>

                <p class="mt-1 text-sm text-slate-400">
                    Coba ubah filter pencarian.
                </p>

            </div>

        @endif

    </section>

    <x-modal
        name="category-documents"
        maxWidth="2xl"
        containerClass="flex items-center justify-center sm:px-4"
        panelClass="relative !mb-0 flex h-[calc(100dvh-3rem)] max-h-[720px] w-full flex-col overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-2xl shadow-blue-950/20 sm:h-[74vh] sm:!max-w-[94vw] lg:h-[72vh] lg:!max-w-[960px]"
        backdropClass="bg-slate-950/45 backdrop-blur-[2px]"
        labelledBy="category-documents-title"
        describedBy="category-documents-description"
        focusable
    >
        <div data-category-documents-browser class="flex min-h-0 flex-1 flex-col">
            <div class="h-1.5 w-full flex-shrink-0 bg-blue-800"></div>

            <div
                data-category-documents-header
                class="flex-shrink-0 border-b border-blue-100 bg-gradient-to-br from-blue-50 to-white px-4 py-4 sm:px-6"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">
                            BMC QualityHub
                        </p>

                        <h2 id="category-documents-title" class="mt-1 text-lg font-bold text-slate-900 sm:text-xl">
                            Dokumen Kategori:
                            <span data-category-documents-name class="break-words">-</span>
                        </h2>

                        <p id="category-documents-description" class="mt-1 text-sm text-slate-500">
                            Daftar dokumen aktif dan arsip yang menggunakan kategori ini.
                        </p>
                    </div>

                    <button
                        type="button"
                        x-on:click="$dispatch('close')"
                        class="inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl border border-blue-100 bg-white text-slate-500 transition hover:bg-blue-50 hover:text-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        aria-label="Tutup daftar dokumen kategori"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p
                        data-category-documents-summary
                        class="text-sm font-semibold text-blue-800"
                        aria-live="polite"
                    >
                        0 dokumen
                    </p>

                    <label class="block w-full sm:w-72">
                        <span class="sr-only">
                            Cari dalam kategori
                        </span>

                        <span class="relative block">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <circle cx="11" cy="11" r="8" />
                                    <path d="m21 21-4.3-4.3" />
                                </svg>
                            </span>

                            <input
                                type="search"
                                data-category-documents-search
                                data-qh-modal-initial-focus
                                maxlength="160"
                                autocomplete="off"
                                placeholder="Judul, kode, part, file..."
                                class="w-full rounded-xl border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </span>
                    </label>
                </div>
            </div>

            <div
                data-category-documents-scroll
                class="min-h-0 flex-1 overflow-y-auto overscroll-contain bg-slate-50/70 px-4 py-3 sm:px-5"
            >
                <div
                    data-category-documents-loading
                    class="flex h-full min-h-[180px] items-center justify-center"
                    role="status"
                    hidden
                >
                    <div class="text-center">
                        <span class="mx-auto block h-9 w-9 animate-spin rounded-full border-4 border-blue-100 border-t-blue-700"></span>
                        <p class="mt-3 text-sm font-semibold text-slate-600">
                            Memuat dokumen...
                        </p>
                    </div>
                </div>

                <div
                    data-category-documents-error
                    class="flex h-full min-h-[180px] items-center justify-center"
                    role="alert"
                    hidden
                >
                    <div class="max-w-sm text-center">
                        <p class="font-semibold text-slate-800">
                            Data dokumen tidak dapat dimuat.
                        </p>
                        <p class="mt-1 text-sm text-slate-500">
                            Periksa koneksi lalu coba kembali.
                        </p>
                        <button
                            type="button"
                            data-category-documents-retry
                            class="mt-4 rounded-xl bg-blue-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        >
                            Coba Lagi
                        </button>
                    </div>
                </div>

                <div
                    data-category-documents-empty
                    class="flex h-full min-h-[180px] items-center justify-center text-center"
                    hidden
                >
                    <div>
                        <p class="font-semibold text-slate-700">
                            Belum ada dokumen pada kategori ini.
                        </p>
                        <p class="mt-1 text-sm text-slate-500">
                            Jumlah akan diperbarui saat dokumen ditambahkan.
                        </p>
                    </div>
                </div>

                <div
                    data-category-documents-no-results
                    class="flex h-full min-h-[180px] items-center justify-center text-center"
                    hidden
                >
                    <div>
                        <p class="font-semibold text-slate-700">
                            Tidak ada dokumen yang cocok.
                        </p>
                        <p class="mt-1 text-sm text-slate-500">
                            Coba kata kunci lain pada kategori yang sama.
                        </p>
                    </div>
                </div>

                <ul
                    data-category-documents-results
                    class="space-y-2"
                    aria-live="polite"
                    hidden
                ></ul>
            </div>

            <div
                data-category-documents-pagination
                class="flex flex-shrink-0 flex-col gap-3 border-t border-slate-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6"
                hidden
            >
                <p data-category-documents-page-info class="text-sm text-slate-500"></p>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        data-category-documents-previous
                        class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Sebelumnya
                    </button>

                    <button
                        type="button"
                        data-category-documents-next
                        class="rounded-lg bg-blue-800 px-3 py-2 text-sm font-semibold text-white transition hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Berikutnya
                    </button>
                </div>
            </div>

            <template data-category-document-template>
                <li
                    data-category-document-result
                    class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4"
                >
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <p data-category-document-title class="break-words text-sm font-bold text-slate-900"></p>
                            <p class="mt-1 truncate text-xs text-slate-500">
                                File:
                                <span data-category-document-file-name></span>
                            </p>
                        </div>

                        <span
                            data-category-document-status-active
                            class="inline-flex w-fit rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700"
                            hidden
                        >
                            Aktif
                        </span>

                        <span
                            data-category-document-status-archived
                            class="inline-flex w-fit rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600"
                            hidden
                        >
                            Diarsipkan
                        </span>
                    </div>

                    <dl class="mt-3 grid grid-cols-1 gap-2 text-sm sm:grid-cols-3">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Kode Dokumen
                            </dt>
                            <dd data-category-document-code class="mt-0.5 break-all font-semibold text-slate-700"></dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Nomor Part
                            </dt>
                            <dd data-category-document-part-number class="mt-0.5 break-all font-semibold text-slate-700"></dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Tanggal Dokumen
                            </dt>
                            <dd data-category-document-date class="mt-0.5 font-semibold text-slate-700"></dd>
                        </div>
                    </dl>

                    <div class="mt-3 flex justify-end border-t border-slate-100 pt-2.5">
                        <a
                            data-category-document-detail
                            href="#"
                            class="inline-flex items-center rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            hidden
                        >
                            Lihat Detail
                        </a>

                        <span
                            data-category-document-detail-unavailable
                            class="text-xs font-medium text-slate-400"
                            hidden
                        >
                            Detail tidak tersedia untuk dokumen arsip.
                        </span>
                    </div>
                </li>
            </template>
        </div>
    </x-modal>

    @foreach($categories as $category)

        @if($category->is_active && (int) $category->documents_count === 0)

            <x-confirmation-modal
                name="delete-category-{{ $category->id }}"
                :action="route('categories.destroy', $category)"
                method="DELETE"
                variant="danger"
                title="Hapus Kategori?"
                description="Kategori {{ $category->name }} belum digunakan oleh dokumen dan akan dihapus permanen."
                confirmLabel="Hapus Permanen"
            />

        @elseif($category->is_active)

            <x-confirmation-modal
                name="deactivate-category-{{ $category->id }}"
                :action="route('categories.deactivate', $category)"
                variant="warning"
                title="Nonaktifkan Kategori?"
                description="Kategori {{ $category->name }} digunakan oleh {{ number_format($category->documents_count) }} dokumen dan tidak dapat dihapus permanen."
                secondaryDescription="Kategori tidak akan tersedia untuk dokumen baru, tetapi tetap dipertahankan pada dokumen historis."
                confirmLabel="Nonaktifkan"
            />

        @endif

    @endforeach
</x-app-layout>
