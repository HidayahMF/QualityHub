<x-guest-layout>

    <div class="mb-8">
        <div class="mb-4 inline-flex items-center gap-2 text-sm font-medium text-blue-700">
            <span class="h-2 w-2 rounded-full bg-blue-600"></span>
            Pemulihan Akun
        </div>

        <h2 class="text-3xl font-bold tracking-tight text-slate-900">
            Forgot Password
        </h2>

        <p class="mt-2 text-sm leading-relaxed text-slate-500">
            Masukkan alamat email akun Anda. BMC QualityHub akan mengirimkan
            tautan untuk mengatur ulang kata sandi jika email terdaftar di sistem.
        </p>
    </div>

    <x-auth-session-status
        class="mb-5 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
        :status="session('status')"
    />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">
                Alamat Email
            </label>

            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="email"
                placeholder="nama@bmc.co.id"
                class="w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500"
            >

            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <button
            type="submit"
            class="w-full rounded-xl bg-blue-800 px-5 py-3.5 font-semibold text-white shadow-lg shadow-blue-900/10 transition hover:-translate-y-0.5 hover:bg-blue-900"
        >
            Kirim Tautan Atur Ulang Kata Sandi
        </button>

        @if(Route::has('login'))
            <p class="pt-2 text-center text-sm text-slate-500">
                Sudah ingat kata sandi?
                <a href="{{ route('login') }}" class="font-semibold text-blue-700 hover:text-blue-900">
                    Kembali ke Login
                </a>
            </p>
        @endif
    </form>

    <div class="mt-8 border-t border-slate-100 pt-6 text-center text-xs text-slate-400">
        Pemulihan akun internal BMC QualityHub yang dilindungi
    </div>

</x-guest-layout>
