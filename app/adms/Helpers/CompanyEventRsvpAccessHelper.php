<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Quem pode registrar RSVP em nome de outro colaborador (prazos ignorados).
 */
final class CompanyEventRsvpAccessHelper
{
    /**
     * @param array<string, mixed> $event
     * @param array<int, string> $buttonPermissions
     */
    public static function canAdminRsvpForOthers(array $event, int $userId, array $buttonPermissions): bool
    {
        if ($userId <= 0) {
            return false;
        }
        if (UserAccessHelper::hasFullSystemAccess()) {
            return true;
        }
        if ((int)($event['created_by'] ?? 0) === $userId) {
            return true;
        }
        foreach (['UpdateCompanyEvent', 'DeleteCompanyEvent', 'CreateCompanyEvent', 'CompanyEventReport'] as $p) {
            if (in_array($p, $buttonPermissions, true)) {
                return true;
            }
        }
        return false;
    }
}
