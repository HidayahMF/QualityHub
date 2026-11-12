<x-app-layout>
    @vite('resources/js/profile-page.js')

    <div class="qh-profile-page mx-auto max-w-7xl space-y-6 pb-10" data-qh-profile-page>
        @if($user->must_change_password)
            <div role="alert" class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-semibold text-amber-900 shadow-sm">
                <svg class="mt-0.5 h-5 w-5 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m12 3 10 18H2L12 3Z"/><path d="M12 9v4m0 4h.01"/></svg>
                Demi keamanan, password sementara wajib diganti sekarang. Gunakan formulir Keamanan Kata Sandi di bawah.
            </div>
        @endif

        @if(in_array(session('status'), ['profile-photo-updated', 'profile-photo-removed'], true))
            <div role="status" class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800 shadow-sm">
                <svg class="h-5 w-5 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>
                {{ session('status') === 'profile-photo-updated' ? 'Foto profil berhasil diperbarui.' : 'Foto profil berhasil dihapus.' }}
            </div>
        @endif

        {{-- Corporate identity hero --}}
        <section class="relative isolate overflow-hidden rounded-3xl bg-gradient-to-br from-blue-950 via-blue-900 to-blue-700 px-6 py-7 text-white shadow-xl shadow-blue-950/10 sm:px-9 sm:py-9">
            <div class="pointer-events-none absolute -right-20 -top-28 h-80 w-80 rounded-full border border-white/10 bg-white/5" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-32 right-36 h-72 w-72 rounded-full border border-white/10" aria-hidden="true"></div>
            <div class="relative z-10 flex flex-col gap-7 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-semibold tracking-wide text-blue-50">
                        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                        BMC QUALITYHUB / ACCOUNT CENTER
                    </div>
                    <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Profil Saya</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-blue-100 sm:text-base">
                        Kelola informasi akun dan keamanan akses BMC QualityHub.
                    </p>
                    <div class="mt-6 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-lg border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-bold text-white">
                            {{ match($user->role) { 'super_admin' => 'Super Admin', 'admin' => 'Admin Dokumen', 'user' => 'Karyawan', default => ucfirst(str_replace('_', ' ', $user->role)) } }}
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-lg border border-emerald-300/20 bg-emerald-400/15 px-3 py-1.5 text-xs font-semibold text-emerald-100">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
                            {{ $user->isActive() ? 'Aktif' : ucfirst($user->account_status) }}
                        </span>
                    </div>
                </div>

                <div class="flex min-w-0 items-center gap-4 rounded-2xl border border-white/20 bg-white/10 p-4 shadow-lg shadow-blue-950/10 backdrop-blur-sm sm:min-w-[290px]">
                    <div class="flex h-16 w-16 flex-none items-center justify-center overflow-hidden rounded-2xl border-2 border-white/30 bg-white/20 text-2xl font-black text-white shadow-md">
                        @if($user->profile_photo_path)
                            <img src="{{ route('profile.photo.show') }}" alt="Foto profil {{ $user->name }}" class="h-full w-full object-cover">
                        @else
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-lg font-bold text-white">{{ $user->name }}</p>
                        <p class="mt-1 truncate text-sm text-blue-100">{{ $user->email }}</p>
                        <p class="mt-2 text-xs font-medium text-blue-200">Identitas pengguna terverifikasi melalui login</p>
                    </div>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-[minmax(0,0.85fr)_minmax(0,1.4fr)]">
            <aside class="min-w-0 space-y-6">
                {{-- Photo management --}}
                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm shadow-slate-900/5" aria-labelledby="profilePhotoTitle">
                    <div class="border-b border-slate-100 px-6 py-5 sm:px-7">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><rect x="3" y="5" width="18" height="15" rx="3"/><circle cx="8.5" cy="10" r="1.7"/><path d="m3 17 5-4 4 3 3-3 6 5"/></svg>
                            </span>
                            <div>
                                <h2 id="profilePhotoTitle" class="text-lg font-bold text-slate-900">Foto Profil</h2>
                                <p class="text-sm text-slate-500">Personalisasi identitas akun Anda.</p>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-7 sm:px-7">
                        <div class="mb-6 flex flex-col items-center gap-4 text-center">
                            <div class="relative">
                                <div class="flex h-32 w-32 items-center justify-center overflow-hidden rounded-[28px] border-4 border-white bg-gradient-to-br from-blue-700 to-blue-950 text-5xl font-black text-white shadow-xl shadow-blue-900/20">
                                    @if($user->profile_photo_path)
                                        <img id="profilePhotoPreview" src="{{ route('profile.photo.show') }}" alt="Pratinjau foto profil" class="h-full w-full object-cover">
                                    @else
                                        <img id="profilePhotoPreview" src="" alt="Pratinjau foto profil" class="hidden h-full w-full object-cover">
                                        <span data-qh-avatar-fallback>{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                    @endif
                                </div>
                                <span class="absolute -bottom-1 -right-1 flex h-9 w-9 items-center justify-center rounded-xl border-4 border-white bg-blue-600 text-white shadow">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h3l2-3h6l2 3h3v13H4z"/><circle cx="12" cy="13" r="3"/></svg>
                                </span>
                            </div>
                            <div>
                                <p class="font-bold text-slate-900">{{ $user->name }}</p>
                                <p class="mt-1 text-sm text-slate-500">JPG, PNG, atau WEBP · Maksimal 2 MB</p>
                            </div>
                        </div>

                        <form method="post" action="{{ route('profile.photo.store') }}" enctype="multipart/form-data" class="space-y-3">
                            @csrf
                            <label for="profilePhotoInput" class="flex min-h-[44px] cursor-pointer items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-bold text-blue-800 transition hover:border-blue-400 hover:bg-blue-100 focus-within:ring-2 focus-within:ring-blue-500">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 16V3m-5 5 5-5 5 5"/><path d="M4 16v4h16v-4"/></svg>
                                Unggah Foto
                            </label>
                            <input id="profilePhotoInput" name="photo" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" aria-describedby="profilePhotoHint">
                            <p id="profilePhotoHint" data-qh-photo-file-name class="break-all text-center text-xs text-slate-500" aria-live="polite">Pilih gambar untuk melihat pratinjau.</p>
                            @if($errors->profilePhoto->has('photo'))
                                <p class="rounded-lg bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700" role="alert">{{ $errors->profilePhoto->first('photo') }}</p>
                            @endif
                            <button type="submit" class="inline-flex min-h-[44px] w-full items-center justify-center rounded-xl bg-blue-700 px-4 py-3 text-sm font-bold text-white shadow-sm shadow-blue-700/20 transition hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500">
                                Simpan Foto Profil
                            </button>
                        </form>
                        @if($user->profile_photo_path)
                            <form method="post" action="{{ route('profile.photo.destroy') }}" class="mt-3">
                                @csrf
                                @method('delete')
                                <button type="submit" class="inline-flex min-h-[44px] w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700">
                                    Hapus Foto Profil
                                </button>
                            </form>
                        @endif
                    </div>
                </section>

                {{-- Read-only identity information: authority stays with User Management --}}
                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-900/5 sm:p-7" aria-labelledby="accountOverviewTitle">
                    <div class="mb-5 flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="3"/><path d="M8 9h8M8 13h6M8 17h4"/></svg>
                        </span>
                        <div>
                            <h2 id="accountOverviewTitle" class="text-lg font-bold text-slate-900">Ringkasan Akun</h2>
                            <p class="text-sm text-slate-500">Informasi akses yang tercatat.</p>
                        </div>
                    </div>
                    <dl class="space-y-4">
                        <div class="flex flex-wrap items-start justify-between gap-2 border-b border-slate-100 pb-3">
                            <dt class="text-sm text-slate-500">ID Karyawan</dt>
                            <dd class="max-w-full break-all text-right text-sm font-bold text-slate-800">{{ $user->employee_id ?: '—' }}</dd>
                        </div>
                        <div class="flex flex-wrap items-start justify-between gap-2 border-b border-slate-100 pb-3">
                            <dt class="text-sm text-slate-500">Departemen</dt>
                            <dd class="max-w-full text-right text-sm font-bold text-slate-800">{{ $user->department?->name ?: 'Belum ditetapkan' }}</dd>
                        </div>
                        <div class="flex flex-wrap items-start justify-between gap-2 border-b border-slate-100 pb-3">
                            <dt class="text-sm text-slate-500">Hak Akses</dt>
                            <dd class="max-w-full text-right text-sm font-bold text-slate-800">{{ match($user->role) { 'super_admin' => 'Super Admin', 'admin' => 'Admin Dokumen', 'user' => 'Karyawan', default => ucfirst(str_replace('_', ' ', $user->role)) } }}</dd>
                        </div>
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <dt class="text-sm text-slate-500">Status Akun</dt>
                            <dd class="text-sm font-bold {{ $user->isActive() ? 'text-emerald-700' : 'text-amber-700' }}">{{ $user->isActive() ? 'Aktif' : ucfirst($user->account_status) }}</dd>
                        </div>
                    </dl>
                </section>
            </aside>

            <div class="min-w-0 space-y-6">
                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm shadow-slate-900/5">
                    @include('profile.partials.update-profile-information-form')
                </section>
                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm shadow-slate-900/5">
                    @include('profile.partials.update-password-form')
                </section>
                <div class="flex items-start gap-3 rounded-2xl border border-blue-100 bg-blue-50/70 px-5 py-4 text-sm text-blue-900">
                    <svg class="mt-0.5 h-5 w-5 flex-none text-blue-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 11v6m0-10h.01"/></svg>
                    <p>Perubahan hak akses, status, dan departemen dikelola melalui User Management. Gunakan kata sandi pribadi yang kuat dan jangan dibagikan kepada pengguna lain.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
