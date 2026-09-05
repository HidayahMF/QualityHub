<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        {{ config('app.name', 'BMC QualityHub') }}
    </title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])


    {{-- ========================================================= --}}
    {{-- BMC QUALITYHUB · AUTH VISUAL SYSTEM --}}
    {{-- ========================================================= --}}

    <style>
        :root {
            --qh-auth-blue:
                #1d4ed8;

            --qh-auth-deep:
                #172554;

            --qh-auth-shadow:
                0 30px 80px
                rgba(15, 23, 42, 0.12);
        }


        .qualityhub-auth-canvas {
            background:
                radial-gradient(
                    circle at 88% 8%,
                    rgba(59, 130, 246, 0.08),
                    transparent 28rem
                ),
                linear-gradient(
                    180deg,
                    #f8fbff 0%,
                    #eef4fa 100%
                );
        }


        .qualityhub-auth-left-grid {
            opacity: 0.22;

            background-image:
                linear-gradient(
                    rgba(255, 255, 255, 0.08) 1px,
                    transparent 1px
                ),
                linear-gradient(
                    90deg,
                    rgba(255, 255, 255, 0.07) 1px,
                    transparent 1px
                );

            background-size:
                38px 38px;

            mask-image:
                linear-gradient(
                    to bottom,
                    rgba(0, 0, 0, 0.75),
                    transparent 88%
                );
        }


        .qualityhub-auth-feature {
            transition:
                transform 220ms
                cubic-bezier(0.22, 1, 0.36, 1),
                border-color 220ms ease,
                background-color 220ms ease,
                box-shadow 220ms ease;
        }


        @media (hover: hover) and (pointer: fine) {
            .qualityhub-auth-feature:hover {
                transform:
                    translateY(-3px);

                border-color:
                    rgba(255, 255, 255, 0.18);

                background:
                    rgba(255, 255, 255, 0.12);

                box-shadow:
                    0 16px 32px
                    rgba(15, 23, 42, 0.13);
            }
        }


        .qualityhub-auth-orbit {
            animation:
                qualityhubFloat
                9s
                ease-in-out
                infinite alternate;
        }


        @keyframes qualityhubFloat {
            from {
                transform:
                    translate3d(0, 0, 0)
                    scale(1);
            }

            to {
                transform:
                    translate3d(-14px, 18px, 0)
                    scale(1.03);
            }
        }


        @media (prefers-reduced-motion: reduce) {
            .qualityhub-auth-orbit {
                animation:
                    none !important;
            }

            .qualityhub-auth-feature {
                transition:
                    none !important;
            }
        }
    </style>
</head>


<body
    class="font-sans
           antialiased
           text-slate-900"
>

    <main
        class="qualityhub-auth-canvas
               min-h-screen"
    >

        <div
            class="grid
                   min-h-screen
                   lg:grid-cols-[minmax(0,1.04fr)_minmax(540px,0.96fr)]"
        >

            {{-- ================================================= --}}
            {{-- LEFT · CORPORATE QUALITYHUB PANEL --}}
            {{-- ================================================= --}}

            <section
                class="relative
                       hidden
                       overflow-hidden
                       bg-gradient-to-br
                       from-blue-950
                       via-blue-900
                       to-blue-700
                       p-10
                       text-white
                       lg:flex
                       xl:p-14"
            >

                {{-- Subtle grid --}}
                <div
                    class="qualityhub-auth-left-grid
                           pointer-events-none
                           absolute
                           inset-0"
                ></div>


                {{-- Decorative glow --}}
                <div
                    class="pointer-events-none
                           absolute
                           -left-28
                           -top-28
                           h-[360px]
                           w-[360px]
                           rounded-full
                           bg-white/[0.055]"
                ></div>


                <div
                    class="qualityhub-auth-orbit
                           pointer-events-none
                           absolute
                           -bottom-40
                           -right-20
                           h-[520px]
                           w-[520px]
                           rounded-full
                           bg-blue-400/[0.10]"
                ></div>


                <div
                    class="pointer-events-none
                           absolute
                           right-[8%]
                           top-[28%]
                           h-64
                           w-64
                           rounded-full
                           bg-cyan-300/[0.055]
                           blur-3xl"
                ></div>


                <div
                    class="relative
                           z-10
                           flex
                           w-full
                           flex-col
                           justify-between"
                >

                    {{-- BRAND --}}
                    <div>

                        <div
                            class="flex
                                   items-center
                                   gap-3.5"
                        >

                            <div
                                class="flex
                                       h-14
                                       w-14
                                       items-center
                                       justify-center
                                       rounded-2xl
                                       bg-white
                                       p-2
                                       shadow-[0_12px_32px_rgba(0,0,0,0.16)]"
                            >

                                <img
                                    src="{{ asset('images/bmc-logo.png') }}"
                                    alt="PT Braja Mukti Cakra"
                                    class="h-full
                                           w-full
                                           object-contain"
                                >

                            </div>


                            <div>

                                <h1
                                    class="text-xl
                                           font-bold
                                           tracking-wide
                                           text-white"
                                >
                                    BMC QualityHub
                                </h1>


                                <p
                                    class="mt-0.5
                                           text-[11px]
                                           font-medium
                                           tracking-wide
                                           text-blue-200/80"
                                >
                                    Sistem Data & Analitik Quality Control
                                </p>

                            </div>

                        </div>

                    </div>


                    {{-- HERO --}}
                    <div
                        class="my-10
                               max-w-2xl
                               xl:my-14"
                    >

                        <div
                            class="inline-flex
                                   items-center
                                   gap-2
                                   rounded-full
                                   border
                                   border-white/10
                                   bg-white/[0.08]
                                   px-4
                                   py-2
                                   text-xs
                                   font-semibold
                                   text-blue-100
                                   backdrop-blur-sm"
                        >

                            <span
                                class="h-2
                                       w-2
                                       rounded-full
                                       bg-emerald-400
                                       shadow-[0_0_0_5px_rgba(52,211,153,0.08)]"
                            ></span>

                            PT Braja Mukti Cakra · Quality Control
                        </div>


                        <p
                            class="mt-7
                                   text-xs
                                   font-semibold
                                   uppercase
                                   tracking-[0.2em]
                                   text-blue-200/60"
                        >
                            Ruang Kerja Quality Terintegrasi
                        </p>


                        <h2
                            class="mt-3
                                   max-w-xl
                                   text-4xl
                                   font-bold
                                   leading-[1.12]
                                   tracking-tight
                                   text-white
                                   xl:text-5xl"
                        >
                            Data Quality.
                            <span
                                class="bg-gradient-to-r
                                       from-blue-200
                                       to-cyan-300
                                       bg-clip-text
                                       text-transparent"
                            >
                                Lebih Jelas. Lebih Cepat.
                            </span>
                        </h2>


                        <p
                            class="mt-6
                                   max-w-xl
                                   text-base
                                   leading-8
                                   text-blue-100/72
                                   xl:text-[17px]"
                        >
                            Pusat dokumen dan data Quality Control untuk
                            pencarian, pengelolaan revisi, analitik spreadsheet,
                            serta traceability informasi secara terstruktur
                            dan aman.
                        </p>


                        {{-- FEATURE CARDS --}}
                        <div
                            class="mt-9
                                   grid
                                   grid-cols-3
                                   gap-3
                                   xl:gap-4"
                        >

                            {{-- FEATURE 1 --}}
                            <article
                                class="qualityhub-auth-feature
                                       rounded-2xl
                                       border
                                       border-white/10
                                       bg-white/[0.075]
                                       p-4
                                       backdrop-blur-sm"
                            >

                                <div
                                    class="flex
                                           h-9
                                           w-9
                                           items-center
                                           justify-center
                                           rounded-xl
                                           bg-white/10
                                           text-blue-100"
                                >
                                    <svg
                                        width="18"
                                        height="18"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <circle cx="11" cy="11" r="8"/>
                                        <path d="m21 21-4.3-4.3"/>
                                    </svg>
                                </div>


                                <p
                                    class="mt-4
                                           text-sm
                                           font-bold
                                           text-white"
                                >
                                    Pencarian Cerdas
                                </p>


                                <p
                                    class="mt-1
                                           text-[11px]
                                           leading-5
                                           text-blue-200/70"
                                >
                                    Temukan dokumen QC lebih cepat.
                                </p>

                            </article>


                            {{-- FEATURE 2 --}}
                            <article
                                class="qualityhub-auth-feature
                                       rounded-2xl
                                       border
                                       border-white/10
                                       bg-white/[0.075]
                                       p-4
                                       backdrop-blur-sm"
                            >

                                <div
                                    class="flex
                                           h-9
                                           w-9
                                           items-center
                                           justify-center
                                           rounded-xl
                                           bg-white/10
                                           text-cyan-100"
                                >
                                    <svg
                                        width="18"
                                        height="18"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path d="M3 3v18h18"/>
                                        <path d="m7 15 4-5 3 3 5-7"/>
                                    </svg>
                                </div>


                                <p
                                    class="mt-4
                                           text-sm
                                           font-bold
                                           text-white"
                                >
                                    Analitik Quality
                                </p>


                                <p
                                    class="mt-1
                                           text-[11px]
                                           leading-5
                                           text-blue-200/70"
                                >
                                    Analisis data Excel lebih jelas.
                                </p>

                            </article>


                            {{-- FEATURE 3 --}}
                            <article
                                class="qualityhub-auth-feature
                                       rounded-2xl
                                       border
                                       border-white/10
                                       bg-white/[0.075]
                                       p-4
                                       backdrop-blur-sm"
                            >

                                <div
                                    class="flex
                                           h-9
                                           w-9
                                           items-center
                                           justify-center
                                           rounded-xl
                                           bg-white/10
                                           text-emerald-100"
                                >
                                    <svg
                                        width="18"
                                        height="18"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <rect width="18" height="11" x="3" y="11" rx="2"/>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                    </svg>
                                </div>


                                <p
                                    class="mt-4
                                           text-sm
                                           font-bold
                                           text-white"
                                >
                                    Akses Aman
                                </p>


                                <p
                                    class="mt-1
                                           text-[11px]
                                           leading-5
                                           text-blue-200/70"
                                >
                                    Akses internal sesuai akun dan role.
                                </p>

                            </article>

                        </div>


                        {{-- TRUST STRIP --}}
                        <div
                            class="mt-6
                                   flex
                                   flex-wrap
                                   items-center
                                   gap-x-5
                                   gap-y-2
                                   text-[11px]
                                   font-medium
                                   text-blue-200/60"
                        >

                            <span
                                class="inline-flex
                                       items-center
                                       gap-1.5"
                            >
                                <span
                                    class="h-1.5
                                           w-1.5
                                           rounded-full
                                           bg-emerald-400"
                                ></span>

                                Penyimpanan dokumen privat
                            </span>


                            <span
                                class="inline-flex
                                       items-center
                                       gap-1.5"
                            >
                                <span
                                    class="h-1.5
                                           w-1.5
                                           rounded-full
                                           bg-cyan-300"
                                ></span>

                                Ketertelusuran revisi
                            </span>


                            <span
                                class="inline-flex
                                       items-center
                                       gap-1.5"
                            >
                                <span
                                    class="h-1.5
                                           w-1.5
                                           rounded-full
                                           bg-blue-300"
                                ></span>

                                Analitik internal
                            </span>

                        </div>

                    </div>


                    {{-- FOOTER --}}
                    <div
                        class="flex
                               items-center
                               justify-between
                               gap-4
                               text-[11px]
                               text-blue-200/55"
                    >

                        <p>
                            © {{ date('Y') }} PT Braja Mukti Cakra
                        </p>


                        <p
                            class="hidden
                                   xl:block"
                        >
                            Sistem Internal Quality Control
                        </p>

                    </div>

                </div>

            </section>


            {{-- ================================================= --}}
            {{-- RIGHT · AUTH CONTENT --}}
            {{-- ================================================= --}}

            <section
                class="relative
                       flex
                       min-h-screen
                       items-center
                       justify-center
                       overflow-hidden
                       px-5
                       py-10
                       sm:px-8
                       lg:px-10
                       xl:px-14"
            >

                {{-- Soft background shapes --}}
                <div
                    class="pointer-events-none
                           absolute
                           right-[-110px]
                           top-[-110px]
                           h-72
                           w-72
                           rounded-full
                           bg-blue-500/[0.055]
                           blur-3xl"
                ></div>


                <div
                    class="pointer-events-none
                           bottom-[-100px]
                           left-[-70px]
                           absolute
                           h-64
                           w-64
                           rounded-full
                           bg-cyan-400/[0.045]
                           blur-3xl"
                ></div>


                <div
                    class="relative
                           z-10
                           w-full
                           {{
                               request()->routeIs('register')
                                   ? 'max-w-2xl'
                                   : 'max-w-lg'
                           }}"
                >

                    {{-- MOBILE BRAND --}}
                    <div
                        class="mb-8
                               flex
                               items-center
                               gap-3
                               lg:hidden"
                    >

                        <div
                            class="flex
                                   h-12
                                   w-12
                                   items-center
                                   justify-center
                                   rounded-xl
                                   border
                                   border-slate-200
                                   bg-white
                                   p-2
                                   shadow-[0_8px_22px_rgba(15,23,42,0.08)]"
                        >

                            <img
                                src="{{ asset('images/bmc-logo.png') }}"
                                alt="PT Braja Mukti Cakra"
                                class="h-full
                                       w-full
                                       object-contain"
                            >

                        </div>


                        <div>

                            <h1
                                class="text-xl
                                       font-bold
                                       tracking-tight
                                       text-blue-950"
                            >
                                BMC QualityHub
                            </h1>


                            <p
                                class="mt-0.5
                                       text-xs
                                       text-slate-500"
                            >
                                Sistem Data & Analitik Quality Control
                            </p>

                        </div>

                    </div>


                    {{ $slot }}


                    {{-- SMALL AUTH FOOTER --}}
                    <div
                        class="mt-6
                               flex
                               flex-col
                               items-center
                               justify-center
                               gap-1
                               text-center
                               text-[10px]
                               text-slate-400"
                    >
                        <p>
                            Sistem internal PT Braja Mukti Cakra
                        </p>

                        <p>
                            BMC QualityHub · Sistem Dokumen & Analitik Quality Control
                        </p>
                    </div>

                </div>

            </section>

        </div>

    </main>

</body>

</html>
