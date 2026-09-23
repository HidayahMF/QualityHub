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
