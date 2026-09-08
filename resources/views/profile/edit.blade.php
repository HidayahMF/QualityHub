<x-app-layout>

    {{-- ========================================================= --}}
    {{-- PROFILE HEADER --}}
    {{-- ========================================================= --}}

    <div class="qh-profile-page">

        <section
            class="qh-profile-page-header
                   mb-7
                   flex
                   flex-col
                   lg:flex-row
                   lg:items-end
                   lg:justify-between
                   gap-5"
        >
            <div class="min-w-0">
                <div
                    class="inline-flex
                           items-center
                           gap-2
                           mb-2
                           text-sm
                           font-semibold
                           text-blue-700"
                >
                    <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                    Akun & Keamanan
                </div>

                <h1
                    class="text-3xl
                           sm:text-4xl
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
                    Kelola informasi akun dan keamanan akses Anda ke BMC QualityHub.
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
                </div>
            </div>
        </section>


        {{-- ========================================================= --}}
        {{-- PROFILE WORKSPACE --}}
        {{-- ========================================================= --}}

        <div
            class="qh-profile-layout
                   grid
                   xl:grid-cols-[minmax(0,1.45fr)_minmax(320px,0.8fr)]
                   gap-6
                   items-start"
        >
            <div
                class="qh-profile-panel
                       rounded-2xl
                       border
                       border-slate-200
                       overflow-hidden"
            >
                @include('profile.partials.update-profile-information-form')
            </div>


            <div class="space-y-6">

                <div
                    class="qh-profile-panel
                           rounded-2xl
                           border
                           border-slate-200
                           overflow-hidden"
                >
                    @include('profile.partials.update-password-form')
                </div>


                <div
                    class="qh-profile-danger-zone
                           rounded-2xl
                           border
                           border-red-200
                           overflow-hidden"
                >
                    @include('profile.partials.delete-user-form')
                </div>

            </div>
        </div>

    </div>

</x-app-layout>
