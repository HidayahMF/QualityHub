<x-app-layout>

    @php
        $isFavorite = (bool) ($isFavorited ?? false);

        $currentRevision = (int) ($document->current_revision ?? 0);
        $currentRevisionLabel = $document->getCurrentRevisionLabel();

        $extension = strtolower(
            $document->file_extension ?? ''
        );

        /*
        |--------------------------------------------------------------------------
        | Browser Preview Capability
        |--------------------------------------------------------------------------
        | Route documents.open tetap dipertahankan.
        | Tombol pratinjau hanya ditampilkan untuk format yang memang
        | dapat dirender langsung oleh browser pada implementasi saat ini.
        */
        $canPreviewInBrowser = in_array(
            $extension,
            ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'tif', 'tiff', 'pdf', 'txt', 'csv'],
            true
        );

        $fileIconClasses = match($extension) {
            'pdf' =>
                'bg-red-50 text-red-700',

            'xls',
            'xlsx',
            'csv' =>
                'bg-emerald-50 text-emerald-700',

            'doc',
            'docx' =>
                'bg-blue-50 text-blue-700',

            'txt' =>
                'bg-slate-100 text-slate-600',

            default =>
                'bg-indigo-50 text-indigo-700',
        };

        $keywords = $document->keywords
            ? array_values(
                array_filter(
                    array_map(
                        'trim',
                        explode(',', $document->keywords)
                    )
                )
            )
            : [];
    @endphp


    {{-- ========================================================= --}}
    {{-- SUCCESS MESSAGE --}}
    {{-- ========================================================= --}}

    @if(session('success'))

        <div
            x-data="{ show: true }"
            x-show="show"

            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"

            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"

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

            <div
                class="flex
                       items-start
                       gap-3"
            >

                <div
                    class="w-10
                           h-10

                           rounded-xl

                           bg-emerald-100
                           text-emerald-700

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
                        <path d="m9 12 2 2 4-4"/>
                        <circle cx="12" cy="12" r="9"/>
                    </svg>

                </div>


                <div>

                    <p
                        class="text-sm
                               font-bold
                               text-emerald-900"
                    >
                        Berhasil
                    </p>


                    <p
                        class="mt-1
                               text-sm
                               text-emerald-700"
                    >
                        {{ session('success') }}
                    </p>

                </div>

            </div>


            <button
                type="button"

                @click="show = false"

                class="text-emerald-600
                       hover:text-emerald-900
                       transition"

                aria-label="Tutup notifikasi"
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
                    <path d="M18 6 6 18"/>
                    <path d="m6 6 12 12"/>
                </svg>

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

            <div
                class="flex
                       items-start
                       gap-3"
            >

                <div
                    class="w-10
                           h-10

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
                        width="19"
                        height="19"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 8v4"/>
                        <path d="M12 16h.01"/>
                    </svg>

                </div>


                <div>

                    <p
                        class="text-sm
                               font-bold
                               text-red-900"
                    >
                        Ada hal yang perlu diperiksa
                    </p>


                    <ul
                        class="mt-2
                               space-y-1
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

            </div>

        </div>

    @endif



    {{-- ========================================================= --}}
    {{-- PAGE HEADER --}}
    {{-- ========================================================= --}}

    <section
        class="flex
               flex-col
               xl:flex-row
               xl:items-start
               xl:justify-between

               gap-6
               mb-8"
    >

        <div class="min-w-0">


            {{-- BACK --}}

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



            {{-- PAGE LABEL --}}

            <div
                class="flex
                       flex-wrap
                       items-center
                       gap-2
                       mb-3"
            >

                <div
                    class="inline-flex
                           items-center
                           gap-2

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

                    Document Detail

                </div>



                {{-- ACTIVE STATUS --}}

                <span
                    class="inline-flex
                           items-center
                           gap-1.5

                           rounded-lg

                           bg-emerald-50

                           px-2.5
                           py-1

                           text-[11px]
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



                {{-- CURRENT REVISION STATUS --}}

                @if(Route::has('documents.revisions.index'))

                    <a
                        href="{{ route(
                            'documents.revisions.index',
                            $document
                        ) }}"

                        class="inline-flex
                               items-center
                               gap-1.5

                               rounded-lg

                               bg-indigo-50

                               px-2.5
                               py-1

                               text-[11px]
                               font-semibold
                               text-indigo-700

                               hover:bg-indigo-100
                               transition"
                    >

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="12"
                            height="12"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M3 12a9 9 0 1 0 3-6.7L3 8"/>
                            <path d="M3 3v5h5"/>
                            <path d="M12 7v5l3 2"/>
                        </svg>

                        {{ $currentRevisionLabel }}

                    </a>

                @endif



                {{-- FAVORITE STATUS --}}

                @if($isFavorite)

                    <span
                        class="inline-flex
                               items-center
                               gap-1.5

                               rounded-lg

                               bg-amber-50

                               px-2.5
                               py-1

                               text-[11px]
                               font-semibold
                               text-amber-700"
                    >

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="12"
                            height="12"
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

                        Favorit

                    </span>

                @endif

            </div>



            {{-- TITLE --}}

            <h1
                class="text-3xl
                       sm:text-4xl

                       font-bold
                       tracking-tight
                       text-slate-900

                       break-words"
            >
                {{ $document->title }}
            </h1>



            {{-- ORIGINAL FILE NAME --}}

            <div
                class="mt-3

                       flex
                       flex-wrap
                       items-center
                       gap-2"
            >

                <p
                    class="text-sm
                           text-slate-500
                           break-all"
                >
                    {{ $document->file_name }}
                </p>


                <span
                    class="inline-flex

                           rounded-md

                           bg-slate-100

                           px-2
                           py-1

                           text-[10px]
                           font-bold
                           uppercase
                           text-slate-500"
                >
                    {{ $document->getFileTypeLabel() }}
                </span>

            </div>

        </div>



        {{-- ===================================================== --}}
        {{-- ACTION BUTTONS --}}
        {{-- ===================================================== --}}

        <div
            class="flex
                   flex-col
                   sm:flex-row
                   sm:flex-wrap

                   gap-3

                   flex-shrink-0"
        >


            {{-- EDIT METADATA --}}
            {{-- ADMIN + SUPER ADMIN --}}

            @if(
                Auth::user()->isAdmin()
                && Route::has('documents.edit')
            )

                <a
                    href="{{ route(
                        'documents.edit',
                        $document
                    ) }}"

                    class="inline-flex
                           items-center
                           justify-center
                           gap-2

                           rounded-xl

                           border
                           border-slate-200

                           bg-white

                           px-5
                           py-3

                           text-sm
                           font-semibold
                           text-slate-700

                           hover:bg-slate-50
                           hover:border-slate-300

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
                        <path
                            d="M12 20h9"
                        />

                        <path
                            d="M16.5 3.5
                               a2.121 2.121 0 0 1 3 3
                               L7 19
                               l-4 1
                               1-4Z"
                        />
                    </svg>

                    Ubah

                </a>

            @endif



            {{-- FAVORITE --}}

            @if(
                Route::has('favorites.store')
                && Route::has('favorites.destroy')
            )

                <x-favorite-button
                    :document="$document"
                    :favorited="$isFavorite"
                />

            @endif



            {{-- ARCHIVE DOCUMENT: action langsung dari Document Detail. --}}
            {{-- Gunakan authorization serta endpoint arsip existing. --}}
            @if(
                Auth::user()->isAdmin()
                && $document->is_active
                && Route::has('documents.archive')
            )
                <button
                    type="button"
                    x-on:click.prevent="$dispatch('open-modal', 'archive-document-{{ $document->id }}')"
                    aria-haspopup="dialog"
                    class="inline-flex items-center justify-center gap-2
                           rounded-xl border border-sky-200 bg-sky-50
                           px-5 py-3 text-sm font-semibold text-sky-800
                           hover:bg-sky-100 hover:border-sky-300
                           focus-visible:outline-none focus-visible:ring-2
                           focus-visible:ring-blue-500 focus-visible:ring-offset-2
                           transition"
                >
                    <svg xmlns="http://www.w3.org/2000/svg"
                         width="18" height="18" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round"
                         aria-hidden="true">
                        <rect x="3" y="4" width="18" height="4" rx="1" />
                        <path d="M5 8v11a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8" />
                        <path d="M10 12h4" />
                    </svg>
                    Arsipkan
                </button>
            @endif


            {{-- REVISION HISTORY --}}

            @if(Route::has('documents.revisions.index'))

                <a
                    href="{{ route(
                        'documents.revisions.index',
                        $document
                    ) }}"

                    class="inline-flex
                           items-center
                           justify-center
                           gap-2

                           rounded-xl

                           border
                           border-indigo-200

                           bg-indigo-50

                           px-5
                           py-3

                           text-sm
                           font-semibold
                           text-indigo-700

                           hover:bg-indigo-100
                           hover:border-indigo-300

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
                        <path d="M3 12a9 9 0 1 0 3-6.7L3 8"/>
                        <path d="M3 3v5h5"/>
                        <path d="M12 7v5l3 2"/>
                    </svg>

                    Riwayat Revisi

                    <span
                        class="rounded-md

                               bg-indigo-100

                               px-2
                               py-0.5

                               text-[10px]
                               font-bold
                               text-indigo-700"
                    >
                        {{ $currentRevisionLabel }}
                    </span>

                </a>

            @endif



            {{-- OPEN / PREVIEW --}}

            @if($canPreviewInBrowser)

                <a
                    href="{{ route(
                        'documents.open',
                        $document
                    ) }}"

                    target="_blank"

                    rel="noopener noreferrer"

                    class="inline-flex
                           items-center
                           justify-center
                           gap-2

                           rounded-xl

                           border
                           border-blue-200

                           bg-blue-50

                           px-5
                           py-3

                           text-sm
                           font-semibold
                           text-blue-800

                           hover:bg-blue-100
                           hover:border-blue-300

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
                        <path
                            d="M2.062 12.348
                               a1 1 0 0 1 0-.696
                               10.75 10.75 0 0 1 19.876 0
                               1 1 0 0 1 0 .696
                               10.75 10.75 0 0 1-19.876 0"
                        />

                        <circle
                            cx="12"
                            cy="12"
                            r="3"
                        />
                    </svg>

                    Buka / Pratinjau

                </a>

            @else

                <div
                    class="inline-flex
                           items-center
                           gap-2

                           rounded-xl

                           border
                           border-slate-200

                           bg-slate-50

                           px-4
                           py-3

                           text-xs
                           font-medium
                           text-slate-500"
                    title="Format {{ strtoupper($extension ?: 'FILE') }} tidak mendukung pratinjau langsung di browser."
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        aria-hidden="true"
                    >
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v-4"/>
                        <path d="M12 8h.01"/>
                    </svg>

                    {{ strtoupper($extension ?: 'File') }}
                    tidak mendukung pratinjau browser.
                    Gunakan tombol Unduh.

                </div>

            @endif



            {{-- DOWNLOAD --}}

            <a
                href="{{ route(
                    'documents.download',
                    $document
                ) }}"

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
                    <path
                        d="M21 15v4
                           a2 2 0 0 1-2 2
                           H5
                           a2 2 0 0 1-2-2
                           v-4"
                    />

                    <polyline
                        points="7 10 12 15 17 10"
                    />

                    <line
                        x1="12"
                        x2="12"
                        y1="15"
                        y2="3"
                    />
                </svg>

                Unduh

            </a>

        </div>

    </section>



    {{-- ========================================================= --}}
    {{-- ARCHIVE CONFIRMATION MODAL --}}
    {{-- Reuse route/controller archive existing; tidak menghapus file. --}}
    {{-- ========================================================= --}}
    @if(
        Auth::user()->isAdmin()
        && $document->is_active
        && Route::has('documents.archive')
    )
        <x-modal
            name="archive-document-{{ $document->id }}"
            maxWidth="xl"
            focusable
            labelledBy="archive-document-title-{{ $document->id }}"
            containerClass="!z-[1000] flex flex-col"
            panelClass="relative w-full shrink-0 !mx-auto !my-auto max-h-[calc(100dvh-4rem)] !overflow-y-auto overscroll-contain rounded-2xl border border-sky-100 bg-white shadow-2xl"
            backdropClass="bg-slate-950/60"
        >
            <div class="p-4 sm:p-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center
                                rounded-xl bg-sky-100 text-sky-800">
                        <svg xmlns="http://www.w3.org/2000/svg"
                             width="22" height="22" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round"
                             aria-hidden="true">
                            <rect x="3" y="4" width="18" height="4" rx="1" />
                            <path d="M5 8v11a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8" />
                            <path d="M10 12h4" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 id="archive-document-title-{{ $document->id }}"
                            class="text-lg font-bold text-slate-900">
                            Arsipkan Dokumen?
                        </h2>
                        <p class="mt-1 text-sm leading-relaxed text-slate-600">
                            Dokumen akan dipindahkan ke Archived Documents.
                            File, metadata, dan riwayat revisinya tetap tersimpan.
                        </p>
                    </div>
                </div>

                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3 sm:p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Dokumen yang akan diarsipkan
                    </p>
                    <p class="mt-1 break-words text-sm font-semibold text-slate-900">
                        {{ $document->title }}
                    </p>
                    <p class="mt-1 break-all text-xs text-slate-500">
                        {{ $document->file_name }}
                    </p>
                </div>

                <form method="POST"
                      action="{{ route('documents.archive', $document) }}"
                      class="mt-4 space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="archive-reason-{{ $document->id }}"
                               class="block text-sm font-semibold text-slate-800">
                            Alasan Pengarsipan <span class="text-red-600">*</span>
                        </label>
                        <textarea
                            id="archive-reason-{{ $document->id }}"
                            name="archive_reason"
                            rows="3"
                            minlength="3"
                            maxlength="1000"
                            required
                            placeholder="Tuliskan alasan dokumen diarsipkan (minimal 3 karakter)."
                            class="mt-2 block w-full rounded-xl border-slate-300
                                   bg-white text-sm text-slate-800 shadow-sm
                                   placeholder:text-slate-400
                                   focus:border-blue-500 focus:ring-blue-500"
                        >{{ old('archive_reason') }}</textarea>
                        <p class="mt-2 text-xs leading-relaxed text-slate-500">
                            Wajib diisi, 3 sampai 1.000 karakter. Alasan akan
                            dicatat bersama informasi pengarsipan.
                        </p>
                        @error('archive_reason')
                            <p class="mt-2 text-sm text-red-700" role="alert">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            x-on:click.prevent="close"
                            class="inline-flex items-center justify-center rounded-xl
                                   border border-slate-200 bg-white px-5 py-3
                                   text-sm font-semibold text-slate-700
                                   hover:bg-slate-50 focus-visible:outline-none
                                   focus-visible:ring-2 focus-visible:ring-blue-500
                                   focus-visible:ring-offset-2 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl
                                   bg-blue-800 px-5 py-3 text-sm font-semibold text-white
                                   hover:bg-blue-900 focus-visible:outline-none
                                   focus-visible:ring-2 focus-visible:ring-blue-500
                                   focus-visible:ring-offset-2 transition"
                        >
                            Ya, Arsipkan
                        </button>
                    </div>
                </form>
            </div>
        </x-modal>
    @endif


    {{-- IMAGE PREVIEW --}}
    {{-- ========================================================= --}}

    @if(in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'tif', 'tiff'], true))
        <section
            class="mb-8
                   overflow-hidden
                   rounded-2xl
                   border
                   border-slate-200/70
                   bg-white
                   shadow-sm"
        >
            <div
                class="flex
                       flex-col
                       gap-3
                       border-b
                       border-slate-100
                       px-6
                       py-5
                       sm:flex-row
                       sm:items-center
                       sm:justify-between"
            >
                <div>
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-700"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <rect x="3" y="3" width="18" height="18" rx="2"/>
                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                <path d="m21 15-5-5L5 21"/>
                            </svg>
                        </div>

                        <div>
                            <h2 class="font-bold text-slate-900">
                                Pratinjau Berkas
                            </h2>
                            <p class="mt-1 text-sm text-slate-500">
                                Gambar hasil scan dapat dilihat langsung tanpa mengunduh file.
                            </p>
                        </div>
                    </div>
                </div>

                <a
                    href="{{ route('documents.open', $document) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-800 transition hover:border-blue-300 hover:bg-blue-100"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M15 3h6v6"/>
                        <path d="M10 14 21 3"/>
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                    </svg>
                    Buka Penuh
                </a>
            </div>

            <div class="bg-slate-100 p-4 sm:p-6">
                <div
                    class="flex min-h-[320px] items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-white p-3 sm:min-h-[460px]"
                >
                    <img
                        src="{{ route('documents.open', $document) }}"
                        alt="Pratinjau {{ $document->title }}"
                        class="max-h-[760px] w-auto max-w-full rounded-lg object-contain shadow-sm"
                        loading="lazy"
                        decoding="async"
                    >
                </div>
            </div>
        </section>
    @endif


    {{-- ========================================================= --}}
    {{-- SPREADSHEET ANALYTICS DASHBOARD --}}
    {{-- ========================================================= --}}

    @if($isSpreadsheet ?? false)

        @include(
            'documents.partials.analytics-dashboard',
            [
                'document' => $document,
                'currentVersion' => $currentVersion ?? null,
                'documentAnalytics' => $documentAnalytics ?? null,
                'analyticsCellCount' => $analyticsCellCount ?? 0,
                'analyticsCompleteness' => $analyticsCompleteness ?? null,
            ]
        )

    @endif



    {{-- ========================================================= --}}
    {{-- CONTENT --}}
    {{-- ========================================================= --}}

    <section
        class="grid
               xl:grid-cols-3
               gap-6"
    >


        {{-- ===================================================== --}}
        {{-- MAIN CONTENT --}}
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

                    <div
                        class="flex
                               items-center
                               gap-3"
                    >

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
                                <circle
                                    cx="12"
                                    cy="12"
                                    r="9"
                                />

                                <path d="M12 11v5"/>
                                <path d="M12 8h.01"/>
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
                                Metadata yang telah terdaftar dan terindeks
                                di BMC QualityHub.
                            </p>

                        </div>

                    </div>

                </div>



                {{-- INFORMATION GRID --}}

                <div class="p-6">

                    <div
                        class="grid
                               md:grid-cols-2

                               gap-x-8
                               gap-y-7"
                    >


                        {{-- DOCUMENT CODE --}}

                        <div>

                            <p
                                class="text-xs
                                       uppercase
                                       tracking-wider
                                       font-semibold
                                       text-slate-400"
                            >
                                Kode Dokumen
                            </p>


                            <p
                                class="mt-2
                                       text-sm
                                       font-semibold
                                       text-slate-800
                                       break-words"
                            >
                                {{ $document->document_code ?? '-' }}
                            </p>

                        </div>



                        {{-- PART NUMBER --}}

                        <div>

                            <p
                                class="text-xs
                                       uppercase
                                       tracking-wider
                                       font-semibold
                                       text-slate-400"
                            >
                                Nomor Part
                            </p>


                            <p
                                class="mt-2
                                       text-sm
                                       font-semibold
                                       text-slate-800
                                       break-words"
                            >
                                {{ $document->part_number ?? '-' }}
                            </p>

                        </div>



                        {{-- DEPARTMENT --}}

                        <div>

                            <p
                                class="text-xs
                                       uppercase
                                       tracking-wider
                                       font-semibold
                                       text-slate-400"
                            >
                                Departemen
                            </p>


                            <div
                                class="mt-2

                                       flex
                                       items-center
                                       gap-2"
                            >

                                <div
                                    class="w-8
                                           h-8

                                           rounded-lg

                                           bg-slate-100
                                           text-slate-500

                                           flex
                                           items-center
                                           justify-center

                                           flex-shrink-0"
                                >

                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="15"
                                        height="15"
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
                                    </svg>

                                </div>


                                <div>

                                    <p
                                        class="text-sm
                                               font-semibold
                                               text-slate-800"
                                    >
                                        {{
                                            $document
                                                ->category
                                                ?->department
                                                ?->name
                                            ?? '-'
                                        }}
                                    </p>


                                    @if(
                                        $document
                                            ->category
                                            ?->department
                                            ?->code
                                    )

                                        <p
                                            class="mt-0.5
                                                   text-xs
                                                   text-slate-400"
                                        >
                                            {{
                                                $document
                                                    ->category
                                                    ?->department
                                                    ?->code
                                            }}
                                        </p>

                                    @endif

                                </div>

                            </div>

                        </div>



                        {{-- CATEGORY --}}

                        <div>

                            <p
                                class="text-xs
                                       uppercase
                                       tracking-wider
                                       font-semibold
                                       text-slate-400"
                            >
                                Kategori
                            </p>


                            <span
                                class="mt-2

                                       inline-flex
                                       items-center
                                       gap-1.5

                                       rounded-lg

                                       bg-blue-50

                                       px-2.5
                                       py-1

                                       text-xs
                                       font-semibold
                                       text-blue-700"
                            >

                                <span
                                    class="w-1.5
                                           h-1.5
                                           rounded-full
                                           bg-blue-500"
                                ></span>

                                {{
                                    $document->category?->name
                                    ?? 'Tanpa Kategori'
                                }}

                            </span>

                        </div>



                        {{-- DOCUMENT DATE --}}

                        <div>

                            <p
                                class="text-xs
                                       uppercase
                                       tracking-wider
                                       font-semibold
                                       text-slate-400"
                            >
                                Tanggal Dokumen
                            </p>


                            <p
                                class="mt-2
                                       text-sm
                                       font-semibold
                                       text-slate-800"
                            >
                                {{
                                    $document->document_date
                                        ? $document->document_date->locale('id')->translatedFormat('d M Y')
                                        : '-'
                                }}
                            </p>

                        </div>



                        {{-- CREATED AT --}}

                        <div>

                            <p
                                class="text-xs
                                       uppercase
                                       tracking-wider
                                       font-semibold
                                       text-slate-400"
                            >
                                Ditambahkan ke QualityHub
                            </p>


                            <p
                                class="mt-2
                                       text-sm
                                       font-semibold
                                       text-slate-800"
                            >
                                {{
                                    $document->created_at
                                        ? $document->created_at->locale('id')->translatedFormat('d M Y, H:i')
                                        : '-'
                                }}
                            </p>


                            @if($document->created_at)

                                <p
                                    class="mt-1
                                           text-xs
                                           text-slate-400"
                                >
                                    {{ $document->created_at->locale('id')->diffForHumans() }}
                                </p>

                            @endif

                        </div>



                        {{-- LAST UPDATED --}}

                        <div>

                            <p
                                class="text-xs
                                       uppercase
                                       tracking-wider
                                       font-semibold
                                       text-slate-400"
                            >
                                Terakhir Diperbarui
                            </p>


                            <p
                                class="mt-2
                                       text-sm
                                       font-semibold
                                       text-slate-800"
                            >
                                {{
                                    $document->updated_at
                                        ? $document->updated_at->locale('id')->translatedFormat('d M Y, H:i')
                                        : '-'
                                }}
                            </p>


                            @if($document->updated_at)

                                <p
                                    class="mt-1
                                           text-xs
                                           text-slate-400"
                                >
                                    {{ $document->updated_at->locale('id')->diffForHumans() }}
                                </p>

                            @endif

                        </div>



                        {{-- CURRENT REVISION --}}

                        <div>

                            <p
                                class="text-xs
                                       uppercase
                                       tracking-wider
                                       font-semibold
                                       text-slate-400"
                            >
                                Revisi Saat Ini
                            </p>


                            <div
                                class="mt-2

                                       flex
                                       flex-wrap
                                       items-center
                                       gap-2"
                            >

                                <span
                                    class="inline-flex
                                           items-center
                                           gap-1.5

                                           rounded-lg

                                           bg-indigo-50

                                           px-2.5
                                           py-1

                                           text-xs
                                           font-bold
                                           text-indigo-700"
                                >

                                    <span
                                        class="w-1.5
                                               h-1.5
                                               rounded-full
                                               bg-indigo-500"
                                    ></span>

                                    {{ $currentRevisionLabel }}

                                </span>


                                @if(Route::has('documents.revisions.index'))

                                    <a
                                        href="{{ route(
                                            'documents.revisions.index',
                                            $document
                                        ) }}"

                                        class="text-xs
                                               font-semibold
                                               text-indigo-600
                                               hover:text-indigo-800
                                               transition"
                                    >
                                        Lihat Riwayat
                                    </a>

                                @endif

                            </div>

                        </div>



                        {{-- STATUS --}}

                        <div>

                            <p
                                class="text-xs
                                       uppercase
                                       tracking-wider
                                       font-semibold
                                       text-slate-400"
                            >
                                Status Dokumen
                            </p>


                            <span
                                class="mt-2

                                       inline-flex
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

                        </div>

                    </div>

                </div>

            </section>



            @php
                $scanMetadata = $document->scanMetadata;
                $axleProduction = $document->axleProduction;
                $brakeProduction = $document->brakeProduction;
                $scanType = $scanMetadata?->scan_type;
                $productionMetadataTitle = null;
                $productionMetadataSubtitle = null;
                $productionMetadataItems = [];

                $productionValue = static function ($value): ?string {
                    if ($value === null || trim((string) $value) === '') {
                        return null;
                    }

                    return (string) $value;
                };

                $productionTime = static function ($value) use ($productionValue): ?string {
                    $value = $productionValue($value);

                    return $value === null ? null : substr($value, 0, 5);
                };

                $ppicProduct = static function ($code) use ($ppicProducts): ?object {
                    $key = strtolower(trim((string) $code));

                    return $key === '' ? null : $ppicProducts->get($key);
                };

                $productName = static function ($code) use ($productionValue, $ppicProduct): ?string {
                    $product = $ppicProduct($code);

                    return $productionValue($product?->name) ?? $productionValue($code);
                };

                $productCycle = static function ($code) use ($ppicProduct): ?string {
                    $cycle = $ppicProduct($code)?->cycle_time;

                    return $cycle === null ? null : number_format((float) $cycle, 2, '.', '');
                };

                $fromCycleSnapshot = $productionValue(
                    $retoolingCycleTimeSnapshot['from_cycle_time'] ?? null
                );

                $toCycleSnapshot = $productionValue(
                    $retoolingCycleTimeSnapshot['to_cycle_time'] ?? null
                );

                $productionDate = static function ($value): ?string {
                    return $value === null
                        ? null
                        : $value->locale('id')->translatedFormat('d M Y');
                };

                if (
                    $axleProduction !== null
                    || $scanType === 'Laporan Hasil Produksi - Axle Assy'
                ) {
                    $productionMetadataTitle = 'Informasi Produksi Axle Assy';

                    if ($axleProduction !== null) {
                        $lineName = $productionValue($axleProduction->snapshot_line_name);
                        $lineCode = $productionValue($axleProduction->snapshot_line_code);
                        $shift = $productionValue($axleProduction->shift);
                        $productionMetadataSubtitle = 'Data produksi saat dokumen ini dibuat.';
                        $productionMetadataItems = [
                            ['label' => 'Type Axle', 'value' => $productionValue($axleProduction->snapshot_axle_type_name), 'detail' => $productionValue($axleProduction->snapshot_axle_type_code)],
                            ['label' => 'Posisi', 'value' => $productionValue($axleProduction->snapshot_position_name), 'detail' => $productionValue($axleProduction->snapshot_position_code)],
                            ['label' => 'Shift', 'value' => $shift === null ? null : 'Shift '.$shift],
                            ['label' => 'Line', 'value' => $lineName ?? $lineCode, 'detail' => $lineName === null ? null : $lineCode],
                            ['label' => 'Jam', 'value' => $productionTime($axleProduction->production_time)],
                        ];
                    } else {
                        $shift = $productionValue($scanMetadata?->shift);
                        $productionMetadataSubtitle = 'Metadata produksi dari dokumen lama.';
                        $productionMetadataItems = [
                            ['label' => 'Type Axle', 'value' => $productionValue($scanMetadata?->axle_assy_type)],
                            ['label' => 'Posisi', 'value' => $productionValue($scanMetadata?->axle_side)],
                            ['label' => 'Shift', 'value' => $shift === null ? null : 'Shift '.$shift],
                            ['label' => 'Line', 'value' => $productionValue($scanMetadata?->line)],
                            ['label' => 'Jam', 'value' => $productionTime($scanMetadata?->scan_time)],
                        ];
                    }
                } elseif (
                    $brakeProduction !== null
                    || $scanType === 'Laporan Hasil Produksi - Brake Assy'
                ) {
                    $productionMetadataTitle = 'Informasi Produksi Brake Assy';

                    if ($brakeProduction !== null) {
                        $lineName = $productionValue($brakeProduction->snapshot_line_name);
                        $lineCode = $productionValue($brakeProduction->snapshot_line_code);
                        $shift = $productionValue($brakeProduction->shift);
                        $productionMetadataSubtitle = 'Data produksi saat dokumen ini dibuat.';
                        $productionMetadataItems = [
                            ['label' => 'Prefix Part No', 'value' => $productionValue($brakeProduction->snapshot_prefix)],
                            ['label' => 'Variant Part No', 'value' => $productionValue($brakeProduction->snapshot_variant)],
                            ['label' => 'Part Number', 'value' => $productionValue($brakeProduction->snapshot_display_name)],
                            ['label' => 'Shift', 'value' => $shift === null ? null : 'Shift '.$shift],
                            ['label' => 'Line', 'value' => $lineName ?? $lineCode, 'detail' => $lineName === null ? null : $lineCode],
                            ['label' => 'Jam', 'value' => $productionTime($brakeProduction->production_time)],
                        ];
                    } else {
                        $legacyPrefix = $productionValue($scanMetadata?->brake_part_prefix);
                        $legacyVariant = $productionValue($scanMetadata?->brake_part_variant);
                        $legacyPartNumber = $productionValue($scanMetadata?->brake_part_number)
                            ?? $productionValue(implode(' ', array_filter([$legacyPrefix, $legacyVariant])));
                        $shift = $productionValue($scanMetadata?->shift);
                        $productionMetadataSubtitle = 'Metadata produksi dari dokumen lama.';
                        $productionMetadataItems = [
                            ['label' => 'Prefix Part No', 'value' => $legacyPrefix],
                            ['label' => 'Variant Part No', 'value' => $legacyVariant],
                            ['label' => 'Part Number', 'value' => $legacyPartNumber],
                            ['label' => 'Shift', 'value' => $shift === null ? null : 'Shift '.$shift],
                            ['label' => 'Line', 'value' => $productionValue($scanMetadata?->line)],
                            ['label' => 'Jam', 'value' => $productionTime($scanMetadata?->scan_time)],
                        ];
                    }
                } elseif (
                    $document->retooling !== null
                    || $scanType === 'Retooling'
                ) {
                    $retooling = $document->retooling;
                    $productionMetadataTitle = 'Informasi Retooling';
                    $productionMetadataSubtitle = 'Data perubahan type saat dokumen ini dibuat.';

                    if ($retooling !== null) {
                        $lineName = $productionValue($retooling->productionLine?->name);
                        $lineCode = $productionValue($retooling->productionLine?->code);
                        $fromCode = $retooling->fromCasting?->code ?? $retooling->fromCasting?->name;
                        $toCode = $retooling->toCasting?->code ?? $retooling->toCasting?->name;
                        $fromName = $productName($fromCode);
                        $toName = $productName($toCode);
                        $fromCycle = $fromCycleSnapshot
                            ?? $productionValue($retooling->from_cycle_time)
                            ?? $productCycle($fromCode);

                        $toCycle = $toCycleSnapshot
                            ?? $productionValue($retooling->to_cycle_time)
                            ?? $productCycle($toCode);
                        $shift = $productionValue($retooling->shift);
                        $productionMetadataItems = [
                            ['label' => 'Shift', 'value' => $shift === null ? null : 'Shift '.$shift],
                            ['label' => 'Line', 'value' => $lineName ?? $lineCode, 'detail' => $lineName === null ? null : $lineCode],
                            ['label' => 'Tanggal Proses', 'value' => $productionDate($retoolingProcessDate)],
                            ['label' => 'Tanggal Retooling', 'value' => $productionDate($retoolingDate)],
                            ['label' => 'Dari Type', 'value' => $fromName],
                            ['label' => 'Cycle Time Dari', 'value' => $fromCycle === null ? null : number_format((float) $fromCycle, 2, '.', '')],
                            ['label' => 'Ke Type', 'value' => $toName],
                            ['label' => 'Cycle Time Ke', 'value' => $toCycle === null ? null : number_format((float) $toCycle, 2, '.', '')],
                            ['label' => 'Jam', 'value' => $productionTime($scanMetadata?->scan_time)],
                        ];
                    } else {
                        $shift = $productionValue($scanMetadata?->shift);
                        $productionMetadataItems = [
                            ['label' => 'Shift', 'value' => $shift === null ? null : 'Shift '.$shift],
                            ['label' => 'Line', 'value' => $productionValue($scanMetadata?->line)],
                            ['label' => 'Tanggal Proses', 'value' => $productionDate($retoolingProcessDate)],
                            ['label' => 'Tanggal Retooling', 'value' => $productionDate($retoolingDate)],
                            ['label' => 'Dari Type', 'value' => $productName($scanMetadata?->from_type)],
                            [
                                'label' => 'Cycle Time Dari',
                                'value' => $fromCycleSnapshot !== null
                                    ? number_format((float) $fromCycleSnapshot, 2, '.', '')
                                    : $productCycle($scanMetadata?->from_type),
                            ],
                            ['label' => 'Ke Type', 'value' => $productName($scanMetadata?->to_type)],
                            [
                                'label' => 'Cycle Time Ke',
                                'value' => $toCycleSnapshot !== null
                                    ? number_format((float) $toCycleSnapshot, 2, '.', '')
                                    : $productCycle($scanMetadata?->to_type),
                            ],
                            ['label' => 'Jam', 'value' => $productionTime($scanMetadata?->scan_time)],
                        ];
                    }
                }
            @endphp

            @if($productionMetadataTitle !== null)
                <section class="overflow-hidden rounded-2xl border border-slate-200/70 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-5">
                        <h2 class="font-bold text-slate-900">
                            {{ $productionMetadataTitle }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $productionMetadataSubtitle }}
                        </p>
                    </div>

                    <dl class="grid gap-x-8 gap-y-5 p-6 sm:grid-cols-2">
                        @foreach($productionMetadataItems as $item)
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                    {{ $item['label'] }}
                                </dt>
                                <dd class="mt-2 break-words text-sm font-semibold text-slate-800">
                                    {{ $item['value'] ?? '-' }}
                                </dd>
                                @if(! empty($item['detail']))
                                    <p class="mt-1 text-xs text-slate-400">
                                        Kode: {{ $item['detail'] }}
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endif



            {{-- ================================================= --}}
            {{-- DESCRIPTION & SEARCH INFORMATION --}}
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

                    <div
                        class="flex
                               items-center
                               gap-3"
                    >

                        <div
                            class="w-10
                                   h-10

                                   rounded-xl

                                   bg-indigo-50
                                   text-indigo-700

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
                                <path d="M4 6h16"/>
                                <path d="M4 12h16"/>
                                <path d="M4 18h10"/>
                            </svg>

                        </div>


                        <div>

                            <h2
                                class="font-bold
                                       text-slate-900"
                            >
                                Deskripsi & Informasi Pencarian
                            </h2>


                            <p
                                class="mt-1
                                       text-sm
                                       text-slate-500"
                            >
                                Metadata yang digunakan oleh mesin
                                pencarian QualityHub.
                            </p>

                        </div>

                    </div>

                </div>



                <div
                    class="p-6
                           space-y-6"
                >


                    {{-- DESCRIPTION --}}

                    <div>

                        <p
                            class="text-xs
                                   uppercase
                                   tracking-wider
                                   font-semibold
                                   text-slate-400"
                        >
                            Deskripsi
                        </p>


                        @if($document->description)

                            <p
                                class="mt-3

                                       text-sm
                                       leading-7
                                       text-slate-600

                                       whitespace-pre-line"
                            >
                                {{ $document->description }}
                            </p>

                        @else

                            <p
                                class="mt-3
                                       text-sm
                                       italic
                                       text-slate-400"
                            >
                                Belum ada deskripsi.
                            </p>

                        @endif

                    </div>



                    <div
                        class="border-t
                               border-slate-100"
                    ></div>



                    {{-- KEYWORDS --}}

                    <div>

                        <div
                            class="flex
                                   flex-wrap
                                   items-center
                                   justify-between
                                   gap-3"
                        >

                            <p
                                class="text-xs
                                       uppercase
                                       tracking-wider
                                       font-semibold
                                       text-slate-400"
                            >
                                Kata Kunci Pencarian
                            </p>


                            @if(count($keywords))

                                <span
                                    class="inline-flex
                                           items-center
                                           gap-1.5

                                           rounded-md

                                           bg-emerald-50

                                           px-2
                                           py-1

                                           text-[10px]
                                           font-semibold
                                           text-emerald-700"
                                >

                                    <span
                                        class="w-1.5
                                               h-1.5
                                               rounded-full
                                               bg-emerald-500"
                                    ></span>

                                    Terindeks QualityHub

                                </span>

                            @endif

                        </div>


                        @if(count($keywords))

                            <div
                                class="mt-3

                                       flex
                                       flex-wrap
                                       gap-2"
                            >

                                @foreach($keywords as $keyword)

                                    <span
                                        class="inline-flex
                                               items-center
                                               gap-1.5

                                               rounded-lg

                                               bg-slate-100

                                               px-3
                                               py-1.5

                                               text-xs
                                               font-medium
                                               text-slate-600"
                                    >

                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="12"
                                            height="12"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                d="M20.59 13.41
                                                   11 3.83
                                                   V3H4
                                                   a1 1 0 0 0-1 1
                                                   v7
                                                   h.83
                                                   l9.59 9.59
                                                   a2 2 0 0 0 2.82 0
                                                   l4.35-4.35
                                                   a2 2 0 0 0 0-2.83Z"
                                            />

                                            <circle
                                                cx="7.5"
                                                cy="7.5"
                                                r=".5"
                                                fill="currentColor"
                                            />
                                        </svg>

                                        {{ $keyword }}

                                    </span>

                                @endforeach

                            </div>

                        @else

                            <p
                                class="mt-3
                                       text-sm
                                       italic
                                       text-slate-400"
                            >
                                Belum ada kata kunci pencarian.
                            </p>

                        @endif

                    </div>

                </div>

            </section>



            {{-- ================================================= --}}
            {{-- REVISION OVERVIEW --}}
            {{-- ================================================= --}}

            @if(Route::has('documents.revisions.index'))

                <section
                    class="bg-white

                           rounded-2xl

                           border
                           border-indigo-100

                           shadow-sm

                           overflow-hidden"
                >

                    <div
                        class="px-6
                               py-5

                               border-b
                               border-slate-100"
                    >

                        <div
                            class="flex
                                   flex-col
                                   sm:flex-row
                                   sm:items-center
                                   sm:justify-between
                                   gap-4"
                        >

                            <div
                                class="flex
                                       items-center
                                       gap-3"
                            >

                                <div
                                    class="w-10
                                           h-10

                                           rounded-xl

                                           bg-indigo-50
                                           text-indigo-700

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
                                        <path d="M3 12a9 9 0 1 0 3-6.7L3 8"/>
                                        <path d="M3 3v5h5"/>
                                        <path d="M12 7v5l3 2"/>
                                    </svg>

                                </div>


                                <div>

                                    <h2
                                        class="font-bold
                                               text-slate-900"
                                    >
                                        Revisi Dokumen
                                    </h2>


                                    <p
                                        class="mt-1
                                               text-sm
                                               text-slate-500"
                                    >
                                        File revisi sebelumnya tetap disimpan dan tidak ditimpa.
                                    </p>

                                </div>

                            </div>


                            <span
                                class="inline-flex
                                       items-center
                                       justify-center

                                       rounded-xl

                                       bg-indigo-50

                                       px-4
                                       py-2

                                       text-sm
                                       font-bold
                                       text-indigo-700"
                            >
                                Saat Ini · {{ $currentRevisionLabel }}
                            </span>

                        </div>

                    </div>


                    <div
                        class="p-6

                               flex
                               flex-col
                               lg:flex-row
                               lg:items-center
                               lg:justify-between

                               gap-5"
                    >

                        <div>

                            <p
                                class="text-sm
                                       font-semibold
                                       text-slate-800"
                            >
                                {{ $document->file_name }}
                            </p>


                            <p
                                class="mt-1
                                       text-xs
                                       leading-relaxed
                                       text-slate-500"
                            >
                                Buka Riwayat Revisi untuk mengakses Revisi 0, Revisi 1, Revisi 2, dan setiap revisi berikutnya secara terpisah.
                            </p>

                        </div>


                        <div
                            class="flex
                                   flex-col
                                   sm:flex-row
                                   gap-2
                                   flex-shrink-0"
                        >

                            <a
                                href="{{ route(
                                    'documents.revisions.index',
                                    $document
                                ) }}"

                                class="inline-flex
                                       items-center
                                       justify-center
                                       gap-2

                                       rounded-xl

                                       border
                                       border-indigo-200

                                       bg-indigo-50

                                       px-4
                                       py-2.5

                                       text-sm
                                       font-semibold
                                       text-indigo-700

                                       hover:bg-indigo-100
                                       transition"
                            >
                                Riwayat Revisi
                            </a>


                            @if(
                                Auth::user()->isAdmin()
                                && Route::has('documents.revisions.create')
                            )

                                <a
                                    href="{{ route(
                                        'documents.revisions.create',
                                        $document
                                    ) }}"

                                    class="inline-flex
                                           items-center
                                           justify-center
                                           gap-2

                                           rounded-xl

                                           bg-indigo-700

                                           px-4
                                           py-2.5

                                           text-sm
                                           font-semibold
                                           text-white

                                           hover:bg-indigo-800
                                           transition"
                                >
                                    + Unggah Revisi Baru
                                </a>

                            @endif

                        </div>

                    </div>

                </section>

            @endif



            {{-- ================================================= --}}
            {{-- QUICK ACCESS --}}
            {{-- ================================================= --}}

            <section
                class="bg-gradient-to-r
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
                           md:flex-row
                           md:items-center
                           md:justify-between

                           gap-5"
                >

                    <div>

                        <p
                            class="text-xs
                                   uppercase
                                   tracking-[0.16em]
                                   font-semibold
                                   text-blue-300"
                        >
                            Akses Cepat
                        </p>


                        <h2
                            class="mt-2
                                   text-xl
                                   font-bold"
                        >
                            {{
                                $isFavorite
                                    ? 'Tersimpan di Favorit Anda'
                                    : 'Sering membutuhkan dokumen ini?'
                            }}
                        </h2>


                        <p
                            class="mt-2

                                   max-w-xl

                                   text-sm
                                   leading-relaxed
                                   text-blue-200"
                        >

                            @if($isFavorite)

                                Dokumen ini sudah tersedia di
                                ruang kerja Favorit pribadi Anda.

                            @else

                                Simpan dokumen ini ke Favorit agar
                                dapat diakses lebih cepat nanti.

                            @endif

                        </p>

                    </div>


                    @if(
                        Route::has('favorites.store')
                        && Route::has('favorites.destroy')
                    )

                        <div class="flex-shrink-0">

                            <x-favorite-button
                                :document="$document"
                                :favorited="$isFavorite"
                            />

                        </div>

                    @endif

                </div>

            </section>



            {{-- ================================================= --}}
            {{-- ADMIN MANAGEMENT --}}
            {{-- ================================================= --}}

            @if(Auth::user()->isAdmin())

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

                        <div
                            class="flex
                                   items-center
                                   gap-3"
                        >

                            <div
                                class="w-10
                                       h-10

                                       rounded-xl

                                       bg-slate-100
                                       text-slate-700

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
                                    <path
                                        d="M12.22 2h-.44
                                           a2 2 0 0 0-2 2
                                           v.18
                                           a2 2 0 0 1-1 1.73
                                           l-.43.25
                                           a2 2 0 0 1-2 0
                                           l-.15-.08
                                           a2 2 0 0 0-2.73.73
                                           l-.22.38
                                           a2 2 0 0 0 .73 2.73
                                           l.15.1
                                           a2 2 0 0 1 1 1.72
                                           v.51
                                           a2 2 0 0 1-1 1.74
                                           l-.15.09
                                           a2 2 0 0 0-.73 2.73
                                           l.22.38
                                           a2 2 0 0 0 2.73.73
                                           l.15-.08
                                           a2 2 0 0 1 2 0
                                           l.43.25
                                           a2 2 0 0 1 1 1.73
                                           V20
                                           a2 2 0 0 0 2 2
                                           h.44
                                           a2 2 0 0 0 2-2
                                           v-.18
                                           a2 2 0 0 1 1-1.73
                                           l.43-.25
                                           a2 2 0 0 1 2 0
                                           l.15.08
                                           a2 2 0 0 0 2.73-.73
                                           l.22-.38
                                           a2 2 0 0 0-.73-2.73
                                           l-.15-.09
                                           a2 2 0 0 1-1-1.74
                                           v-.51
                                           a2 2 0 0 1 1-1.74
                                           l.15-.09
                                           a2 2 0 0 0 .73-2.73
                                           l-.22-.38
                                           a2 2 0 0 0-2.73-.73
                                           l-.15.08
                                           a2 2 0 0 1-2 0
                                           l-.43-.25
                                           a2 2 0 0 1-1-1.73
                                           V4
                                           a2 2 0 0 0-2-2Z"
                                    />

                                    <circle
                                        cx="12"
                                        cy="12"
                                        r="3"
                                    />
                                </svg>

                            </div>


                            <div>

                                <h2
                                    class="font-bold
                                           text-slate-900"
                                >
                                    Pengelolaan Dokumen
                                </h2>


                                <p
                                    class="mt-1
                                           text-sm
                                           text-slate-500"
                                >
                                    Tindakan administratif untuk dokumen.
                                </p>

                            </div>

                        </div>

                    </div>


                    <div
                        class="p-6

                               grid
                               md:grid-cols-2
                               xl:grid-cols-3
                               gap-4"
                    >


                        {{-- EDIT --}}

                        @if(Route::has('documents.edit'))

                            <a
                                href="{{ route(
                                    'documents.edit',
                                    $document
                                ) }}"

                                class="group

                                       rounded-2xl

                                       border
                                       border-slate-200

                                       bg-slate-50

                                       p-5

                                       hover:border-blue-200
                                       hover:bg-blue-50/50

                                       transition"
                            >

                                <div
                                    class="flex
                                           items-start
                                           gap-3"
                                >

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
                                            width="18"
                                            height="18"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path d="M12 20h9"/>

                                            <path
                                                d="M16.5 3.5
                                                   a2.121 2.121 0 0 1 3 3
                                                   L7 19
                                                   l-4 1
                                                   1-4Z"
                                            />
                                        </svg>

                                    </div>


                                    <div>

                                        <p
                                            class="text-sm
                                                   font-bold
                                                   text-slate-800

                                                   group-hover:text-blue-800

                                                   transition"
                                        >
                                            Ubah Metadata
                                        </p>


                                        <p
                                            class="mt-1
                                                   text-xs
                                                   leading-relaxed
                                                   text-slate-500"
                                        >
                                            Perbaiki judul, kode dokumen,
                                            nomor part, kategori, tanggal,
                                            deskripsi, atau kata kunci.
                                        </p>

                                    </div>

                                </div>

                            </a>

                        @endif



                        {{-- UPLOAD REVISION --}}

                        @if(Route::has('documents.revisions.create'))

                            <a
                                href="{{ route(
                                    'documents.revisions.create',
                                    $document
                                ) }}"

                                class="group

                                       rounded-2xl

                                       border
                                       border-indigo-200

                                       bg-indigo-50

                                       p-5

                                       hover:bg-indigo-100/70

                                       transition"
                            >

                                <div
                                    class="flex
                                           items-start
                                           gap-3"
                                >

                                    <div
                                        class="w-10
                                               h-10

                                               rounded-xl

                                               bg-indigo-100
                                               text-indigo-700

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
                                            <path d="M12 16V4"/>
                                            <path d="m7 9 5-5 5 5"/>
                                            <path d="M5 20h14"/>
                                        </svg>

                                    </div>


                                    <div>

                                        <p
                                            class="text-sm
                                                   font-bold
                                                   text-indigo-900"
                                        >
                                            Unggah Revisi
                                        </p>


                                        <p
                                            class="mt-1
                                                   text-xs
                                                   leading-relaxed
                                                   text-indigo-700"
                                        >
                                            Buat Revisi {{ $currentRevision + 1 }} sambil tetap mempertahankan {{ $currentRevisionLabel }} di Riwayat Revisi.
                                        </p>

                                    </div>

                                </div>

                            </a>

                        @endif



                        {{-- ARCHIVE INFORMATION --}}

                        <a
                            href="{{ route(
                                'documents.edit',
                                $document
                            ) }}"

                            class="group

                                   rounded-2xl

                                   border
                                   border-amber-200

                                   bg-amber-50

                                   p-5

                                   hover:bg-amber-100/70

                                   transition"
                        >

                            <div
                                class="flex
                                       items-start
                                       gap-3"
                            >

                                <div
                                    class="w-10
                                           h-10

                                           rounded-xl

                                           bg-amber-100
                                           text-amber-700

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
                                        <path d="M3 6h18"/>
                                        <path d="M5 6v14h14V6"/>
                                        <path d="M9 10h6"/>
                                        <path d="M8 3h8"/>
                                    </svg>

                                </div>


                                <div>

                                    <p
                                        class="text-sm
                                               font-bold
                                               text-amber-900"
                                    >
                                        Arsipkan Dokumen
                                    </p>


                                    <p
                                        class="mt-1
                                               text-xs
                                               leading-relaxed
                                               text-amber-700"
                                    >
                                        Pengarsipan tersedia melalui halaman
                                        Edit dan memerlukan alasan.
                                        File fisik akan tetap tersimpan.
                                    </p>

                                </div>

                            </div>

                        </a>

                    </div>

                </section>

            @endif

        </div>



        {{-- ===================================================== --}}
        {{-- RIGHT SIDEBAR --}}
        {{-- ===================================================== --}}

        <aside class="space-y-6">


            {{-- ================================================= --}}
            {{-- FILE INFORMATION --}}
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

                    <h2
                        class="font-bold
                               text-slate-900"
                    >
                        Informasi File
                    </h2>


                    <p
                        class="mt-1
                               text-sm
                               text-slate-500"
                    >
                        Informasi teknis tentang file revisi saat ini.
                    </p>

                </div>



                <div class="p-6">


                    {{-- FILE ICON --}}

                    <div
                        class="w-16
                               h-16

                               rounded-2xl

                               flex
                               items-center
                               justify-center

                               {{ $fileIconClasses }}"
                    >

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="29"
                            height="29"
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

                            <polyline
                                points="14 2 14 8 20 8"
                            />
                        </svg>

                    </div>



                    {{-- ORIGINAL FILE NAME --}}

                    <p
                        class="mt-4

                               text-sm
                               font-semibold
                               text-slate-800

                               break-all"
                    >
                        {{ $document->file_name }}
                    </p>


                    <p
                        class="mt-1
                               text-xs
                               text-slate-400"
                    >
                        Nama file asli
                    </p>



                    {{-- FILE DETAILS --}}

                    <div
                        class="mt-5
                               pt-5

                               border-t
                               border-slate-100

                               space-y-4"
                    >


                        {{-- CURRENT REVISION --}}

                        <div
                            class="flex
                                   items-center
                                   justify-between
                                   gap-3"
                        >

                            <span
                                class="text-xs
                                       text-slate-400"
                            >
                                Revisi Saat Ini
                            </span>


                            <span
                                class="rounded-lg

                                       bg-indigo-50

                                       px-2.5
                                       py-1

                                       text-xs
                                       font-bold
                                       text-indigo-700"
                            >
                                {{ $currentRevisionLabel }}
                            </span>

                        </div>



                        {{-- TYPE --}}

                        <div
                            class="flex
                                   items-center
                                   justify-between
                                   gap-3"
                        >

                            <span
                                class="text-xs
                                       text-slate-400"
                            >
                                Jenis File
                            </span>


                            <span
                                class="rounded-lg

                                       bg-slate-100

                                       px-2.5
                                       py-1

                                       text-xs
                                       font-bold
                                       uppercase
                                       text-slate-600"
                            >
                                {{ $document->getFileTypeLabel() }}
                            </span>

                        </div>



                        {{-- SIZE --}}

                        <div
                            class="flex
                                   items-center
                                   justify-between
                                   gap-3"
                        >

                            <span
                                class="text-xs
                                       text-slate-400"
                            >
                                Ukuran File
                            </span>


                            <span
                                class="text-xs
                                       font-semibold
                                       text-slate-700"
                            >
                                {{ $document->getFormattedFileSize() }}
                            </span>

                        </div>



                        {{-- MIME TYPE --}}

                        <div>

                            <span
                                class="block
                                       text-xs
                                       text-slate-400"
                            >
                                Tipe MIME
                            </span>


                            <p
                                class="mt-1
                                       text-xs
                                       font-medium
                                       text-slate-600
                                       break-all"
                            >
                                {{ $document->mime_type ?: '-' }}
                            </p>

                        </div>

                    </div>



                    {{-- FILE ACTIONS --}}

                    <div
                        class="mt-6

                               grid
                               {{ $canPreviewInBrowser
                                    ? 'grid-cols-2'
                                    : 'grid-cols-1'
                               }}
                               gap-2"
                    >

                        @if($canPreviewInBrowser)

                            <a
                                href="{{ route(
                                    'documents.open',
                                    $document
                                ) }}"

                                target="_blank"

                                rel="noopener noreferrer"

                                class="inline-flex
                                       items-center
                                       justify-center
                                       gap-1.5

                                       rounded-lg

                                       border
                                       border-slate-200

                                       bg-white

                                       px-3
                                       py-2.5

                                       text-center
                                       text-xs
                                       font-semibold
                                       text-slate-600

                                       hover:bg-slate-50

                                       transition"
                            >

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="14"
                                    height="14"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        d="M2.062 12.348
                                           a1 1 0 0 1 0-.696
                                           10.75 10.75 0 0 1 19.876 0
                                           1 1 0 0 1 0 .696
                                           10.75 10.75 0 0 1-19.876 0"
                                    />

                                    <circle
                                        cx="12"
                                        cy="12"
                                        r="3"
                                    />
                                </svg>

                                Pratinjau

                            </a>

                        @endif


                        <a
                            href="{{ route(
                                'documents.download',
                                $document
                            ) }}"

                            class="inline-flex
                                   items-center
                                   justify-center
                                   gap-1.5

                                   rounded-lg

                                   bg-blue-800

                                   px-3
                                   py-2.5

                                   text-center
                                   text-xs
                                   font-semibold
                                   text-white

                                   hover:bg-blue-900

                                   transition"
                        >

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="14"
                                height="14"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M12 3v12"/>
                                <path d="m7 10 5 5 5-5"/>
                                <path d="M5 21h14"/>
                            </svg>

                            Unduh

                        </a>

                    </div>

                </div>

            </section>



            {{-- ================================================= --}}
            {{-- FAVORITE STATUS --}}
            {{-- ================================================= --}}

            <section
                class="
                    rounded-2xl

                    border

                    p-5

                    {{
                        $isFavorite
                            ? 'border-amber-200 bg-amber-50'
                            : 'border-slate-200 bg-white'
                    }}
                "
            >

                <div
                    class="flex
                           items-start
                           gap-3"
                >

                    <div
                        class="
                            w-10
                            h-10

                            rounded-xl

                            flex
                            items-center
                            justify-center

                            flex-shrink-0

                            {{
                                $isFavorite
                                    ? 'bg-amber-100 text-amber-600'
                                    : 'bg-slate-100 text-slate-500'
                            }}
                        "
                    >

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="19"
                            height="19"
                            viewBox="0 0 24 24"

                            fill="{{ $isFavorite
                                ? 'currentColor'
                                : 'none'
                            }}"

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


                    <div>

                        <p
                            class="
                                text-sm
                                font-bold

                                {{
                                    $isFavorite
                                        ? 'text-amber-900'
                                        : 'text-slate-800'
                                }}
                            "
                        >
                            {{
                                $isFavorite
                                    ? 'Tersimpan di Favorit'
                                    : 'Belum ada di Favorit'
                            }}
                        </p>


                        <p
                            class="
                                mt-1
                                text-xs
                                leading-relaxed

                                {{
                                    $isFavorite
                                        ? 'text-amber-700'
                                        : 'text-slate-500'
                                }}
                            "
                        >

                            @if($isFavorite)

                                Dokumen ini tersedia di
                                ruang kerja Favorit pribadi Anda.

                            @else

                                Tambahkan dokumen ini ke Favorit
                                agar dapat diakses lebih cepat nanti.

                            @endif

                        </p>



                        @if(Route::has('favorites.index'))

                            <a
                                href="{{ route('favorites.index') }}"

                                class="
                                    mt-3
                                    inline-flex
                                    items-center
                                    gap-1.5

                                    text-xs
                                    font-semibold

                                    {{
                                        $isFavorite
                                            ? 'text-amber-800 hover:text-amber-950'
                                            : 'text-blue-700 hover:text-blue-900'
                                    }}

                                    transition
                                "
                            >
                                Buka Favorit Saya

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="12"
                                    height="12"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="m9 18 6-6-6-6"/>
                                </svg>

                            </a>

                        @endif

                    </div>

                </div>

            </section>



            {{-- ================================================= --}}
            {{-- UPLOADER --}}
            {{-- ================================================= --}}

            <section
                class="bg-white

                       rounded-2xl

                       border
                       border-slate-200/70

                       shadow-sm

                       p-6"
            >

                <p
                    class="text-xs
                           uppercase
                           tracking-wider
                           font-semibold
                           text-slate-400"
                >
                    Diunggah Oleh
                </p>


                <div
                    class="mt-4

                           flex
                           items-center
                           gap-3"
                >

                    <div
                        class="w-11
                               h-11

                               rounded-xl

                               bg-blue-100
                               text-blue-800

                               flex
                               items-center
                               justify-center

                               font-bold

                               flex-shrink-0"
                    >
                        {{
                            strtoupper(
                                substr(
                                    $document->uploader?->name
                                        ?? 'S',
                                    0,
                                    1
                                )
                            )
                        }}
                    </div>


                    <div class="min-w-0">

                        <p
                            class="text-sm
                                   font-semibold
                                   text-slate-800
                                   truncate"
                        >
                            {{ $document->uploader?->name ?? 'Sistem' }}
                        </p>


                        <p
                            class="mt-1
                                   text-xs
                                   text-slate-400
                                   truncate"
                        >
                            {{ $document->uploader?->email ?? '-' }}
                        </p>


                        @if($document->uploader?->role)

                            <p
                                class="mt-1

                                       text-[11px]
                                       font-medium
                                       capitalize
                                       text-blue-600"
                            >
                                {{
                                    str_replace(
                                        '_',
                                        ' ',
                                        $document->uploader->role
                                    )
                                }}
                            </p>

                        @endif

                    </div>

                </div>

            </section>



            {{-- ================================================= --}}
            {{-- ADMIN QUICK MANAGEMENT --}}
            {{-- ================================================= --}}

            @if(
                Auth::user()->isAdmin()
                && Route::has('documents.edit')
            )

                <section
                    class="rounded-2xl

                           border
                           border-blue-100

                           bg-blue-50

                           p-5"
                >

                    <div
                        class="flex
                               items-start
                               gap-3"
                    >

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
                                <path d="M12 20h9"/>

                                <path
                                    d="M16.5 3.5
                                       a2.121 2.121 0 0 1 3 3
                                       L7 19
                                       l-4 1
                                       1-4Z"
                                />
                            </svg>

                        </div>


                        <div class="flex-1">

                            <p
                                class="text-sm
                                       font-bold
                                       text-blue-900"
                            >
                                Akses Administratif
                            </p>


                            <p
                                class="mt-1

                                       text-xs
                                       leading-relaxed
                                       text-blue-700"
                            >
                                Anda dapat mengubah metadata, mengunggah revisi baru,
                                atau mengarsipkan dokumen ini tanpa menghapus
                                riwayat revisinya.
                            </p>


                            <a
                                href="{{ route(
                                    'documents.edit',
                                    $document
                                ) }}"

                                class="mt-3

                                       inline-flex
                                       items-center
                                       gap-1.5

                                       text-xs
                                       font-semibold
                                       text-blue-800

                                       hover:text-blue-950

                                       transition"
                            >
                                Kelola Dokumen

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="12"
                                    height="12"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="m9 18 6-6-6-6"/>
                                </svg>

                            </a>

                        </div>

                    </div>

                </section>

            @endif



            {{-- ================================================= --}}
            {{-- SECURITY --}}
            {{-- ================================================= --}}

            <section
                class="rounded-2xl

                       border
                       border-emerald-100

                       bg-emerald-50

                       p-5"
            >

                <div class="flex gap-3">

                    <div
                        class="w-10
                               h-10

                               rounded-xl

                               bg-emerald-100
                               text-emerald-700

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
                            <path
                                d="M20 13
                                   c0 5-3.5 7.5-7.66 8.95
                                   a1 1 0 0 1-.67-.01
                                   C7.5 20.5 4 18 4 13
                                   V6
                                   a1 1 0 0 1 1-1
                                   c2 0 4.5-1.2 6.24-2.72
                                   a1.17 1.17 0 0 1 1.52 0
                                   C14.51 3.81 17 5 19 5
                                   a1 1 0 0 1 1 1z"
                            />

                            <path d="m9 12 2 2 4-4"/>
                        </svg>

                    </div>


                    <div>

                        <p
                            class="text-sm
                                   font-bold
                                   text-emerald-900"
                        >
                            Akses Dokumen Internal
                        </p>


                        <p
                            class="mt-1

                                   text-xs
                                   leading-relaxed
                                   text-emerald-700"
                        >
                            File ini disimpan di luar direktori web publik
                            dan hanya dapat diakses melalui rute QualityHub
                            yang memerlukan autentikasi.
                        </p>


                        <div
                            class="mt-3

                                   flex
                                   flex-wrap
                                   gap-2"
                        >

                            <span
                                class="rounded-md

                                       bg-emerald-100

                                       px-2
                                       py-1

                                       text-[10px]
                                       font-semibold
                                       text-emerald-700"
                            >
                                Autentikasi Wajib
                            </span>


                            <span
                                class="rounded-md

                                       bg-emerald-100

                                       px-2
                                       py-1

                                       text-[10px]
                                       font-semibold
                                       text-emerald-700"
                            >
                                Penyimpanan Internal
                            </span>


                            <span
                                class="rounded-md

                                       bg-emerald-100

                                       px-2
                                       py-1

                                       text-[10px]
                                       font-semibold
                                       text-emerald-700"
                            >
                                Audit Tercatat
                            </span>

                        </div>

                    </div>

                </div>

            </section>



            {{-- ================================================= --}}
            {{-- DOCUMENT IDENTIFIER --}}
            {{-- ================================================= --}}

            <div
                class="px-2

                       flex
                       items-center
                       justify-between
                       gap-4

                       text-[11px]
                       text-slate-400"
            >

                <span>
                    Dokumen QualityHub
                </span>


                <span>
                    ID #{{ $document->id }} · {{ $currentRevisionLabel }}
                </span>

            </div>

        </aside>

    </section>

</x-app-layout>
