<section class="qh-profile-section">
    <header class="flex items-center gap-2.5 border-b border-slate-100 px-5 py-3.5 sm:px-6">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700" aria-hidden="true">
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                <rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
        </span>
        <h2 class="!text-[18px] font-extrabold tracking-tight text-slate-900">Keamanan Kata Sandi</h2>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="px-5 py-4 sm:px-6">
        @csrf
        @method('put')

        <div class="grid grid-cols-1 gap-3.5 md:grid-cols-3">
            @foreach([
                ['update_password_current_password', 'current_password', 'Kata Sandi Saat Ini', 'current-password'],
                ['update_password_password', 'password', 'Kata Sandi Baru', 'new-password'],
                ['update_password_password_confirmation', 'password_confirmation', 'Konfirmasi Kata Sandi', 'new-password']
            ] as [$id, $field, $label, $autocomplete])
                <div class="min-w-0">
                    <label for="{{ $id }}" class="mb-1.5 block text-xs font-bold text-slate-700">{{ $label }}</label>
                    <div class="relative">
                        <input id="{{ $id }}" name="{{ $field }}" type="password" required autocomplete="{{ $autocomplete }}"
                            @if($field === 'password') aria-describedby="profilePasswordStrength" @endif
                            class="block min-h-[42px] w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 pr-[76px] text-sm text-slate-900 shadow-sm transition focus:border-blue-600 focus:ring-blue-600">
                        <button type="button" data-qh-password-toggle="{{ $id }}" aria-controls="{{ $id }}" aria-pressed="false"
                            class="absolute inset-y-1 right-1 rounded-lg px-2.5 text-xs font-bold text-blue-700 transition hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600">Lihat</button>
                    </div>
                    <x-input-error :messages="$errors->updatePassword->get($field)" class="mt-1.5" />
                    @if($field === 'password')
                        <div id="profilePasswordStrength" class="mt-2 flex flex-wrap items-center gap-2.5">
                            <span class="text-[11px] text-slate-500">Kekuatan</span>
                            <div class="flex h-1 w-24 gap-1" aria-hidden="true">
                                @for($i = 0; $i < 4; $i++)
                                    <span data-qh-password-strength-bar class="h-1 flex-1 rounded-full bg-slate-200"></span>
                                @endfor
                            </div>
                            <span data-qh-password-strength-label aria-live="polite" class="text-[11px] font-bold text-slate-600">Belum diisi</span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-3">
            <button type="submit" class="min-h-[42px] rounded-xl bg-blue-700 px-5 text-xs font-bold tracking-wide text-white shadow-sm transition hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700">
                Perbarui Kata Sandi
            </button>
            @if(session('status') === 'password-updated')
                <span role="status" class="text-xs font-bold text-emerald-700">Kata sandi diperbarui.</span>
            @endif
        </div>
    </form>
</section>
