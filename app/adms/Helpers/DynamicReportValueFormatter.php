<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Infere o tipo de cada coluna pelo valor retornado (não pelo alias)
 * e formata para exibição em pt-BR.
 */
final class DynamicReportValueFormatter
{
    private const ISO_DATE = '/^(\d{4})-(\d{2})-(\d{2})(?:[T\s](\d{2}):(\d{2}):(\d{2})(?:\.\d+)?)?(?:Z|[+-]\d{2}:?\d{2})?$/';

    /**
     * @param list<array<string, mixed>> $rows
     * @return array<string, string> header => date|datetime|month|money|number|integer|text
     */
    public static function inferColumnTypes(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $types = [];
        foreach (array_keys($rows[0]) as $header) {
            $values = [];
            foreach ($rows as $row) {
                if (is_array($row) && array_key_exists($header, $row)) {
                    $values[] = $row[$header];
                }
            }
            $types[$header] = self::inferType($values);
        }

        return $types;
    }

    /**
     * @param list<mixed> $values
     */
    public static function inferType(array $values): string
    {
        $samples = [];
        foreach ($values as $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if (is_array($value) || is_object($value)) {
                return 'text';
            }
            $samples[] = $value;
        }

        if ($samples === []) {
            return 'text';
        }

        $dates = 0;
        $months = 0;
        $datetimes = 0;
        $nums = 0;
        $maxDecimals = 0;
        $hasFraction = false;

        foreach ($samples as $value) {
            $iso = self::parseIso((string) $value);
            if ($iso !== null) {
                $dates++;
                if ($iso['day'] === '01' && $iso['midnight']) {
                    $months++;
                }
                if (!$iso['midnight']) {
                    $datetimes++;
                }
                continue;
            }

            $number = self::parseNumber($value);
            if ($number === null) {
                return 'text';
            }
            $nums++;
            $decimals = self::decimalPlaces($value, $number);
            if ($decimals > $maxDecimals) {
                $maxDecimals = $decimals;
            }
            if ($decimals > 0) {
                $hasFraction = true;
            }
        }

        $n = count($samples);
        if ($dates === $n) {
            if ($months === $n) {
                return 'month';
            }
            return $datetimes > 0 ? 'datetime' : 'date';
        }
        if ($nums === $n) {
            if (!$hasFraction) {
                return 'integer';
            }
            return $maxDecimals <= 2 ? 'money' : 'number';
        }

        return 'text';
    }

    public static function formatCell(mixed $value, string $type): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_array($value) || is_object($value)) {
            return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $iso = self::parseIso(trim((string) $value));
        if ($type === 'month' && $iso !== null) {
            return $iso['month'] . '/' . $iso['year'];
        }
        if ($type === 'datetime' && $iso !== null) {
            return $iso['day'] . '/' . $iso['month'] . '/' . $iso['year']
                . ' ' . $iso['hour'] . ':' . $iso['minute'];
        }
        if ($type === 'date' && $iso !== null) {
            return $iso['day'] . '/' . $iso['month'] . '/' . $iso['year'];
        }

        if (in_array($type, ['money', 'number'], true)) {
            $number = self::parseNumber($value);
            if ($number !== null) {
                if ($type === 'money') {
                    return 'R$ ' . number_format($number, 2, ',', '.');
                }
                $decimals = min(6, max(1, self::decimalPlaces($value, $number)));
                return number_format($number, $decimals, ',', '.');
            }
        }

        return trim((string) $value);
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    public static function formatRows(array $rows): array
    {
        $types = self::inferColumnTypes($rows);
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                $out[] = $row;
                continue;
            }
            $formatted = [];
            foreach ($row as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $formatted[$key] = $value;
                    continue;
                }
                $formatted[$key] = self::formatCell($value, $types[$key] ?? 'text');
            }
            $out[] = $formatted;
        }

        return $out;
    }

    /**
     * @param list<mixed> $labels
     * @return list<string>
     */
    public static function formatLabels(array $labels): array
    {
        $type = self::inferType($labels);
        $out = [];
        foreach ($labels as $label) {
            $out[] = self::formatCell($label, $type);
        }

        return $out;
    }

    /**
     * @return array{year:string,month:string,day:string,hour:string,minute:string,midnight:bool}|null
     */
    private static function parseIso(string $value): ?array
    {
        if (!preg_match(self::ISO_DATE, trim($value), $m)) {
            return null;
        }

        $hour = $m[4] ?? '00';
        $minute = $m[5] ?? '00';
        $second = $m[6] ?? '00';
        $midnight = ($hour === '00' && $minute === '00' && $second === '00');

        return [
            'year' => $m[1],
            'month' => $m[2],
            'day' => $m[3],
            'hour' => $hour,
            'minute' => $minute,
            'midnight' => $midnight,
        ];
    }

    private static function parseNumber(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        if (!is_string($value)) {
            return null;
        }
        $s = trim(str_replace(' ', '', $value));
        if ($s === '' || preg_match(self::ISO_DATE, $s)) {
            return null;
        }
        if (!preg_match('/^-?\d+(\.\d+)?$/', $s)) {
            return null;
        }

        return (float) $s;
    }

    private static function decimalPlaces(mixed $raw, float $number): int
    {
        if (is_int($raw)) {
            return 0;
        }
        if (is_string($raw) && preg_match('/^-?\d+\.(\d+)$/', trim($raw), $m)) {
            return strlen(rtrim($m[1], '0'));
        }
        for ($d = 0; $d <= 6; $d++) {
            $factor = 10 ** $d;
            if (abs($number - (round($number * $factor) / $factor)) < 1e-8) {
                return $d;
            }
        }

        return 6;
    }
}
