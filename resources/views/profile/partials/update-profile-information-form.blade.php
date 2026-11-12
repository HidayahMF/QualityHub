<section class="qh-profile-section">
    <header class="flex items-start gap-3 border-b border-slate-100 px-6 py-6 sm:px-8">
        <span class="flex h-11 w-11 flex-none items-center justify-center rounded-xl bg-blue-50 text-blue-700">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg>
        </span>
        <div class="min-w-0">
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900">Informasi Akun</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">Perbarui nama dan email yang digunakan untuk akun QualityHub.</p>
        </div>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-6 px-6 py-7 sm:px-8">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Nama Lengkap')" class="mb-2 text-sm font-semibold text-slate-700" />
            <x-text-input id="name" name="name" type="text" class="block min-h-[46px] w-full rounded-xl border-slate-300 bg-slate-50 text-slate-900 focus:border-blue-600 focus:ring-blue-600"
                :value="old('name', $user->name)" required autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Alamat Email')" class="mb-2 text-sm font-semibold text-slate-700" />
            <x-text-input id="email" name="email" type="email" class="block min-h-[46px] w-full rounded-xl border-slate-300 bg-slate-50 text-slate-900 focus:border-blue-600 focus:ring-blue-600"
                :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Alamat email Anda belum diverifikasi.
                    <button form="send-verification" class="ml-1 font-bold underline underline-offset-2">Kirim ulang verifikasi</button>
                    @if(session('status') === 'verification-link-sent')
                        <p class="mt-2 font-semibold text-emerald-700">Tautan verifikasi telah dikirim.</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-4 border-t border-slate-100 pt-6">
            <x-primary-button class="min-h-[44px] rounded-xl bg-blue-700 px-6 py-3 shadow-sm hover:bg-blue-800">Simpan Perubahan</x-primary-button>
            @if(session('status') === 'profile-updated')
                <span class="inline-flex items-center gap-2 text-sm font-bold text-emerald-700" role="status">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                    Perubahan tersimpan.
                </span>
            @endif
        </div>
    </form>
</section>
