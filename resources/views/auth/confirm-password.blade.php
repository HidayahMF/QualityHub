<x-guest-layout>

    <div class="mb-8">
        <div class="mb-4 inline-flex items-center gap-2 text-sm font-medium text-blue-700">
            <span class="h-2 w-2 rounded-full bg-blue-600"></span>
            Verifikasi Keamanan
        </div>

        <h2 class="text-3xl font-bold tracking-tight text-slate-900">
            Confirm Password
        </h2>

        <p class="mt-2 text-sm leading-relaxed text-slate-500">
            Halaman ini merupakan area yang dilindungi. Konfirmasikan kata sandi
            akun Anda sebelum melanjutkan tindakan sensitif di BMC QualityHub.
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">
                Kata Sandi
            </label>

            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Masukkan kata sandi Anda"
                class="w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500"
            >

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <button
            type="submit"
            class="w-full rounded-xl bg-blue-800 px-5 py-3.5 font-semibold text-white shadow-lg shadow-blue-900/10 transition hover:-translate-y-0.5 hover:bg-blue-900"
        >
            Konfirmasi Kata Sandi
        </button>
    </form>

    <div class="mt-8 border-t border-slate-100 pt-6 text-center text-xs text-slate-400">
        Verifikasi sesi internal BMC QualityHub
    </div>

</x-guest-layout>
