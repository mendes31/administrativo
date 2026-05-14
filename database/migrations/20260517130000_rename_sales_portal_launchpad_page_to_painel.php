<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Alinha o nome da página do painel do portal de vendas ao rótulo «Painel» (menu e cadastro).
 */
final class RenameSalesPortalLaunchpadPageToPainel extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        $this->execute(
            "UPDATE adms_pages SET name = 'Portal de Vendas — Painel', obs = 'Painel do portal de vendas integrado ao SAP B1.', updated_at = NOW() WHERE controller = 'SalesPortalLaunchpad'"
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        $this->execute(
            "UPDATE adms_pages SET name = 'Portal de Vendas — Início', obs = 'Launchpad do portal de vendas integrado ao SAP B1.', updated_at = NOW() WHERE controller = 'SalesPortalLaunchpad'"
        );
    }
}
