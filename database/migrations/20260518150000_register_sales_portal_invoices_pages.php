<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Regista páginas «Faturas» e «Ver fatura» do portal de vendas.
 * A matriz de permissões fica a cargo das seeds ({@see AddAdmsPages}, {@see SyncAccessLevelsPages}).
 */
final class RegisterSalesPortalInvoicesPages extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $ref = $this->fetchRow("SELECT adms_groups_page_id AS gid FROM adms_pages WHERE controller = 'SalesPortalLaunchpad' LIMIT 1");
        $gid = (int) ($ref['gid'] ?? 0);
        if ($gid <= 0) {
            return;
        }

        $pages = [
            [
                'name' => 'Portal de Vendas — Faturas',
                'controller' => 'SalesPortalListInvoices',
                'controller_url' => 'sales-portal-list-invoices',
                'obs' => 'Listagem de faturas de cliente (Invoices) via Service Layer.',
            ],
            [
                'name' => 'Portal de Vendas — Ver fatura',
                'controller' => 'SalesPortalViewInvoice',
                'controller_url' => 'sales-portal-view-invoice',
                'obs' => 'Detalhe de fatura de cliente (Invoices) via Service Layer.',
            ],
        ];

        $now = date('Y-m-d H:i:s');
        foreach ($pages as $p) {
            $ctrl = $p['controller'];
            $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$this->esc($ctrl)}' LIMIT 1");
            if ($exists) {
                continue;
            }
            $this->table('adms_pages')->insert([
                'name' => $p['name'],
                'controller' => $p['controller'],
                'controller_url' => $p['controller_url'],
                'directory' => 'salesPortal',
                'obs' => $p['obs'],
                'public_page' => 0,
                'default_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $gid,
                'created_at' => $now,
                'updated_at' => $now,
            ])->save();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        foreach (['SalesPortalViewInvoice', 'SalesPortalListInvoices'] as $ctrl) {
            $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$this->esc($ctrl)}' LIMIT 1");
            if (!$row || empty($row['id'])) {
                continue;
            }
            $pid = (int) $row['id'];
            if ($this->hasTable('adms_access_levels_pages')) {
                $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
            }
            $this->execute("DELETE FROM adms_pages WHERE id = {$pid} LIMIT 1");
        }
    }

    private function esc(string $s): string
    {
        return str_replace("'", "''", $s);
    }
}
