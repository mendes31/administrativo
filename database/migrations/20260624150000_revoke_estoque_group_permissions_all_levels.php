<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Estoque: remove autorizações do grupo em todos os níveis de acesso.
 * Super Admin/Super usuário mantém acesso por regra de aplicação.
 */
final class RevokeEstoqueGroupPermissionsAllLevels extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $estoqueGroup = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Estoque' LIMIT 1");
        if (!$estoqueGroup) {
            return;
        }
        $estoqueGroupId = (int) ($estoqueGroup['id'] ?? 0);
        if ($estoqueGroupId <= 0) {
            return;
        }

        // Evita autorização implícita para novos níveis de acesso.
        $this->execute(
            "UPDATE adms_pages
             SET default_page = 0, updated_at = NOW()
             WHERE adms_groups_page_id = {$estoqueGroupId}"
        );

        if (!$this->hasTable('adms_access_levels_pages')) {
            $this->bumpMenuPermissionCache();
            return;
        }

        // Fecha permissões privadas do módulo Estoque para todos os níveis.
        $this->execute(
            "UPDATE adms_access_levels_pages alp
             INNER JOIN adms_pages p ON p.id = alp.adms_page_id
             SET alp.permission = 0,
                 alp.updated_at = NOW()
             WHERE p.adms_groups_page_id = {$estoqueGroupId}
               AND p.public_page = 0"
        );

        $this->bumpMenuPermissionCache();
    }

    public function down(): void
    {
        // Sem rollback automático seguro de permissões.
    }

    private function bumpMenuPermissionCache(): void
    {
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'system';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . DIRECTORY_SEPARATOR . 'menu_permission_version.txt', (string) time());
    }
}
