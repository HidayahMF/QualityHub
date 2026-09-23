<section class="qh-profile-section flex h-full flex-col">

    <header
        class="qh-profile-section-header
               flex
               items-start
               gap-3
               px-5
               sm:px-6
               py-5"
    >
        <div
            class="flex
                   h-10
                   w-10
                   flex-shrink-0
                   items-center
                   justify-center
                   rounded-xl
                   bg-blue-100
                   text-blue-700"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="19"
                height="19"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                aria-hidden="true"
            >
                <path d="M20 21a8 8 0 0 0-16 0"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
        </div>

        <div class="min-w-0">
            <h2 class="text-base font-bold text-slate-900">
                {{ __('Informasi Profil') }}
            </h2>

            <p class="mt-1 text-sm leading-6 text-slate-500">
                {{ __('Perbarui informasi profil akun dan alamat email Anda.') }}
            </p>
        </div>
    </header>


    <form
        id="send-verification"
        method="post"
        action="{{ route('verification.send') }}"
    >
        @csrf
    </form>


    <form
        method="post"
        action="{{ route('profile.update') }}"
        class="qh-profile-form
               flex
               flex-1
               flex-col
               space-y-5
               px-5
               sm:px-6
               py-6"
    >
        @csrf
        @method('patch')

        <div>
            <x-input-label
                for="name"
                :value="__('Nama')"
                class="qh-profile-label"
            />

            <x-text-input
                id="name"
                name="name"
                type="text"
                class="qh-profile-input mt-2 block w-full"
                :value="old('name', $user->name)"
                required
                autofocus
                autocomplete="name"
            />

            <x-input-error
                class="mt-2"
                :messages="$errors->get('name')"
            />
        </div>


        <div>
            <x-input-label
                for="email"
                :value="__('Email')"
                class="qh-profile-label"
            />

            <x-text-input
                id="email"
                name="email"
                type="email"
                class="qh-profile-input mt-2 block w-full"
                :value="old('email', $user->email)"
                required
                autocomplete="username"
            />

            <x-input-error
                class="mt-2"
                :messages="$errors->get('email')"
            />


            @if (
                $user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail
                && ! $user->hasVerifiedEmail()
            )
                <div
                    class="mt-4
                           rounded-xl
                           border
                           border-amber-200
                           bg-amber-50
                           px-4
                           py-3"
                >
                    <p class="text-sm leading-6 text-amber-800">
                        {{ __('Alamat email Anda belum diverifikasi.') }}

                        <button
                            form="send-verification"
                            class="font-semibold
                                   underline
                                   underline-offset-2
                                   hover:text-amber-950
                                   focus:outline-none
                                   focus:ring-2
                                   focus:ring-amber-400
                                   focus:ring-offset-2"
                        >
                            {{ __('Klik di sini untuk mengirim ulang email verifikasi.') }}
                        </button>
                    </p>


                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm font-semibold text-emerald-700">
                            {{ __('Tautan verifikasi baru telah dikirim ke alamat email Anda.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>


        <div
            class="qh-profile-actions
                   flex
                   flex-wrap
                   items-center
                   gap-4
                   mt-auto
                   pt-6"
        >
            <x-primary-button class="qh-profile-primary">
                {{ __('Simpan Perubahan') }}
            </x-primary-button>


            @if (session('status') === 'profile-updated')
                <p
                    class="text-sm font-semibold text-emerald-700"
                    role="status"
                >
                    {{ __('Perubahan tersimpan.') }}
                </p>
            @endif
        </div>
    </form>

</section>
