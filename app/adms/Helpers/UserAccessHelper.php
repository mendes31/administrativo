<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Acesso total ao sistema: nível "Super Administrador" (id 1) ou flag "Super usuário" no cadastro.
 */
final class UserAccessHelper
{
    /** Nível de acesso Super Administrador (adms_access_levels.id = 1). */
    public const SUPER_ADMIN_LEVEL_ID = 1;

    public static function isSuperAdminLevel(): bool
    {
        return (int)($_SESSION['user_access_level_id'] ?? 0) === self::SUPER_ADMIN_LEVEL_ID;
    }

    /**
     * Flag definida no cadastro do usuário (independente do nível de acesso atribuído).
     */
    public static function isSuperUserFlag(): bool
    {
        return !empty($_SESSION['user_super_usuario']);
    }

    /**
     * Mesmo efeito prático do Super Administrador: todas as páginas/permissões do sistema.
     */
    public static function hasFullSystemAccess(): bool
    {
        return self::isSuperAdminLevel() || self::isSuperUserFlag();
    }
}
