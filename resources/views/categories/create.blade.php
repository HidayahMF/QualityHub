<x-app-layout>

    <section class="mb-8">

        <a
            href="{{ route('categories.index') }}"
            class="inline-flex
                   items-center
                   gap-2
                   mb-5
                   text-sm
                   font-semibold
                   text-slate-500
                   hover:text-blue-700"
        >
            ← Kembali ke Kategori
        </a>


        <h1
            class="text-3xl
                   sm:text-4xl
                   font-bold
                   text-slate-900"
        >
            Add Category
        </h1>


        <p class="mt-2 text-slate-500">
            Buat klasifikasi dokumen baru yang akan digunakan di BMC QualityHub.
        </p>

    </section>


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


    <form
        method="POST"
        action="{{ route('categories.store') }}"
        class="max-w-3xl
               bg-white
               rounded-2xl
               border
               border-slate-200
               shadow-sm"
    >

        @csrf


        <div class="p-6 space-y-6">


            {{-- DEPARTMENT --}}

            <div>

                <label
                    for="department_id"
                    class="block
                           mb-2
                           text-sm
                           font-semibold
                           text-slate-700"
                >
                    Departemen

                    <span class="text-red-500">
                        *
                    </span>
                </label>


                <select
                    id="department_id"
                    name="department_id"
                    required
                    class="w-full
                           rounded-xl
                           border-slate-200
                           bg-slate-50
                           px-4
                           py-3"
                >

                    <option value="">
                        Pilih Departemen
                    </option>


                    @foreach($departments as $department)

                        <option
                            value="{{ $department->id }}"
                            @selected(
                                old('department_id')
                                == $department->id
                            )
                        >
                            {{ $department->name }}
                            ({{ $department->code }})
                        </option>

                    @endforeach

                </select>

            </div>



            {{-- NAME --}}

            <div>

                <label
                    for="name"
                    class="block
                           mb-2
                           text-sm
                           font-semibold
                           text-slate-700"
                >
                    Nama Kategori

                    <span class="text-red-500">
                        *
                    </span>
                </label>


                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name') }}"
                    required
                    placeholder="Contoh: Keluhan Pelanggan"
                    class="w-full
                           rounded-xl
                           border-slate-200
                           bg-slate-50
                           px-4
                           py-3"
                >


                <p class="mt-2 text-xs text-slate-400">
                    Slug kategori akan dibuat secara otomatis.
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
                    placeholder="Jelaskan jenis dokumen yang termasuk dalam kategori ini..."
                    class="w-full
                           rounded-xl
                           border-slate-200
                           bg-slate-50
                           px-4
                           py-3
                           resize-none"
                >{{ old('description') }}</textarea>

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
                href="{{ route('categories.index') }}"
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
                Simpan Kategori
            </button>

        </div>

    </form>

</x-app-layout>
