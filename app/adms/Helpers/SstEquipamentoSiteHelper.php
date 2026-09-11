<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Sites do equipamento de segurança (lista fixa, independente do cadastro de empresas/filiais).
 * Compõem a localização junto com sala/área.
 */
final class SstEquipamentoSiteHelper
{
    public const LABORATORIO_TIARAJU = 'laboratorio_tiaraju';
    public const AFRA_PHARMA = 'afra_pharma';
    public const AFRA_BIOTICS = 'afra_biotics';

    /** @var array<string, string> slug => rótulo */
    private const OPTIONS = [
        self::LABORATORIO_TIARAJU => 'Laboratório Tiaraju',
        self::AFRA_PHARMA => 'Afra Pharma',
        self::AFRA_BIOTICS => 'Afra Biotics',
    ];

    /** @var array<string, string> valor antigo/alternativo => slug atual */
    private const ALIASES = [
        'lab_tiaraju_matriz' => self::LABORATORIO_TIARAJU,
        'lab_tiaraju_filial' => self::AFRA_PHARMA,
        'laboratorio tiaraju' => self::LABORATORIO_TIARAJU,
        'laboratorio tiaraju alimentos e cosmeticos s/a' => self::LABORATORIO_TIARAJU,
        'lab. tiaraju matriz' => self::LABORATORIO_TIARAJU,
        'lab tiaraju matriz' => self::LABORATORIO_TIARAJU,
        'lab. tiaraju filial' => self::AFRA_PHARMA,
        'lab tiaraju filial' => self::AFRA_PHARMA,
    ];

    /** @return array<string, string> */
    public static function options(): array
    {
        return self::OPTIONS;
    }

    /** @return list<string> */
    public static function slugs(): array
    {
        return array_keys(self::OPTIONS);
    }

    /**
     * Expressão SQL para ordenar pela ordem da lista fixa de sites (desconhecidos por último).
     */
    public static function sqlOrderRank(string $column): string
    {
        if (!preg_match('/^[a-z_]+(?:\.[a-z_]+)?$/', $column)) {
            return '99';
        }
        $sql = 'CASE ' . $column;
        foreach (self::slugs() as $i => $slug) {
            $sql .= " WHEN '" . str_replace("'", "''", $slug) . "' THEN " . ($i + 1);
        }

        return $sql . ' ELSE 99 END';
    }

    public static function normalize(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }
        $needle = mb_strtolower($raw, 'UTF-8');
        $needle = strtr($needle, ['á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u']);

        if (isset(self::OPTIONS[$needle])) {
            return $needle;
        }
        if (isset(self::ALIASES[$needle])) {
            return self::ALIASES[$needle];
        }
        foreach (self::OPTIONS as $slug => $label) {
            $labelNorm = mb_strtolower($label, 'UTF-8');
            $labelNorm = strtr($labelNorm, ['á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u']);
            if ($needle === $labelNorm || $needle === $slug) {
                return $slug;
            }
        }

        return null;
    }

    public static function label(?string $slug): string
    {
        $norm = self::normalize($slug);
        if ($norm !== null) {
            return self::OPTIONS[$norm];
        }
        $raw = trim((string) $slug);

        return $raw !== '' ? $raw : '—';
    }

    /**
     * Site + sala/área (ex.: Laboratório Tiaraju — SALA TI).
     */
    public static function formatLocalizacao(?string $siteSlug, ?string $localizacao): string
    {
        $site = self::normalize($siteSlug) !== null ? self::label($siteSlug) : '';
        $local = trim((string) $localizacao);
        if ($site !== '' && $local !== '') {
            return $site . ' — ' . $local;
        }

        return $site !== '' ? $site : ($local !== '' ? $local : '—');
    }

    public static function letterheadSlug(?string $slug): ?string
    {
        return match (self::normalize($slug)) {
            self::LABORATORIO_TIARAJU => 'lab_tiaraju_matriz',
            self::AFRA_PHARMA => 'lab_tiaraju_filial',
            default => self::normalize($slug),
        };
    }

    /**
     * Slugs a considerar no filtro (inclui valores antigos ainda no banco).
     *
     * @return list<string>
     */
    public static function slugsForFilter(string $value): array
    {
        $norm = self::normalize($value);
        if ($norm === null) {
            return [];
        }
        $out = [$norm];
        foreach (self::ALIASES as $alias => $slug) {
            if ($slug === $norm && !in_array($alias, $out, true) && !str_contains($alias, ' ')) {
                $out[] = $alias;
            }
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function sqlInColumn(string $column, string $filterValue, array &$params, string $paramPrefix): string
    {
        $slugs = self::slugsForFilter($filterValue);
        if ($slugs === []) {
            return '0';
        }
        $parts = [];
        foreach ($slugs as $i => $slug) {
            $key = ':' . $paramPrefix . $i;
            $parts[] = $key;
            $params[$key] = $slug;
        }

        return $column . ' IN (' . implode(', ', $parts) . ')';
    }
}
