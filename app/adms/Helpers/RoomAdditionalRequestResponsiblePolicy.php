<?php

namespace App\adms\Helpers;

/**
 * Regras para o campo "responsável" das solicitações adicionais ligadas a uma reserva
 * (tabela booking_additional_requests).
 */
final class RoomAdditionalRequestResponsiblePolicy
{
    /**
     * @param array<string, mixed> $requestType Linha do tipo (RoomRequestTypesRepository::getByCode)
     */
    public static function resolveForBookingAdditionalRequest(
        int $postedResponsibleUserId,
        int $sessionUserId,
        array $requestType
    ): int {
        if ($sessionUserId <= 0) {
            return 0;
        }

        if (!UserAccessHelper::hasFullSystemAccess()) {
            return $sessionUserId;
        }

        if ($postedResponsibleUserId > 0) {
            return $postedResponsibleUserId;
        }

        $defaultUid = (int) ($requestType['default_responsible_user_id'] ?? 0);
        if (!empty($requestType['requires_responsible']) && $defaultUid > 0) {
            return $defaultUid;
        }

        return $sessionUserId;
    }
}
