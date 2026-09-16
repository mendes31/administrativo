<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Páginas ACL: Carteira, Força de vendas e Produto (análises irmãs do cockpit SAP).
 * ACL copiada de CrmSalesDashboard. Sem custo/margem.
 */
final class RegisterCrmSalesAnalyticsPages extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $conn = $this->getAdapter()->getConnection();

        $group = $this->fetchRow(
            "SELECT adms_groups_page_id AS id FROM adms_pages WHERE controller = 'CrmSalesDashboard' LIMIT 1"
        );
        if (!$group) {
            $group = $this->fetchRow(
                "SELECT adms_groups_page_id AS id FROM adms_pages WHERE controller = 'CrmDashboard' LIMIT 1"
            );
        }
        if (!$group) {
            return;
        }
        $gid = (int) $group['id'];

        $pages = [
            [
                'name' => 'Carteira de clientes (Vendas SAP)',
                'controller' => 'CrmSalesCarteira',
                'controller_url' => 'crm-sales-carteira',
                'obs' => 'Pareto ABC, clientes novos/recorrentes e concentração da carteira no cache de vendas SAP. Sem custo/margem.',
            ],
            [
                'name' => 'Força de vendas (Vendas SAP)',
                'controller' => 'CrmSalesVendedores',
                'controller_url' => 'crm-sales-vendedores',
                'obs' => 'Scorecard por vendedor (líquido, desconto, devolução, remessa). Sem custo/margem.',
            ],
            [
                'name' => 'Produto (Vendas SAP)',
                'controller' => 'CrmSalesProdutos',
                'controller_url' => 'crm-sales-produtos',
                'obs' => 'ABC de SKU, desconto, devolução e mix dos grupos 104/106. Sem custo/margem.',
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
            "SELECT id FROM adms_pages WHERE controller = 'CrmSalesDashboard' LIMIT 1"
        );
        if ($ref && $this->hasTable('adms_access_levels_pages')) {
            $refId = (int) $ref['id'];
            foreach (['CrmSalesCarteira', 'CrmSalesVendedores', 'CrmSalesProdutos'] as $controller) {
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
        }

        $this->bumpMenuCache();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        foreach (['CrmSalesProdutos', 'CrmSalesVendedores', 'CrmSalesCarteira'] as $controller) {
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
