<x-app-layout>

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <section
        class="flex
               flex-col
               lg:flex-row
               lg:items-start
               lg:justify-between

               gap-5
               mb-8"
    >

        <div>

            <a
                href="{{ route(
                    'documents.revisions.index',
                    $document
                ) }}"

                class="inline-flex
                       items-center
                       gap-2

                       mb-5

                       text-sm
                       font-semibold
                       text-slate-500

                       hover:text-blue-700"
            >
                ← Kembali ke Riwayat Revisi
            </a>


            <p
                class="text-sm
                       font-semibold
                       text-indigo-700"
            >
                Revisi Dokumen
            </p>


            <h1
                class="mt-2

                       text-3xl
                       sm:text-4xl

                       font-bold
                       text-slate-900"
            >
                Upload New Revision
            </h1>


            <p
                class="mt-2
                       text-sm
                       text-slate-500"
            >
                {{ $document->title }}
            </p>

        </div>


        <div
            class="rounded-xl

                   border
                   border-blue-200

                   bg-blue-50

                   px-4
                   py-3

                   text-sm
                   font-bold
                   text-blue-700"
        >
            Revisi Saat Ini:
            Revisi {{ (int) $document->current_revision }}
        </div>

    </section>



    {{-- ========================================================= --}}
    {{-- ERRORS --}}
    {{-- ========================================================= --}}

    @if($errors->any())

        <div
            class="mb-6

                   rounded-2xl

                   border
                   border-red-200

                   bg-red-50

                   p-5"
        >

            <p
                class="font-bold
                       text-red-900"
            >
                Revisi tidak dapat diunggah.
            </p>


            <ul
                class="mt-2
                       list-disc
                       list-inside
                       text-sm
                       text-red-700"
            >

                @foreach($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif



    <div
        class="grid
               xl:grid-cols-3
               gap-6"
    >


        {{-- ===================================================== --}}
        {{-- FORM --}}
        {{-- ===================================================== --}}

        <form
            method="POST"

            enctype="multipart/form-data"

            action="{{ route(
                'documents.revisions.store',
                $document
            ) }}"

            class="xl:col-span-2

                   rounded-2xl

                   border
                   border-slate-200

                   bg-white

                   shadow-sm

                   overflow-hidden"
        >

            @csrf


            <div
                class="px-6
                       py-5

                       border-b
                       border-slate-100"
            >

                <h2
                    class="font-bold
                           text-slate-900"
                >
                    File Revisi Baru
                </h2>


                <p
                    class="mt-1
                           text-sm
                           text-slate-500"
                >
                    File saat ini akan tetap tersedia di
                    Riwayat Revisi.
                </p>

            </div>


            <div
                class="p-6
                       space-y-6"
            >


                {{-- FILE --}}

                <div>

                    <label
                        for="file"

                        class="block
                               mb-2

                               text-sm
                               font-semibold
                               text-slate-700"
                    >
                        File Revisi

                        <span class="text-red-500">
                            *
                        </span>
                    </label>


                    <input
                        id="file"

                        type="file"

                        name="file"

                        required

                        accept=".pdf,.xls,.xlsx,.doc,.docx,.csv,.txt"

                        class="block
                               w-full

                               rounded-xl

                               border
                               border-slate-200

                               bg-slate-50

                               p-3

                               text-sm"
                    >


                    <p
                        class="mt-2
                               text-xs
                               text-slate-400"
                    >
                        PDF, Excel, Word, CSV, atau TXT · Maksimal 20 MB
                    </p>

                </div>



                {{-- REVISION NOTE --}}

                <div>

                    <label
                        for="revision_note"

                        class="block
                               mb-2

                               text-sm
                               font-semibold
                               text-slate-700"
                    >
                        Catatan Revisi

                        <span class="text-red-500">
                            *
                        </span>
                    </label>


                    <textarea
                        id="revision_note"

                        name="revision_note"

                        rows="6"

                        required

                        maxlength="1000"

                        placeholder="Contoh: Hasil inspeksi diperbarui setelah koreksi pengukuran."

                        class="w-full

                               rounded-xl

                               border-slate-200

                               bg-slate-50

                               px-4
                               py-3

                               resize-none"
                    >{{ old('revision_note') }}</textarea>


                    <p
                        class="mt-2
                               text-xs
                               text-slate-400"
                    >
                        Jelaskan perubahan dibandingkan dengan
                        Revisi {{ (int) $document->current_revision }}.
                    </p>

                </div>

            </div>


            <div
                class="px-6
                       py-5

                       border-t
                       border-slate-100

                       bg-slate-50

                       flex
                       flex-col-reverse
                       sm:flex-row
                       sm:justify-end

                       gap-3"
            >

                <a
                    href="{{ route(
                        'documents.revisions.index',
                        $document
                    ) }}"

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
                           text-slate-600"
                >
                    Batal
                </a>


                <button
                    type="submit"

                    class="inline-flex
                           items-center
                           justify-center

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
                    Unggah Revisi Baru
                </button>

            </div>

        </form>



        {{-- ===================================================== --}}
        {{-- SIDEBAR --}}
        {{-- ===================================================== --}}

        <aside class="space-y-5">


            {{-- VERSION PROTECTION --}}

            <section
                class="rounded-2xl

                       border
                       border-emerald-200

                       bg-emerald-50

                       p-5"
            >

                <p
                    class="font-bold
                           text-emerald-900"
                >
                    Revisi Sebelumnya Terlindungi
                </p>


                <p
                    class="mt-2

                           text-sm
                           leading-relaxed
                           text-emerald-700"
                >
                    Mengunggah revisi ini akan membuat file fisik baru.
                    Revisi sebelumnya tetap tersedia di
                    Riwayat Revisi.
                </p>

            </section>



            {{-- NEXT REVISION --}}

            <section
                class="rounded-2xl

                       border
                       border-blue-200

                       bg-blue-50

                       p-5"
            >

                <p
                    class="text-xs
                           uppercase
                           tracking-wider
                           font-semibold
                           text-blue-600"
                >
                    Revisi Berikutnya
                </p>


                <p
                    class="mt-2

                           text-3xl
                           font-bold
                           text-blue-900"
                >
                    Revisi {{ (int) $document->current_revision + 1 }}
                </p>


                <p
                    class="mt-2
                           text-xs
                           text-blue-700"
                >
                    Revisi saat ini:
                    Revisi {{ (int) $document->current_revision }}
                </p>

            </section>



            {{-- CURRENT FILE --}}

            <section
                class="rounded-2xl

                       border
                       border-slate-200

                       bg-white

                       p-5"
            >

                <p
                    class="text-xs
                           uppercase
                           tracking-wider
                           font-semibold
                           text-slate-400"
                >
                    File Saat Ini
                </p>


                <p
                    class="mt-2

                           text-sm
                           font-semibold
                           text-slate-800

                           break-all"
                >
                    {{ $document->file_name }}
                </p>


                <div
                    class="mt-3

                           flex
                           flex-wrap
                           gap-2"
                >

                    <span
                        class="rounded-lg
                               bg-slate-100
                               px-2.5
                               py-1
                               text-xs
                               font-bold
                               text-slate-600"
                    >
                        {{ $document->getFileTypeLabel() }}
                    </span>


                    <span
                        class="rounded-lg
                               bg-slate-100
                               px-2.5
                               py-1
                               text-xs
                               font-medium
                               text-slate-600"
                    >
                        {{ $document->getFormattedFileSize() }}
                    </span>

                </div>

            </section>

        </aside>

    </div>

</x-app-layout>