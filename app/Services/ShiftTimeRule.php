<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

final class ShiftTimeRule
{
    /**
     * Rentang menggunakan batas akhir eksklusif agar waktu pergantian shift
     * hanya dimiliki oleh satu shift.
     *
     * @var array<int, array{start_minutes: int, end_minutes: int, label: string}>
     */
    private const RANGES = [
        1 => [
            'start_minutes' => 7 * 60,
            'end_minutes' => 16 * 60,
            'label' => '07:00–15:59',
        ],
        2 => [
            'start_minutes' => 16 * 60,
            'end_minutes' => 23 * 60,
            'label' => '16:00–22:59',
        ],
        3 => [
            'start_minutes' => 23 * 60,
            'end_minutes' => 7 * 60,
            'label' => '23:00–06:59',
        ],
    ];

    public static function validate(int|string|null $shift, ?string $time): void
    {
        if ($time === null || $time === '') {
            return;
        }

        if ($shift === null || $shift === '') {
            throw ValidationException::withMessages([
                'shift' => 'Pilih Shift ketika Jam diisi.',
            ]);
        }

        if (self::allows($shift, $time)) {
            return;
        }

        $label = self::rangeLabel($shift);

        if ($label === null) {
            throw ValidationException::withMessages([
                'shift' => 'Shift yang dipilih tidak valid.',
            ]);
        }

        throw ValidationException::withMessages([
            'scan_time' => "Jam {$time} tidak sesuai dengan Shift {$shift} ({$label}).",
        ]);
    }

    public static function allows(int|string|null $shift, ?string $time): bool
    {
        $range = self::range($shift);

        if (
            $range === null
            || $time === null
            || preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $time) !== 1
        ) {
            return false;
        }

        [$hours, $minutes] = array_map('intval', explode(':', $time));
        $timeMinutes = ($hours * 60) + $minutes;
        $start = $range['start_minutes'];
        $end = $range['end_minutes'];

        if ($start < $end) {
            return $timeMinutes >= $start && $timeMinutes < $end;
        }

        return $timeMinutes >= $start || $timeMinutes < $end;
    }

    public static function rangeLabel(int|string|null $shift): ?string
    {
        return self::range($shift)['label'] ?? null;
    }

    /**
     * @return array<int, array{start_minutes: int, end_minutes: int, label: string}>
     */
    public static function frontendRanges(): array
    {
        return self::RANGES;
    }

    /**
     * @return array{start_minutes: int, end_minutes: int, label: string}|null
     */
    private static function range(int|string|null $shift): ?array
    {
        if ($shift === null || ! in_array((string) $shift, ['1', '2', '3'], true)) {
            return null;
        }

        return self::RANGES[(int) $shift];
    }
}
