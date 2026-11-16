<?php

namespace App\Services;

/**
 * Bounded, read-only Compound File Binary (OLE/CFB) structure checks.
 *
 * Does not execute or render Office content. Used in addition to the
 * existing upload size, MIME, OLE magic, and VBA checks.
 */
final class LegacyPowerPointInspector
{
    private const FREE = 0xFFFFFFFF;
    private const END = 0xFFFFFFFE;

    public function isPowerPoint(string $bytes): bool
    {
        try {
            return $this->inspect($bytes);
        } catch (\Throwable) {
            // Malformed or truncated untrusted input must be rejected safely.
            return false;
        }
    }

    private function inspect(string $bytes): bool
    {
        $length = strlen($bytes);

        if ($length < 1536
            || substr($bytes, 0, 8) !== "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"
            || $this->u16($bytes, 28) !== 0xFFFE) {
            return false;
        }

        $sectorShift = $this->u16($bytes, 30);
        $majorVersion = $this->u16($bytes, 26);

        if (($sectorShift !== 9 && $sectorShift !== 12)
            || ($sectorShift === 9 && $majorVersion !== 3)
            || ($sectorShift === 12 && $majorVersion !== 4)) {
            return false;
        }

        $sectorSize = 1 << $sectorShift;
        $sectorCount = intdiv($length, $sectorSize) - 1;

        if ($sectorCount < 2) {
            return false;
        }

        $fatCount = $this->u32($bytes, 44);
        $directoryStart = $this->u32($bytes, 48);
        $difatStart = $this->u32($bytes, 68);
        $difatCount = $this->u32($bytes, 72);

        if ($fatCount < 1 || $fatCount > $sectorCount || $difatCount > $sectorCount) {
            return false;
        }

        $fatSectors = [];

        for ($i = 0; $i < 109; $i++) {
            $sector = $this->u32($bytes, 76 + ($i * 4));

            if ($sector !== self::FREE) {
                $fatSectors[] = $sector;
            }
        }

        $seenDifat = [];

        for ($i = 0; $i < $difatCount; $i++) {
            if ($difatStart >= $sectorCount || isset($seenDifat[$difatStart])) {
                return false;
            }

            $seenDifat[$difatStart] = true;
            $chunk = $this->sector($bytes, $difatStart, $sectorSize, $sectorCount);

            for ($j = 0; $j < intdiv($sectorSize, 4) - 1; $j++) {
                $sector = $this->u32($chunk, $j * 4);

                if ($sector !== self::FREE) {
                    $fatSectors[] = $sector;
                }
            }

            $difatStart = $this->u32($chunk, $sectorSize - 4);
        }

        if (count($fatSectors) !== $fatCount
            || count(array_unique($fatSectors)) !== $fatCount) {
            return false;
        }

        $fat = '';

        foreach ($fatSectors as $sector) {
            $fat .= $this->sector($bytes, $sector, $sectorSize, $sectorCount);
        }

        $directory = $this->readChain($bytes, $fat, $directoryStart, $sectorSize, $sectorCount);

        if ($directory === '') {
            return false;
        }

        $rootFound = false;
        $presentation = null;
        $currentUser = false;

        for ($offset = 0; $offset + 128 <= strlen($directory); $offset += 128) {
            $type = ord($directory[$offset + 66]);

            if ($type === 0) {
                continue;
            }

            $nameBytes = $this->u16($directory, $offset + 64);

            if ($nameBytes < 2 || $nameBytes > 64 || ($nameBytes % 2) !== 0) {
                return false;
            }

            $rawName = substr($directory, $offset, $nameBytes - 2);
            $name = strtolower(mb_convert_encoding($rawName, 'UTF-8', 'UTF-16LE'));

            // Disallow VBA projects and their storage/stream indicators.
            if (str_contains($name, 'vba')
                || str_contains($name, 'macro')
                || $name === 'project'
                || str_starts_with($name, '__srp_')) {
                return false;
            }

            if ($type === 5 && $name === 'root entry') {
                $rootFound = true;
            }

            if ($type !== 2) {
                continue;
            }

            $streamSize = $this->u32($directory, $offset + 120);

            if ($this->u32($directory, $offset + 124) !== 0) {
                return false;
            }

            if ($name === 'powerpoint document' && $streamSize >= 8) {
                $presentation = [
                    'start' => $this->u32($directory, $offset + 116),
                    'size' => $streamSize,
                ];
            }

            if ($name === 'current user' && $streamSize >= 16) {
                $currentUser = true;
            }
        }

        if (! $rootFound || ! $currentUser || $presentation === null
            || $presentation['size'] > $length) {
            return false;
        }

        // Large stream payloads must resolve through an actual FAT chain;
        // a fake OLE header with stream names in arbitrary bytes is rejected.
        if ($presentation['size'] >= 4096) {
            $expected = intdiv($presentation['size'] + $sectorSize - 1, $sectorSize);

            if (! $this->validChain($fat, $presentation['start'], $expected, $sectorCount)) {
                return false;
            }
        }

        return true;
    }

    private function readChain(
        string $bytes,
        string $fat,
        int $sector,
        int $sectorSize,
        int $sectorCount
    ): string {
        $result = '';
        $seen = [];

        while ($sector !== self::END) {
            if ($sector >= $sectorCount || isset($seen[$sector])) {
                return '';
            }

            $seen[$sector] = true;
            $result .= $this->sector($bytes, $sector, $sectorSize, $sectorCount);
            $sector = $this->u32($fat, $sector * 4);

            if (count($seen) > $sectorCount) {
                return '';
            }
        }

        return $result;
    }

    private function validChain(string $fat, int $sector, int $expected, int $sectorCount): bool
    {
        $seen = [];

        for ($i = 0; $i < $expected; $i++) {
            if ($sector >= $sectorCount || isset($seen[$sector])) {
                return false;
            }

            $seen[$sector] = true;
            $sector = $this->u32($fat, $sector * 4);
        }

        return $sector === self::END;
    }

    private function sector(string $bytes, int $id, int $size, int $count): string
    {
        if ($id >= $count) {
            throw new \UnexpectedValueException('OLE sector out of bounds');
        }

        $data = substr($bytes, ($id + 1) * $size, $size);

        if (strlen($data) !== $size) {
            throw new \UnexpectedValueException('OLE sector truncated');
        }

        return $data;
    }

    private function u16(string $bytes, int $offset): int
    {
        if (strlen($bytes) < $offset + 2) {
            throw new \UnexpectedValueException('OLE header truncated');
        }

        return unpack('v', substr($bytes, $offset, 2))[1];
    }

    private function u32(string $bytes, int $offset): int
    {
        if (strlen($bytes) < $offset + 4) {
            throw new \UnexpectedValueException('OLE FAT truncated');
        }

        return unpack('V', substr($bytes, $offset, 4))[1];
    }
}
