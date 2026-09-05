<x-guest-layout>

    <div class="mb-8">
        <div class="mb-4 inline-flex items-center gap-2 text-sm font-medium text-blue-700">
            <span class="h-2 w-2 rounded-full bg-blue-600"></span>
            Keamanan Akun
        </div>

        <h2 class="text-3xl font-bold tracking-tight text-slate-900">
            Reset Password
        </h2>

        <p class="mt-2 text-sm leading-relaxed text-slate-500">
            Buat kata sandi baru untuk akun BMC QualityHub Anda.
            Gunakan kata sandi yang kuat dan tidak mudah ditebak.
        </p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ request()->route('token') }}">

        <div>
            <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">
                Alamat Email
            </label>

            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email', request('email')) }}"
                required
                autofocus
                autocomplete="username"
                placeholder="nama@bmc.co.id"
                class="w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500"
            >

            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">
                Kata Sandi Baru
            </label>

            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                placeholder="Masukkan kata sandi baru"
                class="w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500"
            >

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-slate-700">
                Konfirmasi Kata Sandi Baru
            </label>

            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="Ulangi kata sandi baru"
                class="w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500"
            >

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <button
            type="submit"
            class="w-full rounded-xl bg-blue-800 px-5 py-3.5 font-semibold text-white shadow-lg shadow-blue-900/10 transition hover:-translate-y-0.5 hover:bg-blue-900"
        >
            Simpan Kata Sandi Baru
        </button>
    </form>

    <div class="mt-8 border-t border-slate-100 pt-6 text-center text-xs text-slate-400">
        Pengaturan ulang kata sandi BMC QualityHub yang aman
    </div>

</x-guest-layout>
