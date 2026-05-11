<?php

namespace App\adms\Helpers;

use App\adms\Models\Repository\RoomRequestGroupsRepository;

/**
 * Quem pode ver ou alterar solicitações de serviço (módulo Salas).
 */
final class RoomServiceRequestAccessHelper
{
    public static function currentUserMayViewServiceRequest(array $request): bool
    {
        if (UserAccessHelper::hasFullSystemAccess()) {
            return true;
        }
        $uid = (int) ($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
            return false;
        }
        if ((int) ($request['requester_user_id'] ?? 0) === $uid) {
            return true;
        }
        if ((int) ($request['booking_id'] ?? 0) > 0
            && (int) ($request['booking_organizer_user_id'] ?? 0) === $uid) {
            return true;
        }
        $gid = (int) ($request['responsible_group_id'] ?? 0);
        if ($gid <= 0) {
            return false;
        }
        $members = (new RoomRequestGroupsRepository())->getMemberUserIds($gid);

        return in_array($uid, $members, true);
    }

    public static function currentUserMayEditServiceRequest(array $request): bool
    {
        return self::currentUserMayViewServiceRequest($request);
    }

    public static function currentUserMayDeleteServiceRequest(array $request): bool
    {
        if (UserAccessHelper::hasFullSystemAccess()) {
            return true;
        }
        $uid = (int) ($_SESSION['user_id'] ?? 0);

        return $uid > 0 && (int) ($request['requester_user_id'] ?? 0) === $uid;
    }
}
