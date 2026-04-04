<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Corrige adms_pages.controller quando o slug (controller_url) foi gravado no campo controller.
 * A listagem de páginas mostra controller_url na coluna "Controller" — o nome da classe PHP fica só no cadastro detalhado.
 */
final class FixAdmsPagesControllerWhenSlugStored extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $this->execute(
            "UPDATE adms_pages SET
                controller = 'ListConnectedUsers',
                updated_at = NOW()
             WHERE controller_url = 'list-connected-users'
               AND (controller LIKE '%-%' OR controller = controller_url)"
        );
    }

    public function down(): void
    {
        // Correção de dados; sem reversão segura.
    }
}
