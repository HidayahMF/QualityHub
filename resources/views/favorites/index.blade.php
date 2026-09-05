<x-app-layout>

    {{-- ========================================================= --}}
    {{-- HEADER --}}
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
                       text-amber-700"
            >

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="currentColor"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        d="m12 17.27
                           5.18 3.04
                           -1.4-5.89
                           4.58-3.96
                           -6.03-.52
                           L12 4.38
                           9.67 9.94
                           l-6.03.52
                           4.58 3.96
                           -1.4 5.89Z"
                    />
                </svg>

                Ruang Kerja Pribadi

            </div>


            <h1
                class="text-3xl
                       sm:text-4xl
                       font-bold
                       tracking-tight
                       text-slate-900"
            >
                My Favorites
            </h1>


            <p
                class="mt-2
                       text-sm
                       sm:text-base
                       text-slate-500"
            >
                Akses dengan cepat dokumen yang sering Anda gunakan.
            </p>

        </div>


        <a
            href="{{ route('documents.index') }}"

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

                   hover:bg-blue-900

                   transition"
        >

            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="17"
                height="17"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <circle cx="11" cy="11" r="8"/>
                <path d="m21 21-4.3-4.3"/>
            </svg>

            Jelajahi Dokumen

        </a>

    </section>



    {{-- ========================================================= --}}
    {{-- SUCCESS --}}
    {{-- ========================================================= --}}

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

            <p
                class="text-sm
                       font-semibold
                       text-emerald-700"
            >
                {{ session('success') }}
            </p>


            <button
                type="button"
                @click="show = false"

                class="text-emerald-700
                       hover:text-emerald-900"
            >
                ✕
            </button>

        </div>

    @endif



    {{-- ========================================================= --}}
    {{-- SUMMARY --}}
    {{-- ========================================================= --}}

    <section
        class="mb-6
               bg-gradient-to-r
               from-blue-950
               to-blue-800

               rounded-2xl

               p-6

               text-white

               shadow-lg
               shadow-blue-950/10"
    >

        <div
            class="flex
                   flex-col
                   sm:flex-row
                   sm:items-center
                   sm:justify-between
                   gap-4"
        >

            <div>

                <p
                    class="text-sm
                           font-medium
                           text-blue-200"
                >
                    Dokumen Tersimpan
                </p>


                <p
                    class="mt-2
                           text-4xl
                           font-bold"
                >
                    {{ number_format($totalFavorites) }}
                </p>


                <p
                    class="mt-2
                           text-sm
                           text-blue-200"
                >
                    Dokumen favorit hanya dapat dilihat melalui akun Anda.
                </p>

            </div>


            <div
                class="w-16
                       h-16
                       rounded-2xl

                       bg-white/10

                       flex
                       items-center
                       justify-center

                       text-amber-300"
            >

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="30"
                    height="30"
                    viewBox="0 0 24 24"
                    fill="currentColor"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        d="m12 17.27
                           5.18 3.04
                           -1.4-5.89
                           4.58-3.96
                           -6.03-.52
                           L12 4.38
                           9.67 9.94
                           l-6.03.52
                           4.58 3.96
                           -1.4 5.89Z"
                    />
                </svg>

            </div>

        </div>

    </section>



    {{-- ========================================================= --}}
    {{-- SEARCH FILTER --}}
    {{-- ========================================================= --}}

    <form
        method="GET"
        action="{{ route('favorites.index') }}"

        class="mb-6

               bg-white

               rounded-2xl

               border
               border-slate-200/70

               shadow-sm

               p-5"
    >

        <div
            class="grid
                   md:grid-cols-2
                   xl:grid-cols-5
                   gap-3"
        >

            <div class="xl:col-span-2">

                <input
                    type="text"
                    name="search"

                    value="{{ request('search') }}"

                    placeholder="Cari dokumen favorit Anda..."

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



            <select
                name="extension"

                class="rounded-xl
                       border-slate-200
                       bg-slate-50
                       px-4
                       py-3
                       text-sm"
            >

                <option value="">
                    Semua Jenis File
                </option>


                @foreach($extensions as $extension)

                    <option
                        value="{{ $extension }}"

                        @selected(
                            request('extension')
                            === $extension
                        )
                    >
                        {{ strtoupper($extension) }}
                    </option>

                @endforeach

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
                href="{{ route('favorites.index') }}"

                class="rounded-xl
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
                Atur Ulang
            </a>

        </div>

    </form>



    {{-- ========================================================= --}}
    {{-- FAVORITE DOCUMENTS --}}
    {{-- ========================================================= --}}

    @if($favorites->count())

        <section
            class="grid
                   md:grid-cols-2
                   2xl:grid-cols-3
                   gap-5"
        >

            @foreach($favorites as $favorite)

                @php
                    $document = $favorite->document;
                @endphp


                <article
                    class="group

                           flex
                           flex-col

                           bg-white

                           rounded-2xl

                           border
                           border-slate-200/70

                           shadow-sm

                           hover:shadow-lg
                           hover:border-blue-200

                           transition
                           duration-300

                           overflow-hidden"
                >


                    {{-- CARD HEADER --}}

                    <div class="p-5 flex-1">

                        <div
                            class="flex
                                   items-start
                                   justify-between
                                   gap-4"
                        >

                            <div
                                class="w-12
                                       h-12

                                       rounded-2xl

                                       bg-blue-50
                                       text-blue-700

                                       flex
                                       items-center
                                       justify-center

                                       flex-shrink-0"
                            >

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="22"
                                    height="22"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        d="M14.5 2H6
                                           a2 2 0 0 0-2 2
                                           v16
                                           a2 2 0 0 0 2 2
                                           h12
                                           a2 2 0 0 0 2-2
                                           V7.5Z"
                                    />

                                    <polyline points="14 2 14 8 20 8"/>
                                </svg>

                            </div>


                            <x-favorite-button
                                :document="$document"
                                :favorited="true"
                                :compact="true"
                            />

                        </div>



                        {{-- FILE TYPE --}}

                        <div class="mt-5">

                            <span
                                class="inline-flex
                                       rounded-lg
                                       bg-slate-100
                                       px-2.5
                                       py-1
                                       text-[10px]
                                       font-bold
                                       text-slate-500"
                            >
                                {{ strtoupper(
                                    $document->file_extension
                                    ?? 'FILE'
                                ) }}
                            </span>

                        </div>



                        {{-- TITLE --}}

                        <a
                            href="{{ route(
                                'documents.show',
                                $document
                            ) }}"

                            class="block
                                   mt-3

                                   text-lg
                                   font-bold
                                   text-slate-900

                                   group-hover:text-blue-800

                                   transition"
                        >
                            {{ $document->title }}
                        </a>



                        {{-- ORIGINAL FILE --}}

                        <p
                            class="mt-1

                                   text-xs
                                   text-slate-400

                                   truncate"
                        >
                            {{ $document->file_name }}
                        </p>



                        {{-- METADATA --}}

                        <div
                            class="mt-4
                                   space-y-2"
                        >

                            <div
                                class="flex
                                       justify-between
                                       gap-4

                                       text-xs"
                            >

                                <span class="text-slate-400">
                                    Kode Dokumen
                                </span>

                                <span
                                    class="font-semibold
                                           text-slate-700
                                           text-right"
                                >
                                    {{ $document->document_code ?? '-' }}
                                </span>

                            </div>


                            <div
                                class="flex
                                       justify-between
                                       gap-4

                                       text-xs"
                            >

                                <span class="text-slate-400">
                                    Nomor Part
                                </span>

                                <span
                                    class="font-semibold
                                           text-slate-700
                                           text-right"
                                >
                                    {{ $document->part_number ?? '-' }}
                                </span>

                            </div>


                            <div
                                class="flex
                                       justify-between
                                       gap-4

                                       text-xs"
                            >

                                <span class="text-slate-400">
                                    Kategori
                                </span>

                                <span
                                    class="font-semibold
                                           text-blue-700
                                           text-right"
                                >
                                    {{ $document->category?->name ?? '-' }}
                                </span>

                            </div>


                            <div
                                class="flex
                                       justify-between
                                       gap-4

                                       text-xs"
                            >

                                <span class="text-slate-400">
                                    Departemen
                                </span>

                                <span
                                    class="font-semibold
                                           text-slate-700
                                           text-right"
                                >
                                    {{ $document
                                        ->category
                                        ?->department
                                        ?->name
                                        ?? '-'
                                    }}
                                </span>

                            </div>

                        </div>

                    </div>



                    {{-- CARD FOOTER --}}

                    <div
                        class="border-t
                               border-slate-100

                               bg-slate-50/70

                               px-5
                               py-4"
                    >

                        <div
                            class="mb-3

                                   flex
                                   items-center
                                   justify-between
                                   gap-3

                                   text-[11px]
                                   text-slate-400"
                        >

                            <span>
                                Disimpan
                                {{ $favorite->created_at->locale('id')->diffForHumans() }}
                            </span>


                            @if($document->document_date)

                                <span>
                                    {{ $document->document_date->locale('id')->translatedFormat('d M Y') }}
                                </span>

                            @endif

                        </div>


                        <div
                            class="grid
                                   grid-cols-3
                                   gap-2"
                        >

                            <a
                                href="{{ route(
                                    'documents.show',
                                    $document
                                ) }}"

                                class="rounded-lg
                                       border
                                       border-slate-200
                                       bg-white
                                       px-3
                                       py-2
                                       text-center
                                       text-xs
                                       font-semibold
                                       text-slate-600
                                       hover:bg-slate-50"
                            >
                                Detail
                            </a>


                            <a
                                href="{{ route(
                                    'documents.open',
                                    $document
                                ) }}"

                                target="_blank"

                                rel="noopener noreferrer"

                                class="rounded-lg
                                       bg-blue-50
                                       px-3
                                       py-2
                                       text-center
                                       text-xs
                                       font-semibold
                                       text-blue-700
                                       hover:bg-blue-100"
                            >
                                Buka
                            </a>


                            <a
                                href="{{ route(
                                    'documents.download',
                                    $document
                                ) }}"

                                class="rounded-lg
                                       bg-blue-800
                                       px-3
                                       py-2
                                       text-center
                                       text-xs
                                       font-semibold
                                       text-white
                                       hover:bg-blue-900"
                            >
                                Unduh
                            </a>

                        </div>

                    </div>

                </article>

            @endforeach

        </section>



        @if($favorites->hasPages())

            <div
                class="mt-6

                       bg-white

                       rounded-2xl

                       border
                       border-slate-200

                       px-5
                       py-4"
            >
                {{ $favorites->links() }}
            </div>

        @endif


    @else

        {{-- EMPTY STATE --}}

        <section
            class="bg-white

                   rounded-2xl

                   border
                   border-slate-200

                   py-20
                   px-6

                   text-center

                   shadow-sm"
        >

            <div
                class="w-16
                       h-16

                       mx-auto

                       rounded-2xl

                       bg-amber-50
                       text-amber-500

                       flex
                       items-center
                       justify-center"
            >

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="28"
                    height="28"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        d="m12 17.27
                           5.18 3.04
                           -1.4-5.89
                           4.58-3.96
                           -6.03-.52
                           L12 4.38
                           9.67 9.94
                           l-6.03.52
                           4.58 3.96
                           -1.4 5.89Z"
                    />
                </svg>

            </div>


            <h2
                class="mt-5
                       text-xl
                       font-bold
                       text-slate-800"
            >
                Belum ada dokumen favorit
            </h2>


            <p
                class="mt-2
                       text-sm
                       text-slate-500"
            >
                Simpan dokumen yang sering digunakan agar Anda dapat
                menemukannya dengan cepat di sini.
            </p>


            <a
                href="{{ route('documents.index') }}"

                class="mt-6

                       inline-flex
                       items-center
                       justify-center

                       rounded-xl

                       bg-blue-800

                       px-5
                       py-2.5

                       text-sm
                       font-semibold
                       text-white

                       hover:bg-blue-900"
            >
                Jelajahi Dokumen
            </a>

        </section>

    @endif

</x-app-layout>