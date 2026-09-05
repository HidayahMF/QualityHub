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
            x-data="{ show: true }"
            x-show="show"
            x-transition

            class="mb-6
                   flex
                   items-center
                   justify-between
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

            <button
                type="button"
                @click="show = false"
                class="text-emerald-700"
            >
                ✕
            </button>

        </div>

    @endif


    {{-- STATISTICS --}}
    <section
        class="grid
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

        class="mb-6
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
        class="bg-white
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

                                    <span
                                        class="inline-flex
                                               rounded-lg
                                               bg-slate-100
                                               px-3
                                               py-1.5
                                               text-xs
                                               font-semibold
                                               text-slate-700"
                                    >
                                        {{ $category->documents_count }}
                                        dokumen
                                    </span>

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


                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'categories.toggle-status',
                                                $category
                                            ) }}"

                                            data-confirm-title="{{ $category->is_active
                                                ? 'Nonaktifkan Kategori?'
                                                : 'Aktifkan Kategori?'
                                            }}"
                                            data-confirm-message="{{ $category->is_active
                                                ? 'Kategori akan dinonaktifkan. Dokumen yang sudah menggunakan kategori ini tetap tersedia.'
                                                : 'Kategori akan diaktifkan kembali dan dapat digunakan untuk dokumen baru.'
                                            }}"
                                            data-confirm-button="{{ $category->is_active
                                                ? 'Nonaktifkan'
                                                : 'Aktifkan'
                                            }}"
                                            x-on:submit.prevent="$dispatch('qualityhub-confirm', {
                                                form: $event.currentTarget,
                                                title: $event.currentTarget.dataset.confirmTitle,
                                                message: $event.currentTarget.dataset.confirmMessage,
                                                confirmText: $event.currentTarget.dataset.confirmButton
                                            })"
                                        >

                                            @csrf
                                            @method('PATCH')


                                            <button
                                                type="submit"

                                                class="
                                                    rounded-lg
                                                    px-3
                                                    py-2
                                                    text-xs
                                                    font-semibold

                                                    {{
                                                        $category->is_active

                                                        ? 'bg-amber-50 text-amber-700 hover:bg-amber-100'

                                                        : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                                                    }}
                                                "
                                            >
                                                {{ $category->is_active
                                                    ? 'Nonaktifkan'
                                                    : 'Aktifkan'
                                                }}
                                            </button>

                                        </form>

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

    {{-- ========================================================= --}}
    {{-- BMC QUALITYHUB CUSTOM CONFIRMATION MODAL --}}
    {{-- Menggantikan native browser confirm() dengan modal biru-putih --}}
    {{-- ========================================================= --}}

    <style>
        [x-cloak] { display: none !important; }
    </style>

    <div
        x-data="{
            open: false,
            form: null,
            title: 'Konfirmasi Tindakan',
            message: 'Pastikan tindakan ini memang ingin dilanjutkan.',
            confirmText: 'Lanjutkan'
        }"
        x-on:qualityhub-confirm.window="
            form = $event.detail.form;
            title = $event.detail.title || 'Konfirmasi Tindakan';
            message = $event.detail.message || 'Pastikan tindakan ini memang ingin dilanjutkan.';
            confirmText = $event.detail.confirmText || 'Lanjutkan';
            open = true;
        "
        x-on:keydown.escape.window="
            open = false;
            form = null;
        "
        x-show="open"
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="qualityhub-confirm-title"
    >
        <div
            x-show="open"
            x-transition.opacity
            class="absolute inset-0 bg-slate-950/45 backdrop-blur-[2px]"
            x-on:click="
                open = false;
                form = null;
            "
        ></div>

        <section
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-2"
            class="relative w-full max-w-md overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-2xl shadow-blue-950/20"
        >
            <div class="h-1.5 w-full bg-blue-800"></div>

            <div class="p-6 sm:p-7">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-700 ring-1 ring-blue-100">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="22"
                            height="22"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/>
                            <path d="M9 12l2 2 4-4"/>
                        </svg>
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">
                            BMC QualityHub
                        </p>

                        <h3
                            id="qualityhub-confirm-title"
                            class="mt-1.5 text-xl font-bold text-slate-900"
                            x-text="title"
                        ></h3>

                        <p
                            class="mt-2 text-sm leading-6 text-slate-600"
                            x-text="message"
                        ></p>
                    </div>
                </div>

                <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2"
                        x-on:click="
                            open = false;
                            form = null;
                        "
                    >
                        Batal
                    </button>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl bg-blue-800 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-900/15 transition hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        x-text="confirmText"
                        x-on:click="
                            const target = form;
                            open = false;
                            form = null;

                            if (target) {
                                target.submit();
                            }
                        "
                    ></button>
                </div>
            </div>
        </section>
    </div>

</x-app-layout>