<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Páginas ACL: Dashboard de Vendas CRM + endpoint JSON agregado.
 * ACL copiada de CrmDashboard.
 */
final class RegisterCrmSalesDashboardPages extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_groups_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $conn = $this->getAdapter()->getConnection();

        // Preferir o mesmo grupo de CrmDashboard (ex.: "CRM - Operação"), não o pai "CRM".
        $group = $this->fetchRow(
            "SELECT adms_groups_page_id AS id FROM adms_pages WHERE controller = 'CrmDashboard' LIMIT 1"
        );
        if (!$group) {
            $group = $this->fetchRow(
                "SELECT id FROM adms_groups_pages WHERE name = 'CRM - Operação' LIMIT 1"
            );
        }
        if (!$group) {
            $group = $this->fetchRow(
                "SELECT id FROM adms_groups_pages WHERE name LIKE 'CRM%' ORDER BY id DESC LIMIT 1"
            );
        }
        if (!$group) {
            return;
        }
        $gid = (int) $group['id'];

        $pages = [
            [
                'name' => 'Dashboard de Vendas CRM',
                'controller' => 'CrmSalesDashboard',
                'controller_url' => 'crm-sales-dashboard',
                'obs' => 'Dashboard de faturamento líquido SAP (faturas + devoluções) no CRM.',
            ],
            [
                'name' => 'API Dashboard Vendas CRM',
                'controller' => 'CrmSalesDashboardData',
                'controller_url' => 'crm-sales-dashboard-data',
                'obs' => 'Endpoint JSON agregado (KPIs/gráficos) do Dashboard de Vendas CRM.',
            ],
        ];

        foreach ($pages as $p) {
            $existing = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($p['controller']) . ' LIMIT 1'
            );
            if ($existing) {
                continue;
            }
            $this->execute(
                'INSERT INTO adms_pages
                    (name, controller, controller_url, directory, obs, public_page, default_page, page_status,
                     adms_packages_page_id, adms_groups_page_id, created_at, updated_at)
                 VALUES ('
                . $conn->quote($p['name']) . ', '
                . $conn->quote($p['controller']) . ', '
                . $conn->quote($p['controller_url']) . ', '
                . $conn->quote('crm') . ', '
                . $conn->quote($p['obs']) . ', '
                . '0, 0, 1, 1, '
                . $gid . ', '
                . $conn->quote($now) . ', '
                . $conn->quote($now)
                . ')'
            );
        }

        $ref = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'CrmDashboard' LIMIT 1"
        );
        if (!$ref || !$this->hasTable('adms_access_levels_pages')) {
            $this->bumpMenuCache();
            return;
        }
        $refId = (int) $ref['id'];

        foreach (['CrmSalesDashboard', 'CrmSalesDashboardData'] as $controller) {
            $page = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1'
            );
            if (!$page) {
                continue;
            }
            $pageId = (int) $page['id'];
            $this->execute(
                "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                 SELECT alp.permission, alp.adms_access_level_id, {$pageId}, "
                . $conn->quote($now) . ', ' . $conn->quote($now) . "
                 FROM adms_access_levels_pages alp
                 WHERE alp.adms_page_id = {$refId}
                   AND NOT EXISTS (
                       SELECT 1 FROM adms_access_levels_pages x
                       WHERE x.adms_access_level_id = alp.adms_access_level_id
                         AND x.adms_page_id = {$pageId}
                   )"
            );
        }

        $this->bumpMenuCache();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        foreach (['CrmSalesDashboardData', 'CrmSalesDashboard'] as $controller) {
            $row = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = '{$controller}' LIMIT 1"
            );
            if (!$row) {
                continue;
            }
            $pid = (int) $row['id'];
            if ($this->hasTable('adms_access_levels_pages')) {
                $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
            }
            $this->execute("DELETE FROM adms_pages WHERE id = {$pid}");
        }
        $this->bumpMenuCache();
    }

    private function bumpMenuCache(): void
    {
        $cacheDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'system';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }
        @file_put_contents(
            $cacheDir . DIRECTORY_SEPARATOR . 'menu_permission_version.txt',
            (string) time()
        );
    }
}
