<x-app-layout>

    {{-- HEADER --}}
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
                       text-amber-700"
            >
                <span
                    class="w-2
                           h-2
                           rounded-full
                           bg-amber-500"
                ></span>

                Document Archive
            </div>


            <h1
                class="text-3xl
                       sm:text-4xl
                       font-bold
                       text-slate-900"
            >
                Archived Documents
            </h1>


            <p
                class="mt-2
                       text-sm
                       text-slate-500"
            >
                Dokumen yang diarsipkan tetap tersimpan dengan aman dan
                dapat dipulihkan kembali saat diperlukan.
            </p>

        </div>


        <a
            href="{{ route('documents.index') }}"

            class="inline-flex
                   items-center
                   justify-center
                   rounded-xl
                   border
                   border-slate-200
                   bg-white
                   px-5
                   py-3
                   text-sm
                   font-semibold
                   text-slate-600
                   hover:bg-slate-50"
        >
            Kembali ke Dokumen Aktif
        </a>

    </section>



    {{-- SUCCESS --}}
    @if(session('success'))

        <div
            class="mb-6
                   rounded-2xl
                   border
                   border-emerald-200
                   bg-emerald-50
                   px-5
                   py-4
                   text-sm
                   font-semibold
                   text-emerald-700"
        >
            {{ session('success') }}
        </div>

    @endif



    {{-- SEARCH --}}
    <form
        method="GET"
        action="{{ route('documents.archived') }}"

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

                    placeholder="Cari dokumen yang diarsipkan..."

                    class="w-full
                           rounded-xl
                           border-slate-200
                           bg-slate-50
                           px-4
                           py-3
                           text-sm"
                >

            </div>


            <select
                name="department"

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
                            (string) request('department')
                            === (string) $department->id
                        )
                    >
                        {{ $department->name }}
                    </option>

                @endforeach

            </select>


            <select
                name="category"

                class="rounded-xl
                       border-slate-200
                       bg-slate-50
                       px-4
                       py-3
                       text-sm"
            >

                <option value="">
                    Semua Kategori
                </option>


                @foreach($categories as $category)

                    <option
                        value="{{ $category->id }}"

                        @selected(
                            (string) request('category')
                            === (string) $category->id
                        )
                    >
                        {{ $category->name }}
                    </option>

                @endforeach

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
                       text-white"
            >
                Terapkan Filter
            </button>


            <a
                href="{{ route('documents.archived') }}"

                class="rounded-xl
                       border
                       border-slate-200
                       px-5
                       py-2.5
                       text-sm
                       font-semibold
                       text-slate-600"
            >
                Atur Ulang
            </a>

        </div>

    </form>



    {{-- DOCUMENTS --}}
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
                Arsip Dokumen
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                {{ $documents->total() }}
                dokumen diarsipkan.
            </p>

        </div>


        @if($documents->count())

            <div class="overflow-x-auto">

                <table
                    class="w-full
                           min-w-[1100px]
                           text-left"
                >

                    <thead class="bg-slate-50">

                        <tr
                            class="text-[11px]
                                   uppercase
                                   tracking-wider
                                   text-slate-500"
                        >

                            <th class="px-6 py-4">
                                Dokumen
                            </th>

                            <th class="px-6 py-4">
                                Kategori
                            </th>

                            <th class="px-6 py-4">
                                Diarsipkan
                            </th>

                            <th class="px-6 py-4">
                                Alasan
                            </th>

                            <th class="px-6 py-4 text-right">
                                Aksi
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-100">

                        @foreach($documents as $document)

                            <tr class="hover:bg-slate-50">

                                <td class="px-6 py-4">

                                    <p
                                        class="text-sm
                                               font-semibold
                                               text-slate-900"
                                    >
                                        {{ $document->title }}
                                    </p>


                                    <p
                                        class="mt-1
                                               text-xs
                                               text-slate-400"
                                    >
                                        {{ $document->document_code
                                            ?? $document->file_name
                                        }}
                                    </p>

                                </td>


                                <td class="px-6 py-4">

                                    <span
                                        class="rounded-lg
                                               bg-blue-50
                                               px-2.5
                                               py-1
                                               text-xs
                                               font-semibold
                                               text-blue-700"
                                    >
                                        {{ $document->category?->name ?? '-' }}
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


                                <td class="px-6 py-4">

                                    <p
                                        class="text-sm
                                               font-semibold
                                               text-slate-700"
                                    >
                                        {{
                                            $document->archived_at
                                                ?->locale('id')->translatedFormat('d M Y, H:i')
                                            ?? '-'
                                        }}
                                    </p>


                                    <p
                                        class="mt-1
                                               text-xs
                                               text-slate-400"
                                    >
                                        Oleh:
                                        {{
                                            $document
                                                ->archivedBy
                                                ?->name
                                            ?? 'Tidak diketahui'
                                        }}
                                    </p>

                                </td>


                                <td class="px-6 py-4">

                                    <p
                                        class="max-w-[300px]
                                               text-sm
                                               text-slate-600"
                                    >
                                        {{ $document->archive_reason ?? '-' }}
                                    </p>

                                </td>


                                <td class="px-6 py-4">

                                    <div
                                        class="flex
                                               justify-end
                                               gap-2"
                                    >

                                        <a
                                            href="{{ route(
                                                'documents.edit',
                                                $document
                                            ) }}"

                                            class="rounded-lg
                                                   bg-slate-100
                                                   px-3
                                                   py-2
                                                   text-xs
                                                   font-semibold
                                                   text-slate-700
                                                   hover:bg-slate-200"
                                        >
                                            Ubah
                                        </a>


                                        <form
                                            method="POST"

                                            action="{{ route(
                                                'documents.restore',
                                                $document
                                            ) }}"
                                        >

                                            @csrf
                                            @method('PATCH')


                                            <button
                                                type="submit"

                                                class="rounded-lg
                                                       bg-blue-800
                                                       px-3
                                                       py-2
                                                       text-xs
                                                       font-semibold
                                                       text-white
                                                       hover:bg-blue-900"
                                            >
                                                Pulihkan
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>


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

            <div class="py-20 text-center">

                <p
                    class="font-bold
                           text-slate-700"
                >
                    Belum ada dokumen yang diarsipkan
                </p>


                <p
                    class="mt-2
                           text-sm
                           text-slate-400"
                >
                    Dokumen yang diarsipkan akan ditampilkan di sini.
                </p>

            </div>

        @endif

    </section>

</x-app-layout>