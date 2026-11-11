<x-guest-layout>
    <div class="mb-8">
        <div class="mb-4 inline-flex items-center gap-2 text-sm font-medium text-blue-700">
            <span class="h-2 w-2 rounded-full bg-blue-600"></span>
            Pemulihan Akun
        </div>

        <h2 class="text-3xl font-bold tracking-tight text-slate-900">
            Lupa Password?
        </h2>

        <p class="mt-3 text-sm leading-relaxed text-slate-600">
            Untuk keamanan sistem BMC QualityHub, pengaturan ulang password
            dilakukan langsung oleh Super Admin. Tidak diperlukan email atau SMTP.
        </p>

        <div class="mt-6 rounded-xl border border-blue-100 bg-blue-50 px-5 py-4 text-sm text-blue-900">
            Hubungi Super Admin BMC QualityHub dan sampaikan identitas karyawan
            Anda. Setelah diverifikasi, Super Admin akan memberikan password
            sementara yang wajib Anda ganti saat login berikutnya.
        </div>

        <a href="{{ route('login') }}"
           class="mt-6 inline-flex w-full items-center justify-center rounded-xl bg-blue-800 px-5 py-3.5 font-semibold text-white hover:bg-blue-900">
            Kembali ke Login
        </a>
    </div>
</x-guest-layout>
