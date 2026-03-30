<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Repository\LoginRepository;

/**
 * Acesso total ao sistema: nível "Super Administrador" (id 1) ou flag "Super usuário" no cadastro.
 */
final class UserAccessHelper
{
    /** Nível de acesso Super Administrador (adms_access_levels.id = 1). */
    public const SUPER_ADMIN_LEVEL_ID = 1;

    /** Evita múltiplas leituras do flag na mesma requisição HTTP. */
    private static bool $superUsuarioSyncedThisRequest = false;

    public static function isSuperAdminLevel(): bool
    {
        return (int)($_SESSION['user_access_level_id'] ?? 0) === self::SUPER_ADMIN_LEVEL_ID;
    }

    /**
     * Flag definida no cadastro do usuário (independente do nível de acesso atribuído).
     */
    public static function isSuperUserFlag(): bool
    {
        return (int)($_SESSION['user_super_usuario'] ?? 0) === 1;
    }

    /**
     * Mesmo efeito prático do Super Administrador: todas as páginas/permissões do sistema.
     */
    public static function hasFullSystemAccess(): bool
    {
        if (self::isSuperAdminLevel()) {
            return true;
        }
        self::syncSuperUsuarioFromDatabaseOncePerRequest();
        return self::isSuperUserFlag();
    }

    /**
     * Alinha $_SESSION['user_super_usuario'] ao valor atual em adms_users.
     * Necessário após alterar o cadastro em outro ambiente, deploy ou sessão antiga sem a chave.
     * Uma consulta leve por requisição (apenas quem não é nível 1).
     */
    private static function syncSuperUsuarioFromDatabaseOncePerRequest(): void
    {
        if (self::$superUsuarioSyncedThisRequest) {
            return;
        }
        self::$superUsuarioSyncedThisRequest = true;
        if (empty($_SESSION['user_id'])) {
            return;
        }
        try {
            $repo = new LoginRepository();
            $_SESSION['user_super_usuario'] = $repo->getSuperUsuarioFlag((int) $_SESSION['user_id']) ? 1 : 0;
        } catch (\Throwable $e) {
            if (!isset($_SESSION['user_super_usuario'])) {
                $_SESSION['user_super_usuario'] = 0;
            }
        }
    }
}
