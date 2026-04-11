<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Repository\LoginRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Services\CrmPermissionService;

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
     * Pode atribuir ou revogar o flag «super usuário» no cadastro de **outro** utilizador.
     * No produto isto corresponde a quem já tem acesso de gestão total: Super Administrador (nível 1)
     * ou utilizador já marcado como super usuário. Quem só tem perfis normais de «gestor» sem estes
     * privilégios não passa aqui.
     */
    public static function canManageSuperUsuarioForOthers(): bool
    {
        return self::hasFullSystemAccess();
    }

    /**
     * Cargos gerenciais (mesma regra do CRM) passam a ter super_usuario = 1 ao gravar, quando quem grava pode atribuir o flag.
     *
     * @param array<string, mixed> $form
     */
    public static function applySuperUsuarioDefaultForManagerPosition(array &$form, bool $editorCanAssignSuperUsuarioFlag): void
    {
        if (!$editorCanAssignSuperUsuarioFlag) {
            return;
        }
        $pid = (int)($form['user_position_id'] ?? 0);
        if ($pid <= 0) {
            return;
        }
        try {
            $row = (new PositionsRepository())->getPosition($pid);
        } catch (\Throwable) {
            return;
        }
        if (!is_array($row)) {
            return;
        }
        $name = (string)($row['name'] ?? '');
        if (CrmPermissionService::positionNameIndicatesManagerRole($name)) {
            $form['super_usuario'] = 1;
        }
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
