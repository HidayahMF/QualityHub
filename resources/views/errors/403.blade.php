<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Akses Ditolak · {{ config('app.name', 'BMC QualityHub') }}</title>

    <style nonce="{{ $cspNonce }}">
        :root {
            color-scheme: light;
            --blue-950: #172554;
            --blue-900: #1e3a8a;
            --blue-800: #1e40af;
            --blue-700: #1d4ed8;
            --blue-100: #dbeafe;
            --blue-50: #eff6ff;
            --slate-950: #020617;
            --slate-900: #0f172a;
            --slate-700: #334155;
            --slate-600: #475569;
            --slate-500: #64748b;
            --slate-300: #cbd5e1;
            --slate-200: #e2e8f0;
            --slate-100: #f1f5f9;
            --white: #ffffff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family:
                Inter,
                ui-sans-serif,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
            color: var(--slate-900);
            background:
                radial-gradient(circle at top left, rgba(219, 234, 254, .95), transparent 34rem),
                linear-gradient(180deg, #f8fbff 0%, #f3f7fb 100%);
        }

        .page {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 28px 18px;
        }

        .shell {
            width: min(100%, 980px);
            overflow: hidden;
            border: 1px solid rgba(203, 213, 225, .88);
            border-radius: 30px;
            background: rgba(255, 255, 255, .96);
            box-shadow:
                0 28px 70px rgba(15, 23, 42, .10),
                0 4px 14px rgba(30, 64, 175, .05);
        }

        .topline {
            height: 6px;
            background: linear-gradient(
                90deg,
                var(--blue-950),
                var(--blue-800),
                var(--blue-700)
            );
        }

        .content {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(270px, .9fr);
            gap: 34px;
            align-items: center;
            padding: 48px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 26px;
        }

        .brand-mark {
            display: grid;
            place-items: center;
            width: 46px;
            height: 46px;
            overflow: hidden;
            border: 1px solid var(--blue-100);
            border-radius: 15px;
            background: var(--white);
            box-shadow: 0 8px 22px rgba(30, 64, 175, .08);
        }

        .brand-mark img {
            display: block;
            max-width: 34px;
            max-height: 34px;
            object-fit: contain;
        }

        .brand-name {
            margin: 0;
            font-size: 16px;
            font-weight: 800;
            letter-spacing: -.02em;
            color: var(--blue-950);
        }

        .brand-subtitle {
            margin: 3px 0 0;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--slate-500);
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 14px;
            padding: 7px 11px;
            border: 1px solid var(--blue-100);
            border-radius: 999px;
            background: var(--blue-50);
            color: var(--blue-800);
            font-size: 12px;
            font-weight: 800;
        }

        .eyebrow-dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: var(--blue-700);
        }

        h1 {
            max-width: 640px;
            margin: 0;
            font-size: clamp(34px, 5vw, 54px);
            line-height: 1.03;
            letter-spacing: -.045em;
            color: var(--slate-950);
        }

        .description {
            max-width: 630px;
            margin: 18px 0 0;
            color: var(--slate-600);
            font-size: 15px;
            line-height: 1.8;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 11px;
            margin-top: 28px;
        }

        .button {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 11px 18px;
            border-radius: 13px;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            transition:
                transform .16s ease,
                background-color .16s ease,
                border-color .16s ease;
        }

        .button:hover {
            transform: translateY(-1px);
        }

        .button-primary {
            border: 1px solid var(--blue-800);
            background: var(--blue-800);
            color: var(--white);
            box-shadow: 0 10px 26px rgba(30, 64, 175, .16);
        }

        .button-primary:hover {
            background: var(--blue-900);
        }

        .button-secondary {
            border: 1px solid var(--slate-200);
            background: var(--white);
            color: var(--slate-700);
        }

        .button-secondary:hover {
            background: var(--slate-100);
        }

        .status-card {
            position: relative;
            overflow: hidden;
            min-height: 330px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 28px;
            border: 1px solid rgba(219, 234, 254, .9);
            border-radius: 26px;
            background:
                linear-gradient(
                    150deg,
                    var(--blue-950) 0%,
                    var(--blue-900) 62%,
                    var(--blue-800) 100%
                );
            color: var(--white);
            box-shadow: 0 22px 46px rgba(30, 58, 138, .18);
        }

        .status-card::before,
        .status-card::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            background: rgba(255, 255, 255, .06);
        }

        .status-card::before {
            width: 230px;
            height: 230px;
            top: -100px;
            right: -80px;
        }

        .status-card::after {
            width: 170px;
            height: 170px;
            bottom: -95px;
            left: -60px;
        }

        .status-inner {
            position: relative;
            z-index: 1;
        }

        .code {
            margin: 0;
            font-size: clamp(72px, 11vw, 118px);
            line-height: .9;
            font-weight: 900;
            letter-spacing: -.08em;
            color: rgba(255, 255, 255, .98);
        }

        .status-label {
            margin: 18px 0 0;
            font-size: 13px;
            font-weight: 800;
            color: #bfdbfe;
        }

        .security-note {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding-top: 22px;
            border-top: 1px solid rgba(255, 255, 255, .12);
            color: rgba(219, 234, 254, .88);
            font-size: 12px;
            line-height: 1.6;
        }

        .lock {
            flex: 0 0 auto;
            display: grid;
            place-items: center;
            width: 30px;
            height: 30px;
            border-radius: 10px;
            background: rgba(255, 255, 255, .08);
        }

        .footer {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 48px 22px;
            border-top: 1px solid var(--slate-100);
            color: var(--slate-500);
            font-size: 11px;
        }

        @media (max-width: 760px) {
            .content {
                grid-template-columns: 1fr;
                padding: 30px 24px;
            }

            .status-card {
                min-height: 250px;
            }

            .footer {
                padding: 17px 24px 21px;
            }
        }
    </style>
</head>

<body>
    <main class="page">
        <section class="shell" aria-labelledby="error-title">
            <div class="topline"></div>

            <div class="content">
                <div>
                    <div class="brand">
                        <div class="brand-mark">
                            <img
                                src="{{ asset('images/bmc-logo.png') }}"
                                alt="BMC"
                            >
                        </div>

                        <div>
                            <p class="brand-name">BMC QualityHub</p>
                            <p class="brand-subtitle">
                                Quality Control Data System
                            </p>
                        </div>
                    </div>

                    <div class="eyebrow">
                        <span class="eyebrow-dot"></span>
                        Akses Terbatas
                    </div>

                    <h1 id="error-title">
                        Anda tidak memiliki izin untuk membuka halaman ini.
                    </h1>

                    <p class="description">
                        Halaman yang Anda coba buka hanya tersedia untuk pengguna
                        dengan hak akses tertentu. Tidak ada data yang diubah.
                        Jika akses ini memang diperlukan untuk pekerjaan Anda,
                        silakan hubungi administrator BMC QualityHub.
                    </p>

                    <div class="actions">
                        @auth
                            @if(Route::has('dashboard'))
                                <a
                                    href="{{ route('dashboard') }}"
                                    class="button button-primary"
                                >
                                    Kembali ke Dashboard
                                </a>
                            @endif
                        @else
                            @if(Route::has('login'))
                                <a
                                    href="{{ route('login') }}"
                                    class="button button-primary"
                                >
                                    Kembali ke Login
                                </a>
                            @endif
                        @endauth

                        <a
                            href="{{ url()->previous() }}"
                            class="button button-secondary"
                        >
                            Kembali ke Halaman Sebelumnya
                        </a>
                    </div>
                </div>

                <aside class="status-card" aria-label="Status akses">
                    <div class="status-inner">
                        <p class="code">403</p>
                        <p class="status-label">
                            AKSES DITOLAK · AUTHORIZATION PROTECTION
                        </p>
                    </div>

                    <div class="security-note">
                        <div class="lock" aria-hidden="true">🔒</div>

                        <div>
                            Proteksi hak akses BMC QualityHub sedang bekerja.
                            Halaman tetap terlindungi dari pengguna yang tidak
                            memiliki izin yang sesuai.
                        </div>
                    </div>
                </aside>
            </div>

            <footer class="footer">
                <span>
                    BMC QualityHub · Quality Control Document & Analytics System
                </span>

                <span>
                    PT Braja Mukti Cakra
                </span>
            </footer>
        </section>
    </main>
</body>
</html>

