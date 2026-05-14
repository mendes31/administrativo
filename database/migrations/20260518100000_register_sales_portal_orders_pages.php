<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Regista as páginas «Portal de Vendas — Pedidos» e «Ver pedido» em instalações já existentes.
 * A matriz de permissões fica a cargo das seeds ({@see AddAdmsPages}, {@see SyncAccessLevelsPages}).
 */
final class RegisterSalesPortalOrdersPages extends AbstractMigration
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

        $now = date('Y-m-d H:i:s');

        $existsList = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'SalesPortalListOrders' LIMIT 1");
        if (!$existsList) {
            $this->table('adms_pages')->insert([
                'name' => 'Portal de Vendas — Pedidos',
                'controller' => 'SalesPortalListOrders',
                'controller_url' => 'sales-portal-list-orders',
                'directory' => 'salesPortal',
                'obs' => 'Listagem de pedidos de venda (Orders) via Service Layer.',
                'public_page' => 0,
                'default_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $gid,
                'created_at' => $now,
                'updated_at' => $now,
            ])->save();
        }

        $existsView = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'SalesPortalViewOrder' LIMIT 1");
        if (!$existsView) {
            $this->table('adms_pages')->insert([
                'name' => 'Portal de Vendas — Ver pedido',
                'controller' => 'SalesPortalViewOrder',
                'controller_url' => 'sales-portal-view-order',
                'directory' => 'salesPortal',
                'obs' => 'Detalhe de pedido de venda (Orders) via Service Layer.',
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

        foreach (['SalesPortalViewOrder', 'SalesPortalListOrders'] as $controller) {
            $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$controller}' LIMIT 1");
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
}
