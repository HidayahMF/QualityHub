<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Sesi Anda Telah Berakhir - BMC QualityHub</title>

    <style nonce="{{ $cspNonce }}">
        :root {
            color-scheme: light;
            --navy: #172554;
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
            min-height: 100vh;
            margin: 0;
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
                radial-gradient(circle at 8% 8%, rgba(219, 234, 254, .95), transparent 31rem),
                radial-gradient(circle at 92% 92%, rgba(224, 242, 254, .72), transparent 28rem),
                linear-gradient(180deg, #f8fbff 0%, #f1f5f9 100%);
        }

        .page {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 28px 18px;
        }

        .shell {
            width: min(100%, 940px);
            overflow: hidden;
            border: 1px solid rgba(203, 213, 225, .86);
            border-radius: 30px;
            background: rgba(255, 255, 255, .97);
            box-shadow:
                0 28px 70px rgba(15, 23, 42, .11),
                0 4px 16px rgba(30, 64, 175, .05);
        }

        .topline {
            height: 6px;
            background: linear-gradient(90deg, var(--navy), var(--blue-800), #0284c7);
        }

        .content {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(260px, .85fr);
            gap: 38px;
            align-items: center;
            padding: 48px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 27px;
        }

        .brand-mark {
            width: 46px;
            height: 46px;
            display: grid;
            place-items: center;
            overflow: hidden;
            border: 1px solid var(--blue-100);
            border-radius: 15px;
            background: var(--white);
            box-shadow: 0 8px 22px rgba(30, 64, 175, .09);
        }

        .brand-mark img {
            display: block;
            max-width: 34px;
            max-height: 34px;
            object-fit: contain;
        }

        .brand-name {
            margin: 0;
            color: var(--navy);
            font-size: 16px;
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .brand-subtitle {
            margin: 3px 0 0;
            color: var(--slate-500);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
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
            letter-spacing: .02em;
        }

        .eyebrow-dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: var(--blue-700);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, .10);
        }

        h1 {
            max-width: 620px;
            margin: 0;
            color: var(--slate-950);
            font-size: clamp(34px, 5vw, 52px);
            line-height: 1.04;
            letter-spacing: -.045em;
        }

        .description {
            max-width: 630px;
            margin: 18px 0 0;
            color: var(--slate-600);
            font-size: 15px;
            line-height: 1.8;
        }

        .recovery-note {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 20px;
            padding: 13px 15px;
            border: 1px solid var(--slate-200);
            border-radius: 14px;
            background: #f8fafc;
            color: var(--slate-600);
            font-size: 12px;
            line-height: 1.6;
        }

        .recovery-note svg {
            flex: 0 0 auto;
            margin-top: 1px;
            color: var(--blue-700);
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 11px;
            margin-top: 27px;
        }

        .button {
            min-height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 11px 19px;
            border: 1px solid var(--blue-800);
            border-radius: 13px;
            background: var(--blue-800);
            color: var(--white);
            box-shadow: 0 11px 28px rgba(30, 64, 175, .18);
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            transition: transform .16s ease, background-color .16s ease;
        }

        .button:hover {
            transform: translateY(-1px);
            background: var(--blue-900);
        }

        .button:focus-visible {
            outline: 3px solid rgba(59, 130, 246, .35);
            outline-offset: 3px;
        }

        .status-card {
            position: relative;
            min-height: 330px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 28px;
            border: 1px solid rgba(219, 234, 254, .7);
            border-radius: 26px;
            background: linear-gradient(150deg, var(--navy) 0%, var(--blue-900) 60%, var(--blue-800) 100%);
            color: var(--white);
            box-shadow: 0 22px 46px rgba(30, 58, 138, .2);
        }

        .status-card::before,
        .status-card::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            background: rgba(255, 255, 255, .06);
        }

        .status-card::before {
            width: 225px;
            height: 225px;
            top: -100px;
            right: -78px;
        }

        .status-card::after {
            width: 170px;
            height: 170px;
            bottom: -92px;
            left: -62px;
        }

        .status-icon,
        .status-detail {
            position: relative;
            z-index: 1;
        }

        .status-icon {
            width: 62px;
            height: 62px;
            display: grid;
            place-items: center;
            border: 1px solid rgba(255, 255, 255, .14);
            border-radius: 20px;
            background: rgba(255, 255, 255, .09);
        }

        .status-code {
            margin: 0;
            color: rgba(255, 255, 255, .98);
            font-size: clamp(68px, 10vw, 104px);
            font-weight: 900;
            line-height: .88;
            letter-spacing: -.075em;
        }

        .status-label {
            margin: 18px 0 0;
            color: #bfdbfe;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .07em;
        }

        .security-note {
            margin: 13px 0 0;
            color: rgba(219, 234, 254, .88);
            font-size: 12px;
            line-height: 1.65;
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
            .page {
                padding: 18px 12px;
            }

            .shell {
                border-radius: 23px;
            }

            .content {
                grid-template-columns: 1fr;
                gap: 28px;
                padding: 30px 24px;
            }

            .status-card {
                min-height: 265px;
            }

            .footer {
                padding: 17px 24px 21px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .button {
                transition: none;
            }
        }
    </style>
</head>

<body>
    <main class="page">
        <section class="shell" aria-labelledby="session-expired-title">
            <div class="topline"></div>

            <div class="content">
                <div>
                    <div class="brand">
                        <div class="brand-mark">
                            <img src="{{ asset('images/bmc-logo.png') }}" alt="BMC">
                        </div>

                        <div>
                            <p class="brand-name">BMC QualityHub</p>
                            <p class="brand-subtitle">Quality Control Data System</p>
                        </div>
                    </div>

                    <div class="eyebrow">
                        <span class="eyebrow-dot" aria-hidden="true"></span>
                        Perlindungan Sesi
                    </div>

                    <h1 id="session-expired-title">Sesi Anda Telah Berakhir</h1>

                    <p class="description">
                        Demi keamanan, sesi BMC QualityHub telah berakhir karena tidak ada aktivitas
                        dalam beberapa waktu. Silakan masuk kembali untuk melanjutkan.
                    </p>

                    <div class="recovery-note">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"></circle>
                            <path d="M12 8v4"></path>
                            <path d="M12 16h.01"></path>
                        </svg>

                        <span>Data yang belum dikirim mungkin perlu dimasukkan kembali.</span>
                    </div>

                    <div class="actions">
                        <a href="{{ route('login') }}" class="button">
                            Masuk Kembali

                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="m9 18 6-6-6-6"></path>
                            </svg>
                        </a>
                    </div>
                </div>

                <aside class="status-card" aria-label="Status sesi">
                    <div class="status-icon" aria-hidden="true">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"></path>
                            <path d="M12 8v4"></path>
                            <path d="M12 16h.01"></path>
                        </svg>
                    </div>

                    <div class="status-detail">
                        <p class="status-code">419</p>
                        <p class="status-label">SESSION EXPIRED</p>
                        <p class="security-note">
                            Proteksi sesi BMC QualityHub telah menghentikan request dengan token yang
                            sudah tidak berlaku. Tidak ada tindakan lama yang dikirim ulang otomatis.
                        </p>
                    </div>
                </aside>
            </div>

            <footer class="footer">
                <span>BMC QualityHub - Quality Control Document &amp; Analytics System</span>
                <span>PT Braja Mukti Cakra</span>
            </footer>
        </section>
    </main>
</body>
</html>
