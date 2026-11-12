<section class="qh-profile-section">
    <header class="flex items-start gap-3 border-b border-slate-100 px-6 py-6 sm:px-8">
        <span class="flex h-11 w-11 flex-none items-center justify-center rounded-xl bg-blue-50 text-blue-700">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </span>
        <div class="min-w-0">
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900">Keamanan Kata Sandi</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">Lindungi akun Anda dengan kata sandi pribadi yang kuat.</p>
        </div>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="space-y-5 px-6 py-7 sm:px-8">
        @csrf
        @method('put')

        @foreach([
            ['update_password_current_password', 'current_password', 'Kata Sandi Saat Ini', 'current-password'],
            ['update_password_password', 'password', 'Kata Sandi Baru', 'new-password'],
            ['update_password_password_confirmation', 'password_confirmation', 'Konfirmasi Kata Sandi', 'new-password']
        ] as [$id, $field, $label, $autocomplete])
            <div>
                <label for="{{ $id }}" class="mb-2 block text-sm font-bold text-slate-700">{{ $label }}</label>
                <div class="relative">
                    <input id="{{ $id }}" name="{{ $field }}" type="password" required autocomplete="{{ $autocomplete }}"
                        @if($field === 'password') aria-describedby="profilePasswordStrength" @endif
                        class="block min-h-[46px] w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 pr-24 text-sm text-slate-900 shadow-sm transition focus:border-blue-600 focus:ring-blue-600">
                    <button type="button" data-qh-password-toggle="{{ $id }}" aria-controls="{{ $id }}" aria-pressed="false"
                        class="absolute inset-y-1 right-1 rounded-lg px-3 text-xs font-bold text-blue-800 transition hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600">Lihat</button>
                </div>
                <x-input-error :messages="$errors->updatePassword->get($field)" class="mt-2" />
                @if($field === 'password')
                    <div id="profilePasswordStrength" class="mt-3 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs font-medium text-slate-600">Indikator kekuatan kata sandi</span>
                            <span data-qh-password-strength-label class="text-xs font-bold text-slate-600" aria-live="polite">Belum diisi</span>
                        </div>
                        <div class="mt-2 grid grid-cols-4 gap-1.5" aria-hidden="true">
                            @for($i = 0; $i < 4; $i++)
                                <span data-qh-password-strength-bar class="h-1.5 rounded-full bg-slate-200"></span>
                            @endfor
                        </div>
                        <p class="mt-2 text-xs leading-5 text-slate-500">Gunakan kata sandi panjang dan unik dengan kombinasi karakter yang beragam.</p>
                    </div>
                @endif
            </div>
        @endforeach

        <div class="flex flex-wrap items-center gap-4 border-t border-slate-100 pt-6">
            <x-primary-button class="min-h-[44px] rounded-xl bg-blue-700 px-6 py-3 shadow-sm hover:bg-blue-800">Perbarui Kata Sandi</x-primary-button>
            @if(session('status') === 'password-updated')
                <span class="text-sm font-bold text-emerald-700" role="status">Kata sandi berhasil diperbarui.</span>
            @endif
        </div>
    </form>
</section>
