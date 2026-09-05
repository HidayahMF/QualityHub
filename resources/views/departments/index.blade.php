<x-app-layout>

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

                Master Data
            </div>


            <h1
                class="text-3xl
                       sm:text-4xl
                       font-bold
                       tracking-tight
                       text-slate-900"
            >
                Department Management
            </h1>


            <p
                class="mt-2
                       max-w-2xl
                       text-sm
                       sm:text-base
                       text-slate-500"
            >
                Kelola data departemen yang digunakan untuk kategori dokumen
                dan akun pengguna di BMC QualityHub.
            </p>

        </div>


        <a
            href="{{ route('departments.create') }}"

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

            Tambah Departemen
        </a>

    </section>


    {{-- ========================================================= --}}
    {{-- SUCCESS MESSAGE --}}
    {{-- ========================================================= --}}

    @if(session('success'))

        <div
            x-data="{ show: true }"
            x-show="show"
            x-transition

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

            <div>
                <p class="text-sm font-bold text-emerald-900">
                    Berhasil
                </p>

                <p class="mt-1 text-sm text-emerald-700">
                    {{ session('success') }}
                </p>
            </div>


            <button
                type="button"
                @click="show = false"
                class="text-emerald-700 hover:text-emerald-900"
                aria-label="Tutup notifikasi"
            >
                ✕
            </button>

        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- ERROR MESSAGE --}}
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
            <p class="text-sm font-bold text-red-900">
                Tindakan tidak dapat diproses.
            </p>

            <ul
                class="mt-2
                       list-disc
                       list-inside
                       space-y-1
                       text-sm
                       text-red-700"
            >
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- STATISTICS --}}
    {{-- ========================================================= --}}

    <section
        class="grid
               grid-cols-1
               sm:grid-cols-3
               gap-5
               mb-6"
    >

        <div
            class="rounded-2xl
                   border
                   border-slate-200
                   bg-white
                   p-5
                   shadow-sm"
        >
            <p class="text-sm text-slate-500">
                Total Departemen
            </p>

            <p class="mt-2 text-3xl font-bold text-slate-900">
                {{ number_format($statistics['total']) }}
            </p>
        </div>


        <div
            class="rounded-2xl
                   border
                   border-slate-200
                   bg-white
                   p-5
                   shadow-sm"
        >
            <p class="text-sm text-slate-500">
                Departemen Aktif
            </p>

            <p class="mt-2 text-3xl font-bold text-emerald-700">
                {{ number_format($statistics['active']) }}
            </p>
        </div>


        <div
            class="rounded-2xl
                   border
                   border-slate-200
                   bg-white
                   p-5
                   shadow-sm"
        >
            <p class="text-sm text-slate-500">
                Departemen Tidak Aktif
            </p>

            <p class="mt-2 text-3xl font-bold text-slate-600">
                {{ number_format($statistics['inactive']) }}
            </p>
        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- FILTER --}}
    {{-- ========================================================= --}}

    <form
        method="GET"
        action="{{ route('departments.index') }}"

        class="mb-6
               rounded-2xl
               border
               border-slate-200
               bg-white
               p-5
               shadow-sm"
    >

        <div
            class="grid
                   grid-cols-1
                   md:grid-cols-[minmax(0,1fr)_220px]
                   gap-3"
        >

            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                maxlength="255"
                placeholder="Cari nama, kode, atau deskripsi departemen..."

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


            <select
                name="status"

                class="rounded-xl
                       border-slate-200
                       bg-slate-50
                       px-4
                       py-3
                       text-sm
                       focus:border-blue-500
                       focus:ring-blue-500"
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


        <div
            class="mt-4
                   flex
                   flex-wrap
                   gap-3"
        >

            <button
                type="submit"

                class="rounded-xl
                       bg-blue-800
                       px-5
                       py-2.5
                       text-sm
                       font-semibold
                       text-white
                       hover:bg-blue-900
                       transition"
            >
                Terapkan Filter
            </button>


            <a
                href="{{ route('departments.index') }}"

                class="rounded-xl
                       border
                       border-slate-200
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
    {{-- DEPARTMENT TABLE --}}
    {{-- ========================================================= --}}

    <section
        class="overflow-hidden
               rounded-2xl
               border
               border-slate-200
               bg-white
               shadow-sm"
    >

        <div
            class="flex
                   flex-col
                   sm:flex-row
                   sm:items-center
                   sm:justify-between
                   gap-2
                   border-b
                   border-slate-100
                   px-6
                   py-5"
        >

            <div>
                <h2 class="font-bold text-slate-900">
                    Daftar Departemen
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    {{ number_format($departments->total()) }}
                    departemen ditemukan.
                </p>
            </div>


            <div
                class="inline-flex
                       items-center
                       gap-2
                       text-xs
                       font-medium
                       text-slate-500"
            >
                <span
                    class="w-2
                           h-2
                           rounded-full
                           bg-emerald-500"
                ></span>

                Data terhubung ke kategori dan pengguna
            </div>

        </div>


        @if($departments->count())

            <div class="overflow-x-auto">

                <table class="w-full min-w-[900px] text-left">

                    <thead class="bg-slate-50">

                        <tr
                            class="text-[11px]
                                   uppercase
                                   tracking-wider
                                   text-slate-500"
                        >
                            <th class="px-6 py-4 font-semibold">
                                Departemen
                            </th>

                            <th class="px-6 py-4 font-semibold">
                                Kategori
                            </th>

                            <th class="px-6 py-4 font-semibold">
                                Pengguna
                            </th>

                            <th class="px-6 py-4 font-semibold">
                                Status
                            </th>

                            <th class="px-6 py-4 font-semibold text-right">
                                Aksi
                            </th>
                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-100">

                        @foreach($departments as $department)

                            <tr
                                class="hover:bg-slate-50/70
                                       transition"
                            >

                                {{-- DEPARTMENT --}}
                                <td class="px-6 py-4">

                                    <div
                                        class="flex
                                               items-center
                                               gap-3"
                                    >

                                        <div
                                            class="flex
                                                   h-11
                                                   w-11
                                                   flex-shrink-0
                                                   items-center
                                                   justify-center
                                                   rounded-xl
                                                   bg-blue-50
                                                   text-blue-700"
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
                                                <path d="M3 21h18"/>
                                                <path d="M6 21V7l6-4 6 4v14"/>
                                                <path d="M9 9h1"/>
                                                <path d="M14 9h1"/>
                                                <path d="M9 13h1"/>
                                                <path d="M14 13h1"/>
                                                <path d="M9 17h1"/>
                                                <path d="M14 17h1"/>
                                            </svg>
                                        </div>


                                        <div class="min-w-0">

                                            <p
                                                class="text-sm
                                                       font-semibold
                                                       text-slate-900"
                                            >
                                                {{ $department->name }}
                                            </p>

                                            <p
                                                class="mt-1
                                                       text-xs
                                                       font-semibold
                                                       tracking-wide
                                                       text-blue-700"
                                            >
                                                {{ $department->code }}
                                            </p>

                                            @if($department->description)

                                                <p
                                                    class="mt-1
                                                           max-w-[360px]
                                                           truncate
                                                           text-xs
                                                           text-slate-500"
                                                    title="{{ $department->description }}"
                                                >
                                                    {{ $department->description }}
                                                </p>

                                            @endif

                                        </div>

                                    </div>

                                </td>


                                {{-- CATEGORY COUNT --}}
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
                                        {{ number_format($department->categories_count) }}
                                        kategori
                                    </span>

                                </td>


                                {{-- USER COUNT --}}
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
                                        {{ number_format($department->users_count) }}
                                        pengguna
                                    </span>

                                </td>


                                {{-- STATUS --}}
                                <td class="px-6 py-4">

                                    @if($department->is_active)

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
                                                class="h-1.5
                                                       w-1.5
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
                                                class="h-1.5
                                                       w-1.5
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
                                               flex-wrap
                                               justify-end
                                               gap-2"
                                    >

                                        <a
                                            href="{{ route(
                                                'departments.edit',
                                                $department
                                            ) }}"

                                            class="rounded-lg
                                                   bg-blue-50
                                                   px-3
                                                   py-2
                                                   text-xs
                                                   font-semibold
                                                   text-blue-700
                                                   hover:bg-blue-100
                                                   transition"
                                        >
                                            Ubah
                                        </a>


                                        <form
                                            method="POST"

                                            action="{{ route(
                                                'departments.toggle-status',
                                                $department
                                            ) }}"

                                            data-confirm-title="{{ $department->is_active
                                                ? 'Nonaktifkan Departemen?'
                                                : 'Aktifkan Departemen?'
                                            }}"
                                            data-confirm-message="{{ $department->is_active
                                                ? 'Departemen hanya dapat dinonaktifkan jika tidak memiliki kategori aktif atau pengguna aktif.'
                                                : 'Departemen akan diaktifkan kembali dan dapat digunakan oleh kategori maupun pengguna.'
                                            }}"
                                            data-confirm-button="{{ $department->is_active
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
                                                    transition

                                                    {{
                                                        $department->is_active
                                                            ? 'bg-amber-50 text-amber-700 hover:bg-amber-100'
                                                            : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                                                    }}
                                                "
                                            >
                                                {{
                                                    $department->is_active
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


            @if($departments->hasPages())

                <div
                    class="border-t
                           border-slate-100
                           px-6
                           py-4"
                >
                    {{ $departments->links() }}
                </div>

            @endif

        @else

            <div class="px-6 py-16 text-center">

                <div
                    class="mx-auto
                           flex
                           h-14
                           w-14
                           items-center
                           justify-center
                           rounded-2xl
                           bg-slate-100
                           text-slate-500"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M3 21h18"/>
                        <path d="M6 21V7l6-4 6 4v14"/>
                    </svg>
                </div>

                <p class="mt-4 font-semibold text-slate-700">
                    Departemen tidak ditemukan.
                </p>

                <p class="mt-1 text-sm text-slate-400">
                    Coba ubah kata pencarian atau filter status.
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
