<x-app-layout>

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <section
        class="flex flex-col
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
                       text-sm
                       font-semibold
                       text-blue-700
                       mb-2"
            >
                <span
                    class="w-2 h-2
                           rounded-full
                           bg-blue-600"
                ></span>

                Administrasi Sistem
            </div>


            <h1
                class="text-3xl
                       sm:text-4xl
                       font-bold
                       tracking-tight
                       text-slate-900"
            >
                User Management
            </h1>


            <p
                class="mt-2
                       text-sm
                       sm:text-base
                       text-slate-500"
            >
                Tinjau permintaan akun dan kelola hak akses
                ke BMC QualityHub.
            </p>

        </div>


        <div
            class="inline-flex
                   items-center
                   gap-2
                   rounded-xl
                   border border-emerald-200
                   bg-emerald-50
                   px-4 py-2.5
                   text-sm
                   font-semibold
                   text-emerald-700"
        >
            <span
                class="w-2 h-2
                       rounded-full
                       bg-emerald-500"
            ></span>

            Akses Administrator
        </div>

    </section>



    {{-- ========================================================= --}}
    {{-- SUCCESS MESSAGE --}}
    {{-- ========================================================= --}}

    @php
        /*
        |--------------------------------------------------------------------------
        | Success Message Localization
        |--------------------------------------------------------------------------
        | Mendukung pesan legacy dari User Management Controller tanpa
        | mengubah status, route, action, atau identifier teknis.
        */
        $userSuccessMessage = session('success');

        if (is_string($userSuccessMessage)) {
            $patterns = [
                '/^Account (.+) successfully approved\.$/' =>
                    'Akun $1 berhasil disetujui.',

                '/^Account (.+) successfully rejected\.$/' =>
                    'Akun $1 berhasil ditolak.',

                '/^Account (.+) has been disabled\.$/' =>
                    'Akun $1 berhasil dinonaktifkan.',

                '/^Account (.+) successfully activated\.$/' =>
                    'Akun $1 berhasil diaktifkan.',
            ];

            foreach ($patterns as $pattern => $replacement) {
                if (preg_match($pattern, $userSuccessMessage) === 1) {
                    $userSuccessMessage = preg_replace(
                        $pattern,
                        $replacement,
                        $userSuccessMessage
                    );

                    break;
                }
            }
        }
    @endphp

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

            <div class="flex gap-3">

                <div
                    class="w-9 h-9
                           rounded-xl
                           bg-emerald-100
                           text-emerald-700
                           flex
                           items-center
                           justify-center"
                >
                    ✓
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
                        {{ $userSuccessMessage }}
                    </p>

                </div>

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
    {{-- STATISTICS --}}
    {{-- ========================================================= --}}

    <section
        class="grid
               grid-cols-1
               sm:grid-cols-2
               xl:grid-cols-4
               gap-5
               mb-6"
    >

        {{-- TOTAL --}}
        <div
            class="bg-white
                   rounded-2xl
                   border border-slate-200/70
                   p-5
                   shadow-sm"
        >

            <p class="text-sm font-medium text-slate-500">
                Total Akun
            </p>

            <p class="mt-3 text-3xl font-bold text-slate-900">
                {{ number_format($statistics['total']) }}
            </p>

            <p class="mt-3 text-xs text-slate-400">
                Pengguna terdaftar
            </p>

        </div>


        {{-- ACTIVE --}}
        <div
            class="bg-white
                   rounded-2xl
                   border border-slate-200/70
                   p-5
                   shadow-sm"
        >

            <p class="text-sm font-medium text-slate-500">
                Pengguna Aktif
            </p>

            <p class="mt-3 text-3xl font-bold text-emerald-700">
                {{ number_format($statistics['active']) }}
            </p>

            <p class="mt-3 text-xs text-slate-400">
                Akun dengan akses aktif
            </p>

        </div>


        {{-- PENDING --}}
        <div
            class="bg-white
                   rounded-2xl
                   border border-amber-200
                   p-5
                   shadow-sm"
        >

            <p class="text-sm font-medium text-slate-500">
                Menunggu Persetujuan
            </p>

            <p class="mt-3 text-3xl font-bold text-amber-700">
                {{ number_format($statistics['pending']) }}
            </p>

            <p class="mt-3 text-xs text-slate-400">
                Menunggu peninjauan
            </p>

        </div>


        {{-- INACTIVE --}}
        <div
            class="bg-white
                   rounded-2xl
                   border border-slate-200/70
                   p-5
                   shadow-sm"
        >

            <p class="text-sm font-medium text-slate-500">
                Dibatasi
            </p>

            <p class="mt-3 text-3xl font-bold text-slate-700">
                {{ number_format($statistics['inactive']) }}
            </p>

            <p class="mt-3 text-xs text-slate-400">
                Dinonaktifkan / ditolak
            </p>

        </div>

    </section>



    {{-- ========================================================= --}}
    {{-- SEARCH / FILTER --}}
    {{-- ========================================================= --}}

    <form
        method="GET"
        action="{{ route('users.index') }}"

        class="bg-white
               rounded-2xl
               border border-slate-200/70
               shadow-sm
               p-5
               mb-6"
    >

        <div
            class="grid
                   lg:grid-cols-4
                   gap-3"
        >

            {{-- SEARCH --}}
            <div class="lg:col-span-2 relative">

                <div
                    class="absolute
                           inset-y-0
                           left-0
                           pl-4
                           flex
                           items-center
                           pointer-events-none
                           text-slate-400"
                >
                    🔎
                </div>


                <input
                    type="text"
                    name="search"

                    value="{{ request('search') }}"

                    placeholder="Cari nama, ID karyawan, email, departemen..."

                    class="w-full
                           rounded-xl
                           border-slate-200
                           bg-slate-50
                           pl-11 pr-4 py-3
                           text-sm
                           focus:border-blue-500
                           focus:ring-blue-500"
                >

            </div>


            {{-- STATUS --}}
            <select
                name="status"

                class="rounded-xl
                       border-slate-200
                       bg-slate-50
                       px-4 py-3
                       text-sm
                       text-slate-700
                       focus:border-blue-500
                       focus:ring-blue-500"
            >

                <option value="">
                    Semua Status
                </option>

                <option
                    value="pending"
                    @selected(request('status') === 'pending')
                >
                    Menunggu
                </option>

                <option
                    value="active"
                    @selected(request('status') === 'active')
                >
                    Aktif
                </option>

                <option
                    value="inactive"
                    @selected(request('status') === 'inactive')
                >
                    Tidak Aktif
                </option>

                <option
                    value="rejected"
                    @selected(request('status') === 'rejected')
                >
                    Ditolak
                </option>

            </select>


            {{-- ROLE --}}
            <select
                name="role"

                class="rounded-xl
                       border-slate-200
                       bg-slate-50
                       px-4 py-3
                       text-sm
                       text-slate-700
                       focus:border-blue-500
                       focus:ring-blue-500"
            >

                <option value="">
                    Semua Peran
                </option>

                <option
                    value="super_admin"
                    @selected(request('role') === 'super_admin')
                >
                    Super Admin
                </option>

                <option
                    value="user"
                    @selected(request('role') === 'user')
                >
                    Pengguna
                </option>

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
                       px-5 py-2.5
                       text-sm
                       font-semibold
                       text-white
                       hover:bg-blue-900
                       transition"
            >
                Terapkan Filter
            </button>


            <a
                href="{{ route('users.index') }}"

                class="rounded-xl
                       border border-slate-200
                       bg-white
                       px-5 py-2.5
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
    {{-- USER TABLE --}}
    {{-- ========================================================= --}}

    <section
        class="bg-white
               rounded-2xl
               border border-slate-200/70
               shadow-sm
               overflow-hidden"
    >

        <div
            class="px-6 py-5
                   border-b border-slate-100"
        >

            <h2 class="font-bold text-slate-900">
                Akun Terdaftar
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                {{ $users->total() }} akun ditemukan.
            </p>

        </div>



        @if($users->count())

            <div class="overflow-x-auto">

                <table class="w-full text-left">

                    <thead class="bg-slate-50">

                        <tr
                            class="text-[11px]
                                   uppercase
                                   tracking-wider
                                   text-slate-500"
                        >

                            <th class="px-6 py-4 font-semibold">
                                Karyawan
                            </th>

                            <th class="px-6 py-4 font-semibold">
                                Departemen
                            </th>

                            <th class="px-6 py-4 font-semibold">
                                Peran
                            </th>

                            <th class="px-6 py-4 font-semibold">
                                Status
                            </th>

                            <th class="px-6 py-4 font-semibold">
                                Login Terakhir
                            </th>

                            <th class="px-6 py-4 font-semibold text-right">
                                Aksi
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-100">

                        @foreach($users as $user)

                            <tr
                                class="hover:bg-slate-50/70
                                       transition"
                            >

                                {{-- EMPLOYEE --}}
                                <td class="px-6 py-4">

                                    <div class="flex items-center gap-3">

                                        <div
                                            class="w-10 h-10
                                                   rounded-xl
                                                   bg-blue-100
                                                   text-blue-800
                                                   flex
                                                   items-center
                                                   justify-center
                                                   font-bold
                                                   flex-shrink-0"
                                        >
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>


                                        <div class="min-w-0">

                                            <p
                                                class="text-sm
                                                       font-semibold
                                                       text-slate-900"
                                            >
                                                {{ $user->name }}
                                            </p>

                                            <p
                                                class="mt-1
                                                       text-xs
                                                       text-slate-400"
                                            >
                                                {{ $user->employee_id ?? 'Tidak ada ID Karyawan' }}
                                                ·
                                                {{ $user->email }}
                                            </p>

                                        </div>

                                    </div>

                                </td>


                                {{-- DEPARTMENT --}}
                                <td class="px-6 py-4">

                                    <span class="text-sm text-slate-700">
                                        {{ $user->department?->name ?? '-' }}
                                    </span>

                                </td>


                                {{-- ROLE --}}
                                <td class="px-6 py-4">

                                    <span
                                        class="inline-flex
                                               rounded-lg
                                               bg-blue-50
                                               px-2.5 py-1
                                               text-xs
                                               font-semibold
                                               text-blue-700
                                               capitalize"
                                    >
                                        {{ match($user->role) {
                                            'super_admin' => 'Super Admin',
                                            'user' => 'Pengguna',
                                            default => ucwords(str_replace('_', ' ', $user->role)),
                                        } }}
                                    </span>

                                </td>


                                {{-- STATUS --}}
                                <td class="px-6 py-4">

                                    @php
                                        $statusClasses = match($user->account_status) {
                                            'active' =>
                                                'bg-emerald-50 text-emerald-700',

                                            'pending' =>
                                                'bg-amber-50 text-amber-700',

                                            'rejected' =>
                                                'bg-red-50 text-red-700',

                                            default =>
                                                'bg-slate-100 text-slate-600',
                                        };
                                    @endphp


                                    <span
                                        class="inline-flex
                                               items-center
                                               gap-1.5
                                               rounded-lg
                                               px-2.5 py-1
                                               text-xs
                                               font-semibold
                                               capitalize
                                               {{ $statusClasses }}"
                                    >

                                        <span
                                            class="w-1.5 h-1.5
                                                   rounded-full
                                                   bg-current"
                                        ></span>

                                        {{ match($user->account_status) {
                                            'active' => 'Aktif',
                                            'pending' => 'Menunggu',
                                            'inactive' => 'Tidak Aktif',
                                            'rejected' => 'Ditolak',
                                            default => ucfirst($user->account_status),
                                        } }}

                                    </span>

                                </td>


                                {{-- LAST LOGIN --}}
                                <td class="px-6 py-4">

                                    <span
                                        class="text-xs
                                               text-slate-500"
                                    >
                                        {{ $user->last_login_at
                                            ? $user->last_login_at->locale('id')->translatedFormat('d M Y, H:i')
                                            : 'Belum pernah'
                                        }}
                                    </span>

                                </td>


                                {{-- ACTION --}}
                                <td class="px-6 py-4">

                                    <div
                                        class="flex
                                               justify-end
                                               flex-wrap
                                               gap-2"
                                    >

                                        {{-- SUPER ADMIN PROTECTION --}}
                                        @if($user->isSuperAdmin())

                                            <span
                                                class="rounded-lg
                                                       bg-slate-100
                                                       px-3 py-2
                                                       text-xs
                                                       font-semibold
                                                       text-slate-500"
                                            >
                                                Dilindungi
                                            </span>


                                        {{-- PENDING --}}
                                        @elseif($user->account_status === 'pending')

                                            <form
                                                method="POST"
                                                action="{{ route('users.approve', $user) }}"
                                            >
                                                @csrf
                                                @method('PATCH')

                                                <button
                                                    type="submit"

                                                    class="rounded-lg
                                                           bg-emerald-600
                                                           px-3 py-2
                                                           text-xs
                                                           font-semibold
                                                           text-white
                                                           hover:bg-emerald-700
                                                           transition"
                                                >
                                                    Setujui
                                                </button>

                                            </form>


                                            <button
                                                type="button"
                                                x-on:click.prevent="$dispatch('open-modal', 'reject-user-{{ $user->id }}')"
                                                class="rounded-lg
                                                       bg-red-50
                                                       px-3 py-2
                                                       text-xs
                                                       font-semibold
                                                       text-red-700
                                                       hover:bg-red-100
                                                       transition"
                                            >
                                                Tolak
                                            </button>


                                        {{-- ACTIVE --}}
                                        @elseif($user->account_status === 'active')

                                            <button
                                                type="button"
                                                x-on:click.prevent="$dispatch('open-modal', 'deactivate-user-{{ $user->id }}')"
                                                class="rounded-lg
                                                       bg-amber-50
                                                       px-3 py-2
                                                       text-xs
                                                       font-semibold
                                                       text-amber-700
                                                       hover:bg-amber-100
                                                       transition"
                                            >
                                                Nonaktifkan
                                            </button>


                                        {{-- INACTIVE / REJECTED --}}
                                        @else

                                            <form
                                                method="POST"
                                                action="{{ route('users.activate', $user) }}"
                                            >
                                                @csrf
                                                @method('PATCH')

                                                <button
                                                    type="submit"

                                                    class="rounded-lg
                                                           bg-blue-50
                                                           px-3 py-2
                                                           text-xs
                                                           font-semibold
                                                           text-blue-700
                                                           hover:bg-blue-100
                                                           transition"
                                                >
                                                    Aktifkan
                                                </button>

                                            </form>

                                        @endif

                                    </div>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>


            @if($users->hasPages())

                <div
                    class="px-6 py-4
                           border-t border-slate-100"
                >
                    {{ $users->links() }}
                </div>

            @endif


        @else

            <div class="py-16 text-center">

                <p class="font-semibold text-slate-700">
                    Tidak ada akun yang ditemukan.
                </p>

                <p class="mt-1 text-sm text-slate-400">
                    Coba ubah pencarian atau filter.
                </p>

            </div>

        @endif

    </section>

    @foreach($users as $user)
        @if(! $user->isSuperAdmin() && $user->account_status === 'pending')
            <x-confirmation-modal
                name="reject-user-{{ $user->id }}"
                :action="route('users.reject', $user)"
                variant="danger"
                title="Tolak Permintaan Akun?"
                description="Permintaan akun {{ $user->name }} akan ditandai sebagai ditolak dan pengguna tidak dapat masuk ke BMC QualityHub."
                confirmLabel="Tolak Akun"
            />
        @elseif(! $user->isSuperAdmin() && $user->account_status === 'active')
            <x-confirmation-modal
                name="deactivate-user-{{ $user->id }}"
                :action="route('users.deactivate', $user)"
                variant="warning"
                title="Nonaktifkan Pengguna?"
                description="Pengguna {{ $user->name }} tidak akan dapat masuk ke BMC QualityHub sampai akun diaktifkan kembali."
                confirmLabel="Nonaktifkan"
            />
        @endif
    @endforeach
</x-app-layout>
