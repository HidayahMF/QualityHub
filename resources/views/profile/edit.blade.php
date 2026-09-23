<x-app-layout>

    {{-- ========================================================= --}}
    {{-- PROFILE HEADER --}}
    {{-- ========================================================= --}}

    <div class="qh-profile-page">

        <section
            class="qh-profile-page-header
                   mb-6
                   flex
                   flex-col
                   lg:flex-row
                   lg:items-center
                   lg:justify-between
                   gap-5"
        >
            <div class="min-w-0">
                <h1
                    class="text-2xl
                           sm:text-3xl
                           font-bold
                           tracking-tight
                           text-slate-900"
                >
                    My Profile
                </h1>

                <p
                    class="mt-2
                           max-w-2xl
                           text-sm
                           sm:text-base
                           text-slate-500"
                >
                    Kelola informasi akun dan keamanan akses BMC QualityHub.
                </p>
            </div>


            <div
                class="qh-profile-identity
                       flex
                       items-center
                       gap-3
                       min-w-0
                       rounded-2xl
                       border
                       border-slate-200
                       px-4
                       py-3"
            >
                <div
                    class="flex
                           h-11
                           w-11
                           flex-shrink-0
                           items-center
                           justify-center
                           rounded-xl
                           bg-blue-800
                           text-sm
                           font-bold
                           text-white"
                >
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>

                <div class="min-w-0">
                    <p class="truncate text-sm font-bold text-slate-900">
                        {{ $user->name }}
                    </p>

                    <p class="mt-0.5 truncate text-xs text-slate-500">
                        {{ $user->email }}
                    </p>

                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span
                            class="rounded-md
                                   bg-blue-100
                                   px-2
                                   py-0.5
                                   text-[11px]
                                   font-semibold
                                   text-blue-800"
                        >
                            {{ $user->isSuperAdmin() ? 'Super Admin' : ucfirst($user->role) }}
                        </span>

                        <span
                            class="inline-flex
                                   items-center
                                   gap-1.5
                                   rounded-md
                                   bg-emerald-100
                                   px-2
                                   py-0.5
                                   text-[11px]
                                   font-semibold
                                   text-emerald-700"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Aktif
                        </span>
                    </div>
                </div>
            </div>
        </section>


        {{-- ========================================================= --}}
        {{-- PROFILE WORKSPACE --}}
        {{-- ========================================================= --}}

        <div
            class="qh-profile-layout
                   grid
                   lg:grid-cols-2
                   gap-6
                   items-stretch"
        >
            <div
                class="qh-profile-panel
                       h-full
                       rounded-2xl
                       border
                       border-slate-200
                       overflow-hidden"
            >
                @include('profile.partials.update-profile-information-form')
            </div>

            <div
                class="qh-profile-panel
                       h-full
                       rounded-2xl
                       border
                       border-slate-200
                       overflow-hidden"
            >
                @include('profile.partials.update-password-form')
            </div>
        </div>

    </div>

</x-app-layout>
