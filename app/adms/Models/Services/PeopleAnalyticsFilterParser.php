<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\UserFormHelper;

/**
 * Filtros GET alinhados ao People Analytics (pa_de, pa_ate, pa_dep[], pa_pos[], demografia).
 */
final class PeopleAnalyticsFilterParser
{
    /**
     * @return array{
     *     period_start: string,
     *     period_end: string,
     *     departamento_ids: int[],
     *     cargo_ids: int[],
     *     sexo: string|null,
     *     estado_civil: string|null,
     *     pais_residencia_iso: string|null,
     *     filhos: string|null
     * }
     */
    public static function parseFromRequest(?array $get = null): array
    {
        $get = $get ?? $_GET;
        $today = date('Y-m-d');
        $defaultStart = date('Y-m-d', strtotime('-12 months'));

        $de = isset($get['pa_de']) ? (string) $get['pa_de'] : $defaultStart;
        $ate = isset($get['pa_ate']) ? (string) $get['pa_ate'] : $today;

        $de = self::normalizeDateOr($de, $defaultStart);
        $ate = self::normalizeDateOr($ate, $today);
        if ($de > $ate) {
            [$de, $ate] = [$ate, $de];
        }

        return [
            'period_start' => $de,
            'period_end' => $ate,
            'departamento_ids' => self::parseIdList($get['pa_dep'] ?? null),
            'cargo_ids' => self::parseIdList($get['pa_pos'] ?? null),
            'sexo' => UserFormHelper::normalizeSexo($get['pa_sexo'] ?? null),
            'estado_civil' => UserFormHelper::normalizeEstadoCivil($get['pa_estado_civil'] ?? null),
            'pais_residencia_iso' => UserFormHelper::normalizePaisResidenciaIso($get['pa_pais'] ?? null),
            'filhos' => UserFormHelper::normalizeFilhos($get['pa_filhos'] ?? null),
        ];
    }

    public static function usersRepoFilterPayload(array $parsed): array
    {
        return [
            'departamento_ids' => $parsed['departamento_ids'],
            'cargo_ids' => $parsed['cargo_ids'],
            'sexo' => $parsed['sexo'],
            'estado_civil' => $parsed['estado_civil'],
            'pais_residencia_iso' => $parsed['pais_residencia_iso'],
            'filhos' => $parsed['filhos'],
        ];
    }

    public static function buildQueryString(array $parsed): string
    {
        $parts = [
            'pa_de=' . rawurlencode($parsed['period_start']),
            'pa_ate=' . rawurlencode($parsed['period_end']),
        ];
        foreach ($parsed['departamento_ids'] as $id) {
            $parts[] = 'pa_dep%5B%5D=' . rawurlencode((string) $id);
        }
        foreach ($parsed['cargo_ids'] as $id) {
            $parts[] = 'pa_pos%5B%5D=' . rawurlencode((string) $id);
        }
        if ($parsed['sexo'] !== null && $parsed['sexo'] !== '') {
            $parts[] = 'pa_sexo=' . rawurlencode((string) $parsed['sexo']);
        }
        if ($parsed['estado_civil'] !== null && $parsed['estado_civil'] !== '') {
            $parts[] = 'pa_estado_civil=' . rawurlencode((string) $parsed['estado_civil']);
        }
        if ($parsed['pais_residencia_iso'] !== null && $parsed['pais_residencia_iso'] !== '') {
            $parts[] = 'pa_pais=' . rawurlencode((string) $parsed['pais_residencia_iso']);
        }
        if ($parsed['filhos'] !== null && $parsed['filhos'] !== '') {
            $parts[] = 'pa_filhos=' . rawurlencode((string) $parsed['filhos']);
        }

        return implode('&', $parts);
    }

    private static function normalizeDateOr(string $value, string $fallback): string
    {
        $d = \DateTime::createFromFormat('Y-m-d', $value);

        return ($d && $d->format('Y-m-d') === $value) ? $value : $fallback;
    }

    /**
     * @return int[]
     */
    private static function parseIdList(mixed $raw): array
    {
        if ($raw === null || $raw === '' || $raw === []) {
            return [];
        }
        if (!is_array($raw)) {
            $raw = explode(',', (string) $raw);
        }
        $out = [];
        foreach ($raw as $v) {
            if (is_string($v)) {
                $v = trim($v);
            }
            if ($v === '' || $v === null) {
                continue;
            }
            if (is_numeric($v)) {
                $id = (int) $v;
                if ($id > 0) {
                    $out[$id] = true;
                }
            }
        }

        return array_keys($out);
    }
}
