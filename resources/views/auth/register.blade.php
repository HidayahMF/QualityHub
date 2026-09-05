<x-guest-layout>

    {{-- HEADER --}}
    <div class="mb-8">

        <div class="inline-flex items-center gap-2
                    text-blue-700 text-sm font-medium mb-4">

            <span class="w-2 h-2 rounded-full bg-blue-600"></span>

            Pendaftaran Akun Karyawan

        </div>

        <h2 class="text-3xl font-bold tracking-tight text-slate-900">
            Ajukan akun
        </h2>

        <p class="mt-2 text-sm text-slate-500 leading-relaxed">
            Daftarkan akun karyawan Anda untuk mengakses
            BMC QualityHub.
        </p>

    </div>


    <form
        method="POST"
        action="{{ route('register') }}"
        class="space-y-5">

        @csrf


        {{-- EMPLOYEE ID --}}
        <div>

            <label
                for="employee_id"
                class="block text-sm font-semibold text-slate-700 mb-2">

                ID Karyawan

            </label>

            <input
                id="employee_id"
                type="text"
                name="employee_id"
                value="{{ old('employee_id') }}"
                required
                autofocus

                placeholder="Contoh: BMC001"

                class="w-full rounded-xl
                       border-slate-200
                       bg-slate-50
                       px-4 py-3
                       text-slate-900
                       placeholder:text-slate-400
                       focus:border-blue-500
                       focus:ring-blue-500
                       transition duration-200"
            >

            <x-input-error
                :messages="$errors->get('employee_id')"
                class="mt-2"
            />

        </div>


        {{-- FULL NAME --}}
        <div>

            <label
                for="name"
                class="block text-sm font-semibold text-slate-700 mb-2">

                Nama Lengkap

            </label>

            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name') }}"
                required

                placeholder="Masukkan nama lengkap Anda"

                class="w-full rounded-xl
                       border-slate-200
                       bg-slate-50
                       px-4 py-3
                       text-slate-900
                       placeholder:text-slate-400
                       focus:border-blue-500
                       focus:ring-blue-500
                       transition duration-200"
            >

            <x-input-error
                :messages="$errors->get('name')"
                class="mt-2"
            />

        </div>


        {{-- EMAIL --}}
        <div>

            <label
                for="email"
                class="block text-sm font-semibold text-slate-700 mb-2">

                Alamat Email

            </label>

            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required

                placeholder="name@bmc.co.id"

                class="w-full rounded-xl
                       border-slate-200
                       bg-slate-50
                       px-4 py-3
                       text-slate-900
                       placeholder:text-slate-400
                       focus:border-blue-500
                       focus:ring-blue-500
                       transition duration-200"
            >

            <x-input-error
                :messages="$errors->get('email')"
                class="mt-2"
            />

        </div>


        {{-- DEPARTMENT --}}
        <div>

            <label
                for="department_id"
                class="block text-sm font-semibold text-slate-700 mb-2">

                Departemen

            </label>

            <select
                id="department_id"
                name="department_id"
                required

                class="w-full rounded-xl
                       border-slate-200
                       bg-slate-50
                       px-4 py-3
                       text-slate-900
                       focus:border-blue-500
                       focus:ring-blue-500
                       transition duration-200">

                <option value="">
                    Select Departemen
                </option>

                @foreach ($departments as $department)

                    <option
                        value="{{ $department->id }}"
                        @selected(old('department_id') == $department->id)>

                        {{ $department->name }}

                    </option>

                @endforeach

            </select>

            <x-input-error
                :messages="$errors->get('department_id')"
                class="mt-2"
            />

        </div>


        {{-- PASSWORD --}}
        <div>

            <label
                for="password"
                class="block text-sm font-semibold text-slate-700 mb-2">

                Kata Sandi

            </label>

            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="new-password"

                placeholder="Buat kata sandi yang aman"

                class="w-full rounded-xl
                       border-slate-200
                       bg-slate-50
                       px-4 py-3
                       text-slate-900
                       placeholder:text-slate-400
                       focus:border-blue-500
                       focus:ring-blue-500
                       transition duration-200"
            >

            <x-input-error
                :messages="$errors->get('password')"
                class="mt-2"
            />

        </div>


        {{-- CONFIRM PASSWORD --}}
        <div>

            <label
                for="password_confirmation"
                class="block text-sm font-semibold text-slate-700 mb-2">

                Konfirmasi Kata Sandi

            </label>

            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"

                placeholder="Ulangi kata sandi Anda"

                class="w-full rounded-xl
                       border-slate-200
                       bg-slate-50
                       px-4 py-3
                       text-slate-900
                       placeholder:text-slate-400
                       focus:border-blue-500
                       focus:ring-blue-500
                       transition duration-200"
            >

            <x-input-error
                :messages="$errors->get('password_confirmation')"
                class="mt-2"
            />

        </div>


        {{-- INFORMATION --}}
        <div class="rounded-xl
                    border border-blue-100
                    bg-blue-50
                    p-4">

            <div class="flex gap-3">

                <div class="mt-0.5 text-blue-700">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2">

                        <circle cx="12" cy="12" r="10"/>

                        <path d="M12 16v-4"/>

                        <path d="M12 8h.01"/>

                    </svg>

                </div>

                <div>

                    <p class="text-sm font-semibold text-blue-900">
                        Persetujuan Akun Diperlukan
                    </p>

                    <p class="text-xs text-blue-700 mt-1 leading-relaxed">
                        Akun yang baru didaftarkan harus disetujui
                        oleh administrator BMC QualityHub
                        sebelum dapat mengakses sistem.
                    </p>

                </div>

            </div>

        </div>


        {{-- REGISTER BUTTON --}}
        <button
            type="submit"

            class="w-full rounded-xl
                   bg-blue-800
                   px-5 py-3.5
                   font-semibold text-white
                   shadow-lg shadow-blue-900/10
                   transition duration-300
                   hover:bg-blue-900
                   hover:-translate-y-0.5
                   active:translate-y-0">

            Ajukan Akun

        </button>


        {{-- LOGIN --}}
        <p class="text-center text-sm text-slate-500 pt-2">

            Sudah memiliki akun?

            <a
                href="{{ route('login') }}"
                class="font-semibold
                       text-blue-700
                       hover:text-blue-900">

                Masuk

            </a>

        </p>

    </form>


    {{-- SECURITY --}}
    <div class="mt-8 pt-6 border-t border-slate-100">

        <div class="flex items-center justify-center gap-2
                    text-xs text-slate-400">

            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="14"
                height="14"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2">

                <rect
                    width="18"
                    height="11"
                    x="3"
                    y="11"
                    rx="2"
                    ry="2"
                />

                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>

            </svg>

            Pendaftaran akun internal BMC yang aman

        </div>

    </div>

</x-guest-layout>