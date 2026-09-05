<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Throwable;

class PrivateDocumentStorageService
{
    public function isSafeRelativePath(
        ?string $relativePath
    ): bool {

        return $this->normalizeRelativePath(
            $relativePath
        ) !== null;
    }


    /**
     * Resolve file hanya jika canonical realpath masih berada
     * di dalam root private disk.
     */
    public function resolveExistingFile(
        ?string $relativePath,
        string $diskName = 'bmc_documents'
    ): ?string {

        $normalizedPath =
            $this->normalizeRelativePath(
                $relativePath
            );

        if ($normalizedPath === null) {
            return null;
        }

        /*
        | BMC SmartFind saat ini memakai local disk / mapped network drive.
        | Disk non-local ditolak karena boundary check ini bergantung realpath.
        */

        $driver =
            strtolower(
                (string) config(
                    'filesystems.disks.'
                    . $diskName
                    . '.driver',
                    ''
                )
            );

        if ($driver !== 'local') {
            return null;
        }

        try {

            $disk =
                Storage::disk(
                    $diskName
                );

            $declaredRoot =
                $disk->path('');

            $candidatePath =
                $disk->path(
                    $normalizedPath
                );

        } catch (Throwable) {

            return null;
        }

        $canonicalRoot =
            realpath(
                $declaredRoot
            );

        $canonicalFile =
            realpath(
                $candidatePath
            );

        if (
            $canonicalRoot === false
            || $canonicalFile === false
            || ! is_dir(
                $canonicalRoot
            )
            || ! is_file(
                $canonicalFile
            )
        ) {
            return null;
        }

        /*
        | Symlink/junction escape otomatis ditolak:
        | realpath target harus tetap di bawah canonical root.
        */

        if (
            ! $this->isInsideRoot(
                $canonicalRoot,
                $canonicalFile
            )
        ) {
            return null;
        }

        return $canonicalFile;
    }


    private function normalizeRelativePath(
        ?string $relativePath
    ): ?string {

        if (
            ! is_string(
                $relativePath
            )
            || $relativePath === ''
        ) {
            return null;
        }

        if (
            trim(
                $relativePath
            ) !== $relativePath
        ) {
            return null;
        }

        if (
            preg_match(
                '/[\x00-\x1F\x7F]/u',
                $relativePath
            ) === 1
        ) {
            return null;
        }

        $normalized =
            str_replace(
                '\\',
                '/',
                $relativePath
            );

        /*
        |--------------------------------------------------------------------------
        | Absolute / UNC / Scheme / ADS / Encoded Path Protection
        |--------------------------------------------------------------------------
        */

        if (
            str_starts_with(
                $normalized,
                '/'
            )
            || preg_match(
                '/^[A-Za-z]:\//',
                $normalized
            ) === 1
            || str_starts_with(
                $normalized,
                '//'
            )
            || str_contains(
                $normalized,
                '://'
            )
            || str_contains(
                $normalized,
                ':'
            )
            || str_contains(
                $normalized,
                '%'
            )
        ) {
            return null;
        }

        $segments =
            explode(
                '/',
                $normalized
            );

        foreach (
            $segments
            as $segment
        ) {
            if (
                $segment === ''
                || $segment === '.'
                || $segment === '..'
            ) {
                return null;
            }

            /*
            | Windows dapat mengabaikan trailing dot/space.
            | Generated SmartFind paths tidak membutuhkannya.
            */

            if (
                rtrim(
                    $segment,
                    " ."
                ) !== $segment
            ) {
                return null;
            }
        }

        return implode(
            '/',
            $segments
        );
    }


    private function isInsideRoot(
        string $canonicalRoot,
        string $canonicalFile
    ): bool {

        $root =
            $this->normalizeForComparison(
                $canonicalRoot
            );

        $file =
            $this->normalizeForComparison(
                $canonicalFile
            );

        if (
            $root === ''
            || $file === ''
        ) {
            return false;
        }

        $rootPrefix =
            rtrim(
                $root,
                '/'
            )
            . '/';

        return str_starts_with(
            $file,
            $rootPrefix
        );
    }


    private function normalizeForComparison(
        string $path
    ): string {

        $normalized =
            rtrim(
                str_replace(
                    '\\',
                    '/',
                    $path
                ),
                '/'
            );

        if (
            PHP_OS_FAMILY === 'Windows'
        ) {
            $normalized =
                mb_strtolower(
                    $normalized
                );
        }

        return $normalized;
    }
}
