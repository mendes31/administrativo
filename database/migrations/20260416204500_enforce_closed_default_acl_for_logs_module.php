<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Logs: páginas privadas não devem nascer autorizadas para níveis comuns.
 * Super Admin/Super usuário mantém acesso por regra de aplicação.
 */
final class EnforceClosedDefaultAclForLogsModule extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $logsGroup = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Logs' LIMIT 1");
        if (!$logsGroup) {
            return;
        }
        $logsGroupId = (int)($logsGroup['id'] ?? 0);
        if ($logsGroupId <= 0) {
            return;
        }

        // Remove autorização implícita por default_page para o módulo.
        $this->execute(
            "UPDATE adms_pages
             SET default_page = 0, updated_at = NOW()
             WHERE adms_groups_page_id = {$logsGroupId}"
        );

        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        // Fecha permissões privadas do módulo Logs para todos níveis.
        $this->execute(
            "UPDATE adms_access_levels_pages alp
             INNER JOIN adms_pages p ON p.id = alp.adms_page_id
             SET alp.permission = 0,
                 alp.updated_at = NOW()
             WHERE p.adms_groups_page_id = {$logsGroupId}
               AND p.public_page = 0"
        );
    }

    public function down(): void
    {
        // Sem rollback automático seguro de permissões.
    }
}

