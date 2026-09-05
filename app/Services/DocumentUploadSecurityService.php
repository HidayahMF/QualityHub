<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Throwable;
use ZipArchive;

class DocumentUploadSecurityService
{
    /**
     * Validasi file berbasis:
     *
     * - extension whitelist
     * - server-side MIME
     * - file signature / magic bytes
     * - struktur OOXML XLSX / DOCX
     * - struktur OLE XLS / DOC
     * - text/binary check CSV / TXT
     * - macro payload check
     *
     * File tidak pernah dieksekusi.
     */
    public function validate(
        UploadedFile $file
    ): array {

        if (! $file->isValid()) {
            $this->reject(
                'File yang diunggah tidak valid.'
            );
        }

        $extension =
            strtolower(
                trim(
                    (string) $file
                        ->getClientOriginalExtension()
                )
            );

        $supportedExtensions =
            array_map(
                'strtolower',
                config(
                    'smartfind_upload_security.supported_extensions',
                    [
                        'pdf',
                        'xls',
                        'xlsx',
                        'doc',
                        'docx',
                        'csv',
                        'txt',
                    ]
                )
            );

        if (
            $extension === ''
            || ! in_array(
                $extension,
                $supportedExtensions,
                true
            )
        ) {
            $this->reject(
                'Jenis file tidak didukung oleh BMC SmartFind.'
            );
        }

        $size =
            (int) (
                $file->getSize()
                ?: 0
            );

        $maxBytes =
            max(
                1,
                (int) config(
                    'smartfind_upload_security.max_file_size_bytes',
                    20 * 1024 * 1024
                )
            );

        if (
            $size <= 0
            || $size > $maxBytes
        ) {
            $this->reject(
                'Ukuran file tidak valid atau melebihi batas upload.'
            );
        }

        $realPath =
            $file->getRealPath();

        if (
            ! is_string($realPath)
            || $realPath === ''
            || ! is_file($realPath)
            || ! is_readable($realPath)
        ) {
            $this->reject(
                'File upload tidak dapat dibaca untuk pemeriksaan keamanan.'
            );
        }

        $serverMime =
            strtolower(
                trim(
                    (string) (
                        $file->getMimeType()
                        ?: 'application/octet-stream'
                    )
                )
            );

        $mimeStatus =
            $this->validateMime(
                $extension,
                $serverMime
            );

        $signatureResult =
            match ($extension) {

                'pdf' =>
                    $this->validatePdf(
                        $realPath
                    ),

                'xlsx' =>
                    $this->validateXlsx(
                        $realPath
                    ),

                'docx' =>
                    $this->validateDocx(
                        $realPath
                    ),

                'xls' =>
                    $this->validateLegacyXls(
                        $realPath
                    ),

                'doc' =>
                    $this->validateLegacyDoc(
                        $realPath
                    ),

                'csv',
                'txt' =>
                    $this->validateTextFile(
                        $realPath,
                        $extension
                    ),

                default =>
                    throw new RuntimeException(
                        'Extension security validator tidak dikenali.'
                    ),
            };

        return [
            'extension' =>
                $extension,

            'server_mime' =>
                $serverMime,

            'mime_status' =>
                $mimeStatus,

            'signature' =>
                $signatureResult[
                    'signature'
                ],

            'structure' =>
                $signatureResult[
                    'structure'
                ],

            'macro_check' =>
                $signatureResult[
                    'macro_check'
                ],

            'file_size' =>
                $size,

            'original_name' =>
                $this->sanitizeOriginalName(
                    $file->getClientOriginalName(),
                    $extension
                ),
        ];
    }


    /**
     * MIME adalah lapisan kedua.
     *
     * application/octet-stream / generic container hanya diterima untuk
     * format yang memang sering terdeteksi generic oleh fileinfo,
     * tetapi tetap WAJIB lolos signature/structure check.
     */
    private function validateMime(
        string $extension,
        string $mime
    ): string {

        $allowedMimeMap =
            config(
                'smartfind_upload_security.allowed_mimes',
                []
            );

        $allowedMimes =
            array_map(
                'strtolower',
                (array) (
                    $allowedMimeMap[
                        $extension
                    ]
                    ?? []
                )
            );

        if (
            in_array(
                $mime,
                $allowedMimes,
                true
            )
        ) {
            return 'matched';
        }

        $genericFallbacks =
            array_map(
                'strtolower',
                (array) config(
                    'smartfind_upload_security.generic_mime_fallbacks',
                    [
                        'application/octet-stream',
                    ]
                )
            );

        if (
            in_array(
                $mime,
                $genericFallbacks,
                true
            )
            && in_array(
                $extension,
                [
                    'xls',
                    'xlsx',
                    'doc',
                    'docx',
                ],
                true
            )
        ) {
            return 'generic_signature_required';
        }

        $this->reject(
            'MIME file tidak sesuai dengan jenis file '
            . strtoupper($extension)
            . '.'
        );
    }


    private function validatePdf(
        string $path
    ): array {

        $header =
            $this->readBytes(
                $path,
                8
            );

        if (
            ! str_starts_with(
                $header,
                '%PDF-'
            )
        ) {
            $this->reject(
                'File ber-extension PDF tidak memiliki signature PDF yang valid.'
            );
        }

        return [
            'signature' =>
                'pdf_magic',

            'structure' =>
                'pdf',

            'macro_check' =>
                'not_applicable',
        ];
    }


    private function validateXlsx(
        string $path
    ): array {

        $zip =
            $this->openZip(
                $path
            );

        try {

            $requiredEntries = [
                '[Content_Types].xml',
                'xl/workbook.xml',
            ];

            foreach (
                $requiredEntries
                as $entry
            ) {
                if (
                    $zip->locateName(
                        $entry,
                        ZipArchive::FL_NOCASE
                    ) === false
                ) {
                    $this->reject(
                        'Struktur XLSX tidak valid.'
                    );
                }
            }

            $this->rejectMacroEntries(
                $zip,
                [
                    'xl/vbaProject.bin',
                    'xl/vbaData.xml',
                ],
                'XLSX'
            );

        } finally {

            $zip->close();
        }

        /*
        | PhpSpreadsheet identify adalah verifikasi tambahan:
        | file harus benar-benar dapat dikenali sebagai spreadsheet.
        */

        try {

            $readerType =
                IOFactory::identify(
                    $path
                );

        } catch (Throwable) {

            $this->reject(
                'File XLSX tidak dapat dikenali sebagai spreadsheet yang valid.'
            );
        }

        if (
            strtolower(
                (string) $readerType
            ) !== 'xlsx'
        ) {
            $this->reject(
                'Isi file tidak sesuai dengan extension XLSX.'
            );
        }

        return [
            'signature' =>
                'zip_ooxml',

            'structure' =>
                'xlsx',

            'macro_check' =>
                'passed',
        ];
    }


    private function validateDocx(
        string $path
    ): array {

        $zip =
            $this->openZip(
                $path
            );

        try {

            $requiredEntries = [
                '[Content_Types].xml',
                'word/document.xml',
            ];

            foreach (
                $requiredEntries
                as $entry
            ) {
                if (
                    $zip->locateName(
                        $entry,
                        ZipArchive::FL_NOCASE
                    ) === false
                ) {
                    $this->reject(
                        'Struktur DOCX tidak valid.'
                    );
                }
            }

            $this->rejectMacroEntries(
                $zip,
                [
                    'word/vbaProject.bin',
                    'word/vbaData.xml',
                ],
                'DOCX'
            );

        } finally {

            $zip->close();
        }

        return [
            'signature' =>
                'zip_ooxml',

            'structure' =>
                'docx',

            'macro_check' =>
                'passed',
        ];
    }


    private function validateLegacyXls(
        string $path
    ): array {

        $binary =
            $this->readBinaryForOleValidation(
                $path
            );

        $this->validateOleMagic(
            $binary,
            'XLS'
        );

        $hasWorkbookStream =
            str_contains(
                $binary,
                $this->utf16Le(
                    'Workbook'
                )
            )
            || str_contains(
                $binary,
                $this->utf16Le(
                    'Book'
                )
            );

        if (! $hasWorkbookStream) {
            $this->reject(
                'Isi file tidak sesuai dengan extension XLS.'
            );
        }

        $this->rejectLegacyMacroProject(
            $binary,
            'XLS'
        );

        /*
        | Verifikasi tambahan oleh PhpSpreadsheet.
        */

        try {

            $readerType =
                IOFactory::identify(
                    $path
                );

        } catch (Throwable) {

            $this->reject(
                'File XLS tidak dapat dikenali sebagai spreadsheet yang valid.'
            );
        }

        if (
            strtolower(
                (string) $readerType
            ) !== 'xls'
        ) {
            $this->reject(
                'Isi file tidak sesuai dengan extension XLS.'
            );
        }

        return [
            'signature' =>
                'ole_compound',

            'structure' =>
                'xls',

            'macro_check' =>
                'passed',
        ];
    }


    private function validateLegacyDoc(
        string $path
    ): array {

        $binary =
            $this->readBinaryForOleValidation(
                $path
            );

        $this->validateOleMagic(
            $binary,
            'DOC'
        );

        $hasWordDocumentStream =
            str_contains(
                $binary,
                $this->utf16Le(
                    'WordDocument'
                )
            );

        if (! $hasWordDocumentStream) {
            $this->reject(
                'Isi file tidak sesuai dengan extension DOC.'
            );
        }

        $this->rejectLegacyMacroProject(
            $binary,
            'DOC'
        );

        return [
            'signature' =>
                'ole_compound',

            'structure' =>
                'doc',

            'macro_check' =>
                'passed',
        ];
    }


    private function validateTextFile(
        string $path,
        string $extension
    ): array {

        $sampleBytes =
            max(
                4096,
                (int) config(
                    'smartfind_upload_security.text_sample_bytes',
                    65536
                )
            );

        $sample =
            $this->readBytes(
                $path,
                $sampleBytes
            );

        /*
        | NUL byte merupakan indikator kuat file binary.
        */

        if (
            str_contains(
                $sample,
                "\0"
            )
        ) {
            $this->reject(
                'File '
                . strtoupper($extension)
                . ' terdeteksi sebagai file binary terselubung.'
            );
        }

        /*
        | Block common disguised binary signatures.
        */

        $blockedSignatures = [
            'MZ' =>
                'Windows executable',

            "\x7FELF" =>
                'ELF executable',

            '%PDF-' =>
                'PDF',

            "PK\x03\x04" =>
                'ZIP/OOXML',

            "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1" =>
                'OLE document',
        ];

        foreach (
            $blockedSignatures
            as $magic => $label
        ) {
            if (
                str_starts_with(
                    $sample,
                    $magic
                )
            ) {
                $this->reject(
                    'File '
                    . strtoupper($extension)
                    . ' sebenarnya terdeteksi sebagai '
                    . $label
                    . '.'
                );
            }
        }

        return [
            'signature' =>
                'text_like',

            'structure' =>
                $extension,

            'macro_check' =>
                'not_applicable',
        ];
    }


    private function openZip(
        string $path
    ): ZipArchive {

        $zip =
            new ZipArchive();

        $result =
            $zip->open(
                $path,
                ZipArchive::RDONLY
            );

        if ($result !== true) {
            $this->reject(
                'Container ZIP/OOXML file tidak valid.'
            );
        }

        return $zip;
    }


    private function rejectMacroEntries(
        ZipArchive $zip,
        array $macroEntries,
        string $label
    ): void {

        foreach (
            $macroEntries
            as $entry
        ) {
            if (
                $zip->locateName(
                    $entry,
                    ZipArchive::FL_NOCASE
                ) !== false
            ) {
                $this->reject(
                    $label
                    . ' mengandung payload macro/VBA dan tidak diizinkan.'
                );
            }
        }
    }


    private function validateOleMagic(
        string $binary,
        string $label
    ): void {

        $oleMagic =
            "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";

        if (
            ! str_starts_with(
                $binary,
                $oleMagic
            )
        ) {
            $this->reject(
                'File '
                . $label
                . ' tidak memiliki signature OLE yang valid.'
            );
        }
    }


    private function rejectLegacyMacroProject(
        string $binary,
        string $label
    ): void {

        $macroMarker =
            $this->utf16Le(
                '_VBA_PROJECT_CUR'
            );

        if (
            str_contains(
                $binary,
                $macroMarker
            )
        ) {
            $this->reject(
                $label
                . ' mengandung project VBA/macro dan tidak diizinkan.'
            );
        }
    }


    /**
     * OLE directory stream berada di file compound yang sama.
     * Ukuran upload SmartFind dibatasi 20 MB, jadi pembacaan ini tetap
     * berada dalam batas yang sudah dikontrol.
     */
    private function readBinaryForOleValidation(
        string $path
    ): string {

        $contents =
            file_get_contents(
                $path
            );

        if ($contents === false) {
            $this->reject(
                'File OLE tidak dapat dibaca untuk pemeriksaan keamanan.'
            );
        }

        return $contents;
    }


    private function readBytes(
        string $path,
        int $length
    ): string {

        $handle =
            fopen(
                $path,
                'rb'
            );

        if ($handle === false) {
            $this->reject(
                'File tidak dapat dibaca untuk pemeriksaan signature.'
            );
        }

        try {

            $bytes =
                fread(
                    $handle,
                    $length
                );

        } finally {

            fclose(
                $handle
            );
        }

        if ($bytes === false) {
            $this->reject(
                'Signature file tidak dapat dibaca.'
            );
        }

        return $bytes;
    }


    private function utf16Le(
        string $value
    ): string {

        if (
            function_exists(
                'mb_convert_encoding'
            )
        ) {
            return mb_convert_encoding(
                $value,
                'UTF-16LE',
                'UTF-8'
            );
        }

        /*
        | Fallback ASCII → UTF-16LE untuk stream marker yang dipakai di sini.
        */

        return implode(
            '',
            array_map(
                fn (string $char) =>
                    $char . "\0",
                str_split(
                    $value
                )
            )
        );
    }


    /**
     * Nama asli hanya metadata/display name.
     * Physical filename tetap UUID dari controller.
     */
    private function sanitizeOriginalName(
        string $fileName,
        string $extension
    ): string {

        $fileName =
            basename(
                str_replace(
                    '\\',
                    '/',
                    $fileName
                )
            );

        /*
        | Hapus control characters + Unicode bidi overrides yang dapat
        | menyamarkan extension saat nama ditampilkan.
        */

        $fileName =
            preg_replace(
                '/[\x00-\x1F\x7F\x{202A}-\x{202E}\x{2066}-\x{2069}]/u',
                '',
                $fileName
            )
            ?? '';

        $fileName =
            trim(
                $fileName
            );

        if ($fileName === '') {
            return 'document.'
                . $extension;
        }

        $expectedSuffix =
            '.'
            . $extension;

        if (
            ! str_ends_with(
                strtolower(
                    $fileName
                ),
                $expectedSuffix
            )
        ) {
            $fileName .=
                $expectedSuffix;
        }

        /*
        | Batasi panjang tetapi pertahankan extension.
        */

        $maxLength =
            240;

        if (
            mb_strlen(
                $fileName
            ) > $maxLength
        ) {
            $baseName =
                pathinfo(
                    $fileName,
                    PATHINFO_FILENAME
                );

            $fileName =
                mb_substr(
                    $baseName,
                    0,
                    max(
                        1,
                        $maxLength
                        - mb_strlen(
                            $expectedSuffix
                        )
                    )
                )
                . $expectedSuffix;
        }

        return $fileName;
    }


    private function reject(
        string $message
    ): never {

        throw ValidationException::withMessages([
            'file' =>
                $message,
        ]);
    }
}
