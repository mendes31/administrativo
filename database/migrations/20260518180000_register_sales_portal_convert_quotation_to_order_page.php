<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Regista a página «Converter cotação em pedido» do portal de vendas.
 */
final class RegisterSalesPortalConvertQuotationToOrderPage extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'SalesPortalConvertQuotationToOrder' LIMIT 1");
        if ($exists) {
            return;
        }

        $ref = $this->fetchRow("SELECT adms_groups_page_id AS gid FROM adms_pages WHERE controller = 'SalesPortalLaunchpad' LIMIT 1");
        $gid = (int) ($ref['gid'] ?? 0);
        if ($gid <= 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $this->table('adms_pages')->insert([
            'name' => 'Portal de Vendas — Converter cotação em pedido',
            'controller' => 'SalesPortalConvertQuotationToOrder',
            'controller_url' => 'sales-portal-convert-quotation-to-order',
            'directory' => 'salesPortal',
            'obs' => 'POST: cria pedido a partir da cotação e fecha a cotação na Service Layer.',
            'public_page' => 0,
            'default_page' => 0,
            'page_status' => 1,
            'adms_packages_page_id' => 1,
            'adms_groups_page_id' => $gid,
            'created_at' => $now,
            'updated_at' => $now,
        ])->save();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'SalesPortalConvertQuotationToOrder' LIMIT 1");
        if (!$row || empty($row['id'])) {
            return;
        }
        $pid = (int) $row['id'];
        if ($this->hasTable('adms_access_levels_pages')) {
            $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
        }
        $this->execute("DELETE FROM adms_pages WHERE id = {$pid} LIMIT 1");
    }
}
