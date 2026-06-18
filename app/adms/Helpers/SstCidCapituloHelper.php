<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Capítulos oficiais CID-10 (OMS / DATASUS).
 */
final class SstCidCapituloHelper
{
    /** @var list<array{num: int, inicio: string, fim: string, nome: string}> */
    private const CAPITULOS = [
        ['num' => 1, 'inicio' => 'A00', 'fim' => 'B99', 'nome' => 'Algumas doenças infecciosas e parasitárias'],
        ['num' => 2, 'inicio' => 'C00', 'fim' => 'D48', 'nome' => 'Neoplasias [tumores]'],
        ['num' => 3, 'inicio' => 'D50', 'fim' => 'D89', 'nome' => 'Doenças do sangue e dos órgãos hematopoéticos'],
        ['num' => 4, 'inicio' => 'E00', 'fim' => 'E90', 'nome' => 'Doenças endócrinas, nutricionais e metabólicas'],
        ['num' => 5, 'inicio' => 'F00', 'fim' => 'F99', 'nome' => 'Transtornos mentais e comportamentais'],
        ['num' => 6, 'inicio' => 'G00', 'fim' => 'G99', 'nome' => 'Doenças do sistema nervoso'],
        ['num' => 7, 'inicio' => 'H00', 'fim' => 'H59', 'nome' => 'Doenças do olho e anexos'],
        ['num' => 8, 'inicio' => 'H60', 'fim' => 'H95', 'nome' => 'Doenças do ouvido e da apófise mastóide'],
        ['num' => 9, 'inicio' => 'I00', 'fim' => 'I99', 'nome' => 'Doenças do aparelho circulatório'],
        ['num' => 10, 'inicio' => 'J00', 'fim' => 'J99', 'nome' => 'Doenças do aparelho respiratório'],
        ['num' => 11, 'inicio' => 'K00', 'fim' => 'K93', 'nome' => 'Doenças do aparelho digestivo'],
        ['num' => 12, 'inicio' => 'L00', 'fim' => 'L99', 'nome' => 'Doenças da pele e do tecido subcutâneo'],
        ['num' => 13, 'inicio' => 'M00', 'fim' => 'M99', 'nome' => 'Doenças do sistema osteomuscular e do tecido conjuntivo'],
        ['num' => 14, 'inicio' => 'N00', 'fim' => 'N99', 'nome' => 'Doenças do aparelho geniturinário'],
        ['num' => 15, 'inicio' => 'O00', 'fim' => 'O99', 'nome' => 'Gravidez, parto e puerpério'],
        ['num' => 16, 'inicio' => 'P00', 'fim' => 'P96', 'nome' => 'Afecções originadas no período perinatal'],
        ['num' => 17, 'inicio' => 'Q00', 'fim' => 'Q99', 'nome' => 'Malformações congênitas'],
        ['num' => 18, 'inicio' => 'R00', 'fim' => 'R99', 'nome' => 'Sintomas, sinais e achados anormais'],
        ['num' => 19, 'inicio' => 'S00', 'fim' => 'T98', 'nome' => 'Lesões, envenenamento e outras consequências de causas externas'],
        ['num' => 20, 'inicio' => 'V01', 'fim' => 'Y98', 'nome' => 'Causas externas de morbidade e mortalidade'],
        ['num' => 21, 'inicio' => 'Z00', 'fim' => 'Z99', 'nome' => 'Fatores que influenciam o estado de saúde'],
        ['num' => 22, 'inicio' => 'U00', 'fim' => 'U99', 'nome' => 'Códigos para propósitos especiais'],
    ];

    /** @return list<array{num: int, inicio: string, fim: string, nome: string}> */
    public static function all(): array
    {
        return self::CAPITULOS;
    }

    /** Rótulo legível: "5 — Transtornos mentais e comportamentais" */
    public static function label(int $num): string
    {
        foreach (self::CAPITULOS as $cap) {
            if ($cap['num'] === $num) {
                return $cap['num'] . ' — ' . $cap['nome'];
            }
        }

        return (string) $num;
    }

    /** @return array{num: int, nome: string}|null */
    public static function byNum(int $num): ?array
    {
        foreach (self::CAPITULOS as $cap) {
            if ($cap['num'] === $num) {
                return ['num' => $cap['num'], 'nome' => $cap['nome']];
            }
        }

        return null;
    }

    /**
     * @return array{num: int, nome: string}|null
     */
    public static function resolveFromCodigo(string $codigo): ?array
    {
        $base = self::normalizeBase($codigo);
        if ($base === '') {
            return null;
        }

        foreach (self::CAPITULOS as $cap) {
            if (self::compareCidBase($base, $cap['inicio']) >= 0
                && self::compareCidBase($base, $cap['fim']) <= 0) {
                return ['num' => $cap['num'], 'nome' => $cap['nome']];
            }
        }

        return null;
    }

    public static function categoriaFromCodigo(string $codigo): ?string
    {
        $norm = strtoupper(str_replace('.', '', trim($codigo)));
        if (strlen($norm) < 3) {
            return null;
        }

        return substr($norm, 0, 3);
    }

    public static function formatCodigoFromSubcat(string $subcat): string
    {
        $raw = strtoupper(trim($subcat));
        if ($raw === '') {
            return '';
        }
        if (str_contains($raw, '.')) {
            return $raw;
        }
        if (strlen($raw) === 4) {
            return substr($raw, 0, 3) . '.' . substr($raw, 3, 1);
        }

        return $raw;
    }

    private static function normalizeBase(string $codigo): string
    {
        $norm = strtoupper(str_replace('.', '', trim($codigo)));
        if (strlen($norm) < 3) {
            return $norm;
        }

        return substr($norm, 0, 3);
    }

    private static function compareCidBase(string $base, string $rangeCode): int
    {
        $range = strtoupper(str_replace('.', '', $rangeCode));
        if (strlen($range) < 3) {
            return strcmp($base, $range);
        }

        return strcmp($base, substr($range, 0, 3));
    }
}
