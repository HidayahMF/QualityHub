<?php

namespace App\Services;

use Illuminate\Support\Str;

class InputSanitizationService
{
    /**
     * Normalisasi field plain-text satu baris.
     *
     * - menghapus blok script/style
     * - menghapus tag HTML
     * - menghapus control / bidi / zero-width characters berisiko
     * - merapikan whitespace
     *
     * Method ini tidak memotong panjang input. Batas panjang tetap ditentukan
     * oleh Laravel validation sehingga input yang terlalu panjang ditolak,
     * bukan diam-diam dipotong.
     */
    public function singleLine(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = (string) $value;
        $value = $this->removeExecutableMarkup($value);
        $value = strip_tags($value);

        // Ubah newline/tab menjadi spasi SEBELUM control character dibuang
        // agar kata tidak menempel (contoh: \"Axle\nAssy\" -> \"Axle Assy\").
        $value = str_replace(
            ["\r\n", "\r", "\n", "\t"],
            ' ',
            $value
        );

        $value = $this->removeUnsafeUnicodeAndControls($value);

        $value = preg_replace('/\s+/u', ' ', $value) ?? '';
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * Normalisasi field plain-text multi-baris seperti description / reason.
     * Newline tetap dipertahankan agar catatan tetap terbaca.
     */
    public function multiline(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = (string) $value;
        $value = $this->removeExecutableMarkup($value);
        $value = strip_tags($value);
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = $this->removeUnsafeUnicodeAndControls($value, preserveNewlines: true);
        $value = str_replace("\t", ' ', $value);

        $lines = array_map(
            static fn (string $line): string => trim(
                preg_replace('/[ ]{2,}/u', ' ', $line) ?? $line
            ),
            explode("\n", $value)
        );

        $value = trim(implode("\n", $lines));

        return $value === '' ? null : $value;
    }

    /**
     * Email disimpan dalam bentuk trim + lowercase.
     */
    public function email(mixed $value): ?string
    {
        $value = $this->singleLine($value);

        return $value === null
            ? null
            : Str::lower($value);
    }

    /**
     * Search term adalah plain-text satu baris.
     */
    public function search(mixed $value): string
    {
        return $this->singleLine($value) ?? '';
    }

    /**
     * Escape wildcard LIKE agar input "%" atau "_" tidak berubah menjadi
     * wildcard database yang dapat memperluas query secara tidak disengaja.
     */
    public function escapeLike(string $value): string
    {
        return str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $value
        );
    }

    private function removeExecutableMarkup(string $value): string
    {
        return preg_replace(
            '#<(script|style)\b[^>]*>.*?</\1\s*>#is',
            '',
            $value
        ) ?? $value;
    }

    private function removeUnsafeUnicodeAndControls(
        string $value,
        bool $preserveNewlines = false
    ): string {
        $controlPattern = $preserveNewlines
            ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{206F}\x{FEFF}]/u'
            : '/[\x00-\x1F\x7F\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{206F}\x{FEFF}]/u';

        return preg_replace(
            $controlPattern,
            '',
            $value
        ) ?? '';
    }
}
