<?php

declare(strict_types=1);

if (!function_exists('invBuildSapPosTextMap')) {
    /**
     * Mapa POS_ID → POS_TEXT a partir das linhas da rota SAP.
     *
     * @param list<array<string, mixed>> $lines
     * @return array<int, int>
     */
    function invBuildSapPosTextMap(array $lines): array
    {
        $map = [];
        foreach ($lines as $line) {
            $posId = (int) ($line['sap_pos_id'] ?? $line['sequence'] ?? 0);
            if ($posId <= 0) {
                continue;
            }
            $posText = (int) ($line['sap_pos_text'] ?? 0);
            if ($posText > 0) {
                $map[$posId] = $posText;
            } elseif (!isset($map[$posId])) {
                $map[$posId] = $posId;
            }
        }

        return $map;
    }
}

if (!function_exists('invSapResolvePosText')) {
    /**
     * Resolve POS_TEXT para um POS_ID (fallback: o próprio POS_ID).
     */
    function invSapResolvePosText(int $posId, array $posTextMap): int
    {
        if ($posId <= 0) {
            return 0;
        }

        return (int) ($posTextMap[$posId] ?? $posId);
    }
}

if (!function_exists('invSapRouteLineSortKey')) {
    function invSapRouteLineSortKey(array $line): int
    {
        $sortId = (int) ($line['sap_sort_id'] ?? 0);
        if ($sortId > 0) {
            return $sortId;
        }

        return (int) ($line['sap_pos_id'] ?? $line['sequence'] ?? 0);
    }
}

if (!function_exists('invSapCompareRouteLines')) {
    /**
     * Ordenação BEAS: SortId, depois POS_ID, depois id.
     */
    function invSapCompareRouteLines(array $a, array $b): int
    {
        $sortA = invSapRouteLineSortKey($a);
        $sortB = invSapRouteLineSortKey($b);
        if ($sortA !== $sortB) {
            return $sortA <=> $sortB;
        }

        $posA = (int) ($a['sap_pos_id'] ?? $a['sequence'] ?? 0);
        $posB = (int) ($b['sap_pos_id'] ?? $b['sequence'] ?? 0);
        if ($posA !== $posB) {
            return $posA <=> $posB;
        }

        return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
    }
}

if (!function_exists('invSapGroupMinSortKey')) {
    /**
     * @param list<array<string, mixed>> $group
     */
    function invSapGroupMinSortKey(array $group): int
    {
        $min = PHP_INT_MAX;
        foreach ($group as $line) {
            $key = invSapRouteLineSortKey($line);
            if ($key > 0) {
                $min = min($min, $key);
            }
        }

        if ($min !== PHP_INT_MAX) {
            return $min;
        }

        return (int) ($group[0]['sap_pos_id'] ?? $group[0]['sequence'] ?? 0);
    }
}
