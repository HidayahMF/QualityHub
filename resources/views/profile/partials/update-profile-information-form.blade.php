<section class="qh-profile-section">
    <header class="flex items-center gap-2.5 border-b border-slate-100 px-5 py-3.5 sm:px-6">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700" aria-hidden="true">
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                <circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>
            </svg>
        </span>
        <h2 class="!text-[18px] font-extrabold tracking-tight text-slate-900">Informasi Akun</h2>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>

    <form method="post" action="{{ route('profile.update') }}" class="px-5 py-4 sm:px-6">
        @csrf
        @method('patch')
        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
            <div class="min-w-0">
                <x-input-label for="name" :value="__('Nama')" class="mb-1.5 text-xs font-semibold text-slate-700" />
                <x-text-input id="name" name="name" type="text"
                    class="block min-h-[42px] w-full rounded-xl border-slate-300 bg-slate-50 text-slate-900 focus:border-blue-600 focus:ring-blue-600"
                    :value="old('name', $user->name)" required autocomplete="name" />
                <x-input-error class="mt-1.5" :messages="$errors->get('name')" />
            </div>
            <div class="min-w-0">
                <x-input-label for="email" :value="__('Email')" class="mb-1.5 text-xs font-semibold text-slate-700" />
                <x-text-input id="email" name="email" type="email"
                    class="block min-h-[42px] w-full rounded-xl border-slate-300 bg-slate-50 text-slate-900 focus:border-blue-600 focus:ring-blue-600"
                    :value="old('email', $user->email)" required autocomplete="username" />
                <x-input-error class="mt-1.5" :messages="$errors->get('email')" />
                @if($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div class="mt-2 text-xs text-amber-700">
                        Email belum diverifikasi.
                        <button form="send-verification" class="font-bold underline underline-offset-2">Kirim verifikasi</button>
                        @if(session('status') === 'verification-link-sent')
                            <span role="status" class="mt-1 block text-emerald-700">Tautan dikirim.</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-3 grid grid-cols-1 gap-3 border-t border-slate-100 pt-3 sm:grid-cols-2">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-slate-500">ID Karyawan</p>
                <p class="mt-0.5 break-all text-xs font-bold text-slate-800">{{ $user->employee_id ?: '—' }}</p>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-slate-500">Departemen</p>
                <p class="mt-0.5 break-words text-xs font-bold text-slate-800">{{ $user->department?->name ?: '—' }}</p>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-3">
            <button type="submit" class="min-h-[42px] rounded-xl bg-blue-700 px-5 text-xs font-bold tracking-wide text-white shadow-sm transition hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700">
                Simpan Perubahan
            </button>
            @if(session('status') === 'profile-updated')
                <span role="status" class="text-xs font-bold text-emerald-700">Perubahan tersimpan.</span>
            @endif
        </div>
    </form>
</section>
