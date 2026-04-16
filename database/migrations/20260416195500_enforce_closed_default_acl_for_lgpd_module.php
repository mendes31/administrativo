<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * LGPD: por padrão, páginas privadas iniciam sem permissão.
 * Somente páginas públicas (public_page=1) devem ficar liberadas para todos.
 */
final class EnforceClosedDefaultAclForLgpdModule extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $lgpdGroup = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'LGPD' LIMIT 1");
        if (!$lgpdGroup) {
            return;
        }
        $lgpdGroupId = (int) ($lgpdGroup['id'] ?? 0);
        if ($lgpdGroupId <= 0) {
            return;
        }

        // Evita autorização implícita para novos níveis de acesso.
        $this->execute(
            "UPDATE adms_pages
             SET default_page = 0, updated_at = NOW()
             WHERE adms_groups_page_id = {$lgpdGroupId}"
        );

        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        // Fecha tudo que for privado no escopo LGPD.
        $this->execute(
            "UPDATE adms_access_levels_pages alp
             INNER JOIN adms_pages p ON p.id = alp.adms_page_id
             SET alp.permission = 0,
                 alp.updated_at = NOW()
             WHERE p.adms_groups_page_id = {$lgpdGroupId}
               AND p.public_page = 0"
        );
    }

    public function down(): void
    {
        // Sem rollback automático seguro:
        // não é possível inferir permissões anteriores.
    }
}

