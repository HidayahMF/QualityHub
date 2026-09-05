<x-app-layout>

    {{-- HEADER --}}
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
                href="{{ $document->is_active
                    ? route('documents.show', $document)
                    : route('documents.archived')
                }}"

                class="inline-flex
                       items-center
                       gap-2
                       mb-5
                       text-sm
                       font-semibold
                       text-slate-500
                       hover:text-blue-700"
            >
                ← Kembali
            </a>


            <div
                class="flex
                       items-center
                       gap-2
                       mb-2"
            >

                <span
                    class="w-2
                           h-2
                           rounded-full
                           bg-blue-600"
                ></span>

                <span
                    class="text-sm
                           font-semibold
                           text-blue-700"
                >
                    Document Management
                </span>

            </div>


            <h1
                class="text-3xl
                       sm:text-4xl
                       font-bold
                       text-slate-900"
            >
                Edit Document
            </h1>


            <p
                class="mt-2
                       text-sm
                       text-slate-500"
            >
                Perbarui metadata dokumen tanpa mengganti
                atau mengubah file fisik.
            </p>

        </div>


        <span
            class="
                inline-flex
                items-center
                gap-2
                rounded-xl
                px-4
                py-2.5
                text-sm
                font-semibold

                {{
                    $document->is_active
                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                        : 'bg-amber-50 text-amber-700 border border-amber-200'
                }}
            "
        >
            <span
                class="
                    w-2
                    h-2
                    rounded-full

                    {{
                        $document->is_active
                            ? 'bg-emerald-500'
                            : 'bg-amber-500'
                    }}
                "
            ></span>

            {{ $document->is_active
                ? 'Dokumen Aktif'
                : 'Dokumen Diarsipkan'
            }}
        </span>

    </section>



    {{-- ERRORS --}}
    @if($errors->any())

        <div
            class="mb-6
                   rounded-2xl
                   border
                   border-red-200
                   bg-red-50
                   p-5"
        >

            <p class="font-bold text-red-900">
                Silakan periksa informasi di bawah ini.
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
        {{-- EDIT FORM --}}
        {{-- ===================================================== --}}

        <form
            method="POST"

            action="{{ route(
                'documents.update',
                $document
            ) }}"

            class="xl:col-span-2

                   bg-white

                   rounded-2xl

                   border
                   border-slate-200

                   shadow-sm

                   overflow-hidden"
        >

            @csrf
            @method('PUT')


            <div
                class="px-6
                       py-5
                       border-b
                       border-slate-100"
            >
                <h2 class="font-bold text-slate-900">
                    Metadata Dokumen
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Kolom-kolom ini dapat dicari melalui SmartFind.
                </p>
            </div>


            <div class="p-6 space-y-6">


                {{-- TITLE --}}
                <div>

                    <label
                        for="title"
                        class="block
                               mb-2
                               text-sm
                               font-semibold
                               text-slate-700"
                    >
                        Judul Dokumen
                        <span class="text-red-500">*</span>
                    </label>


                    <input
                        id="title"
                        type="text"
                        name="title"

                        value="{{ old(
                            'title',
                            $document->title
                        ) }}"

                        required

                        class="w-full
                               rounded-xl
                               border-slate-200
                               bg-slate-50
                               px-4
                               py-3"
                    >

                </div>



                {{-- CATEGORY --}}
                <div>

                    <label
                        for="category_id"
                        class="block
                               mb-2
                               text-sm
                               font-semibold
                               text-slate-700"
                    >
                        Kategori
                        <span class="text-red-500">*</span>
                    </label>


                    <select
                        id="category_id"
                        name="category_id"
                        required

                        class="w-full
                               rounded-xl
                               border-slate-200
                               bg-slate-50
                               px-4
                               py-3"
                    >

                        @foreach($categories as $category)

                            <option
                                value="{{ $category->id }}"

                                @selected(
                                    old(
                                        'category_id',
                                        $document->category_id
                                    )
                                    == $category->id
                                )
                            >

                                {{ $category->department?->name ?? '-' }}
                                —
                                {{ $category->name }}

                                @unless($category->is_active)
                                    (Tidak Aktif)
                                @endunless

                            </option>

                        @endforeach

                    </select>

                </div>



                <div
                    class="grid
                           md:grid-cols-2
                           gap-5"
                >


                    {{-- DOCUMENT CODE --}}
                    <div>

                        <label
                            for="document_code"
                            class="block
                                   mb-2
                                   text-sm
                                   font-semibold
                                   text-slate-700"
                        >
                            Kode Dokumen
                        </label>


                        <input
                            id="document_code"
                            name="document_code"
                            type="text"

                            value="{{ old(
                                'document_code',
                                $document->document_code
                            ) }}"

                            class="w-full
                                   rounded-xl
                                   border-slate-200
                                   bg-slate-50
                                   px-4
                                   py-3"
                        >

                    </div>



                    {{-- PART NUMBER --}}
                    <div>

                        <label
                            for="part_number"
                            class="block
                                   mb-2
                                   text-sm
                                   font-semibold
                                   text-slate-700"
                        >
                            Nomor Part
                        </label>


                        <input
                            id="part_number"
                            name="part_number"
                            type="text"

                            value="{{ old(
                                'part_number',
                                $document->part_number
                            ) }}"

                            class="w-full
                                   rounded-xl
                                   border-slate-200
                                   bg-slate-50
                                   px-4
                                   py-3"
                        >

                    </div>

                </div>



                {{-- DOCUMENT DATE --}}
                <div>

                    <label
                        for="document_date"
                        class="block
                               mb-2
                               text-sm
                               font-semibold
                               text-slate-700"
                    >
                        Tanggal Dokumen
                    </label>


                    <input
                        id="document_date"
                        name="document_date"
                        type="date"

                        value="{{ old(
                            'document_date',
                            $document->document_date
                                ?->format('Y-m-d')
                        ) }}"

                        class="w-full
                               rounded-xl
                               border-slate-200
                               bg-slate-50
                               px-4
                               py-3"
                    >

                </div>



                {{-- DESCRIPTION --}}
                <div>

                    <label
                        for="description"
                        class="block
                               mb-2
                               text-sm
                               font-semibold
                               text-slate-700"
                    >
                        Deskripsi
                    </label>


                    <textarea
                        id="description"
                        name="description"
                        rows="5"

                        class="w-full
                               rounded-xl
                               border-slate-200
                               bg-slate-50
                               px-4
                               py-3
                               resize-none"
                    >{{ old(
                        'description',
                        $document->description
                    ) }}</textarea>

                </div>



                {{-- KEYWORDS --}}
                <div>

                    <label
                        for="keywords"
                        class="block
                               mb-2
                               text-sm
                               font-semibold
                               text-slate-700"
                    >
                        Kata Kunci Pencarian
                    </label>


                    <input
                        id="keywords"
                        name="keywords"
                        type="text"

                        value="{{ old(
                            'keywords',
                            $document->keywords
                        ) }}"

                        placeholder="inspeksi, bracket, kualitas..."

                        class="w-full
                               rounded-xl
                               border-slate-200
                               bg-slate-50
                               px-4
                               py-3"
                    >

                </div>

            </div>



            <div
                class="px-6
                       py-5

                       border-t
                       border-slate-100

                       bg-slate-50

                       flex
                       justify-end
                       gap-3"
            >

                <a
                    href="{{ $document->is_active
                        ? route(
                            'documents.show',
                            $document
                        )
                        : route(
                            'documents.archived'
                        )
                    }}"

                    class="rounded-xl
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

                    class="rounded-xl
                           bg-blue-800
                           px-5
                           py-2.5
                           text-sm
                           font-semibold
                           text-white
                           hover:bg-blue-900"
                >
                    Simpan Perubahan
                </button>

            </div>

        </form>



        {{-- ===================================================== --}}
        {{-- SIDEBAR --}}
        {{-- ===================================================== --}}

        <aside class="space-y-6">


            {{-- FILE PROTECTION --}}
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
                    File Fisik Terlindungi
                </p>


                <p
                    class="mt-2
                           text-sm
                           leading-relaxed
                           text-emerald-700"
                >
                    Mengubah metadata tidak akan mengganti,
                    mengubah nama, memindahkan, atau menghapus file asli.
                </p>


                <div
                    class="mt-4
                           rounded-xl
                           bg-white/60
                           p-3"
                >

                    <p
                        class="text-xs
                               font-semibold
                               text-slate-500"
                    >
                        File Asli
                    </p>


                    <p
                        class="mt-1
                               text-xs
                               text-slate-700
                               break-all"
                    >
                        {{ $document->file_name }}
                    </p>

                </div>

            </section>



            {{-- ARCHIVE --}}
            @if($document->is_active)

                <section
                    class="rounded-2xl
                           border
                           border-amber-200
                           bg-amber-50
                           p-5"
                >

                    <h3
                        class="font-bold
                               text-amber-900"
                    >
                        Arsipkan Dokumen
                    </h3>


                    <p
                        class="mt-2
                               text-sm
                               leading-relaxed
                               text-amber-700"
                    >
                        Dokumen akan hilang dari pustaka aktif,
                        tetapi file dan riwayatnya tetap tersimpan.
                    </p>


                    <form
                        method="POST"

                        action="{{ route(
                            'documents.archive',
                            $document
                        ) }}"

                        class="mt-5"

                        x-on:submit.prevent="$dispatch('qualityhub-confirm', {
                            form: $event.currentTarget,
                            title: 'Arsipkan Dokumen?',
                            message: 'Dokumen akan dipindahkan dari pustaka aktif ke arsip. File fisik dan seluruh riwayat revisi tetap tersimpan.',
                            confirmText: 'Arsipkan Dokumen'
                        })"
                    >

                        @csrf
                        @method('PATCH')


                        <label
                            for="archive_reason"

                            class="block
                                   mb-2
                                   text-xs
                                   font-semibold
                                   text-amber-800"
                        >
                            Alasan Pengarsipan
                        </label>


                        <textarea
                            id="archive_reason"
                            name="archive_reason"
                            rows="4"
                            required

                            placeholder="Contoh: Digantikan oleh revisi dokumen yang lebih baru."

                            class="w-full
                                   rounded-xl
                                   border-amber-200
                                   bg-white
                                   px-3
                                   py-3
                                   text-sm
                                   resize-none
                                   focus:border-amber-500
                                   focus:ring-amber-500"
                        ></textarea>


                        <button
                            type="submit"

                            class="mt-3
                                   w-full
                                   rounded-xl
                                   bg-amber-600
                                   px-4
                                   py-2.5
                                   text-sm
                                   font-semibold
                                   text-white
                                   hover:bg-amber-700"
                        >
                            Arsipkan Dokumen
                        </button>

                    </form>

                </section>


            @else

                {{-- RESTORE --}}
                <section
                    class="rounded-2xl
                           border
                           border-blue-200
                           bg-blue-50
                           p-5"
                >

                    <h3
                        class="font-bold
                               text-blue-900"
                    >
                        Dokumen Diarsipkan
                    </h3>


                    @if($document->archive_reason)

                        <p
                            class="mt-3
                                   text-xs
                                   font-semibold
                                   text-blue-700"
                        >
                            Alasan Pengarsipan
                        </p>


                        <p
                            class="mt-1
                                   text-sm
                                   text-blue-800"
                        >
                            {{ $document->archive_reason }}
                        </p>

                    @endif


                    <form
                        method="POST"

                        action="{{ route(
                            'documents.restore',
                            $document
                        ) }}"

                        class="mt-5"
                    >

                        @csrf
                        @method('PATCH')


                        <button
                            type="submit"

                            class="w-full
                                   rounded-xl
                                   bg-blue-800
                                   px-4
                                   py-2.5
                                   text-sm
                                   font-semibold
                                   text-white
                                   hover:bg-blue-900"
                        >
                            Pulihkan Dokumen
                        </button>

                    </form>

                </section>

            @endif

        </aside>

    </div>

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