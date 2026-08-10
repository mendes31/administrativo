<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Cache MySQL do Dashboard de Vendas CRM (espelho agregado do SAP).
 * Grão: dia × tipo × cliente × dimensões comerciais.
 */
final class CreateCrmSalesSapCache extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('crm_sales_fact_daily')) {
            $this->table('crm_sales_fact_daily', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
            ])
                ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false, 'null' => false])
                ->addColumn('grain_hash', 'char', ['limit' => 40, 'null' => false])
                ->addColumn('doc_date', 'date', ['null' => false])
                ->addColumn('ano_mes', 'char', ['limit' => 7, 'null' => false])
                ->addColumn('tipo_documento', 'string', ['limit' => 20, 'null' => false])
                ->addColumn('card_code', 'string', ['limit' => 50, 'null' => false])
                ->addColumn('cliente', 'string', ['limit' => 255, 'null' => false, 'default' => ''])
                ->addColumn('vendedor', 'string', ['limit' => 150, 'null' => false, 'default' => ''])
                ->addColumn('grupo_cliente', 'string', ['limit' => 150, 'null' => false, 'default' => ''])
                ->addColumn('regiao', 'string', ['limit' => 150, 'null' => false, 'default' => ''])
                ->addColumn('grupo_item', 'string', ['limit' => 150, 'null' => false, 'default' => ''])
                ->addColumn('valor_liquido', 'decimal', ['precision' => 18, 'scale' => 4, 'null' => false, 'default' => '0'])
                ->addColumn('qtd_linhas', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
                ->addColumn('synced_at', 'datetime', ['null' => false])
                ->addColumn('created_at', 'datetime', ['null' => false])
                ->addColumn('updated_at', 'datetime', ['null' => true])
                ->addIndex(['grain_hash'], ['unique' => true, 'name' => 'uk_crm_sales_grain'])
                ->addIndex(['doc_date'], ['name' => 'idx_crm_sales_doc_date'])
                ->addIndex(['ano_mes'], ['name' => 'idx_crm_sales_ano_mes'])
                ->addIndex(['vendedor'], ['name' => 'idx_crm_sales_vendedor'])
                ->addIndex(['grupo_cliente'], ['name' => 'idx_crm_sales_grupo_cli'])
                ->addIndex(['regiao'], ['name' => 'idx_crm_sales_regiao'])
                ->addIndex(['grupo_item'], ['name' => 'idx_crm_sales_grupo_item'])
                ->addIndex(['card_code'], ['name' => 'idx_crm_sales_card'])
                ->create();
        }

        if (!$this->hasTable('crm_sales_sap_sync_runs')) {
            $this->table('crm_sales_sap_sync_runs')
                ->addColumn('sync_mode', 'string', [
                    'limit' => 20,
                    'null' => false,
                    'comment' => 'full | incremental | today',
                ])
                ->addColumn('source', 'string', [
                    'limit' => 10,
                    'null' => true,
                    'comment' => 'view | cte',
                ])
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

        if (!$this->hasTable('crm_sales_sap_sync_state')) {
            $this->table('crm_sales_sap_sync_state', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
            ])
                ->addColumn('id', 'integer', ['identity' => false, 'signed' => false, 'null' => false, 'default' => 1])
                ->addColumn('last_mode', 'string', ['limit' => 20, 'null' => true])
                ->addColumn('last_status', 'string', ['limit' => 20, 'null' => true])
                ->addColumn('last_source', 'string', ['limit' => 10, 'null' => true])
                ->addColumn('last_from_date', 'date', ['null' => true])
                ->addColumn('last_to_date', 'date', ['null' => true])
                ->addColumn('last_success_at', 'datetime', ['null' => true])
                ->addColumn('rows_upserted', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
                ->addColumn('message', 'text', ['null' => true])
                ->addColumn('updated_at', 'datetime', ['null' => false])
                ->create();

            $now = date('Y-m-d H:i:s');
            $this->execute(
                'INSERT INTO crm_sales_sap_sync_state (id, last_status, message, updated_at) VALUES (1, '
                . "'never', 'Aguardando primeira sincronização.', "
                . $this->getAdapter()->getConnection()->quote($now) . ')'
            );
        }

        $this->registerSyncPage();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            $row = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = 'CrmSalesDashboardSync' LIMIT 1"
            );
            if ($row) {
                $pid = (int) $row['id'];
                if ($this->hasTable('adms_access_levels_pages')) {
                    $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
                }
                $this->execute("DELETE FROM adms_pages WHERE id = {$pid}");
            }
        }

        if ($this->hasTable('crm_sales_fact_daily')) {
            $this->table('crm_sales_fact_daily')->drop()->save();
        }
        if ($this->hasTable('crm_sales_sap_sync_runs')) {
            $this->table('crm_sales_sap_sync_runs')->drop()->save();
        }
        if ($this->hasTable('crm_sales_sap_sync_state')) {
            $this->table('crm_sales_sap_sync_state')->drop()->save();
        }

        $this->bumpMenuCache();
    }

    private function registerSyncPage(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_groups_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $conn = $this->getAdapter()->getConnection();

        $existing = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'CrmSalesDashboardSync' LIMIT 1"
        );
        if ($existing) {
            return;
        }

        // Preferir o mesmo grupo de CrmSalesDashboard/CrmDashboard (ex.: "CRM - Operação").
        $group = $this->fetchRow(
            "SELECT adms_groups_page_id AS id FROM adms_pages WHERE controller = 'CrmSalesDashboard' LIMIT 1"
        );
        if (!$group) {
            $group = $this->fetchRow(
                "SELECT adms_groups_page_id AS id FROM adms_pages WHERE controller = 'CrmDashboard' LIMIT 1"
            );
        }
        if (!$group) {
            $group = $this->fetchRow(
                "SELECT id FROM adms_groups_pages WHERE name = 'CRM - Operação' LIMIT 1"
            );
        }
        if (!$group) {
            return;
        }
        $gid = (int) $group['id'];

        $this->execute(
            'INSERT INTO adms_pages
                (name, controller, controller_url, directory, obs, public_page, default_page, page_status,
                 adms_packages_page_id, adms_groups_page_id, created_at, updated_at)
             VALUES ('
            . $conn->quote('Sync Dashboard Vendas CRM') . ', '
            . $conn->quote('CrmSalesDashboardSync') . ', '
            . $conn->quote('crm-sales-dashboard-sync') . ', '
            . $conn->quote('crm') . ', '
            . $conn->quote('Dispara sincronização incremental do cache MySQL de vendas SAP.') . ', '
            . '0, 0, 1, 1, '
            . $gid . ', '
            . $conn->quote($now) . ', '
            . $conn->quote($now)
            . ')'
        );

        $ref = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'CrmSalesDashboard' LIMIT 1"
        );
        $page = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'CrmSalesDashboardSync' LIMIT 1"
        );
        if ($ref && $page && $this->hasTable('adms_access_levels_pages')) {
            $refId = (int) $ref['id'];
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
