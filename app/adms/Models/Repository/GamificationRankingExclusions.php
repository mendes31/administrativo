<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

/**
 * Contas que não devem entrar no ranking nem em indicadores agregados (ex.: utilizador institucional).
 */
final class GamificationRankingExclusions
{
    /** Nomes em adms_users.name (comparados com TRIM) */
    public const EXCLUDED_DISPLAY_NAMES = [
        'Grupo Tiaraju',
    ];

    /**
     * Condição SQL: user_id do ledger não pertence a utilizadores com nome excluído.
     *
     * @param string $ledgerUserColumn ex.: "l.user_id"
     */
    public static function sqlLedgerUserNotExcluded(string $ledgerUserColumn = 'l.user_id'): string
    {
        $in = self::sqlQuotedNameList();
        if ($in === '') {
            return '1=1';
        }

        return "{$ledgerUserColumn} NOT IN (SELECT id FROM adms_users WHERE TRIM(name) IN ({$in}))";
    }

    /**
     * Condição SQL sobre coluna de nome já qualificada (ex.: u.name).
     */
    public static function sqlUserNameNotExcluded(string $nameColumn = 'u.name'): string
    {
        $in = self::sqlQuotedNameList();
        if ($in === '') {
            return '1=1';
        }

        return "TRIM({$nameColumn}) NOT IN ({$in})";
    }

    private static function sqlQuotedNameList(): string
    {
        $parts = [];
        foreach (self::EXCLUDED_DISPLAY_NAMES as $n) {
            $n = trim((string)$n);
            if ($n === '') {
                continue;
            }
            $parts[] = "'" . str_replace("'", "''", $n) . "'";
        }

        return implode(',', $parts);
    }
}
