<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Quem pode incluir convidados de fora da empresa no evento.
 * O criador sempre pode. Os demais precisam das duas permissões: criar e editar.
 */
final class CompanyEventExternalInviteAccess
{
    /**
     * @param array<string, mixed> $event
     * @param array<int, string> $buttonPermissions
     */
    public static function canManage(array $event, int $userId, array $buttonPermissions): bool
    {
        if ($userId <= 0) {
            return false;
        }
        if (UserAccessHelper::hasFullSystemAccess()) {
            return true;
        }
        if ((int) ($event['created_by'] ?? 0) === $userId) {
            return true;
        }

        return in_array('CreateCompanyEvent', $buttonPermissions, true)
            && in_array('UpdateCompanyEvent', $buttonPermissions, true);
    }
}
