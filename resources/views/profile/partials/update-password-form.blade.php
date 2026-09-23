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
                <rect width="18" height="11" x="3" y="11" rx="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
        </div>

        <div class="min-w-0">
            <h2 class="text-base font-bold text-slate-900">
                {{ __('Keamanan Kata Sandi') }}
            </h2>

            <p class="mt-1 text-sm leading-6 text-slate-500">
                {{ __('Gunakan kata sandi yang kuat dan tidak digunakan pada layanan lain.') }}
            </p>
        </div>
    </header>


    <form
        method="post"
        action="{{ route('password.update') }}"
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
        @method('put')

        <div>
            <x-input-label
                for="update_password_current_password"
                :value="__('Kata Sandi Saat Ini')"
                class="qh-profile-label"
            />

            <x-text-input
                id="update_password_current_password"
                name="current_password"
                type="password"
                class="qh-profile-input mt-2 block w-full"
                required
                autocomplete="current-password"
            />

            <x-input-error
                :messages="$errors->updatePassword->get('current_password')"
                class="mt-2"
            />
        </div>


        <div>
            <x-input-label
                for="update_password_password"
                :value="__('Kata Sandi Baru')"
                class="qh-profile-label"
            />

            <x-text-input
                id="update_password_password"
                name="password"
                type="password"
                class="qh-profile-input mt-2 block w-full"
                required
                autocomplete="new-password"
            />

            <x-input-error
                :messages="$errors->updatePassword->get('password')"
                class="mt-2"
            />
        </div>


        <div>
            <x-input-label
                for="update_password_password_confirmation"
                :value="__('Konfirmasi Kata Sandi')"
                class="qh-profile-label"
            />

            <x-text-input
                id="update_password_password_confirmation"
                name="password_confirmation"
                type="password"
                class="qh-profile-input mt-2 block w-full"
                required
                autocomplete="new-password"
            />

            <x-input-error
                :messages="$errors->updatePassword->get('password_confirmation')"
                class="mt-2"
            />
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
                {{ __('Perbarui Kata Sandi') }}
            </x-primary-button>


            @if (session('status') === 'password-updated')
                <p
                    class="text-sm font-semibold text-emerald-700"
                    role="status"
                >
                    {{ __('Kata sandi diperbarui.') }}
                </p>
            @endif
        </div>
    </form>

</section>
