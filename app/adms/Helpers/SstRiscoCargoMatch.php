<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Escopo cargo/setor do vínculo risco × colaborador.
 *
 * Só cargo: todos os usuários daquele cargo.
 * Só departamento: todos os usuários daquele setor.
 * Cargo e departamento: somente a combinação.
 */
final class SstRiscoCargoMatch
{
    public static function normalizeId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    public static function hasScope(?int $positionId, ?int $departmentId): bool
    {
        return ($positionId !== null && $positionId > 0)
            || ($departmentId !== null && $departmentId > 0);
    }

    /**
     * SQL: o usuário atende a linha de regra (risco_cargo ou necessidade).
     */
    public static function sqlUsuario(string $aliasRegra, string $aliasUser = 'u'): string
    {
        $pos = 'NULLIF(' . $aliasRegra . '.adms_position_id, 0)';
        $dep = 'NULLIF(' . $aliasRegra . '.adms_department_id, 0)';
        $userPos = 'NULLIF(' . $aliasUser . '.user_position_id, 0)';
        $userDep = 'NULLIF(' . $aliasUser . '.user_department_id, 0)';

        return '(' . $pos . ' IS NOT NULL OR ' . $dep . ' IS NOT NULL)'
            . ' AND (' . $pos . ' IS NULL OR ' . $aliasRegra . '.adms_position_id = ' . $userPos . ')'
            . ' AND (' . $dep . ' IS NULL OR ' . $aliasRegra . '.adms_department_id = ' . $userDep . ')';
    }

    /**
     * SQL: o vínculo de risco se aplica ao cargo da matriz.
     *
     * Só cargo / cargo+setor: pela configuração, sem exigir colaborador.
     * Só setor: cargos que têm colaborador ativo naquele departamento.
     */
    public static function sqlMatrizParaCargo(string $aliasRc, string $cargoExpr, ?int $departmentId, string $depParam = ':dep'): string
    {
        $pos = 'NULLIF(' . $aliasRc . '.adms_position_id, 0)';
        $dep = 'NULLIF(' . $aliasRc . '.adms_department_id, 0)';
        $atLeastOne = '(' . $pos . ' IS NOT NULL OR ' . $dep . ' IS NOT NULL)';
        $cargoOk = '(' . $pos . ' IS NULL OR ' . $aliasRc . '.adms_position_id = ' . $cargoExpr . ')';

        if ($departmentId !== null && $departmentId > 0) {
            $depOk = '(' . $dep . ' IS NULL OR ' . $aliasRc . '.adms_department_id = ' . $depParam . ')';
        } else {
            $depOk = '(' . $dep . ' IS NULL OR ' . $pos . ' IS NOT NULL OR '
                . self::sqlExisteColaboradorDoCargoNoSetor($aliasRc, $cargoExpr) . ')';
        }

        return $atLeastOne . ' AND ' . $cargoOk . ' AND ' . $depOk;
    }

    public static function sqlExisteColaboradorDoCargoNoSetor(string $aliasRc, string $cargoExpr, string $userAlias = 'u_sst_rc'): string
    {
        return 'EXISTS (SELECT 1 FROM adms_users ' . $userAlias
            . ' WHERE ' . $userAlias . '.user_position_id = ' . $cargoExpr
            . ' AND ' . $userAlias . '.user_department_id = ' . $aliasRc . '.adms_department_id'
            . ' AND ' . $userAlias . '.status = \'Ativo\''
            . ' AND ' . $userAlias . '.data_desligamento IS NULL)';
    }
}
