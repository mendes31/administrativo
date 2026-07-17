<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\MenuPermissionUserRepository;
use App\adms\Models\Repository\UsersAccessLevelsRepository;
use App\adms\Models\Repository\WhistleblowingCommitteesRepository;

/**
 * Escopo de dados do Canal de Denúncias: ACL abre o módulo; comitê define quais denúncias o operador vê.
 */
final class WhistleblowingPermissionService
{
    public const OPERATOR_LEVEL_NAME = 'Canal de Denúncias — Operador';

    public const ADMIN_LEVEL_NAME = 'Canal de Denúncias — Administrador';

    /** @var list<string> */
    private const ADMIN_CONTROLLERS = [
        'WhistleblowingListCommittees',
        'WhistleblowingCreateCommittee',
        'WhistleblowingUpdateCommittee',
        'WhistleblowingConfig',
        'WhistleblowingGovernanceLgpd',
        'WhistleblowingAuditEvidence',
    ];

    public static function sessionUserId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    /**
     * Compliance / DPO / super: vê todas as denúncias sem filtro por comitê.
     */
    public static function canViewAllReports(?int $userId = null): bool
    {
        $userId = $userId ?? self::sessionUserId();
        if ($userId <= 0) {
            return false;
        }

        if (UserAccessHelper::hasFullSystemAccess()) {
            return true;
        }

        if (self::userHasAccessLevelName($userId, self::ADMIN_LEVEL_NAME)) {
            return true;
        }

        $allowed = array_flip((new MenuPermissionUserRepository())->getAllowedControllersForUser());
        foreach (self::ADMIN_CONTROLLERS as $controller) {
            if (isset($allowed[$controller])) {
                return true;
            }
        }

        return false;
    }

    /**
     * IDs dos comitês do usuário (membro ativo).
     *
     * @return list<int>
     */
    public static function getUserCommitteeIds(?int $userId = null): array
    {
        $userId = $userId ?? self::sessionUserId();
        if ($userId <= 0) {
            return [];
        }

        return (new WhistleblowingCommitteesRepository())->getCommitteeIdsForUser($userId);
    }

    /**
     * Aplica escopo de listagem/dashboard nos filtros do repositório.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public static function applyReportScopeFilters(array $filters, ?int $userId = null): array
    {
        if (self::canViewAllReports($userId)) {
            return $filters;
        }

        $userId = $userId ?? self::sessionUserId();
        $filters['scope_user_id'] = $userId;
        $filters['scope_committee_ids'] = self::getUserCommitteeIds($userId);

        return $filters;
    }

    /**
     * @param array<string, mixed> $report
     */
    public static function canAccessReport(array $report, ?int $userId = null): bool
    {
        if (self::canViewAllReports($userId)) {
            return true;
        }

        $userId = $userId ?? self::sessionUserId();
        if ($userId <= 0) {
            return false;
        }

        $committeeId = isset($report['committee_id']) ? (int) $report['committee_id'] : 0;
        if ($committeeId > 0 && in_array($committeeId, self::getUserCommitteeIds($userId), true)) {
            return true;
        }

        $assignedUserId = isset($report['assigned_user_id']) ? (int) $report['assigned_user_id'] : 0;

        return $assignedUserId > 0 && $assignedUserId === $userId;
    }

    public static function userHasAccessLevelName(int $userId, string $levelName): bool
    {
        if ($userId <= 0 || $levelName === '') {
            return false;
        }

        $levels = (new UsersAccessLevelsRepository())->getUserAccessLevel($userId);
        if (!is_array($levels)) {
            return false;
        }

        foreach ($levels as $row) {
            if (($row['name'] ?? '') === $levelName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Somente Super Administrador / Super usuário pode atribuir ou remover
     * os níveis do Canal de Denúncias no cadastro do usuário.
     * (A concessão automática de Operador via comitê permanece separada.)
     */
    public static function canManageAccessLevelsAssignment(): bool
    {
        return UserAccessHelper::hasFullSystemAccess();
    }

    /**
     * @return list<string>
     */
    public static function protectedAccessLevelNames(): array
    {
        return [self::OPERATOR_LEVEL_NAME, self::ADMIN_LEVEL_NAME];
    }

    /**
     * @return list<int>
     */
    public static function protectedAccessLevelIds(): array
    {
        $repo = new UsersAccessLevelsRepository();
        $ids = [];
        foreach (self::protectedAccessLevelNames() as $name) {
            $id = $repo->getAccessLevelIdByName($name);
            if ($id !== null && $id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }
}
