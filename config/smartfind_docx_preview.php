<?php

return [
    // Lokasi LibreOffice pada mesin yang MENJALANKAN Laravel, bukan komputer user.
    'binary' => env(
        'SMARTFIND_LIBREOFFICE_BINARY',
        PHP_OS_FAMILY === 'Windows'
            ? 'C:\\Program Files\\LibreOffice\\program\\soffice.exe'
            : '/usr/bin/libreoffice'
    ),
    'timeout' => (int) env('SMARTFIND_DOCX_PREVIEW_TIMEOUT', 60),
    'max_source_bytes' => 20 * 1024 * 1024,
    'max_pdf_bytes' => 40 * 1024 * 1024,
];
