<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\ButtonPermissionUserRepository;

/**
 * ACL de solicitações do colaborador (aprovação RH e fila).
 */
final class EmployeeRequestPermissionService
{
    public const CONTROLLER_APPROVE_HR = 'ApproveEmployeeRequestHR';

    public const CONTROLLER_PENDING_APPROVALS = 'PendingApprovals';

    /**
     * Pode aprovar/rejeitar na etapa de RH (página ApproveEmployeeRequestHR).
     */
    public static function canApproveAsHr(?int $userId = null): bool
    {
        if (UserAccessHelper::hasFullSystemAccess()) {
            return true;
        }

        if ($userId !== null && $userId > 0 && (int) ($_SESSION['user_id'] ?? 0) !== $userId) {
            return false;
        }

        return self::userHasController(self::CONTROLLER_APPROVE_HR);
    }

    /**
     * Pode ver a fila de solicitações aguardando RH.
     */
    public static function canAccessHrApprovalQueue(?int $userId = null): bool
    {
        if (UserAccessHelper::hasFullSystemAccess()) {
            return true;
        }

        if ($userId !== null && $userId > 0 && (int) ($_SESSION['user_id'] ?? 0) !== $userId) {
            return false;
        }

        return self::canApproveAsHr()
            || self::userHasController(self::CONTROLLER_PENDING_APPROVALS);
    }

    /**
     * Pode abrir detalhes de solicitações como aprovador RH (não só as próprias).
     */
    public static function canViewAsHrApprover(?int $userId = null): bool
    {
        return self::canApproveAsHr($userId);
    }

    private static function userHasController(string $controller): bool
    {
        $perm = (new ButtonPermissionUserRepository())->buttonPermission([$controller]);

        return is_array($perm) && in_array($controller, $perm, true);
    }
}
