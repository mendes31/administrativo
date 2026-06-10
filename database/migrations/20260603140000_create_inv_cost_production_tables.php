<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInvCostProductionTables extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_cost_production_warehouses')) {
            $this->table('inv_cost_production_warehouses')
                ->addColumn('code', 'string', ['limit' => 20, 'null' => false])
                ->addColumn('name', 'string', ['limit' => 120, 'null' => false])
                ->addColumn('active', 'boolean', ['default' => true])
                ->addColumn('include_in_sync', 'boolean', ['default' => true])
                ->addColumn('created_at', 'timestamp', ['null' => true])
                ->addColumn('updated_at', 'timestamp', ['null' => true])
                ->addIndex(['code'], ['unique' => true, 'name' => 'uniq_inv_cost_prod_wh_code'])
                ->create();
        }

        if (!$this->hasTable('inv_cost_production_sync_runs')) {
            $this->table('inv_cost_production_sync_runs')
                ->addColumn('source', 'string', ['limit' => 20, 'default' => 'SAP'])
                ->addColumn('sync_mode', 'string', ['limit' => 20, 'default' => 'full'])
                ->addColumn('warehouse_codes_synced', 'text', ['null' => true, 'limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_REGULAR])
                ->addColumn('filter_from_date', 'date', ['null' => true])
                ->addColumn('rows_inserted', 'integer', ['default' => 0])
                ->addColumn('rows_updated', 'integer', ['default' => 0])
                ->addColumn('rows_skipped', 'integer', ['default' => 0])
                ->addColumn('status', 'string', ['limit' => 20, 'default' => 'running'])
                ->addColumn('error_log', 'text', ['null' => true, 'limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_REGULAR])
                ->addColumn('started_at', 'timestamp', ['null' => true])
                ->addColumn('finished_at', 'timestamp', ['null' => true])
                ->create();
        }

        if (!$this->hasTable('inv_cost_production_batches')) {
            $this->table('inv_cost_production_batches')
                ->addColumn('inv_item_id', 'integer', ['signed' => false, 'null' => true])
                ->addColumn('erp_code', 'string', ['limit' => 50, 'null' => false])
                ->addColumn('item_description', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('series_remark', 'string', ['limit' => 120, 'null' => true])
                ->addColumn('production_date', 'date', ['null' => false])
                ->addColumn('doc_date', 'date', ['null' => true])
                ->addColumn('goods_receipt_doc_num', 'integer', ['null' => true])
                ->addColumn('production_order_num', 'integer', ['null' => true])
                ->addColumn('base_type', 'integer', ['null' => true])
                ->addColumn('base_entry', 'integer', ['null' => true])
                ->addColumn('batch_number', 'string', ['limit' => 80, 'null' => false])
                ->addColumn('mnf_date', 'date', ['null' => true])
                ->addColumn('exp_date', 'date', ['null' => true])
                ->addColumn('warehouse_code', 'string', ['limit' => 20, 'null' => false])
                ->addColumn('warehouse_name', 'string', ['limit' => 120, 'null' => true])
                ->addColumn('quantity', 'decimal', ['precision' => 15, 'scale' => 6, 'default' => 0])
                ->addColumn('source', 'string', ['limit' => 20, 'default' => 'SAP'])
                ->addColumn('sap_sync_run_id', 'integer', ['signed' => false, 'null' => true])
                ->addColumn('imported_by', 'integer', ['signed' => false, 'null' => true])
                ->addColumn('imported_at', 'timestamp', ['null' => true])
                ->addColumn('updated_at', 'timestamp', ['null' => true])
                ->addForeignKey('inv_item_id', 'inv_items', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('sap_sync_run_id', 'inv_cost_production_sync_runs', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addIndex(['production_date'], ['name' => 'idx_inv_cost_prod_batch_date'])
                ->addIndex(['warehouse_code'], ['name' => 'idx_inv_cost_prod_batch_wh'])
                ->addIndex(['erp_code'], ['name' => 'idx_inv_cost_prod_batch_erp'])
                ->addIndex(
                    ['base_entry', 'batch_number', 'erp_code', 'goods_receipt_doc_num', 'warehouse_code'],
                    ['unique' => true, 'name' => 'uniq_inv_cost_prod_batch_natural']
                )
                ->create();
        }

        if (!$this->hasTable('inv_cost_periods')) {
            $this->table('inv_cost_periods')
                ->addColumn('name', 'string', ['limit' => 120, 'null' => false])
                ->addColumn('date_from', 'date', ['null' => false])
                ->addColumn('date_to', 'date', ['null' => false])
                ->addColumn('status', 'string', ['limit' => 20, 'default' => 'draft'])
                ->addColumn('kwh_tariff', 'decimal', ['precision' => 12, 'scale' => 6, 'null' => true])
                ->addColumn('notes', 'text', ['null' => true, 'limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_REGULAR])
                ->addColumn('created_at', 'timestamp', ['null' => true])
                ->addColumn('updated_at', 'timestamp', ['null' => true])
                ->addIndex(['date_from', 'date_to'], ['name' => 'idx_inv_cost_period_dates'])
                ->create();
        }

        $this->seedWarehouses();
        $this->registerPages();
    }

    private function seedWarehouses(): void
    {
        if (!$this->hasTable('inv_cost_production_warehouses')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $defaults = [
            ['code' => 'TJQP', 'name' => 'Quarentena Produção TJ'],
            ['code' => 'APQP', 'name' => 'Quarentena Produção AP'],
        ];

        foreach ($defaults as $row) {
            $code = $row['code'];
            $exists = $this->fetchRow("SELECT id FROM inv_cost_production_warehouses WHERE code = '{$code}' LIMIT 1");
            if ($exists) {
                continue;
            }
            $this->table('inv_cost_production_warehouses')->insert([
                [
                    'code' => $code,
                    'name' => $row['name'],
                    'active' => 1,
                    'include_in_sync' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ])->save();
        }
    }

    private function registerPages(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $group = $this->fetchRow('SELECT id FROM adms_groups_pages WHERE id = 33 LIMIT 1');
        $groupId = $group ? (int)$group['id'] : 33;
        $now = date('Y-m-d H:i:s');

        $pages = [
            [
                'name' => 'Lotes Produzidos (Custeio)',
                'controller' => 'ListInvCostProductionBatches',
                'controller_url' => 'list-inventory-cost-production-batches',
                'directory' => 'inventory',
                'obs' => 'Listagem e sincronização SAP dos lotes produzidos para custeio fabril.',
            ],
            [
                'name' => 'Períodos de Custeio',
                'controller' => 'ListInvCostPeriods',
                'controller_url' => 'list-inventory-cost-periods',
                'directory' => 'inventory',
                'obs' => 'Cadastro de períodos analíticos para custeio e simulação.',
            ],
            [
                'name' => 'Cadastrar Período de Custeio',
                'controller' => 'CreateInvCostPeriod',
                'controller_url' => 'create-inventory-cost-period',
                'directory' => 'inventory',
                'obs' => 'Formulário de novo período de custeio.',
            ],
        ];

        foreach ($pages as $page) {
            $controller = $page['controller'];
            $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$controller}' LIMIT 1");
            if ($exists) {
                continue;
            }
            $this->table('adms_pages')->insert([
                [
                    'name' => $page['name'],
                    'controller' => $page['controller'],
                    'controller_url' => $page['controller_url'],
                    'directory' => $page['directory'],
                    'obs' => $page['obs'],
                    'page_status' => 1,
                    'public_page' => 0,
                    'default_page' => 1,
                    'adms_packages_page_id' => 1,
                    'adms_groups_page_id' => $groupId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ])->save();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('inv_cost_production_batches')) {
            $this->table('inv_cost_production_batches')->drop()->save();
        }
        if ($this->hasTable('inv_cost_production_sync_runs')) {
            $this->table('inv_cost_production_sync_runs')->drop()->save();
        }
        if ($this->hasTable('inv_cost_periods')) {
            $this->table('inv_cost_periods')->drop()->save();
        }
        if ($this->hasTable('inv_cost_production_warehouses')) {
            $this->table('inv_cost_production_warehouses')->drop()->save();
        }
        if ($this->hasTable('adms_pages')) {
            $this->execute("DELETE FROM adms_pages WHERE controller IN (
                'ListInvCostProductionBatches',
                'ListInvCostPeriods',
                'CreateInvCostPeriod'
            )");
        }
    }
}
