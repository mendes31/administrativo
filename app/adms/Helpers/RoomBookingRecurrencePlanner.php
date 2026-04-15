<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Gera intervalos de uma série semanal com data final (inclusiva no dia).
 *
 * @phpstan-type Occurrence array{0: string, 1: string}
 */
final class RoomBookingRecurrencePlanner
{
    public const MAX_OCCURRENCES = 104;

    /**
     * @return list<Occurrence> pares [start Y-m-d H:i:s, end Y-m-d H:i:s]
     */
    public static function buildWeeklySeries(string $firstStartSql, string $firstEndSql, string $untilDateYmd): array
    {
        $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $firstStartSql);
        $end = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $firstEndSql);
        if (!$start || !$end || $end <= $start) {
            return [];
        }
        $until = \DateTimeImmutable::createFromFormat('Y-m-d', $untilDateYmd);
        if (!$until) {
            return [];
        }
        $untilDayEnd = $until->setTime(23, 59, 59);
        $durationSec = $end->getTimestamp() - $start->getTimestamp();
        if ($durationSec <= 0) {
            return [];
        }

        $out = [];
        $cur = $start;
        while ($cur->format('Y-m-d') <= $untilDayEnd->format('Y-m-d')) {
            $slotEnd = $cur->setTimestamp($cur->getTimestamp() + $durationSec);
            $out[] = [$cur->format('Y-m-d H:i:s'), $slotEnd->format('Y-m-d H:i:s')];
            if (count($out) >= self::MAX_OCCURRENCES) {
                break;
            }
            $cur = $cur->modify('+7 days');
        }

        return $out;
    }

    /**
     * Próximas ocorrências semanais a partir da última data de início conhecida (exclusiva do próprio +7d).
     *
     * @return list<Occurrence>
     */
    public static function buildWeeklyContinuation(
        string $lastOccurrenceStartSql,
        string $lastOccurrenceEndSql,
        string $newUntilDateYmd
    ): array {
        $lastStart = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $lastOccurrenceStartSql);
        $lastEnd = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $lastOccurrenceEndSql);
        if (!$lastStart || !$lastEnd || $lastEnd <= $lastStart) {
            return [];
        }
        $next = $lastStart->modify('+7 days');
        $durationSec = $lastEnd->getTimestamp() - $lastStart->getTimestamp();

        $until = \DateTimeImmutable::createFromFormat('Y-m-d', $newUntilDateYmd);
        if (!$until) {
            return [];
        }
        $untilDayEnd = $until->setTime(23, 59, 59);

        $out = [];
        $cur = $next;
        while ($cur->format('Y-m-d') <= $untilDayEnd->format('Y-m-d')) {
            $slotEnd = $cur->setTimestamp($cur->getTimestamp() + $durationSec);
            $out[] = [$cur->format('Y-m-d H:i:s'), $slotEnd->format('Y-m-d H:i:s')];
            if (count($out) >= self::MAX_OCCURRENCES) {
                break;
            }
            $cur = $cur->modify('+7 days');
        }

        return $out;
    }
}
