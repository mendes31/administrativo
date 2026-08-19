<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Módulo Produção: cache de ordens SAP/BEAS + páginas ACL do dashboard.
 */
final class CreateProdProductionModule extends AbstractMigration
{
    private const GROUP_NAME = 'Produção';

    public function up(): void
    {
        if (!$this->hasTable('adms_prod_wo_fact')) {
            $this->table('adms_prod_wo_fact', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
            ])
                ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false, 'null' => false])
                ->addColumn('source', 'string', [
                    'limit' => 20,
                    'null' => false,
                    'default' => 'owor',
                    'comment' => 'owor | beas',
                ])
                ->addColumn('doc_entry', 'integer', ['null' => false])
                ->addColumn('doc_num', 'integer', ['null' => false, 'default' => 0])
                ->addColumn('item_code', 'string', ['limit' => 50, 'null' => false])
                ->addColumn('item_name', 'string', ['limit' => 255, 'null' => false, 'default' => ''])
                ->addColumn('product_name', 'string', ['limit' => 150, 'null' => false, 'default' => ''])
                ->addColumn('line_name', 'string', ['limit' => 150, 'null' => false, 'default' => ''])
                ->addColumn('warehouse', 'string', ['limit' => 30, 'null' => false, 'default' => ''])
                ->addColumn('status_code', 'string', ['limit' => 10, 'null' => false, 'default' => ''])
                ->addColumn('status_label', 'string', ['limit' => 30, 'null' => false, 'default' => ''])
                ->addColumn('qty_planned', 'decimal', ['precision' => 18, 'scale' => 4, 'null' => false, 'default' => '0'])
                ->addColumn('qty_completed', 'decimal', ['precision' => 18, 'scale' => 4, 'null' => false, 'default' => '0'])
                ->addColumn('qty_scrap', 'decimal', ['precision' => 18, 'scale' => 4, 'null' => false, 'default' => '0'])
                ->addColumn('start_date', 'date', ['null' => true])
                ->addColumn('due_date', 'date', ['null' => true])
                ->addColumn('close_date', 'date', ['null' => true])
                ->addColumn('cycle_hours', 'decimal', ['precision' => 12, 'scale' => 2, 'null' => true])
                ->addColumn('downtime_hours', 'decimal', ['precision' => 12, 'scale' => 2, 'null' => true])
                ->addColumn('synced_at', 'datetime', ['null' => false])
                ->addColumn('created_at', 'datetime', ['null' => false])
                ->addColumn('updated_at', 'datetime', ['null' => true])
                ->addIndex(['source', 'doc_entry'], ['unique' => true, 'name' => 'uk_prod_wo_source_entry'])
                ->addIndex(['start_date'], ['name' => 'idx_prod_wo_start'])
                ->addIndex(['close_date'], ['name' => 'idx_prod_wo_close'])
                ->addIndex(['due_date'], ['name' => 'idx_prod_wo_due'])
                ->addIndex(['status_code'], ['name' => 'idx_prod_wo_status'])
                ->addIndex(['item_code'], ['name' => 'idx_prod_wo_item'])
                ->addIndex(['line_name'], ['name' => 'idx_prod_wo_line'])
                ->create();
        }

        if (!$this->hasTable('adms_prod_sap_sync_runs')) {
            $this->table('adms_prod_sap_sync_runs')
                ->addColumn('sync_mode', 'string', [
                    'limit' => 20,
                    'null' => false,
                    'comment' => 'full | incremental | today',
                ])
                ->addColumn('source', 'string', ['limit' => 20, 'null' => true])
                ->addColumn('date_from', 'date', ['null' => true])
                ->addColumn('date_to', 'date', ['null' => true])
                ->addColumn('rows_fetched', 'integer', ['signed' => false, 'default' => 0])
                ->addColumn('rows_upserted', 'integer', ['signed' => false, 'default' => 0])
                ->addColumn('status', 'string', ['limit' => 20, 'default' => 'running'])
                ->addColumn('error_log', 'text', ['null' => true])
                ->addColumn('started_at', 'datetime', ['null' => false])
                ->addColumn('finished_at', 'datetime', ['null' => true])
                ->addIndex(['status', 'finished_at'])
                ->create();
        }

        if (!$this->hasTable('adms_prod_sap_sync_state')) {
            $this->table('adms_prod_sap_sync_state', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
            ])
                ->addColumn('id', 'integer', ['identity' => false, 'signed' => false, 'null' => false, 'default' => 1])
                ->addColumn('last_mode', 'string', ['limit' => 20, 'null' => true])
                ->addColumn('last_status', 'string', ['limit' => 20, 'null' => true])
                ->addColumn('last_source', 'string', ['limit' => 20, 'null' => true])
                ->addColumn('last_from_date', 'date', ['null' => true])
                ->addColumn('last_to_date', 'date', ['null' => true])
                ->addColumn('last_success_at', 'datetime', ['null' => true])
                ->addColumn('rows_upserted', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
                ->addColumn('beas_tables', 'text', ['null' => true])
                ->addColumn('message', 'text', ['null' => true])
                ->addColumn('updated_at', 'datetime', ['null' => false])
                ->create();

            $now = date('Y-m-d H:i:s');
            $this->execute(
                'INSERT INTO adms_prod_sap_sync_state (id, last_status, message, updated_at) VALUES (1, '
                . "'never', 'Aguardando primeira sincronização.', "
                . $this->getAdapter()->getConnection()->quote($now) . ')'
            );
        }

        $this->registerGroupAndPages();
    }

    public function down(): void
    {
        $controllers = [
            'ProdProductionDashboardSync',
            'ProdProductionDashboardData',
            'ProdProductionDashboard',
        ];
        foreach ($controllers as $controller) {
            $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$controller}' LIMIT 1");
            if (!$row) {
                continue;
            }
            $pid = (int) $row['id'];
            if ($this->hasTable('adms_access_levels_pages')) {
                $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
            }
            $this->execute("DELETE FROM adms_pages WHERE id = {$pid}");
        }

        foreach (['adms_prod_wo_fact', 'adms_prod_sap_sync_runs', 'adms_prod_sap_sync_state'] as $table) {
            if ($this->hasTable($table)) {
                $this->table($table)->drop()->save();
            }
        }

        $this->bumpMenuCache();
    }

    private function registerGroupAndPages(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_groups_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $conn = $this->getAdapter()->getConnection();

        $existsGroup = $this->fetchRow(
            'SELECT id FROM adms_groups_pages WHERE name = ' . $conn->quote(self::GROUP_NAME) . ' LIMIT 1'
        );
        if (!$existsGroup) {
            $hasUpdated = $this->table('adms_groups_pages')->hasColumn('updated_at');
            if ($hasUpdated) {
                $this->execute(
                    'INSERT INTO adms_groups_pages (name, obs, created_at, updated_at) VALUES ('
                    . $conn->quote(self::GROUP_NAME) . ', '
                    . $conn->quote('Dashboard de produção SAP/BEAS (ADR-0011)') . ', '
                    . $conn->quote($now) . ', '
                    . $conn->quote($now) . ')'
                );
            } else {
                $this->execute(
                    'INSERT INTO adms_groups_pages (name, obs, created_at) VALUES ('
                    . $conn->quote(self::GROUP_NAME) . ', '
                    . $conn->quote('Dashboard de produção SAP/BEAS (ADR-0011)') . ', '
                    . $conn->quote($now) . ')'
                );
            }
        }

        $groupRow = $this->fetchRow(
            'SELECT id FROM adms_groups_pages WHERE name = ' . $conn->quote(self::GROUP_NAME) . ' LIMIT 1'
        );
        if (!$groupRow) {
            return;
        }
        $gid = (int) $groupRow['id'];

        $pages = [
            [
                'name' => 'Dashboard de Produção',
                'controller' => 'ProdProductionDashboard',
                'controller_url' => 'prod-production-dashboard',
                'obs' => 'Dashboard de produção (SKUs, produtos, ordens) com cache SAP/BEAS.',
            ],
            [
                'name' => 'API Dashboard de Produção',
                'controller' => 'ProdProductionDashboardData',
                'controller_url' => 'prod-production-dashboard-data',
                'obs' => 'Endpoint JSON agregado (KPIs/gráficos) do Dashboard de Produção.',
            ],
            [
                'name' => 'Sync Dashboard de Produção',
                'controller' => 'ProdProductionDashboardSync',
                'controller_url' => 'prod-production-dashboard-sync',
                'obs' => 'Dispara sincronização SAP/BEAS → cache MySQL do dashboard de produção.',
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
                . $conn->quote('production') . ', '
                . $conn->quote($p['obs']) . ', '
                . '0, 0, 1, 1, '
                . $gid . ', '
                . $conn->quote($now) . ', '
                . $conn->quote($now)
                . ')'
            );
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
