<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\UserAccessHelper;

/**
 * Regras de escopo para criar/editar/excluir/relatório/remover mídia de informativos:
 * - Super administrador / super usuário: sem restrição de departamento ou autor.
 * - Demais: autor do registro OU mesmo departamento do informativo (department_id do registro).
 *
 * A permissão de botão/página (Update, Delete, Relatório) continua sendo validada pelo ACL
 * em cada controller; este serviço cobre apenas o vínculo autor/departamento.
 */
final class InformativosPermissionService
{
    public static function canManageRecord(array $informativo, int $userId, ?int $userDepartmentId): bool
    {
        if (UserAccessHelper::hasFullSystemAccess()) {
            return true;
        }

        $authorId = (int)($informativo['usuario_id'] ?? 0);
        if ($authorId > 0 && $authorId === $userId) {
            return true;
        }

        $recordDept = isset($informativo['department_id']) ? (int) $informativo['department_id'] : 0;
        $userDept = $userDepartmentId !== null ? (int) $userDepartmentId : 0;

        return $recordDept > 0 && $userDept > 0 && $recordDept === $userDept;
    }

    /**
     * Departamento efetivo ao criar: super escolhe no POST; demais usam o da sessão.
     *
     * @return int|null ID válido ou null se não puder criar (sem dept e não super)
     */
    public static function resolveCreateDepartmentId(int $postedDepartmentId, int $userId, ?int $userDepartmentId): ?int
    {
        if (UserAccessHelper::hasFullSystemAccess()) {
            return $postedDepartmentId > 0 ? $postedDepartmentId : null;
        }

        $ud = $userDepartmentId !== null ? (int) $userDepartmentId : 0;

        return $ud > 0 ? $ud : null;
    }

    /**
     * Em atualização: super pode alterar departamento via POST; demais mantêm o do banco.
     */
    public static function resolveUpdateDepartmentId(
        array $existingRow,
        int $postedDepartmentId,
        ?int $userDepartmentId
    ): int {
        if (UserAccessHelper::hasFullSystemAccess()) {
            return $postedDepartmentId > 0 ? $postedDepartmentId : (int) ($existingRow['department_id'] ?? 0);
        }

        return (int) ($existingRow['department_id'] ?? 0);
    }

    public static function sessionUserDepartmentId(): ?int
    {
        if (!isset($_SESSION['user_department_id'])) {
            return null;
        }
        $v = (int) $_SESSION['user_department_id'];

        return $v > 0 ? $v : null;
    }

    public static function sessionUserId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }
}
