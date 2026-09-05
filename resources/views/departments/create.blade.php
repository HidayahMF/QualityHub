<x-app-layout>

    {{-- ========================================================= --}}
    {{-- PAGE HEADER --}}
    {{-- ========================================================= --}}

    <section class="mb-8">

        <a
            href="{{ route('departments.index') }}"

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
            ← Kembali ke Departemen
        </a>


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
            Add Department
        </h1>


        <p
            class="mt-2
                   max-w-2xl
                   text-sm
                   sm:text-base
                   text-slate-500"
        >
            Tambahkan departemen baru yang nantinya dapat digunakan untuk
            mengelompokkan kategori dokumen dan akun pengguna.
        </p>

    </section>


    {{-- ========================================================= --}}
    {{-- VALIDATION ERRORS --}}
    {{-- ========================================================= --}}

    @if($errors->any())

        <div
            class="mb-6
                   max-w-3xl
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
    {{-- FORM --}}
    {{-- ========================================================= --}}

    <form
        method="POST"
        action="{{ route('departments.store') }}"

        class="max-w-3xl
               overflow-hidden
               rounded-2xl
               border
               border-slate-200
               bg-white
               shadow-sm"
    >

        @csrf


        <div
            class="border-b
                   border-slate-100
                   px-6
                   py-5"
        >
            <h2 class="font-bold text-slate-900">
                Informasi Departemen
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Nama dan kode akan digunakan sebagai identitas departemen
                di seluruh BMC QualityHub.
            </p>
        </div>


        <div class="space-y-6 p-6">

            {{-- NAME --}}
            <div>

                <label
                    for="name"
                    class="mb-2 block text-sm font-semibold text-slate-700"
                >
                    Nama Departemen
                    <span class="text-red-500">*</span>
                </label>


                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name') }}"
                    required
                    maxlength="255"
                    autocomplete="off"
                    placeholder="Contoh: Quality Control"

                    class="w-full
                           rounded-xl
                           border-slate-200
                           bg-slate-50
                           px-4
                           py-3
                           text-slate-900
                           focus:border-blue-500
                           focus:ring-blue-500"
                >

                <p class="mt-2 text-xs text-slate-400">
                    Gunakan nama departemen resmi yang mudah dikenali pengguna.
                </p>

            </div>


            {{-- CODE --}}
            <div>

                <label
                    for="code"
                    class="mb-2 block text-sm font-semibold text-slate-700"
                >
                    Kode Departemen
                    <span class="text-red-500">*</span>
                </label>


                <input
                    id="code"
                    name="code"
                    type="text"
                    value="{{ old('code') }}"
                    required
                    maxlength="20"
                    autocomplete="off"
                    spellcheck="false"
                    placeholder="Contoh: QC"

                    class="w-full
                           rounded-xl
                           border-slate-200
                           bg-slate-50
                           px-4
                           py-3
                           font-mono
                           uppercase
                           text-slate-900
                           focus:border-blue-500
                           focus:ring-blue-500"
                >

                <p class="mt-2 text-xs text-slate-400">
                    Maksimal 20 karakter. Hanya huruf, angka, tanda hubung,
                    dan garis bawah. Sistem akan menyimpannya dalam huruf kapital.
                </p>

            </div>


            {{-- DESCRIPTION --}}
            <div>

                <label
                    for="description"
                    class="mb-2 block text-sm font-semibold text-slate-700"
                >
                    Deskripsi
                </label>


                <textarea
                    id="description"
                    name="description"
                    rows="5"
                    maxlength="2000"
                    placeholder="Jelaskan fungsi atau ruang lingkup departemen..."

                    class="w-full
                           resize-y
                           rounded-xl
                           border-slate-200
                           bg-slate-50
                           px-4
                           py-3
                           text-slate-900
                           focus:border-blue-500
                           focus:ring-blue-500"
                >{{ old('description') }}</textarea>

                <p class="mt-2 text-xs text-slate-400">
                    Opsional, maksimal 2.000 karakter.
                </p>

            </div>

        </div>


        <div
            class="flex
                   flex-col-reverse
                   sm:flex-row
                   sm:items-center
                   sm:justify-end
                   gap-3
                   border-t
                   border-slate-100
                   bg-slate-50/60
                   px-6
                   py-5"
        >

            <a
                href="{{ route('departments.index') }}"

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
                Batal
            </a>


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
                Simpan Departemen
            </button>

        </div>

    </form>

</x-app-layout>
