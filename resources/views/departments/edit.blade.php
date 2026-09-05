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

            Data Master
        </div>


        <h1
            class="text-3xl
                   sm:text-4xl
                   font-bold
                   tracking-tight
                   text-slate-900"
        >
            Edit Department
        </h1>


        <p
            class="mt-2
                   max-w-2xl
                   text-sm
                   sm:text-base
                   text-slate-500"
        >
            Perbarui identitas dan deskripsi departemen tanpa mengubah
            hubungan kategori atau pengguna yang sudah tersimpan.
        </p>

    </section>


    {{-- ========================================================= --}}
    {{-- VALIDATION ERRORS --}}
    {{-- ========================================================= --}}

    @if($errors->any())

        <div
            class="mb-6
                   max-w-5xl
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


    <div
        class="grid
               grid-cols-1
               xl:grid-cols-[minmax(0,1fr)_320px]
               gap-6
               max-w-6xl"
    >

        {{-- ===================================================== --}}
        {{-- EDIT FORM --}}
        {{-- ===================================================== --}}

        <form
            method="POST"

            action="{{ route(
                'departments.update',
                $department
            ) }}"

            class="overflow-hidden
                   rounded-2xl
                   border
                   border-slate-200
                   bg-white
                   shadow-sm"
        >

            @csrf
            @method('PUT')


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
                    Perubahan nama, kode, dan deskripsi akan dicatat
                    di Log Aktivitas.
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

                        value="{{ old(
                            'name',
                            $department->name
                        ) }}"

                        required
                        maxlength="255"
                        autocomplete="off"

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

                        value="{{ old(
                            'code',
                            $department->code
                        ) }}"

                        required
                        maxlength="20"
                        autocomplete="off"
                        spellcheck="false"

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
                        dan garis bawah.
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
                        rows="6"
                        maxlength="2000"

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
                    >{{ old(
                        'description',
                        $department->description
                    ) }}</textarea>

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
                    Simpan Perubahan
                </button>

            </div>

        </form>


        {{-- ===================================================== --}}
        {{-- RELATION / STATUS INFORMATION --}}
        {{-- ===================================================== --}}

        <aside class="space-y-5">

            <section
                class="rounded-2xl
                       border
                       border-slate-200
                       bg-white
                       p-5
                       shadow-sm"
            >

                <p
                    class="text-xs
                           font-semibold
                           uppercase
                           tracking-wider
                           text-slate-400"
                >
                    Status Departemen
                </p>


                <div class="mt-3">

                    @if($department->is_active)

                        <span
                            class="inline-flex
                                   items-center
                                   gap-1.5
                                   rounded-lg
                                   bg-emerald-50
                                   px-3
                                   py-1.5
                                   text-sm
                                   font-semibold
                                   text-emerald-700"
                        >
                            <span
                                class="h-2
                                       w-2
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
                                   px-3
                                   py-1.5
                                   text-sm
                                   font-semibold
                                   text-slate-600"
                        >
                            <span
                                class="h-2
                                       w-2
                                       rounded-full
                                       bg-slate-400"
                            ></span>

                            Tidak Aktif
                        </span>

                    @endif

                </div>


                <p class="mt-4 text-sm leading-relaxed text-slate-500">
                    Status dapat diubah dari halaman pengelolaan departemen.
                    Departemen aktif yang masih digunakan tidak dapat
                    dinonaktifkan secara sembarangan.
                </p>

            </section>


            <section
                class="rounded-2xl
                       border
                       border-slate-200
                       bg-white
                       p-5
                       shadow-sm"
            >

                <p
                    class="text-xs
                           font-semibold
                           uppercase
                           tracking-wider
                           text-slate-400"
                >
                    Penggunaan Data
                </p>


                <div
                    class="mt-4
                           grid
                           grid-cols-2
                           gap-3"
                >

                    <div
                        class="rounded-xl
                               bg-blue-50
                               p-4"
                    >
                        <p class="text-xs font-medium text-blue-600">
                            Kategori
                        </p>

                        <p class="mt-1 text-2xl font-bold text-blue-900">
                            {{ number_format($department->categories_count) }}
                        </p>
                    </div>


                    <div
                        class="rounded-xl
                               bg-slate-100
                               p-4"
                    >
                        <p class="text-xs font-medium text-slate-500">
                            Pengguna
                        </p>

                        <p class="mt-1 text-2xl font-bold text-slate-800">
                            {{ number_format($department->users_count) }}
                        </p>
                    </div>

                </div>


                @if(
                    $department->is_active
                    && (
                        $department->categories_count > 0
                        || $department->users_count > 0
                    )
                )

                    <div
                        class="mt-4
                               rounded-xl
                               border
                               border-amber-200
                               bg-amber-50
                               p-4"
                    >
                        <p class="text-sm font-semibold text-amber-900">
                            Perhatikan relasi departemen
                        </p>

                        <p
                            class="mt-1
                                   text-xs
                                   leading-relaxed
                                   text-amber-700"
                        >
                            Sebagian relasi mungkin masih aktif. Sistem akan
                            melakukan pemeriksaan ulang sebelum departemen
                            dapat dinonaktifkan.
                        </p>
                    </div>

                @endif

            </section>

        </aside>

    </div>

</x-app-layout>
