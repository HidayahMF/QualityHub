<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| BMC QualityHub - Archive Confirmation CSP Fix V2
|--------------------------------------------------------------------------
|
| Target:
| resources/views/documents/edit.blade.php
|
| Fix:
| - Menghapus submit interception Alpine lama pada form Archive.
| - Mengganti modal Alpine inline-object dengan modal JS nonced yang CSP-safe.
| - Backend archive TIDAK diubah.
| - Backup + rollback otomatis jika marker penting tidak ditemukan.
|
*/

$root = dirname(__DIR__);

$viewPath =
    $root
    . DIRECTORY_SEPARATOR . 'resources'
    . DIRECTORY_SEPARATOR . 'views'
    . DIRECTORY_SEPARATOR . 'documents'
    . DIRECTORY_SEPARATOR . 'edit.blade.php';

if (! is_file($viewPath)) {
    fwrite(
        STDERR,
        "View tidak ditemukan: {$viewPath}" . PHP_EOL
    );
    exit(1);
}

$original = file_get_contents($viewPath);

if ($original === false) {
    fwrite(STDERR, "Gagal membaca edit.blade.php." . PHP_EOL);
    exit(1);
}

$eol = str_contains($original, "\r\n") ? "\r\n" : "\n";

$content = str_replace(
    ["\r\n", "\r"],
    "\n",
    $original
);

$backupDir =
    $root
    . DIRECTORY_SEPARATOR . 'storage'
    . DIRECTORY_SEPARATOR . 'backups'
    . DIRECTORY_SEPARATOR . 'archive-confirm-before-csp-fix-v2-'
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
    . 'edit.blade.php';

copy($viewPath, $backupPath);

echo PHP_EOL;
echo "=== BMC QualityHub Archive Confirmation CSP Fix V2 ===" . PHP_EOL;

try {
    /*
    |--------------------------------------------------------------------------
    | 1. Archive form: Alpine submit -> data attributes
    |--------------------------------------------------------------------------
    */

    $alreadyFormFixed =
        str_contains(
            $content,
            'data-qualityhub-confirm="archive"'
        );

    if (! $alreadyFormFixed) {
        $pattern =
            '/\s*x-on:submit\.prevent="\$dispatch\('
            . "'qualityhub-confirm'"
            . ',\s*\{\s*'
            . 'form:\s*\$event\.currentTarget,\s*'
            . "title:\s*'Arsipkan Dokumen\?',\s*"
            . "message:\s*'Dokumen akan dipindahkan dari pustaka aktif ke arsip\. File fisik dan seluruh riwayat revisi tetap tersimpan\.',\s*"
            . "confirmText:\s*'Arsipkan Dokumen'\s*"
            . '\}\)"'
            . '/s';

        $replacement = <<<'BLADE'

                        data-qualityhub-confirm="archive"
                        data-confirm-title="Arsipkan Dokumen?"
                        data-confirm-message="Dokumen akan dipindahkan dari pustaka aktif ke arsip. File fisik dan seluruh riwayat revisi tetap tersimpan."
                        data-confirm-button="Arsipkan Dokumen"
BLADE;

        $count = 0;

        $content = preg_replace(
            $pattern,
            $replacement,
            $content,
            1,
            $count
        );

        if ($content === null) {
            throw new RuntimeException(
                'Regex form archive gagal diproses.'
            );
        }

        if ($count !== 1) {
            throw new RuntimeException(
                "Marker submit Archive ditemukan {$count} kali."
            );
        }

        echo "[OK] Submit Archive tidak lagi memakai expression Alpine lama." . PHP_EOL;
    } else {
        echo "[OK] Form Archive sudah CSP-safe." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 2. Replace / refresh confirmation modal at bottom of page
    |--------------------------------------------------------------------------
    |
    | Bagian ini sengaja selalu me-refresh modal jika:
    | - masih memakai modal Alpine lama, atau
    | - sudah memakai modal CSP-safe versi sebelumnya.
    |
    | Dengan begitu script V2 juga bisa memperbaiki hasil patch lama yang
    | masih memiliki konflik Tailwind "hidden" + "flex" pada elemen yang sama.
    |
    */

    $legacyModalMarker =
        '{{-- BMC QUALITYHUB CUSTOM CONFIRMATION MODAL --}}';

    $cspModalMarker =
        '{{-- BMC QUALITYHUB CSP-SAFE ARCHIVE CONFIRMATION';

    $titlePos = false;

    $cspPos =
        strpos(
            $content,
            $cspModalMarker
        );

    if ($cspPos !== false) {
        $titlePos = $cspPos;
    } else {
        $legacyPos =
            strpos(
                $content,
                $legacyModalMarker
            );

        if ($legacyPos !== false) {
            $titlePos = $legacyPos;
        }
    }

    if ($titlePos === false) {
        throw new RuntimeException(
            'Marker modal konfirmasi lama / CSP-safe tidak ditemukan.'
        );
    }

    $separator =
        '{{-- ========================================================= --}}';

    $modalStart =
        strrpos(
            substr(
                $content,
                0,
                $titlePos
            ),
            $separator
        );

    if ($modalStart === false) {
        $modalStart = $titlePos;
    }

    $layoutClose =
        strrpos(
            $content,
            '</x-app-layout>'
        );

    if ($layoutClose === false) {
        throw new RuntimeException(
            'Penutup </x-app-layout> tidak ditemukan.'
        );
    }

    if ($layoutClose <= $modalStart) {
        throw new RuntimeException(
            'Posisi modal konfirmasi tidak valid.'
        );
    }

    $newModal = <<<'BLADE'
    {{-- ========================================================= --}}
    {{-- BMC QUALITYHUB CSP-SAFE ARCHIVE CONFIRMATION V2 --}}
    {{-- Pure DOM + nonced script; tidak bergantung pada Alpine expression --}}
    {{-- ========================================================= --}}

    <div
        id="qualityhub-archive-confirm"
        class="hidden fixed inset-0 z-[100] overflow-y-auto p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="qualityhub-archive-confirm-title"
        aria-describedby="qualityhub-archive-confirm-description"
        aria-hidden="true"
    >
        <div
            data-qualityhub-confirm-backdrop
            class="fixed inset-0 bg-slate-950/45 backdrop-blur-[2px]"
        ></div>

        <div
            class="relative z-10 flex min-h-full items-center justify-center"
        >
            <section
                class="w-full max-w-md overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-2xl shadow-blue-950/20"
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
                                id="qualityhub-archive-confirm-title"
                                class="mt-1.5 text-xl font-bold text-slate-900"
                            >
                                Arsipkan Dokumen?
                            </h3>

                            <p
                                id="qualityhub-archive-confirm-description"
                                data-qualityhub-confirm-message
                                class="mt-2 text-sm leading-6 text-slate-600"
                            >
                                Dokumen akan dipindahkan dari pustaka aktif ke arsip.
                                File fisik dan seluruh riwayat revisi tetap tersimpan.
                            </p>

                        </div>
                    </div>

                    <div
                        class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"
                    >
                        <button
                            type="button"
                            data-qualityhub-confirm-cancel
                            class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2"
                        >
                            Batal
                        </button>

                        <button
                            type="button"
                            data-qualityhub-confirm-submit
                            class="inline-flex items-center justify-center rounded-xl bg-blue-800 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-900/15 transition hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        >
                            Arsipkan Dokumen
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </div>


    <script nonce="{{ $cspNonce }}">
        (() => {
            const modal =
                document.getElementById(
                    'qualityhub-archive-confirm'
                );

            if (!modal) {
                return;
            }

            const title =
                modal.querySelector(
                    '#qualityhub-archive-confirm-title'
                );

            const message =
                modal.querySelector(
                    '[data-qualityhub-confirm-message]'
                );

            const submitButton =
                modal.querySelector(
                    '[data-qualityhub-confirm-submit]'
                );

            const cancelButton =
                modal.querySelector(
                    '[data-qualityhub-confirm-cancel]'
                );

            const backdrop =
                modal.querySelector(
                    '[data-qualityhub-confirm-backdrop]'
                );

            let pendingForm = null;
            let returnFocusElement = null;

            const hideModal = () => {
                modal.classList.add('hidden');

                modal.setAttribute(
                    'aria-hidden',
                    'true'
                );

                document.body.classList.remove(
                    'overflow-hidden'
                );
            };

            const closeModal = () => {
                hideModal();

                pendingForm = null;

                if (
                    returnFocusElement
                    && typeof returnFocusElement.focus === 'function'
                ) {
                    returnFocusElement.focus();
                }

                returnFocusElement = null;
            };

            const openModal = (form) => {
                pendingForm = form;

                returnFocusElement =
                    document.activeElement;

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

                modal.setAttribute(
                    'aria-hidden',
                    'false'
                );

                document.body.classList.add(
                    'overflow-hidden'
                );

                submitButton.focus();
            };

            document
                .querySelectorAll(
                    'form[data-qualityhub-confirm="archive"]'
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

                    hideModal();

                    pendingForm = null;
                    returnFocusElement = null;

                    form.requestSubmit();
                }
            );
        })();
    </script>


BLADE;

    $content =
        substr(
            $content,
            0,
            $modalStart
        )
        . $newModal
        . substr(
            $content,
            $layoutClose
        );

    echo "[OK] Modal konfirmasi diperbarui ke CSP-safe V2 tanpa konflik hidden/flex." . PHP_EOL;


    /*
    |--------------------------------------------------------------------------
    | 3. Safety guards
    |--------------------------------------------------------------------------
    */

    if (
        str_contains(
            $content,
            'x-on:submit.prevent="$dispatch(\'qualityhub-confirm\''
        )
    ) {
        throw new RuntimeException(
            'Submit interceptor Alpine lama masih ditemukan di edit.blade.php.'
        );
    }

    if (
        str_contains(
            $content,
            'x-data="{'
        )
        && str_contains(
            $content,
            'qualityhub-confirm'
        )
    ) {
        throw new RuntimeException(
            'Modal Alpine object lama masih terdeteksi.'
        );
    }

    if (
        str_contains(
            $content,
            'class="hidden fixed inset-0 z-[100] flex'
        )
    ) {
        throw new RuntimeException(
            'Konflik Tailwind hidden + flex masih ditemukan.'
        );
    }

    if (
        ! str_contains(
            $content,
            'class="relative z-10 flex min-h-full items-center justify-center"'
        )
    ) {
        throw new RuntimeException(
            'Wrapper flex positioner modal tidak ditemukan.'
        );
    }

    if (
        ! str_contains(
            $content,
            'nonce="{{ $cspNonce }}"'
        )
    ) {
        throw new RuntimeException(
            'Script modal baru tidak memiliki CSP nonce.'
        );
    }

    if (
        ! str_contains(
            $content,
            'form.requestSubmit();'
        )
    ) {
        throw new RuntimeException(
            'Submit final CSP-safe tidak ditemukan.'
        );
    }

    echo "[OK] Guard CSP-safe PASS." . PHP_EOL;


    /*
    |--------------------------------------------------------------------------
    | Save with original Windows/Linux line ending
    |--------------------------------------------------------------------------
    */

    if ($eol === "\r\n") {
        $content =
            str_replace(
                "\n",
                "\r\n",
                $content
            );
    }

    file_put_contents(
        $viewPath,
        $content
    );

    echo PHP_EOL;
    echo "Backup:" . PHP_EOL;
    echo $backupPath . PHP_EOL;
    echo PHP_EOL;
    echo "ARCHIVE CONFIRMATION CSP FIX V2: APPLIED" . PHP_EOL;
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
        'edit.blade.php otomatis dikembalikan ke kondisi sebelum patch.'
        . PHP_EOL
    );

    exit(1);
}
