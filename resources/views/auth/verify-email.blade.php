<x-guest-layout>

    <div class="mb-8">
        <div class="mb-4 inline-flex items-center gap-2 text-sm font-medium text-blue-700">
            <span class="h-2 w-2 rounded-full bg-blue-600"></span>
            Verifikasi Akun
        </div>

        <h2 class="text-3xl font-bold tracking-tight text-slate-900">
            Verify Email
        </h2>

        <p class="mt-2 text-sm leading-relaxed text-slate-500">
            Terima kasih telah mendaftar di BMC QualityHub. Sebelum melanjutkan,
            verifikasi alamat email Anda melalui tautan yang telah kami kirimkan.
        </p>
    </div>

    @if(session('status') === 'verification-link-sent')
        <div
            class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
        >
            Tautan verifikasi baru telah dikirim ke alamat email yang Anda gunakan saat pendaftaran.
        </div>
    @endif

    <div class="rounded-xl border border-blue-100 bg-blue-50 p-4">
        <p class="text-sm font-semibold text-blue-900">
            Belum menerima email verifikasi?
        </p>

        <p class="mt-1 text-xs leading-relaxed text-blue-700">
            Periksa kembali kotak masuk atau folder spam. Anda juga dapat mengirim ulang tautan verifikasi.
        </p>
    </div>

    <div class="mt-6 space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <button
                type="submit"
                class="w-full rounded-xl bg-blue-800 px-5 py-3.5 font-semibold text-white shadow-lg shadow-blue-900/10 transition hover:-translate-y-0.5 hover:bg-blue-900"
            >
                Kirim Ulang Email Verifikasi
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button
                type="submit"
                class="w-full rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
            >
                Keluar dari Akun
            </button>
        </form>
    </div>

    <div class="mt-8 border-t border-slate-100 pt-6 text-center text-xs text-slate-400">
        Verifikasi email internal BMC QualityHub yang aman
    </div>

</x-guest-layout>
