<x-app-layout>

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

        <div class="min-w-0">

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
                ← Kembali ke Dokumen
            </a>


            <div
                class="flex
                       flex-wrap
                       items-center
                       gap-2
                       mb-2"
            >

                <span
                    class="text-sm
                           font-semibold
                           text-indigo-700"
                >
                    Revision History
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
                    Revisi {{ (int) $document->current_revision }}
                </span>


                @if($document->is_active)

                    <span
                        class="rounded-lg
                               bg-emerald-50
                               px-2.5
                               py-1
                               text-[11px]
                               font-semibold
                               text-emerald-700"
                    >
                        Aktif
                    </span>

                @else

                    <span
                        class="rounded-lg
                               bg-amber-50
                               px-2.5
                               py-1
                               text-[11px]
                               font-semibold
                               text-amber-700"
                    >
                        Diarsipkan
                    </span>

                @endif

            </div>


            <h1
                class="text-3xl
                       sm:text-4xl
                       font-bold
                       text-slate-900
                       break-words"
            >
                {{ $document->title }}
            </h1>


            <p
                class="mt-2
                       max-w-2xl
                       text-sm
                       text-slate-500"
            >
                Tinjau seluruh riwayat revisi file dokumen ini.
                File revisi sebelumnya tetap tersedia ketika revisi yang lebih baru
                menjadi revisi saat ini.
            </p>

        </div>



        @if(
            Auth::user()->isAdmin()
            && $document->is_active
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
                <span class="text-lg leading-none">
                    +
                </span>

                Unggah Revisi Baru
            </a>

        @endif

    </section>



    {{-- ========================================================= --}}
    {{-- SUMMARY --}}
    {{-- ========================================================= --}}

    <section
        class="grid
               sm:grid-cols-2
               xl:grid-cols-3
               gap-4
               mb-6"
    >

        <div
            class="rounded-2xl
                   border
                   border-slate-200
                   bg-white
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
                Revisi Saat Ini
            </p>


            <p
                class="mt-2
                       text-3xl
                       font-bold
                       text-blue-800"
            >
                Revisi {{ (int) $document->current_revision }}
            </p>

        </div>


        <div
            class="rounded-2xl
                   border
                   border-slate-200
                   bg-white
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
                Total Revisi
            </p>


            <p
                class="mt-2
                       text-3xl
                       font-bold
                       text-slate-900"
            >
                {{ $versions->total() }}
            </p>

        </div>


        <div
            class="rounded-2xl
                   border
                   border-emerald-200
                   bg-emerald-50
                   p-5

                   sm:col-span-2
                   xl:col-span-1"
        >

            <p
                class="text-xs
                       uppercase
                       tracking-wider
                       font-semibold
                       text-emerald-600"
            >
                Perlindungan Revisi
            </p>


            <p
                class="mt-2
                       font-bold
                       text-emerald-900"
            >
                File Revisi Sebelumnya Tetap Tersimpan
            </p>


            <p
                class="mt-1
                       text-xs
                       leading-relaxed
                       text-emerald-700"
            >
                Mengunggah revisi baru akan membuat file fisik baru
                tanpa menimpa file revisi sebelumnya.
            </p>

        </div>

    </section>



    {{-- ========================================================= --}}
    {{-- REVISION TIMELINE --}}
    {{-- ========================================================= --}}

    <section
        class="rounded-2xl

               border
               border-slate-200

               bg-white

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
                Riwayat Revisi
            </h2>


            <p
                class="mt-1
                       text-sm
                       text-slate-500"
            >
                Revisi terbaru ditampilkan paling atas.
            </p>

        </div>



        @if($versions->count())

            <div class="divide-y divide-slate-100">

                @foreach($versions as $version)

                    @php
                        $isCurrent =
                            (int) $version->revision_number
                            === (int) $document->current_revision;

                        $versionExtension =
                            strtolower(
                                (string) $version->file_extension
                            );

                        $isSpreadsheetVersion =
                            in_array(
                                $versionExtension,
                                [
                                    'xlsx',
                                    'xls',
                                    'csv',
                                ],
                                true
                            );

                        $versionAnalytics =
                            $analyticsByVersionId
                                ->get(
                                    $version->id
                                );

                        $analyticsStatus =
                            $versionAnalytics?->status;

                        $analyticsStatusDisplay = match($analyticsStatus) {
                            'ready' => [
                                'label' => 'Analitik Siap',
                                'classes' => 'bg-emerald-50 text-emerald-700',
                            ],

                            'processing' => [
                                'label' => 'Sedang Diproses',
                                'classes' => 'bg-blue-50 text-blue-700',
                            ],

                            'pending' => [
                                'label' => 'Menunggu Analitik',
                                'classes' => 'bg-amber-50 text-amber-700',
                            ],

                            'failed' => [
                                'label' => 'Analitik Gagal',
                                'classes' => 'bg-red-50 text-red-700',
                            ],

                            default => [
                                'label' => 'Belum Dianalisis',
                                'classes' => 'bg-slate-100 text-slate-500',
                            ],
                        };
                    @endphp


                    <article
                        class="
                            p-6

                            {{
                                $isCurrent
                                    ? 'bg-blue-50/40'
                                    : 'bg-white'
                            }}
                        "
                    >

                        <div
                            class="flex
                                   flex-col
                                   lg:flex-row
                                   lg:items-start
                                   lg:justify-between
                                   gap-5"
                        >

                            <div
                                class="flex
                                       items-start
                                       gap-4
                                       min-w-0"
                            >

                                <div
                                    class="
                                        w-12
                                        h-12

                                        rounded-xl

                                        flex
                                        items-center
                                        justify-center

                                        flex-shrink-0

                                        {{
                                            $isCurrent
                                                ? 'bg-blue-100 text-blue-800'
                                                : 'bg-slate-100 text-slate-500'
                                        }}
                                    "
                                >

                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="21"
                                        height="21"
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


                                <div class="min-w-0">

                                    <div
                                        class="flex
                                               flex-wrap
                                               items-center
                                               gap-2"
                                    >

                                        <span
                                            class="text-lg
                                                   font-bold
                                                   text-slate-900"
                                        >
                                            Revisi {{ (int) $version->revision_number }}
                                        </span>


                                        @if($isCurrent)

                                            <span
                                                class="rounded-lg

                                                       bg-blue-100

                                                       px-2.5
                                                       py-1

                                                       text-[10px]
                                                       font-bold
                                                       uppercase
                                                       text-blue-700"
                                            >
                                                Saat Ini
                                            </span>

                                        @endif


                                        @if($isSpreadsheetVersion)

                                            <span
                                                class="rounded-lg
                                                       px-2.5
                                                       py-1
                                                       text-[10px]
                                                       font-bold
                                                       uppercase
                                                       {{ $analyticsStatusDisplay['classes'] }}"
                                            >
                                                {{ $analyticsStatusDisplay['label'] }}
                                            </span>

                                        @endif

                                    </div>


                                    <p
                                        class="mt-2

                                               text-sm
                                               font-semibold
                                               text-slate-700

                                               break-all"
                                    >
                                        {{ $version->file_name }}
                                    </p>


                                    <div
                                        class="mt-2

                                               flex
                                               flex-wrap
                                               items-center
                                               gap-x-3
                                               gap-y-1

                                               text-xs
                                               text-slate-400"
                                    >

                                        <span>
                                            {{ $version->getFileTypeLabel() }}
                                        </span>

                                        <span>•</span>

                                        <span>
                                            {{ $version->getFormattedFileSize() }}
                                        </span>

                                        <span>•</span>

                                        <span>
                                            {{
                                                $version->created_at
                                                    ?->locale('id')->translatedFormat('d M Y, H:i')
                                                ?? '-'
                                            }}
                                        </span>

                                    </div>


                                    <div
                                        class="mt-4

                                               rounded-xl

                                               bg-slate-50

                                               px-4
                                               py-3"
                                    >

                                        <p
                                            class="text-[10px]
                                                   uppercase
                                                   tracking-wider
                                                   font-semibold
                                                   text-slate-400"
                                        >
                                            Catatan Revisi
                                        </p>


                                        <p
                                            class="mt-1
                                                   text-sm
                                                   leading-relaxed
                                                   text-slate-600"
                                        >
                                            {{
                                                $version->revision_note
                                                ?: 'Tidak ada catatan revisi.'
                                            }}
                                        </p>

                                    </div>


                                    <p
                                        class="mt-3
                                               text-xs
                                               text-slate-400"
                                    >
                                        Diunggah oleh

                                        <span
                                            class="font-semibold
                                                   text-slate-600"
                                        >
                                            {{
                                                $version->uploader?->name
                                                ?? 'Sistem'
                                            }}
                                        </span>
                                    </p>

                                </div>

                            </div>



                            {{-- ACTIONS --}}

                            <div
                                class="flex
                                       flex-wrap
                                       gap-2

                                       flex-shrink-0"
                            >

                                @if(
                                    $isSpreadsheetVersion
                                    && $document->is_active
                                )

                                    <a
                                        href="{{ route(
                                            'documents.show',
                                            [
                                                'document' => $document,
                                                'revision' => (int) $version->revision_number,
                                            ]
                                        ) }}"

                                        class="inline-flex
                                               items-center
                                               justify-center
                                               rounded-lg
                                               border border-indigo-200
                                               bg-indigo-50
                                               px-3 py-2
                                               text-xs font-semibold
                                               text-indigo-700
                                               hover:bg-indigo-100
                                               transition"
                                    >
                                        Analitik
                                    </a>

                                @endif


                                <a
                                    href="{{ route(
                                        'documents.revisions.open',
                                        [
                                            $document,
                                            $version,
                                        ]
                                    ) }}"

                                    target="_blank"

                                    rel="noopener noreferrer"

                                    class="inline-flex
                                           items-center
                                           justify-center

                                           rounded-lg

                                           border
                                           border-slate-200

                                           bg-white

                                           px-3
                                           py-2

                                           text-xs
                                           font-semibold
                                           text-slate-700

                                           hover:bg-slate-50"
                                >
                                    Buka
                                </a>


                                <a
                                    href="{{ route(
                                        'documents.revisions.download',
                                        [
                                            $document,
                                            $version,
                                        ]
                                    ) }}"

                                    class="inline-flex
                                           items-center
                                           justify-center

                                           rounded-lg

                                           bg-blue-800

                                           px-3
                                           py-2

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

            </div>


            @if($versions->hasPages())

                <div
                    class="px-6
                           py-4

                           border-t
                           border-slate-100"
                >
                    {{ $versions->links() }}
                </div>

            @endif

        @else

            <div
                class="py-20
                       px-6
                       text-center"
            >

                <p
                    class="font-bold
                           text-slate-700"
                >
                    Belum ada riwayat revisi
                </p>


                <p
                    class="mt-2
                           text-sm
                           text-slate-400"
                >
                    Riwayat revisi akan ditampilkan di sini.
                </p>

            </div>

        @endif

    </section>

</x-app-layout>