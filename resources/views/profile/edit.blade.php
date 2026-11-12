<x-app-layout>
    @vite('resources/js/profile-page.js')

    {{-- Compact BMC profile: one balanced account workspace + full-width security. --}}
    <div class="qh-profile-page mx-auto max-w-[1160px] pb-5" data-qh-profile-page data-qh-compact-profile>
        <header class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-blue-700">My Profile</p>
                <h1 class="mt-0.5 !text-[26px] font-extrabold tracking-tight text-slate-900">Profil Saya</h1>
            </div>
            @if(in_array(session('status'), ['profile-photo-updated', 'profile-photo-removed'], true))
                <p role="status" class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-800">
                    <svg class="h-4 w-4 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>
                    </svg>
                    {{ session('status') === 'profile-photo-updated' ? 'Foto profil diperbarui.' : 'Foto profil dihapus.' }}
                </p>
            @endif
        </header>

        @if($user->must_change_password)
            <div role="alert" class="mb-4 flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900">
                <svg class="h-4 w-4 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="m12 3 10 18H2L12 3Z"/><path d="M12 9v4m0 4h.01"/>
                </svg>
                Kata sandi sementara wajib diganti sebelum melanjutkan.
            </div>
        @endif

        <div class="overflow-hidden rounded-[20px] border border-slate-200 bg-white shadow-sm shadow-slate-900/5" data-qh-profile-main>
            <div class="grid grid-cols-1 lg:grid-cols-[minmax(270px,0.85fr)_minmax(0,1.55fr)]">
                {{-- Identity and photo controls; no standalone tall sidebar card. --}}
                <section class="relative flex min-w-0 flex-col justify-center border-b border-slate-200 bg-gradient-to-br from-blue-50/80 via-white to-white px-5 py-5 sm:px-6 lg:border-b-0 lg:border-r lg:border-slate-200" aria-labelledby="profilePhotoTitle">
                    <div class="flex min-w-0 items-center gap-4">
                        <div class="relative shrink-0">
                            <div class="flex h-[92px] w-[92px] items-center justify-center overflow-hidden rounded-full border-[3px] border-white bg-gradient-to-br from-blue-700 to-blue-950 text-3xl font-extrabold text-white shadow-md ring-1 ring-blue-200">
                                @if($user->profile_photo_path)
                                    <img id="profilePhotoPreview" src="{{ route('profile.photo.show') }}" alt="Foto profil {{ $user->name }}" class="h-full w-full object-cover">
                                @else
                                    <img id="profilePhotoPreview" alt="Pratinjau foto profil" class="hidden h-full w-full object-cover">
                                    <span data-qh-avatar-fallback>{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                @endif
                            </div>
                            <span class="absolute bottom-0 right-0 flex h-7 w-7 items-center justify-center rounded-full border-[3px] border-white bg-blue-700 text-white shadow-sm" aria-hidden="true">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 7h3l2-3h6l2 3h3v13H4z"/><circle cx="12" cy="13" r="3"/>
                                </svg>
                            </span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h2 class="break-words !text-[19px] font-extrabold leading-snug text-slate-900">{{ $user->name }}</h2>
                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                <span class="rounded-full border border-blue-200 bg-blue-100/70 px-2.5 py-1 text-[11px] font-bold text-blue-800">
                                    {{ match($user->role) { 'super_admin' => 'Super Admin', 'admin' => 'Admin Dokumen', 'user' => 'Karyawan', default => ucfirst(str_replace('_', ' ', $user->role)) } }}
                                </span>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                                    {{ $user->isActive() ? 'Aktif' : ucfirst($user->account_status) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 border-t border-blue-100/80 pt-3.5">
                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                            <h3 id="profilePhotoTitle" class="!text-[13px] font-bold text-slate-800">Foto Profil</h3>
                            <span class="text-[11px] text-slate-500">JPG, PNG, WEBP · 2 MB</span>
                        </div>

                        <form method="post" action="{{ route('profile.photo.store') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="grid grid-cols-2 gap-2">
                                <label for="profilePhotoInput" class="flex min-h-[42px] cursor-pointer items-center justify-center gap-1.5 rounded-xl border border-blue-200 bg-white px-2 text-center text-xs font-bold text-blue-800 transition hover:bg-blue-50 focus-within:ring-2 focus-within:ring-blue-600 focus-within:ring-offset-1">
                                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M12 16V3m-5 5 5-5 5 5"/><path d="M4 16v4h16v-4"/>
                                    </svg>
                                    Unggah Foto
                                </label>
                                <input id="profilePhotoInput" name="photo" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" aria-describedby="profilePhotoHint">
                                <button type="submit" class="min-h-[42px] rounded-xl bg-blue-700 px-3 text-xs font-bold text-white transition hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700">
                                    Simpan Foto
                                </button>
                            </div>
                            <p id="profilePhotoHint" data-qh-photo-file-name class="mt-1.5 break-all text-center text-[11px] text-slate-500" aria-live="polite">Pilih foto untuk pratinjau.</p>
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
                                <button type="submit" class="rounded-lg px-3 py-1 text-xs font-semibold text-slate-500 transition hover:bg-rose-50 hover:text-rose-700">
                                    Hapus Foto
                                </button>
                            </form>
                        @endif
                    </div>
                </section>

                {{-- Existing name/email form and read-only employee metadata. --}}
                <section class="min-w-0" aria-label="Informasi akun">
                    @include('profile.partials.update-profile-information-form')
                </section>
            </div>
        </div>

        {{-- Full-width security eliminates the large empty lower-left column. --}}
        <section class="mt-4 overflow-hidden rounded-[20px] border border-slate-200 bg-white shadow-sm shadow-slate-900/5" data-qh-security-panel>
            @include('profile.partials.update-password-form')
        </section>
    </div>
</x-app-layout>
