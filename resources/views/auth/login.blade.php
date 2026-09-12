<x-guest-layout>

    {{-- ========================================================= --}}
    {{-- BMC QUALITYHUB · LOGIN --}}
    {{-- ========================================================= --}}

    <section
        class="auth-qualityhub-panel
               relative
               overflow-hidden
               rounded-[28px]
               border
               border-slate-200/80
               bg-white
               p-6
               shadow-[0_24px_70px_rgba(15,23,42,0.08)]
               sm:p-8"
    >

        <div
            class="pointer-events-none
                   absolute
                   -right-20
                   -top-20
                   h-44
                   w-44
                   rounded-full
                   bg-blue-500/[0.06]
                   blur-2xl"
        ></div>

        <div
            class="pointer-events-none
                   -left-20
                   absolute
                   bottom-0
                   h-36
                   w-36
                   rounded-full
                   bg-cyan-400/[0.045]
                   blur-2xl"
        ></div>


        <div class="relative">

            {{-- HEADER --}}
            <header class="mb-7">

                <div
                    class="inline-flex
                           items-center
                           gap-2
                           rounded-full
                           border
                           border-blue-100
                           bg-blue-50
                           px-3
                           py-1.5
                           text-xs
                           font-semibold
                           text-blue-700"
                >
                    <span
                        class="h-2
                               w-2
                               rounded-full
                               bg-blue-600
                               shadow-[0_0_0_4px_rgba(37,99,235,0.08)]"
                    ></span>

                    Akses Internal Aman
                </div>


                <h1
                    class="mt-5
                           text-3xl
                           font-bold
                           tracking-tight
                           text-slate-950"
                >
                    Selamat datang kembali
                </h1>


                <p
                    class="mt-2
                           max-w-md
                           text-sm
                           leading-6
                           text-slate-500"
                >
                    Masuk ke <strong class="font-semibold text-slate-700">BMC QualityHub</strong>
                    untuk mengakses dokumen, data, dan analitik Quality Control.
                </p>

            </header>


            <x-auth-session-status
                class="mb-5
                       rounded-xl
                       border
                       border-emerald-100
                       bg-emerald-50
                       px-4
                       py-3
                       text-sm
                       text-emerald-700"
                :status="session('status')"
            />


            <form
                method="POST"
                action="{{ route('login') }}"
                class="space-y-5"
            >
                @csrf


                {{-- EMAIL --}}
                <div>

                    <label
                        for="email"
                        class="mb-2
                               block
                               text-sm
                               font-semibold
                               text-slate-700"
                    >
                        Alamat Email
                    </label>


                    <div class="group relative">

                        <span
                            class="pointer-events-none
                                   absolute
                                   left-4
                                   top-1/2
                                   -translate-y-1/2
                                   text-slate-400
                                   transition
                                   group-focus-within:text-blue-600"
                        >
                            <svg
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <rect width="20" height="16" x="2" y="4" rx="2"/>
                                <path d="m22 7-10 6L2 7"/>
                            </svg>
                        </span>


                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="nama@bmc.co.id"
                            class="w-full
                                   rounded-xl
                                   border-slate-200
                                   bg-slate-50/80
                                   py-3.5
                                   pl-11
                                   pr-4
                                   text-sm
                                   text-slate-900
                                   shadow-sm
                                   placeholder:text-slate-400
                                   transition
                                   focus:border-blue-500
                                   focus:bg-white
                                   focus:ring-4
                                   focus:ring-blue-500/10"
                        >

                    </div>


                    <x-input-error
                        :messages="$errors->get('email')"
                        class="mt-2"
                    />

                </div>


                {{-- PASSWORD --}}
                <div>

                    <div
                        class="mb-2
                               flex
                               items-center
                               justify-between
                               gap-3"
                    >

                        <label
                            for="password"
                            class="block
                                   text-sm
                                   font-semibold
                                   text-slate-700"
                        >
                            Kata Sandi
                        </label>


                        @if(Route::has('password.request'))

                            <a
                                href="{{ route('password.request') }}"
                                class="text-xs
                                       font-semibold
                                       text-blue-700
                                       transition
                                       hover:text-blue-900"
                            >
                                Lupa kata sandi?
                            </a>

                        @endif

                    </div>


                    <div class="group relative">

                        <span
                            class="pointer-events-none
                                   absolute
                                   left-4
                                   top-1/2
                                   -translate-y-1/2
                                   text-slate-400
                                   transition
                                   group-focus-within:text-blue-600"
                        >
                            <svg
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <rect width="18" height="11" x="3" y="11" rx="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </span>


                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="Masukkan kata sandi Anda"
                            class="w-full
                                   rounded-xl
                                   border-slate-200
                                   bg-slate-50/80
                                   py-3.5
                                   pl-11
                                   pr-12
                                   text-sm
                                   text-slate-900
                                   shadow-sm
                                   placeholder:text-slate-400
                                   transition
                                   focus:border-blue-500
                                   focus:bg-white
                                   focus:ring-4
                                   focus:ring-blue-500/10"
                        >


                        <button
                            type="button"
                            data-password-toggle="password"
                            class="absolute
                                   right-3
                                   top-1/2
                                   flex
                                   h-8
                                   w-8
                                   -translate-y-1/2
                                   items-center
                                   justify-center
                                   rounded-lg
                                   text-slate-400
                                   transition
                                   hover:bg-slate-100
                                   hover:text-slate-700"
                            aria-label="Tampilkan atau sembunyikan kata sandi"
                        >
                            <svg
                                width="17"
                                height="17"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M2.062 12.348a1 1 0 0 1 0-.696C3.597 7.614 7.486 5 12 5c4.514 0 8.403 2.614 9.938 6.652a1 1 0 0 1 0 .696C20.403 16.386 16.514 19 12 19c-4.514 0-8.403-2.614-9.938-6.652"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>

                    </div>


                    <x-input-error
                        :messages="$errors->get('password')"
                        class="mt-2"
                    />

                </div>


                {{-- PERSISTENT LOGIN POLICY --}}
                @if (config('smartfind_security.auth.remember_login_enabled', false))
                    <div
                        class="flex
                               items-center
                               justify-between
                               gap-3"
                    >
                        <label
                            for="remember_me"
                            class="inline-flex
                                   cursor-pointer
                                   items-center
                                   gap-2.5
                                   text-sm
                                   text-slate-600"
                        >
                            <input
                                id="remember_me"
                                type="checkbox"
                                name="remember"
                                value="1"
                                class="rounded
                                       border-slate-300
                                       text-blue-700
                                       shadow-sm
                                       focus:ring-blue-500"
                            >
                            <span>Ingat saya</span>
                        </label>

                        <span
                            class="inline-flex
                                   items-center
                                   gap-1.5
                                   text-[11px]
                                   text-slate-400"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Sesi aman
                        </span>
                    </div>
                @else
                    <div
                        class="flex items-center gap-2.5 rounded-xl
                               border border-blue-100 bg-blue-50/70
                               px-3.5 py-2.5 text-xs text-blue-700"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="16"
                            height="16"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="shrink-0"
                            aria-hidden="true"
                        >
                            <rect width="18" height="11" x="3" y="11" rx="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <span>Login persisten dinonaktifkan untuk keamanan perangkat internal.</span>
                    </div>
                @endif


                {{-- BUTTON --}}
                <button
                    type="submit"
                    class="group
                           relative
                           flex
                           w-full
                           items-center
                           justify-center
                           gap-2
                           overflow-hidden
                           rounded-xl
                           bg-gradient-to-r
                           from-blue-900
                           via-blue-800
                           to-blue-700
                           px-5
                           py-3.5
                           font-semibold
                           text-white
                           shadow-[0_12px_28px_rgba(30,64,175,0.20)]
                           transition
                           duration-300
                           hover:-translate-y-0.5
                           hover:shadow-[0_16px_34px_rgba(30,64,175,0.28)]
                           active:translate-y-0"
                >
                    <span>Masuk ke QualityHub</span>

                    <svg
                        width="17"
                        height="17"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        class="transition
                               group-hover:translate-x-0.5"
                    >
                        <path d="M5 12h14"/>
                        <path d="m13 6 6 6-6 6"/>
                    </svg>
                </button>


                {{-- REGISTER --}}
                @if(Route::has('register'))

                    <div
                        class="rounded-xl
                               border
                               border-slate-200
                               bg-slate-50/70
                               px-4
                               py-3.5
                               text-center"
                    >

                        <p class="text-sm text-slate-500">
                            Belum memiliki akun?

                            <a
                                href="{{ route('register') }}"
                                class="ml-1
                                       font-semibold
                                       text-blue-700
                                       transition
                                       hover:text-blue-900"
                            >
                                Ajukan akun karyawan
                            </a>
                        </p>

                    </div>

                @endif

            </form>


            {{-- SECURITY --}}
            <footer
                class="mt-7
                       border-t
                       border-slate-100
                       pt-5"
            >

                <div
                    class="flex
                           items-center
                           justify-center
                           gap-2
                           text-[11px]
                           text-slate-400"
                >
                    <svg
                        width="14"
                        height="14"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <rect width="18" height="11" x="3" y="11" rx="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>

                    Akses internal PT Braja Mukti Cakra yang dilindungi
                </div>

            </footer>

        </div>

    </section>


    <script nonce="{{ $cspNonce }}">
        (() => {
            'use strict';

            document.querySelectorAll(
                '[data-password-toggle]'
            ).forEach(
                (button) => {
                    button.addEventListener(
                        'click',
                        () => {
                            const target =
                                document.getElementById(
                                    button.dataset.passwordToggle
                                );

                            if (!target) {
                                return;
                            }

                            target.type =
                                target.type === 'password'
                                    ? 'text'
                                    : 'password';
                        }
                    );
                }
            );
        })();
    </script>

</x-guest-layout>

