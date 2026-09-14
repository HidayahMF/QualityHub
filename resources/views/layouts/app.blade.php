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
        @if(request()->routeIs('dashboard'))
            Dashboard · {{ config('app.name', 'BMC QualityHub') }}
        @elseif(request()->routeIs('documents.revisions.create'))
            Upload Revision · {{ config('app.name', 'BMC QualityHub') }}
        @elseif(request()->routeIs('documents.revisions.*'))
            Revision History · {{ config('app.name', 'BMC QualityHub') }}
        @elseif(request()->routeIs('documents.scan.*'))
            Scan / Capture · {{ config('app.name', 'BMC QualityHub') }}
        @elseif(request()->routeIs('documents.archived'))
            Archived Documents · {{ config('app.name', 'BMC QualityHub') }}
        @elseif(request()->routeIs('documents.edit'))
            Edit Document · {{ config('app.name', 'BMC QualityHub') }}
        @elseif(request()->routeIs('documents.show'))
            Document Detail · {{ config('app.name', 'BMC QualityHub') }}
        @elseif(request()->routeIs('documents.create'))
            Add Document · {{ config('app.name', 'BMC QualityHub') }}
        @elseif(request()->routeIs('documents.*'))
            Documents · {{ config('app.name', 'BMC QualityHub') }}
        @elseif(request()->routeIs('favorites.*'))
            Favorites · {{ config('app.name', 'BMC QualityHub') }}
        @elseif(request()->routeIs('categories.*'))
            Categories · {{ config('app.name', 'BMC QualityHub') }}
        @elseif(request()->routeIs('departments.*'))
            Department Management · {{ config('app.name', 'BMC QualityHub') }}
        @elseif(request()->routeIs('users.*'))
            User Management · {{ config('app.name', 'BMC QualityHub') }}
        @elseif(request()->routeIs('activity-logs.*'))
            Activity Log · {{ config('app.name', 'BMC QualityHub') }}
        @elseif(request()->routeIs('profile.*'))
            My Profile · {{ config('app.name', 'BMC QualityHub') }}
        @else
            {{ config('app.name', 'BMC QualityHub') }}
        @endif
    </title>

    <link
        rel="icon"
        type="image/png"
        href="{{ asset('images/bmc-logo.png') }}"
    >


    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

</head>


<body
    class="font-sans antialiased bg-slate-50 text-slate-900"
    x-data="{ sidebarOpen: false }"
>


<div class="min-h-screen">


    {{-- ========================================================= --}}
    {{-- MOBILE OVERLAY --}}
    {{-- ========================================================= --}}

    <div
        x-show="sidebarOpen"

        x-transition:enter="transition-opacity ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"

        x-transition:leave="transition-opacity ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"

        @click="sidebarOpen = false"

        class="fixed
               inset-0
               z-40

               bg-slate-950/50
               backdrop-blur-sm

               lg:hidden"

        x-cloak
    ></div>



    {{-- ========================================================= --}}
    {{-- SIDEBAR --}}
    {{-- ========================================================= --}}

    <aside
        :class="
            sidebarOpen
                ? 'translate-x-0'
                : '-translate-x-full'
        "

        class="fixed
               inset-y-0
               left-0
               z-50

               w-72

               bg-gradient-to-b
               from-blue-950
               via-blue-950
               to-blue-900

               text-white

               transform
               transition-transform
               duration-300
               ease-in-out

               lg:translate-x-0

               shadow-2xl
               shadow-slate-950/20"
    >

        <div class="flex h-full flex-col">


            {{-- ================================================= --}}
            {{-- BRAND --}}
            {{-- ================================================= --}}

            <div
                class="h-20
                       px-5

                       flex
                       items-center
                       justify-between

                       border-b
                       border-white/10"
            >

                <a
                    href="{{ route('dashboard') }}"

                    class="flex
                           items-center
                           min-w-0
                           group"
                >


                    {{-- LOGO --}}

                    <div
                        class="w-12
                               h-12

                               rounded-2xl

                               bg-white

                               p-2

                               flex
                               items-center
                               justify-center

                               shadow-lg
                               shadow-black/10

                               transition
                               duration-300

                               group-hover:scale-105"
                    >

                        <img
                            src="{{ asset('images/bmc-logo.png') }}"

                            alt="PT Braja Mukti Cakra"

                            class="w-full
                                   h-full
                                   object-contain"
                        >

                    </div>



                    {{-- BRAND NAME --}}

                    <div class="ml-3 min-w-0">

                        <h1
                            class="font-bold
                                   tracking-wide
                                   truncate"
                        >
                            BMC QualityHub
                        </h1>


                        <p
                            class="text-[11px]
                                   text-blue-200
                                   truncate"
                        >
                            Quality Control Data System
                        </p>

                    </div>

                </a>



                {{-- MOBILE CLOSE --}}

                <button
                    type="button"

                    @click="sidebarOpen = false"

                    class="lg:hidden

                           w-9
                           h-9

                           rounded-xl

                           flex
                           items-center
                           justify-center

                           text-blue-100

                           hover:bg-white/10
                           hover:text-white

                           transition"

                    aria-label="Close sidebar"
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
                        <path d="M18 6 6 18"/>
                        <path d="m6 6 12 12"/>
                    </svg>

                </button>

            </div>



            {{-- ================================================= --}}
            {{-- NAVIGATION --}}
            {{-- ================================================= --}}

            <nav
                class="flex-1
                       px-4
                       py-6
                       overflow-y-auto"
            >


                {{-- ================================================= --}}
                {{-- WORKSPACE --}}
                {{-- ================================================= --}}

                <p
                    class="px-3
                           mb-2

                           text-[11px]
                           uppercase
                           tracking-[0.18em]

                           font-semibold
                           text-blue-300/60"
                >
                    Workspace
                </p>



                {{-- DASHBOARD --}}

                <a
                    href="{{ route('dashboard') }}"

                    @click="sidebarOpen = false"

                    @if(request()->routeIs('dashboard'))
                        aria-current="page"
                    @endif

                    class="
                        flex
                        items-center
                        gap-3

                        px-4
                        py-3

                        rounded-xl

                        transition
                        duration-200

                        {{
                            request()->routeIs('dashboard')
                                ? 'bg-white/10 text-white shadow-sm'
                                : 'text-blue-100 hover:bg-white/10 hover:text-white'
                        }}
                    "
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
                            width="7"
                            height="9"
                            x="3"
                            y="3"
                            rx="1"
                        />

                        <rect
                            width="7"
                            height="5"
                            x="14"
                            y="3"
                            rx="1"
                        />

                        <rect
                            width="7"
                            height="9"
                            x="14"
                            y="12"
                            rx="1"
                        />

                        <rect
                            width="7"
                            height="5"
                            x="3"
                            y="16"
                            rx="1"
                        />
                    </svg>


                    <span class="font-medium">
                        Dashboard
                    </span>

                </a>



                {{-- SEARCH DOCUMENTS --}}

                <a
                    href="{{ route('documents.index') }}#document-search"

                    @click="sidebarOpen = false"

                    class="mt-1

                           flex
                           items-center
                           gap-3

                           px-4
                           py-3

                           rounded-xl

                           text-blue-100

                           hover:bg-white/10
                           hover:text-white

                           transition
                           duration-200"
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
                        <circle
                            cx="11"
                            cy="11"
                            r="8"
                        />

                        <path d="m21 21-4.3-4.3"/>
                    </svg>


                    <span>
                        Search Documents
                    </span>

                </a>



                {{-- ================================================= --}}
                {{-- DOCUMENTS --}}
                {{-- ================================================= --}}

                <p
                    class="px-3
                           mt-8
                           mb-2

                           text-[11px]
                           uppercase
                           tracking-[0.18em]

                           font-semibold
                           text-blue-300/60"
                >
                    Documents
                </p>



                {{-- ================================================= --}}
                {{-- ALL DOCUMENTS --}}
                {{-- ================================================= --}}

                <a
                    href="{{ route('documents.index') }}"

                    @click="sidebarOpen = false"

                    @if(
                        request()->routeIs('documents.index')
                        || request()->routeIs('documents.show')
                        || request()->routeIs('documents.revisions.*')
                    )
                        aria-current="page"
                    @endif

                    class="
                        flex
                        items-center
                        gap-3

                        px-4
                        py-3

                        rounded-xl

                        transition
                        duration-200

                        {{
                            request()->routeIs('documents.index')
                            || request()->routeIs('documents.show')
                            || request()->routeIs('documents.revisions.*')

                                ? 'bg-white/10 text-white shadow-sm'
                                : 'text-blue-100 hover:bg-white/10 hover:text-white'
                        }}
                    "
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
                            d="M15 2H6
                               a2 2 0 0 0-2 2
                               v16
                               a2 2 0 0 0 2 2
                               h12
                               a2 2 0 0 0 2-2
                               V7Z"
                        />

                        <path d="M14 2v6h6"/>
                    </svg>


                    <span>
                        All Documents
                    </span>

                </a>



                {{-- ================================================= --}}
                {{-- ADD DOCUMENT --}}
                {{-- ADMIN + SUPER ADMIN --}}
                {{-- ================================================= --}}

                @if(
                    Auth::user()->isAdmin()
                    && Route::has('documents.create')
                )

                    <a
                        href="{{ route('documents.create') }}"

                        @click="sidebarOpen = false"

                        @if(request()->routeIs('documents.create'))
                            aria-current="page"
                        @endif

                        class="
                            flex
                            items-center
                            gap-3

                            px-4
                            py-3

                            rounded-xl

                            transition
                            duration-200

                            {{
                                request()->routeIs('documents.create')
                                    ? 'bg-white/10 text-white shadow-sm'
                                    : 'text-blue-100 hover:bg-white/10 hover:text-white'
                            }}
                        "
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
                            <path d="M12 5v14"/>
                            <path d="M5 12h14"/>
                        </svg>


                        <span>
                            Add Document
                        </span>

                    </a>

                @endif



                {{-- ================================================= --}}
                {{-- SCAN / CAPTURE --}}
                {{-- ADMIN + SUPER ADMIN --}}
                {{-- ================================================= --}}

                @if(
                    Auth::user()->isAdmin()
                    && Route::has('documents.scan.create')
                )

                    <a
                        href="{{ route('documents.scan.create') }}"
                        @click="sidebarOpen = false"

                        @if(request()->routeIs('documents.scan.*'))
                            aria-current="page"
                        @endif

                        class="
                            flex items-center gap-3
                            px-4 py-3
                            rounded-xl
                            transition duration-200
                            {{
                                request()->routeIs('documents.scan.*')
                                    ? 'bg-white/10 text-white shadow-sm'
                                    : 'text-blue-100 hover:bg-white/10 hover:text-white'
                            }}
                        "
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="19" height="19"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <rect x="3" y="4" width="18" height="16" rx="2"/>
                            <path d="M7 8h10"/>
                            <path d="M7 12h10"/>
                            <path d="M7 16h6"/>
                        </svg>
                        <span>Scan / Capture</span>
                    </a>

                @endif


                {{-- ================================================= --}}
                {{-- ARCHIVED DOCUMENTS --}}
                {{-- ADMIN + SUPER ADMIN --}}
                {{-- TAHAP 14 --}}
                {{-- ================================================= --}}

                @if(
                    Auth::user()->isAdmin()
                    && Route::has('documents.archived')
                )

                    <a
                        href="{{ route('documents.archived') }}"

                        @click="sidebarOpen = false"

                        @if(request()->routeIs('documents.archived'))
                            aria-current="page"
                        @endif

                        class="
                            flex
                            items-center
                            gap-3

                            px-4
                            py-3

                            rounded-xl

                            transition
                            duration-200

                            {{
                                request()->routeIs('documents.archived')
                                    ? 'bg-amber-400/15 text-amber-100 shadow-sm'
                                    : 'text-blue-100 hover:bg-white/10 hover:text-white'
                            }}
                        "
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
                                width="20"
                                height="5"
                                x="2"
                                y="3"
                                rx="1"
                            />

                            <path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"/>

                            <path d="M10 12h4"/>
                        </svg>


                        <span>
                            Archived Documents
                        </span>


                        <span
                            class="ml-auto

                                   rounded-md

                                   bg-amber-400/15

                                   px-2
                                   py-0.5

                                   text-[10px]
                                   font-semibold
                                   text-amber-200"
                        >
                            Archive
                        </span>

                    </a>

                @endif



                {{-- ================================================= --}}
                {{-- CATEGORIES --}}
                {{-- SUPER ADMIN ONLY --}}
                {{-- ================================================= --}}

                @if(
                    Auth::user()->isSuperAdmin()
                    && Route::has('categories.index')
                )

                    <a
                        href="{{ route('categories.index') }}"

                        @click="sidebarOpen = false"

                        @if(request()->routeIs('categories.*'))
                            aria-current="page"
                        @endif

                        class="
                            flex
                            items-center
                            gap-3

                            px-4
                            py-3

                            rounded-xl

                            transition
                            duration-200

                            {{
                                request()->routeIs('categories.*')
                                    ? 'bg-white/10 text-white shadow-sm'
                                    : 'text-blue-100 hover:bg-white/10 hover:text-white'
                            }}
                        "
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
                                width="7"
                                height="7"
                                x="3"
                                y="3"
                                rx="1"
                            />

                            <rect
                                width="7"
                                height="7"
                                x="14"
                                y="3"
                                rx="1"
                            />

                            <rect
                                width="7"
                                height="7"
                                x="3"
                                y="14"
                                rx="1"
                            />

                            <rect
                                width="7"
                                height="7"
                                x="14"
                                y="14"
                                rx="1"
                            />
                        </svg>


                        <span>
                            Categories
                        </span>

                    </a>

                @endif



                {{-- ================================================= --}}
                {{-- FAVORITES --}}
                {{-- ================================================= --}}

                @if(Route::has('favorites.index'))

                    <a
                        href="{{ route('favorites.index') }}"

                        @click="sidebarOpen = false"

                        @if(request()->routeIs('favorites.*'))
                            aria-current="page"
                        @endif

                        class="
                            flex
                            items-center
                            gap-3

                            px-4
                            py-3

                            rounded-xl

                            transition
                            duration-200

                            {{
                                request()->routeIs('favorites.*')
                                    ? 'bg-white/10 text-white shadow-sm'
                                    : 'text-blue-100 hover:bg-white/10 hover:text-white'
                            }}
                        "
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


                        <span>
                            Favorites
                        </span>

                    </a>

                @else

                    <div
                        class="flex
                               items-center
                               gap-3

                               px-4
                               py-3

                               rounded-xl

                               text-blue-100/60"
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


                        <span>
                            Favorites
                        </span>


                        <span
                            class="ml-auto

                                   rounded-md

                                   bg-white/10

                                   px-2
                                   py-0.5

                                   text-[10px]
                                   text-blue-200"
                        >
                            Soon
                        </span>

                    </div>

                @endif



                {{-- ================================================= --}}
                {{-- MANAGEMENT --}}
                {{-- ADMIN + SUPER ADMIN --}}
                {{-- ================================================= --}}

                @if(Auth::user()->isAdmin())

                    <p
                        class="px-3
                               mt-8
                               mb-2

                               text-[11px]
                               uppercase
                               tracking-[0.18em]

                               font-semibold
                               text-blue-300/60"
                    >
                        Management
                    </p>



                    {{-- DEPARTMENTS --}}

                    @if(
                        Auth::user()->isSuperAdmin()
                        && Route::has('departments.index')
                    )

                        <a
                            href="{{ route('departments.index') }}"

                            @click="sidebarOpen = false"

                            @if(request()->routeIs('departments.*'))
                                aria-current="page"
                            @endif

                            class="
                                flex
                                items-center
                                gap-3

                                px-4
                                py-3

                                rounded-xl

                                transition
                                duration-200

                                {{
                                    request()->routeIs('departments.*')
                                        ? 'bg-white/10 text-white shadow-sm'
                                        : 'text-blue-100 hover:bg-white/10 hover:text-white'
                                }}
                            "
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
                                    width="16"
                                    height="18"
                                    x="4"
                                    y="3"
                                    rx="2"
                                />

                                <path d="M9 7h1"/>
                                <path d="M14 7h1"/>
                                <path d="M9 11h1"/>
                                <path d="M14 11h1"/>
                                <path d="M9 15h1"/>
                                <path d="M14 15h1"/>
                                <path d="M9 21v-2h6v2"/>
                            </svg>


                            <span>
                                Departments
                            </span>

                        </a>

                    @endif



                    {{-- USERS --}}

                    @if(
                        Auth::user()->isSuperAdmin()
                        && Route::has('users.index')
                    )

                        <a
                            href="{{ route('users.index') }}"

                            @click="sidebarOpen = false"

                            @if(request()->routeIs('users.*'))
                                aria-current="page"
                            @endif

                            class="
                                flex
                                items-center
                                gap-3

                                px-4
                                py-3

                                rounded-xl

                                transition
                                duration-200

                                {{
                                    request()->routeIs('users.*')
                                        ? 'bg-white/10 text-white shadow-sm'
                                        : 'text-blue-100 hover:bg-white/10 hover:text-white'
                                }}
                            "
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
                                    d="M16 21v-2
                                       a4 4 0 0 0-4-4H6
                                       a4 4 0 0 0-4 4v2"
                                />

                                <circle
                                    cx="9"
                                    cy="7"
                                    r="4"
                                />

                                <path
                                    d="M22 21v-2
                                       a4 4 0 0 0-3-3.87"
                                />

                                <path
                                    d="M16 3.13
                                       a4 4 0 0 1 0 7.75"
                                />
                            </svg>


                            <span>
                                Users
                            </span>

                        </a>

                    @endif



                    {{-- ACTIVITY LOG --}}

                    @if(Route::has('activity-logs.index'))

                        <a
                            href="{{ route('activity-logs.index') }}"

                            @click="sidebarOpen = false"

                            @if(request()->routeIs('activity-logs.*'))
                                aria-current="page"
                            @endif

                            class="
                                flex
                                items-center
                                gap-3

                                px-4
                                py-3

                                rounded-xl

                                transition
                                duration-200

                                {{
                                    request()->routeIs('activity-logs.*')
                                        ? 'bg-white/10 text-white shadow-sm'
                                        : 'text-blue-100 hover:bg-white/10 hover:text-white'
                                }}
                            "
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
                                <path d="M12 8v4l3 3"/>

                                <circle
                                    cx="12"
                                    cy="12"
                                    r="9"
                                />
                            </svg>


                            <span>
                                Activity Log
                            </span>

                        </a>

                    @endif

                @endif

            </nav>



            {{-- ================================================= --}}
            {{-- SIDEBAR USER --}}
            {{-- ================================================= --}}

            <div
                class="p-4
                       border-t
                       border-white/10"
            >

                <div
                    class="flex
                           items-center
                           gap-3

                           px-3
                           py-3

                           rounded-2xl

                           bg-white/[0.06]

                           border
                           border-white/[0.06]"
                >

                    <div
                        class="w-10
                               h-10

                               rounded-xl

                               bg-blue-600

                               flex
                               items-center
                               justify-center

                               font-bold
                               text-sm

                               shadow-sm

                               flex-shrink-0"
                    >
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>


                    <div class="min-w-0 flex-1">

                        <p
                            class="text-sm
                                   font-semibold
                                   truncate"
                        >
                            {{ Auth::user()->name }}
                        </p>


                        <p
                            class="text-xs
                                   text-blue-200
                                   capitalize
                                   truncate"
                        >
                            {{ str_replace('_', ' ', Auth::user()->role) }}
                        </p>

                    </div>


                    <span
                        class="w-2
                               h-2
                               rounded-full
                               bg-emerald-400
                               flex-shrink-0"

                        title="Akun aktif"
                    ></span>

                </div>

            </div>

        </div>

    </aside>



    {{-- ========================================================= --}}
    {{-- MAIN AREA --}}
    {{-- ========================================================= --}}

    <div
        class="qualityhub-workspace
               lg:pl-72
               min-h-screen
               flex
               flex-col"
    >


        {{-- ===================================================== --}}
        {{-- TOPBAR --}}
        {{-- ===================================================== --}}

        <header
            class="qualityhub-topbar
                   sticky
                   top-0
                   z-30

                   h-20

                   bg-white/90
                   backdrop-blur-xl

                   border-b
                   border-slate-200/70"
        >

            <div
                class="h-full

                       px-5
                       sm:px-8

                       flex
                       items-center
                       justify-between"
            >


                {{-- LEFT --}}

                <div class="flex items-center gap-4">


                    {{-- MOBILE MENU BUTTON --}}

                    <button
                        type="button"

                        @click="sidebarOpen = true"

                        class="lg:hidden

                               w-10
                               h-10

                               rounded-xl

                               border
                               border-slate-200

                               bg-white

                               flex
                               items-center
                               justify-center

                               text-slate-700

                               hover:bg-slate-50

                               transition"

                        aria-label="Open sidebar"
                    >

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <line
                                x1="4"
                                x2="20"
                                y1="6"
                                y2="6"
                            />

                            <line
                                x1="4"
                                x2="20"
                                y1="12"
                                y2="12"
                            />

                            <line
                                x1="4"
                                x2="20"
                                y1="18"
                                y2="18"
                            />
                        </svg>

                    </button>



                    {{-- ================================================= --}}
                    {{-- DYNAMIC PAGE TITLE --}}
                    {{-- ================================================= --}}

                    <div>

                        <h2
                            class="font-bold
                                   text-slate-900"
                        >

                            @if(request()->routeIs('dashboard'))

                                Dashboard


                            @elseif(request()->routeIs('documents.revisions.create'))

                                Upload Revision


                            @elseif(request()->routeIs('documents.revisions.*'))

                                Revision History


                            @elseif(request()->routeIs('documents.create'))

                                Add Document


                            @elseif(request()->routeIs('documents.scan.*'))

                                Scan / Capture


                            @elseif(request()->routeIs('documents.archived'))

                                Archived Documents


                            @elseif(request()->routeIs('documents.edit'))

                                Edit Document


                            @elseif(request()->routeIs('documents.show'))

                                Document Detail


                            @elseif(request()->routeIs('documents.index'))

                                Documents


                            @elseif(request()->routeIs('documents.*'))

                                Documents


                            @elseif(request()->routeIs('categories.create'))

                                Add Category


                            @elseif(request()->routeIs('categories.edit'))

                                Edit Category


                            @elseif(request()->routeIs('categories.*'))

                                Categories Management


                            @elseif(request()->routeIs('departments.*'))

                                Department Management


                            @elseif(request()->routeIs('users.*'))

                                User Management


                            @elseif(request()->routeIs('activity-logs.*'))

                                Activity Log


                            @elseif(request()->routeIs('favorites.*'))

                                Favorites


                            @elseif(request()->routeIs('profile.*'))

                                My Profile


                            @else

                                BMC QualityHub

                            @endif

                        </h2>



                        {{-- DYNAMIC SUBTITLE --}}

                        <p
                            class="hidden
                                   sm:block

                                   text-xs
                                   text-slate-500"
                        >

                            @if(request()->routeIs('documents.revisions.create'))

                                Unggah revisi file baru tanpa menimpa versi sebelumnya


                            @elseif(request()->routeIs('documents.revisions.*'))

                                Tinjau seluruh riwayat revisi dokumen


                            @elseif(request()->routeIs('documents.archived'))

                                Tinjau dan pulihkan dokumen yang telah diarsipkan


                            @elseif(request()->routeIs('documents.edit'))

                                Perbarui metadata atau arsipkan dokumen ini


                            @elseif(request()->routeIs('documents.create'))

                                Daftarkan dokumen internal baru


                            @elseif(request()->routeIs('documents.scan.*'))

                                Scan / Capture dan simpan berkas Quality Control


                            @elseif(request()->routeIs('documents.show'))

                                Lihat metadata dokumen dan akses file


                            @elseif(request()->routeIs('documents.*'))

                                Pengelolaan dokumen internal BMC


                            @elseif(request()->routeIs('categories.*'))

                                Kelola klasifikasi dokumen BMC


                            @elseif(request()->routeIs('departments.*'))

                                Kelola data departemen dan struktur organisasi BMC


                            @elseif(request()->routeIs('users.*'))

                                Kelola akun pengguna dan hak akses QualityHub


                            @elseif(request()->routeIs('activity-logs.*'))

                                Tinjau aktivitas audit aplikasi QualityHub


                            @elseif(request()->routeIs('favorites.*'))

                                Dokumen yang sering Anda gunakan


                            @elseif(request()->routeIs('profile.*'))

                                Kelola informasi akun QualityHub Anda


                            @elseif(request()->routeIs('dashboard'))

                                Ringkasan ruang kerja BMC QualityHub


                            @else

                                Sistem Dokumen & Analitik Quality Control BMC

                            @endif

                        </p>

                    </div>

                </div>



                {{-- ================================================= --}}
                {{-- RIGHT --}}
                {{-- ================================================= --}}

                <div class="flex items-center gap-2">


                    {{-- AUTHENTICATED SESSION INDICATOR --}}

                    <div
                        class="hidden
                               xl:flex

                               items-center
                               gap-2

                               mr-2

                               px-3
                               py-2

                               rounded-xl

                               bg-emerald-50

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

                        Authenticated Session

                    </div>



                    {{-- ================================================= --}}
                    {{-- PROFILE DROPDOWN --}}
                    {{-- ================================================= --}}

                    <div
                        x-data="{ profileOpen: false }"

                        class="relative"
                    >

                        <button
                            type="button"

                            @click="profileOpen = !profileOpen"

                            :aria-expanded="profileOpen.toString()"

                            class="flex
                                   items-center
                                   gap-3

                                   px-2
                                   sm:px-3
                                   py-2

                                   rounded-xl

                                   hover:bg-slate-100

                                   transition"

                            aria-haspopup="true"
                        >

                            <div
                                class="hidden
                                       sm:block
                                       text-right"
                            >

                                <p
                                    class="text-sm
                                           font-semibold
                                           text-slate-800

                                           max-w-[180px]
                                           truncate"
                                >
                                    {{ Auth::user()->name }}
                                </p>


                                <p
                                    class="text-xs
                                           text-slate-500
                                           capitalize"
                                >
                                    {{ str_replace('_', ' ', Auth::user()->role) }}
                                </p>

                            </div>


                            <div
                                class="w-10
                                       h-10

                                       rounded-xl

                                       bg-blue-100
                                       text-blue-800

                                       flex
                                       items-center
                                       justify-center

                                       font-bold"
                            >
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>


                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="15"
                                height="15"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"

                                class="hidden
                                       sm:block

                                       text-slate-500

                                       transition-transform
                                       duration-200"

                                :class="
                                    profileOpen
                                        ? 'rotate-180'
                                        : ''
                                "
                            >
                                <path d="m6 9 6 6 6-6"/>
                            </svg>

                        </button>



                        {{-- DROPDOWN --}}

                        <div
                            x-show="profileOpen"

                            @click.outside="profileOpen = false"

                            @keydown.escape.window="
                                profileOpen = false
                            "

                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"

                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95 -translate-y-2"

                            class="absolute
                                   right-0

                                   mt-2

                                   w-72

                                   rounded-2xl

                                   bg-white

                                   border
                                   border-slate-200

                                   shadow-2xl
                                   shadow-slate-900/10

                                   overflow-hidden"

                            x-cloak
                        >


                            {{-- ================================================= --}}
                            {{-- PROFILE HEADER --}}
                            {{-- ================================================= --}}

                            <div
                                class="px-4
                                       py-4

                                       bg-slate-50

                                       border-b
                                       border-slate-100"
                            >

                                <div
                                    class="flex
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
                                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                    </div>


                                    <div class="min-w-0">

                                        <p
                                            class="text-sm
                                                   font-semibold
                                                   text-slate-900
                                                   truncate"
                                        >
                                            {{ Auth::user()->name }}
                                        </p>


                                        <p
                                            class="mt-0.5
                                                   text-xs
                                                   text-slate-500
                                                   truncate"
                                        >
                                            {{ Auth::user()->email }}
                                        </p>

                                    </div>

                                </div>



                                {{-- ROLE + STATUS --}}

                                <div
                                    class="mt-3

                                           flex
                                           flex-wrap
                                           items-center
                                           gap-2"
                                >

                                    <span
                                        class="inline-flex
                                               items-center

                                               rounded-lg

                                               bg-blue-100

                                               px-2.5
                                               py-1

                                               text-[11px]
                                               font-semibold
                                               text-blue-800
                                               capitalize"
                                    >
                                        {{ str_replace('_', ' ', Auth::user()->role) }}
                                    </span>


                                    <span
                                        class="inline-flex
                                               items-center
                                               gap-1.5

                                               rounded-lg

                                               bg-emerald-100

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

                                </div>

                            </div>



                            {{-- ================================================= --}}
                            {{-- PROFILE MENU --}}
                            {{-- ================================================= --}}

                            <div class="p-2">


                                {{-- MY PROFILE --}}

                                <a
                                    href="{{ route('profile.edit') }}"

                                    class="flex
                                           items-center
                                           gap-3

                                           px-4
                                           py-2.5

                                           rounded-xl

                                           text-sm
                                           font-medium
                                           text-slate-700

                                           hover:bg-slate-50

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
                                        <path
                                            d="M19 21v-2
                                               a4 4 0 0 0-4-4H9
                                               a4 4 0 0 0-4 4v2"
                                        />

                                        <circle
                                            cx="12"
                                            cy="7"
                                            r="4"
                                        />
                                    </svg>

                                    My Profile

                                </a>



                                {{-- DOCUMENTS --}}

                                <a
                                    href="{{ route('documents.index') }}"

                                    class="flex
                                           items-center
                                           gap-3

                                           px-4
                                           py-2.5

                                           rounded-xl

                                           text-sm
                                           font-medium
                                           text-slate-700

                                           hover:bg-slate-50

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

                                    Documents

                                </a>



                                {{-- FAVORITES --}}

                                @if(Route::has('favorites.index'))

                                    <a
                                        href="{{ route('favorites.index') }}"

                                        class="flex
                                               items-center
                                               gap-3

                                               px-4
                                               py-2.5

                                               rounded-xl

                                               text-sm
                                               font-medium
                                               text-slate-700

                                               hover:bg-slate-50

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

                                        My Favorites

                                    </a>

                                @endif



                                {{-- ================================================= --}}
                                {{-- ARCHIVED DOCUMENTS --}}
                                {{-- ADMIN ONLY --}}
                                {{-- ================================================= --}}

                                @if(
                                    Auth::user()->isAdmin()
                                    && Route::has('documents.archived')
                                )

                                    <a
                                        href="{{ route('documents.archived') }}"

                                        class="flex
                                               items-center
                                               gap-3

                                               px-4
                                               py-2.5

                                               rounded-xl

                                               text-sm
                                               font-medium
                                               text-slate-700

                                               hover:bg-amber-50
                                               hover:text-amber-800

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
                                            <rect
                                                width="20"
                                                height="5"
                                                x="2"
                                                y="3"
                                                rx="1"
                                            />

                                            <path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"/>

                                            <path d="M10 12h4"/>
                                        </svg>

                                        Archived Documents

                                    </a>

                                @endif



                                {{-- CATEGORY MANAGEMENT --}}
                                {{-- SUPER ADMIN ONLY --}}

                                @if(
                                    Auth::user()->isSuperAdmin()
                                    && Route::has('categories.index')
                                )

                                    <a
                                        href="{{ route('categories.index') }}"

                                        class="flex
                                               items-center
                                               gap-3

                                               px-4
                                               py-2.5

                                               rounded-xl

                                               text-sm
                                               font-medium
                                               text-slate-700

                                               hover:bg-slate-50

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
                                            <rect
                                                width="7"
                                                height="7"
                                                x="3"
                                                y="3"
                                                rx="1"
                                            />

                                            <rect
                                                width="7"
                                                height="7"
                                                x="14"
                                                y="3"
                                                rx="1"
                                            />

                                            <rect
                                                width="7"
                                                height="7"
                                                x="3"
                                                y="14"
                                                rx="1"
                                            />

                                            <rect
                                                width="7"
                                                height="7"
                                                x="14"
                                                y="14"
                                                rx="1"
                                            />
                                        </svg>

                                        Categories Management

                                    </a>

                                @endif



                                {{-- USER MANAGEMENT --}}

                                @if(
                                    Auth::user()->isSuperAdmin()
                                    && Route::has('users.index')
                                )

                                    <a
                                        href="{{ route('users.index') }}"

                                        class="flex
                                               items-center
                                               gap-3

                                               px-4
                                               py-2.5

                                               rounded-xl

                                               text-sm
                                               font-medium
                                               text-slate-700

                                               hover:bg-slate-50

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
                                            <path
                                                d="M16 21v-2
                                                   a4 4 0 0 0-4-4H6
                                                   a4 4 0 0 0-4 4v2"
                                            />

                                            <circle
                                                cx="9"
                                                cy="7"
                                                r="4"
                                            />
                                        </svg>

                                        User Management

                                    </a>

                                @endif



                                {{-- ACTIVITY LOG --}}

                                @if(
                                    Auth::user()->isAdmin()
                                    && Route::has('activity-logs.index')
                                )

                                    <a
                                        href="{{ route('activity-logs.index') }}"

                                        class="flex
                                               items-center
                                               gap-3

                                               px-4
                                               py-2.5

                                               rounded-xl

                                               text-sm
                                               font-medium
                                               text-slate-700

                                               hover:bg-slate-50

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
                                            <path d="M12 8v4l3 3"/>

                                            <circle
                                                cx="12"
                                                cy="12"
                                                r="9"
                                            />
                                        </svg>

                                        Activity Log

                                    </a>

                                @endif



                                <div
                                    class="my-2
                                           border-t
                                           border-slate-100"
                                ></div>



                                {{-- LOGOUT --}}

                                <form
                                    method="POST"
                                    action="{{ route('logout') }}"
                                >

                                    @csrf


                                    <button
                                        type="submit"

                                        class="w-full

                                               flex
                                               items-center
                                               gap-3

                                               px-4
                                               py-2.5

                                               rounded-xl

                                               text-left
                                               text-sm
                                               font-medium
                                               text-red-600

                                               hover:bg-red-50

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
                                            <path
                                                d="M9 21H5
                                                   a2 2 0 0 1-2-2
                                                   V5
                                                   a2 2 0 0 1 2-2
                                                   h4"
                                            />

                                            <polyline
                                                points="16 17 21 12 16 7"
                                            />

                                            <line
                                                x1="21"
                                                x2="9"
                                                y1="12"
                                                y2="12"
                                            />
                                        </svg>

                                        Sign Out

                                    </button>

                                </form>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </header>



        {{-- ===================================================== --}}
        {{-- PAGE CONTENT --}}
        {{-- ===================================================== --}}

        <main
            class="qualityhub-content
                   relative
                   z-10
                   flex-1

                   p-5
                   sm:p-6
                   lg:p-8"
        >
            {{ $slot }}
        </main>



        {{-- ===================================================== --}}
        {{-- FOOTER --}}
        {{-- ===================================================== --}}

        <footer
            class="px-5
                   sm:px-8
                   pb-6"
        >

            <div
                class="border-t
                       border-slate-200

                       pt-5

                       flex
                       flex-col
                       sm:flex-row

                       gap-3

                       items-center
                       justify-between

                       text-xs
                       text-slate-400"
            >

                <p>
                    © {{ date('Y') }} PT Braja Mukti Cakra
                </p>


                <div
                    class="flex
                           items-center
                           gap-2"
                >

                    <span
                        class="w-1.5
                               h-1.5
                               rounded-full
                               bg-emerald-500"
                    ></span>


                    <span>
                        BMC QualityHub · Sistem Dokumen & Analitik Quality Control
                    </span>

                </div>

            </div>

        </footer>

    </div>

</div>


</body>

</html>`