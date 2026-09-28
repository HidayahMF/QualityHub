<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;

final class RetoolingDateRule
{
    public static function resolve(
        DateTimeInterface|string|null $processDate,
        int|string|null $shift,
        ?string $time
    ): ?CarbonImmutable {
        $normalizedTime = self::normalizeTime($time);

        if (
            $normalizedTime === null
            || ! ShiftTimeRule::allows($shift, $normalizedTime)
        ) {
            return null;
        }

        $date = self::normalizeDate($processDate);

        if ($date === null) {
            return null;
        }

        if ((int) $shift === 3 && $normalizedTime < '07:00') {
            return $date->addDay();
        }

        return $date;
    }

    private static function normalizeDate(
        DateTimeInterface|string|null $processDate
    ): ?CarbonImmutable {
        if ($processDate instanceof DateTimeInterface) {
            return CarbonImmutable::instance($processDate)->startOfDay();
        }

        if (
            ! is_string($processDate)
            || preg_match('/^\d{4}-\d{2}-\d{2}$/', $processDate) !== 1
        ) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $processDate);

        if ($date === false || $date->format('Y-m-d') !== $processDate) {
            return null;
        }

        return CarbonImmutable::instance($date);
    }

    private static function normalizeTime(?string $time): ?string
    {
        if (
            $time === null
            || preg_match(
                '/^(?:[01][0-9]|2[0-3]):[0-5][0-9](?::[0-5][0-9])?$/',
                $time
            ) !== 1
        ) {
            return null;
        }

        return substr($time, 0, 5);
    }
}
