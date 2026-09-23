<x-app-layout>


    {{-- ========================================================= --}}
    {{-- PAGE HEADER --}}
    {{-- ========================================================= --}}

    <section class="mb-8">

        <a
            href="{{ route('documents.index') }}"

            class="inline-flex
                   items-center
                   gap-2

                   mb-5

                   text-sm
                   font-semibold
                   text-slate-500

                   hover:text-blue-700

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
                <path d="m15 18-6-6 6-6"/>
            </svg>

            Kembali ke Dokumen

        </a>



        <div
            class="inline-flex
                   items-center
                   gap-2

                   text-sm
                   font-semibold
                   text-blue-700
                   mb-2"
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
            Add New Document
        </h1>


        <p
            class="mt-2
                   text-sm
                   sm:text-base
                   text-slate-500"
        >
            Daftarkan metadata dan simpan dokumen BMC baru dengan aman.
        </p>

    </section>



    {{-- ========================================================= --}}
    {{-- VALIDATION ERRORS --}}
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

            <div class="flex items-start gap-3">


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
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 8v4"/>
                        <path d="M12 16h.01"/>
                    </svg>

                </div>


                <div>

                    <p
                        class="font-bold
                               text-red-900"
                    >
                        Silakan periksa kembali informasi dokumen.
                    </p>


                    <ul
                        class="mt-2

                               list-disc
                               list-inside

                               text-sm
                               text-red-700

                               space-y-1"
                    >

                        @foreach($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            </div>

        </div>

    @endif



    {{-- ========================================================= --}}
    {{-- FORM --}}
    {{-- ========================================================= --}}

    <form
        action="{{ route('documents.store') }}"

        method="POST"

        enctype="multipart/form-data"

        class="grid
               xl:grid-cols-3
               gap-6"
    >

        @csrf



        {{-- ===================================================== --}}
        {{-- LEFT COLUMN --}}
        {{-- ===================================================== --}}

        <div
            class="xl:col-span-2
                   space-y-6"
        >


            {{-- ================================================= --}}
            {{-- DOCUMENT INFORMATION --}}
            {{-- ================================================= --}}

            <section
                class="bg-white

                       rounded-2xl

                       border
                       border-slate-200/70

                       shadow-sm

                       overflow-hidden"
            >


                <div
                    class="px-6
                           py-5

                           border-b
                           border-slate-100"
                >

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
                                width="19"
                                height="19"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5Z"/>
                                <polyline points="14 2 14 8 20 8"/>
                            </svg>

                        </div>


                        <div>

                            <h2
                                class="font-bold
                                       text-slate-900"
                            >
                                Informasi Dokumen
                            </h2>

                            <p
                                class="mt-1
                                       text-sm
                                       text-slate-500"
                            >
                                Metadata yang digunakan QualityHub untuk mengenali
                                dan mencari dokumen.
                            </p>

                        </div>

                    </div>

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

                            <span class="text-red-500">
                                *
                            </span>

                        </label>


                        <input
                            id="title"
                            name="title"

                            type="text"

                            value="{{ old('title') }}"

                            required

                            placeholder="Contoh: Final Inspection Bracket A"

                            class="w-full

                                   rounded-xl

                                   border-slate-200

                                   bg-slate-50

                                   px-4
                                   py-3

                                   text-slate-900

                                   placeholder:text-slate-400

                                   focus:border-blue-500
                                   focus:ring-blue-500

                                   transition"
                        >

                    </div>



                    {{-- CATEGORY --}}

                    <div>

                        <x-category-combobox
                            :categories="$categories"
                            :departments="$departments"
                            :allow-create="$allowCategoryCreation"
                            label="Kategori Dokumen"
                            placeholder="Cari atau ketik kategori..."
                        />

                    </div>



                    {{-- CODE & PART NUMBER --}}

                    <div
                        class="grid
                               sm:grid-cols-2
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

                                value="{{ old('document_code') }}"

                                placeholder="Contoh: FI-2026-001"

                                class="w-full

                                       rounded-xl

                                       border-slate-200

                                       bg-slate-50

                                       px-4
                                       py-3

                                       placeholder:text-slate-400

                                       focus:border-blue-500
                                       focus:ring-blue-500"
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

                                value="{{ old('part_number') }}"

                                placeholder="Contoh: BRK-001"

                                class="w-full

                                       rounded-xl

                                       border-slate-200

                                       bg-slate-50

                                       px-4
                                       py-3

                                       placeholder:text-slate-400

                                       focus:border-blue-500
                                       focus:ring-blue-500"
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

                            value="{{ old('document_date') }}"

                            class="w-full

                                   rounded-xl

                                   border-slate-200

                                   bg-slate-50

                                   px-4
                                   py-3

                                   focus:border-blue-500
                                   focus:ring-blue-500"
                        >

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

                            value="{{ old('keywords') }}"

                            placeholder="bracket, inspeksi, kualitas, pelanggan..."

                            class="w-full

                                   rounded-xl

                                   border-slate-200

                                   bg-slate-50

                                   px-4
                                   py-3

                                   placeholder:text-slate-400

                                   focus:border-blue-500
                                   focus:ring-blue-500"
                        >


                        <p
                            class="mt-2
                                   text-xs
                                   text-slate-400"
                        >
                            Pisahkan beberapa kata kunci dengan tanda koma.
                        </p>

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

                            placeholder="Tuliskan deskripsi singkat tentang dokumen ini..."

                            class="w-full

                                   rounded-xl

                                   border-slate-200

                                   bg-slate-50

                                   px-4
                                   py-3

                                   resize-none

                                   placeholder:text-slate-400

                                   focus:border-blue-500
                                   focus:ring-blue-500"
                        >{{ old('description') }}</textarea>

                    </div>

                </div>

            </section>

        </div>



        {{-- ===================================================== --}}
        {{-- RIGHT COLUMN --}}
        {{-- ===================================================== --}}

        <aside class="space-y-6">


            {{-- ================================================= --}}
            {{-- FILE UPLOAD --}}
            {{-- ================================================= --}}

            <section
                x-data="documentFileUpload"

                class="bg-white

                       rounded-2xl

                       border
                       border-slate-200/70

                       shadow-sm

                       overflow-hidden"
            >


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
                        File Dokumen
                    </h2>

                    <p
                        class="mt-1
                               text-sm
                               text-slate-500"
                    >
                        Unggah file dokumen asli.
                    </p>

                </div>



                <div class="p-6">


                    <label
                        for="file"

                        class="relative

                               min-h-[260px]

                               flex
                               flex-col
                               items-center
                               justify-center

                               rounded-2xl

                               border-2
                               border-dashed
                               border-slate-300

                               bg-slate-50

                               px-5
                               py-8

                               text-center

                               cursor-pointer

                               hover:border-blue-400
                               hover:bg-blue-50/50

                               transition
                               duration-200"
                    >


                        <div
                            class="w-14
                                   h-14

                                   rounded-2xl

                                   bg-blue-100
                                   text-blue-700

                                   flex
                                   items-center
                                   justify-center"
                        >

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="25"
                                height="25"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="17 8 12 3 7 8"/>
                                <line x1="12" x2="12" y1="3" y2="15"/>
                            </svg>

                        </div>



                        {{-- BEFORE SELECTING FILE --}}

                        <div x-show="!fileName">

                            <p
                                class="mt-4

                                       font-semibold
                                       text-slate-700"
                            >
                                Pilih dokumen
                            </p>


                            <p
                                class="mt-1

                                       text-xs
                                       text-slate-400"
                            >
                                Klik di sini untuk memilih file
                            </p>

                        </div>



                        {{-- AFTER SELECTING FILE --}}

                        <div
                            x-show="fileName"

                            x-cloak
                        >

                            <p
                                x-text="fileName"

                                class="mt-4

                                       text-sm
                                       font-semibold
                                       text-blue-800

                                       break-all"
                            ></p>


                            <p
                                x-text="fileSize"

                                class="mt-1
                                       text-xs
                                       text-slate-400"
                            ></p>

                        </div>



                        <div
                            class="mt-5

                                   rounded-lg

                                   bg-white

                                   px-3
                                   py-2

                                   text-[11px]
                                   text-slate-500

                                   border
                                   border-slate-200"
                        >
                            PDF · Excel · Word · CSV · TXT
                        </div>


                        <p
                            class="mt-2
                                   text-[11px]
                                   text-slate-400"
                        >
                            Ukuran file maksimum: 20 MB
                        </p>



                        <input
                            id="file"
                            name="file"

                            type="file"

                            required

                            accept=".pdf,.xls,.xlsx,.doc,.docx,.csv,.txt"

                            class="hidden"

                            @change="handleFile($event)"
                        >

                    </label>

                </div>

            </section>



            {{-- ================================================= --}}
            {{-- STORAGE SECURITY --}}
            {{-- ================================================= --}}

            <section
                class="rounded-2xl

                       border
                       border-blue-100

                       bg-blue-50

                       p-5"
            >

                <div class="flex gap-3">

                    <div
                        class="w-10
                               h-10

                               rounded-xl

                               bg-blue-100
                               text-blue-700

                               flex
                               items-center
                               justify-center

                               flex-shrink-0"
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


                    <div>

                        <p
                            class="text-sm
                                   font-bold
                                   text-blue-900"
                        >
                            Penyimpanan Internal
                        </p>


                        <p
                            class="mt-1

                                   text-xs
                                   leading-relaxed
                                   text-blue-700"
                        >
                            Dokumen disimpan di luar direktori web publik
                            dan dikelola melalui
                            BMC QualityHub.
                        </p>

                    </div>

                </div>

            </section>



            {{-- ================================================= --}}
            {{-- STORAGE INFORMATION --}}
            {{-- ================================================= --}}

            <section
                class="bg-white

                       rounded-2xl

                       border
                       border-slate-200/70

                       p-5

                       shadow-sm"
            >

                <p
                    class="text-xs
                           uppercase
                           tracking-wider
                           font-semibold
                           text-slate-400"
                >
                    Penyimpanan Pengembangan
                </p>


                <p
                    class="mt-3

                           text-sm
                           font-semibold
                           text-slate-700

                           break-all"
                >
                    D:\BMC_Data\SmartFindDocuments
                </p>


                <div
                    class="mt-4

                           flex
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

                    Penyimpanan tersedia

                </div>

            </section>



            {{-- ================================================= --}}
            {{-- SUBMIT --}}
            {{-- ================================================= --}}

            <button
                type="submit"

                class="w-full

                       rounded-xl

                       bg-blue-800

                       px-5
                       py-3.5

                       font-semibold
                       text-white

                       shadow-lg
                       shadow-blue-900/10

                       hover:bg-blue-900
                       hover:-translate-y-0.5

                       active:translate-y-0

                       transition
                       duration-200"
            >

                Simpan Dokumen

            </button>



            {{-- CANCEL --}}

            <a
                href="{{ route('documents.index') }}"

                class="block

                       w-full

                       rounded-xl

                       border
                       border-slate-200

                       bg-white

                       px-5
                       py-3

                       text-center
                       text-sm
                       font-semibold
                       text-slate-600

                       hover:bg-slate-50

                       transition"
            >
                Batal
            </a>

        </aside>

    </form>

</x-app-layout>
