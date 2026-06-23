<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\InstitutionalSystemUserHelper;

/**
 * Contas que não devem entrar no ranking nem em indicadores agregados (ex.: utilizador institucional).
 */
final class GamificationRankingExclusions
{
    /** Nomes em adms_users.name (comparados com TRIM) — espelho do helper institucional. */
    public const EXCLUDED_DISPLAY_NAMES = InstitutionalSystemUserHelper::INSTITUTIONAL_DISPLAY_NAMES;

    /**
     * Condição SQL: user_id do ledger não pertence a utilizadores institucionais.
     *
     * @param string $ledgerUserColumn ex.: "l.user_id"
     */
    public static function sqlLedgerUserNotExcluded(string $ledgerUserColumn = 'l.user_id'): string
    {
        return InstitutionalSystemUserHelper::sqlExcludeUserIdColumn($ledgerUserColumn);
    }

    /**
     * Condição SQL sobre coluna de nome já qualificada (ex.: u.name).
     */
    public static function sqlUserNameNotExcluded(string $nameColumn = 'u.name'): string
    {
        $idColumn = preg_replace('/\.name$/', '.id', $nameColumn) ?: 'u.id';

        return InstitutionalSystemUserHelper::sqlExcludeUserIdColumn($idColumn);
    }

}
