<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| BMC QualityHub - User Management Confirmation CSP Fix
|--------------------------------------------------------------------------
|
| Fix:
| - Tombol Tolak dan Nonaktifkan tidak lagi memakai Alpine inline expression.
| - Modal konfirmasi diganti dengan DOM JavaScript + CSP nonce.
| - Route/controller/backend TIDAK diubah.
| - Approve/Aktifkan tetap submit langsung seperti sebelumnya.
| - Backup + rollback otomatis.
|
*/

$root = dirname(__DIR__);

$viewPath =
    $root
    . DIRECTORY_SEPARATOR . 'resources'
    . DIRECTORY_SEPARATOR . 'views'
    . DIRECTORY_SEPARATOR . 'users'
    . DIRECTORY_SEPARATOR . 'index.blade.php';

if (! is_file($viewPath)) {
    fwrite(
        STDERR,
        "View tidak ditemukan: {$viewPath}" . PHP_EOL
    );
    exit(1);
}

$original = file_get_contents($viewPath);

if ($original === false) {
    fwrite(STDERR, "Gagal membaca users/index.blade.php." . PHP_EOL);
    exit(1);
}

$eol =
    str_contains($original, "\r\n")
        ? "\r\n"
        : "\n";

$text =
    str_replace(
        ["\r\n", "\r"],
        "\n",
        $original
    );

$lines = explode("\n", $text);

$backupDir =
    $root
    . DIRECTORY_SEPARATOR . 'storage'
    . DIRECTORY_SEPARATOR . 'backups'
    . DIRECTORY_SEPARATOR . 'user-management-confirm-before-csp-fix-'
    . date('Ymd-His');

if (
    ! is_dir($backupDir)
    && ! mkdir($backupDir, 0777, true)
    && ! is_dir($backupDir)
) {
    fwrite(STDERR, "Gagal membuat folder backup." . PHP_EOL);
    exit(1);
}

$backupPath =
    $backupDir
    . DIRECTORY_SEPARATOR
    . 'index.blade.php';

copy(
    $viewPath,
    $backupPath
);

function indentOf(string $line): string
{
    preg_match(
        '/^[ \t]*/',
        $line,
        $match
    );

    return $match[0] ?? '';
}

function replaceConfirmAttribute(
    array &$lines,
    string $routeNeedle,
    array $dataLines,
    string $label
): void {
    $actionIndex = null;

    foreach ($lines as $i => $line) {
        if (str_contains($line, $routeNeedle)) {
            $actionIndex = $i;
            break;
        }
    }

    if ($actionIndex === null) {
        throw new RuntimeException(
            "Form {$label} tidak ditemukan."
        );
    }

    $formStart = $actionIndex;

    while (
        $formStart >= 0
        && ! str_contains($lines[$formStart], '<form')
    ) {
        $formStart--;
    }

    if ($formStart < 0) {
        throw new RuntimeException(
            "Awal form {$label} tidak ditemukan."
        );
    }

    $formOpenEnd = $actionIndex;

    while (
        $formOpenEnd < count($lines)
        && trim($lines[$formOpenEnd]) !== '>'
    ) {
        $formOpenEnd++;
    }

    if ($formOpenEnd >= count($lines)) {
        throw new RuntimeException(
            "Penutup opening-tag form {$label} tidak ditemukan."
        );
    }

    $xStart = null;
    $xEnd = null;

    for ($i = $formStart; $i <= $formOpenEnd; $i++) {
        if (
            str_contains(
                $lines[$i],
                'x-on:submit.prevent='
            )
        ) {
            $xStart = $i;

            for ($j = $i; $j <= $formOpenEnd; $j++) {
                if (str_contains($lines[$j], '})"')) {
                    $xEnd = $j;
                    break;
                }
            }

            break;
        }
    }

    if ($xStart !== null) {
        if ($xEnd === null) {
            throw new RuntimeException(
                "Akhir Alpine submit {$label} tidak ditemukan."
            );
        }

        array_splice(
            $lines,
            $xStart,
            $xEnd - $xStart + 1
        );

        $formOpenEnd -=
            ($xEnd - $xStart + 1);
    }

    $indent =
        indentOf(
            $lines[$formOpenEnd]
        );

    $prepared = [];

    foreach ($dataLines as $line) {
        $prepared[] =
            $indent . $line;
    }

    array_splice(
        $lines,
        $formOpenEnd,
        0,
        $prepared
    );
}

echo PHP_EOL;
echo "=== BMC QualityHub User Management CSP Fix ===" . PHP_EOL;

try {
    replaceConfirmAttribute(
        $lines,
        "route('users.reject', \$user)",
        [
            'data-qualityhub-user-confirm="1"',
            'data-confirm-title="Tolak Permintaan Akun?"',
            'data-confirm-message="Permintaan akun ini akan ditandai sebagai ditolak dan pengguna tidak dapat masuk ke BMC QualityHub."',
            'data-confirm-button="Tolak Akun"',
        ],
        'Tolak'
    );

    echo "[OK] Form Tolak dibuat CSP-safe." . PHP_EOL;

    replaceConfirmAttribute(
        $lines,
        "route('users.deactivate', \$user)",
        [
            'data-qualityhub-user-confirm="1"',
            'data-confirm-title="Nonaktifkan Pengguna?"',
            'data-confirm-message="Pengguna tidak akan dapat masuk ke BMC QualityHub sampai akun diaktifkan kembali."',
            'data-confirm-button="Nonaktifkan"',
        ],
        'Nonaktifkan'
    );

    echo "[OK] Form Nonaktifkan dibuat CSP-safe." . PHP_EOL;

    $modalMarker = null;
    $layoutClose = null;

    foreach ($lines as $i => $line) {
        if (
            str_contains(
                $line,
                'BMC QUALITYHUB CUSTOM CONFIRMATION MODAL'
            )
        ) {
            $modalMarker = $i;
            break;
        }
    }

    foreach ($lines as $i => $line) {
        if (trim($line) === '</x-app-layout>') {
            $layoutClose = $i;
        }
    }

    if ($modalMarker === null) {
        throw new RuntimeException(
            'Modal konfirmasi lama tidak ditemukan.'
        );
    }

    if ($layoutClose === null) {
        throw new RuntimeException(
            'Penutup </x-app-layout> tidak ditemukan.'
        );
    }

    if ($layoutClose <= $modalMarker) {
        throw new RuntimeException(
            'Posisi modal lama tidak valid.'
        );
    }

    $newModalText = <<<'BLADE'
    {{-- ========================================================= --}}
    {{-- BMC QUALITYHUB CSP-SAFE USER CONFIRMATION --}}
    {{-- ========================================================= --}}

    <div
        id="qualityhub-user-confirm"
        class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="qualityhub-user-confirm-title"
        aria-hidden="true"
    >
        <div
            data-qualityhub-user-confirm-backdrop
            class="absolute inset-0 bg-slate-950/45 backdrop-blur-[2px]"
        ></div>

        <section
            class="relative w-full max-w-md overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-2xl shadow-blue-950/20"
        >
            <div class="h-1.5 w-full bg-blue-800"></div>

            <div class="p-6 sm:p-7">
                <div class="flex items-start gap-4">

                    <div
                        class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-700 ring-1 ring-blue-100"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="22"
                            height="22"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/>
                            <path d="M9 12l2 2 4-4"/>
                        </svg>
                    </div>

                    <div class="min-w-0 flex-1">
                        <p
                            class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600"
                        >
                            BMC QualityHub
                        </p>

                        <h3
                            id="qualityhub-user-confirm-title"
                            class="mt-1.5 text-xl font-bold text-slate-900"
                        >
                            Konfirmasi Tindakan
                        </h3>

                        <p
                            data-qualityhub-user-confirm-message
                            class="mt-2 text-sm leading-6 text-slate-600"
                        >
                            Pastikan tindakan ini memang ingin dilanjutkan.
                        </p>
                    </div>
                </div>

                <div
                    class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"
                >
                    <button
                        type="button"
                        data-qualityhub-user-confirm-cancel
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2"
                    >
                        Batal
                    </button>

                    <button
                        type="button"
                        data-qualityhub-user-confirm-submit
                        class="inline-flex items-center justify-center rounded-xl bg-blue-800 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-900/15 transition hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        Lanjutkan
                    </button>
                </div>
            </div>
        </section>
    </div>

    <script nonce="{{ $cspNonce }}">
        (() => {
            const modal =
                document.getElementById(
                    'qualityhub-user-confirm'
                );

            if (!modal) {
                return;
            }

            const title =
                modal.querySelector(
                    '#qualityhub-user-confirm-title'
                );

            const message =
                modal.querySelector(
                    '[data-qualityhub-user-confirm-message]'
                );

            const submitButton =
                modal.querySelector(
                    '[data-qualityhub-user-confirm-submit]'
                );

            const cancelButton =
                modal.querySelector(
                    '[data-qualityhub-user-confirm-cancel]'
                );

            const backdrop =
                modal.querySelector(
                    '[data-qualityhub-user-confirm-backdrop]'
                );

            let pendingForm = null;

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
                pendingForm = null;
            };

            const openModal = (form) => {
                pendingForm = form;

                title.textContent =
                    form.dataset.confirmTitle
                    || 'Konfirmasi Tindakan';

                message.textContent =
                    form.dataset.confirmMessage
                    || 'Pastikan tindakan ini memang ingin dilanjutkan.';

                submitButton.textContent =
                    form.dataset.confirmButton
                    || 'Lanjutkan';

                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');
                submitButton.focus();
            };

            document
                .querySelectorAll(
                    'form[data-qualityhub-user-confirm="1"]'
                )
                .forEach((form) => {
                    form.addEventListener(
                        'submit',
                        (event) => {
                            if (
                                form.dataset.confirmBypass
                                === '1'
                            ) {
                                delete form.dataset.confirmBypass;
                                return;
                            }

                            event.preventDefault();
                            openModal(form);
                        }
                    );
                });

            cancelButton.addEventListener(
                'click',
                closeModal
            );

            backdrop.addEventListener(
                'click',
                closeModal
            );

            document.addEventListener(
                'keydown',
                (event) => {
                    if (
                        event.key === 'Escape'
                        && ! modal.classList.contains('hidden')
                    ) {
                        closeModal();
                    }
                }
            );

            submitButton.addEventListener(
                'click',
                () => {
                    const form = pendingForm;

                    if (!form) {
                        closeModal();
                        return;
                    }

                    form.dataset.confirmBypass = '1';

                    modal.classList.add('hidden');
                    modal.setAttribute('aria-hidden', 'true');

                    pendingForm = null;

                    form.requestSubmit();
                }
            );
        })();
    </script>
BLADE;

    $newModalLines =
        explode(
            "\n",
            $newModalText
        );

    array_splice(
        $lines,
        $modalMarker,
        $layoutClose - $modalMarker,
        $newModalLines
    );

    echo "[OK] Modal Alpine lama diganti modal CSP-safe." . PHP_EOL;

    $result =
        implode(
            "\n",
            $lines
        );

    if (
        str_contains(
            $result,
            "x-on:submit.prevent=\"\$dispatch('qualityhub-confirm'"
        )
    ) {
        throw new RuntimeException(
            'Alpine submit lama masih ditemukan.'
        );
    }

    if (
        ! str_contains(
            $result,
            'data-qualityhub-user-confirm="1"'
        )
    ) {
        throw new RuntimeException(
            'Data attribute confirmation tidak ditemukan.'
        );
    }

    if (
        ! str_contains(
            $result,
            'nonce="{{ $cspNonce }}"'
        )
    ) {
        throw new RuntimeException(
            'CSP nonce tidak ditemukan.'
        );
    }

    if (
        ! str_contains(
            $result,
            'form.requestSubmit();'
        )
    ) {
        throw new RuntimeException(
            'Final requestSubmit tidak ditemukan.'
        );
    }

    echo "[OK] Guard CSP-safe PASS." . PHP_EOL;

    if ($eol === "\r\n") {
        $result =
            str_replace(
                "\n",
                "\r\n",
                $result
            );
    }

    file_put_contents(
        $viewPath,
        $result
    );

    echo PHP_EOL;
    echo "Backup:" . PHP_EOL;
    echo $backupPath . PHP_EOL;
    echo PHP_EOL;
    echo "USER MANAGEMENT CSP FIX: APPLIED" . PHP_EOL;
    echo PHP_EOL;
    echo "Next:" . PHP_EOL;
    echo "php artisan view:clear" . PHP_EOL;
    echo "php artisan optimize:clear" . PHP_EOL;
    echo "php artisan test" . PHP_EOL;

} catch (Throwable $exception) {

    file_put_contents(
        $viewPath,
        $original
    );

    fwrite(
        STDERR,
        PHP_EOL
        . 'PATCH GAGAL: '
        . $exception->getMessage()
        . PHP_EOL
    );

    fwrite(
        STDERR,
        'users/index.blade.php otomatis dikembalikan ke kondisi sebelum patch.'
        . PHP_EOL
    );

    exit(1);
}
