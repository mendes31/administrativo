<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\MenuPermissionUserRepository;
use App\adms\Models\Repository\UsersAccessLevelsRepository;
use App\adms\Models\Repository\WhistleblowingCommitteesRepository;

/**
 * Concede/revoga o nível secundário «Operador» conforme participação em comitês.
 */
final class WhistleblowingCommitteeAccessSyncService
{
    /**
     * @param list<int> $userIds
     */
    public static function syncUsers(array $userIds): void
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if ($userIds === []) {
            return;
        }

        $service = new self();
        foreach ($userIds as $userId) {
            $service->syncUser($userId);
        }
    }

    public function syncUser(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $levelsRepo = new UsersAccessLevelsRepository();
        $levelId = $levelsRepo->getAccessLevelIdByName(WhistleblowingPermissionService::OPERATOR_LEVEL_NAME);
        if ($levelId === null || $levelId <= 0) {
            return;
        }

        $isMember = (new WhistleblowingCommitteesRepository())->isUserMemberOfAnyCommittee($userId);

        if ($isMember) {
            $levelsRepo->grantAccessLevelToUser($userId, $levelId);
        } else {
            $levelsRepo->revokeAccessLevelFromUser($userId, $levelId);
        }

        MenuPermissionUserRepository::clearSessionCache();
    }
}
