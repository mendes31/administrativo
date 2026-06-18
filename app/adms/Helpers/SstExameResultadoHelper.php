<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/** Resultados esperados ao lançar exames complementares no ASO. */
final class SstExameResultadoHelper
{
    public const NORMAL = 'Normal';
    public const ALTERADO = 'Alterado';
    public const APTO = 'Apto';
    public const INAPTO = 'Inapto';
    public const APTO_RESTRICAO = 'Apto com Restrição';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::NORMAL,
            self::ALTERADO,
            self::APTO,
            self::INAPTO,
            self::APTO_RESTRICAO,
        ];
    }

    public static function isValid(?string $value): bool
    {
        return $value !== null && $value !== '' && in_array($value, self::all(), true);
    }

    /**
     * @param list<string> $values
     * @return list<string>
     */
    public static function filterValid(array $values): array
    {
        $out = [];
        foreach ($values as $v) {
            if (!is_string($v) || $v === '') {
                continue;
            }
            if (self::isValid($v) && !in_array($v, $out, true)) {
                $out[] = $v;
            }
        }

        return $out;
    }

    /**
     * @param list<string> $values
     */
    public static function encode(array $values): ?string
    {
        $filtered = self::filterValid($values);
        if ($filtered === []) {
            return null;
        }

        return json_encode($filtered, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return list<string>
     */
    public static function decode(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }

        return self::filterValid(array_map('strval', $decoded));
    }

    public static function labelList(?string $json): string
    {
        $items = self::decode($json);

        return $items === [] ? '-' : implode(', ', $items);
    }
}
