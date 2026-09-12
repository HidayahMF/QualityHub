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
                    âœ“
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
                âœ•
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
                    ðŸ”Ž
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
                    value="admin"
                    @selected(request('role') === 'admin')
                >
                    Admin
                </option>

                <option
                    value="user"
                    @selected(request('role') === 'user')
                >
                    Pengguna
                </option>

                <option
                    value="viewer"
                    @selected(request('role') === 'viewer')
                >
                    Peninjau
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
                                                Â·
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
                                            'admin' => 'Admin',
                                            'user' => 'Pengguna',
                                            'viewer' => 'Peninjau',
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


                                            <form
                                                method="POST"
                                                action="{{ route('users.reject', $user) }}"

                                                x-on:submit.prevent="$dispatch('qualityhub-confirm', {
                                                    form: $event.currentTarget,
                                                    title: 'Tolak Permintaan Akun?',
                                                    message: 'Permintaan akun ini akan ditandai sebagai ditolak dan pengguna tidak dapat masuk ke BMC QualityHub.',
                                                    confirmText: 'Tolak Akun'
                                                })"
                                            >
                                                @csrf
                                                @method('PATCH')

                                                <button
                                                    type="submit"

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

                                            </form>


                                        {{-- ACTIVE --}}
                                        @elseif($user->account_status === 'active')

                                            <form
                                                method="POST"
                                                action="{{ route('users.deactivate', $user) }}"

                                                x-on:submit.prevent="$dispatch('qualityhub-confirm', {
                                                    form: $event.currentTarget,
                                                    title: 'Nonaktifkan Pengguna?',
                                                    message: 'Pengguna tidak akan dapat masuk ke BMC QualityHub sampai akun diaktifkan kembali.',
                                                    confirmText: 'Nonaktifkan'
                                                })"
                                            >
                                                @csrf
                                                @method('PATCH')

                                                <button
                                                    type="submit"

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

                                            </form>


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

    {{-- ========================================================= --}}
    {{-- BMC QUALITYHUB CUSTOM CONFIRMATION MODAL --}}
    {{-- Menggantikan native browser confirm() dengan modal biru-putih --}}
    {{-- ========================================================= --}}

    <style nonce="{{ $cspNonce }}">
        [x-cloak] { display: none !important; }
    </style>

    <div
        x-data="{
            open: false,
            form: null,
            title: 'Konfirmasi Tindakan',
            message: 'Pastikan tindakan ini memang ingin dilanjutkan.',
            confirmText: 'Lanjutkan'
        }"
        x-on:qualityhub-confirm.window="
            form = $event.detail.form;
            title = $event.detail.title || 'Konfirmasi Tindakan';
            message = $event.detail.message || 'Pastikan tindakan ini memang ingin dilanjutkan.';
            confirmText = $event.detail.confirmText || 'Lanjutkan';
            open = true;
        "
        x-on:keydown.escape.window="
            open = false;
            form = null;
        "
        x-show="open"
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="qualityhub-confirm-title"
    >
        <div
            x-show="open"
            x-transition.opacity
            class="absolute inset-0 bg-slate-950/45 backdrop-blur-[2px]"
            x-on:click="
                open = false;
                form = null;
            "
        ></div>

        <section
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-2"
            class="relative w-full max-w-md overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-2xl shadow-blue-950/20"
        >
            <div class="h-1.5 w-full bg-blue-800"></div>

            <div class="p-6 sm:p-7">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-700 ring-1 ring-blue-100">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="22"
                            height="22"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/>
                            <path d="M9 12l2 2 4-4"/>
                        </svg>
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">
                            BMC QualityHub
                        </p>

                        <h3
                            id="qualityhub-confirm-title"
                            class="mt-1.5 text-xl font-bold text-slate-900"
                            x-text="title"
                        ></h3>

                        <p
                            class="mt-2 text-sm leading-6 text-slate-600"
                            x-text="message"
                        ></p>
                    </div>
                </div>

                <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2"
                        x-on:click="
                            open = false;
                            form = null;
                        "
                    >
                        Batal
                    </button>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl bg-blue-800 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-900/15 transition hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        x-text="confirmText"
                        x-on:click="
                            const target = form;
                            open = false;
                            form = null;

                            if (target) {
                                target.submit();
                            }
                        "
                    ></button>
                </div>
            </div>
        </section>
    </div>

</x-app-layout>
