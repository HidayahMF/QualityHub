<x-app-layout>
    @vite('resources/js/profile-page.js')

    {{-- My Profile · compact enterprise layout, blue/white BMC --}}
    <div class="qh-profile-page mx-auto max-w-[1160px] pb-7" data-qh-profile-page>
        <header class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <div class="mb-1 flex items-center gap-2">
                    <span class="h-4 w-1 rounded-full bg-blue-700" aria-hidden="true"></span>
                    <span class="text-[11px] font-bold uppercase tracking-[0.16em] text-blue-700">My Profile</span>
                </div>
                <h1 class="text-[27px] font-extrabold leading-tight tracking-tight text-slate-900">Profil Saya</h1>
            </div>

            @if(in_array(session('status'), ['profile-photo-updated', 'profile-photo-removed'], true))
                <p role="status" class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-800">
                    <svg class="h-4 w-4 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>
                    </svg>
                    {{ session('status') === 'profile-photo-updated' ? 'Foto profil diperbarui.' : 'Foto profil dihapus.' }}
                </p>
            @endif
        </header>

        @if($user->must_change_password)
            <div role="alert" class="mb-5 flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900">
                <svg class="h-4 w-4 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="m12 3 10 18H2L12 3Z"/><path d="M12 9v4m0 4h.01"/>
                </svg>
                Kata sandi sementara wajib diganti sebelum melanjutkan.
            </div>
        @endif

        <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-[minmax(250px,0.8fr)_minmax(0,1.7fr)]">
            {{-- Identity and photo: a single compact card --}}
            <aside class="min-w-0">
                <section class="overflow-hidden rounded-[22px] border border-slate-200 bg-white shadow-sm shadow-slate-900/5" aria-labelledby="profilePhotoTitle">
                    <div class="h-1 bg-gradient-to-r from-blue-900 via-blue-700 to-blue-500" aria-hidden="true"></div>

                    <div class="px-5 pb-5 pt-6 sm:px-6">
                        <div class="flex flex-col items-center text-center">
                            <div class="relative">
                                <div class="flex h-[104px] w-[104px] items-center justify-center overflow-hidden rounded-full border-4 border-white bg-gradient-to-br from-blue-700 to-blue-950 text-4xl font-extrabold text-white shadow-lg ring-2 ring-blue-100">
                                    @if($user->profile_photo_path)
                                        <img id="profilePhotoPreview" src="{{ route('profile.photo.show') }}" alt="Foto profil {{ $user->name }}" class="h-full w-full object-cover">
                                    @else
                                        <img id="profilePhotoPreview" alt="Pratinjau foto profil" class="hidden h-full w-full object-cover">
                                        <span data-qh-avatar-fallback>{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                    @endif
                                </div>
                                <span class="absolute bottom-0 right-0 flex h-8 w-8 items-center justify-center rounded-full border-[3px] border-white bg-blue-700 text-white shadow-sm" aria-hidden="true">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M4 7h3l2-3h6l2 3h3v13H4z"/><circle cx="12" cy="13" r="3"/>
                                    </svg>
                                </span>
                            </div>

                            <h2 class="mt-4 max-w-full truncate !text-[19px] font-extrabold text-slate-900">{{ $user->name }}</h2>
                            <p class="mt-0.5 max-w-full break-all text-[13px] text-slate-500">{{ $user->email }}</p>

                            <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
                                <span class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-[11px] font-bold text-blue-800">
                                    {{ match($user->role) { 'super_admin' => 'Super Admin', 'admin' => 'Admin Dokumen', 'user' => 'Karyawan', default => ucfirst(str_replace('_', ' ', $user->role)) } }}
                                </span>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-bold text-emerald-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                                    {{ $user->isActive() ? 'Aktif' : ucfirst($user->account_status) }}
                                </span>
                            </div>
                        </div>

                        <div class="my-5 h-px bg-slate-100" aria-hidden="true"></div>

                        <h3 id="profilePhotoTitle" class="mb-3 !text-sm font-bold text-slate-900">Foto Profil</h3>
                        <form method="post" action="{{ route('profile.photo.store') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="grid grid-cols-2 gap-2">
                                <label class="relative flex min-h-[42px] cursor-pointer items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-2 text-[13px] font-bold text-blue-800 transition hover:bg-blue-100 focus-within:ring-2 focus-within:ring-blue-600 focus-within:ring-offset-1">
                                    <svg class="h-4 w-4 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M12 16V3m-5 5 5-5 5 5"/><path d="M4 16v4h16v-4"/>
                                    </svg>
                                    Unggah Foto
                                    <input id="profilePhotoInput" name="photo" type="file" accept="image/jpeg,image/png,image/webp"
                                           class="sr-only" aria-describedby="profilePhotoHint">
                                </label>
                                <button type="submit" class="min-h-[42px] rounded-xl bg-blue-700 px-3 text-[13px] font-bold text-white shadow-sm transition hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700">
                                    Simpan Foto
                                </button>
                            </div>

                            <p id="profilePhotoHint" data-qh-photo-file-name class="mt-2 break-all text-center text-[11px] text-slate-500" aria-live="polite">JPG, PNG, WEBP · Maks. 2 MB</p>

                            @if($errors->profilePhoto->has('photo'))
                                <p role="alert" class="mt-2 rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700">
                                    {{ $errors->profilePhoto->first('photo') }}
                                </p>
                            @endif
                        </form>

                        @if($user->profile_photo_path)
                            <form method="post" action="{{ route('profile.photo.destroy') }}" class="mt-1 text-center">
                                @csrf
                                @method('delete')
                                <button type="submit" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-500 transition hover:bg-rose-50 hover:text-rose-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600">
                                    Hapus Foto
                                </button>
                            </form>
                        @endif

                        {{-- Access metadata stays read-only and is not duplicated elsewhere. --}}
                        <dl class="mt-4 space-y-2 border-t border-slate-100 pt-4">
                            <div class="flex items-start justify-between gap-3">
                                <dt class="shrink-0 text-xs text-slate-500">ID Karyawan</dt>
                                <dd class="min-w-0 break-all text-right text-xs font-semibold text-slate-800">{{ $user->employee_id ?: '—' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt class="shrink-0 text-xs text-slate-500">Departemen</dt>
                                <dd class="min-w-0 text-right text-xs font-semibold text-slate-800">{{ $user->department?->name ?: '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                </section>
            </aside>

            {{-- Forms: no duplicated profile summary, no tall hero. --}}
            <div class="min-w-0 space-y-5">
                <section class="overflow-hidden rounded-[22px] border border-slate-200 bg-white shadow-sm shadow-slate-900/5">
                    @include('profile.partials.update-profile-information-form')
                </section>
                <section class="overflow-hidden rounded-[22px] border border-slate-200 bg-white shadow-sm shadow-slate-900/5">
                    @include('profile.partials.update-password-form')
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
