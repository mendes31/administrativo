<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInvCostExpenseTables extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_cost_dre_imports')) {
            $this->table('inv_cost_dre_imports')
                ->addColumn('inv_cost_period_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('filename', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('rows_imported', 'integer', ['default' => 0])
                ->addColumn('replace_previous', 'boolean', ['default' => false])
                ->addColumn('imported_by', 'integer', ['signed' => false, 'null' => true])
                ->addColumn('imported_at', 'timestamp', ['null' => true])
                ->addColumn('notes', 'text', ['null' => true, 'limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_REGULAR])
                ->addForeignKey('inv_cost_period_id', 'inv_cost_periods', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addIndex(['inv_cost_period_id'], ['name' => 'idx_inv_cost_dre_import_period'])
                ->create();
        }

        if (!$this->hasTable('inv_cost_expense_pools')) {
            $this->table('inv_cost_expense_pools')
                ->addColumn('inv_cost_period_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('dre_import_id', 'integer', ['signed' => false, 'null' => true])
                ->addColumn('source', 'string', ['limit' => 20, 'default' => 'DRE'])
                ->addColumn('account_code', 'string', ['limit' => 40, 'null' => false])
                ->addColumn('description', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('amount', 'decimal', ['precision' => 15, 'scale' => 4, 'default' => 0])
                ->addColumn('area', 'string', ['limit' => 80, 'null' => true])
                ->addColumn('redistribution_group', 'string', ['limit' => 40, 'null' => true])
                ->addColumn('created_at', 'timestamp', ['null' => true])
                ->addColumn('updated_at', 'timestamp', ['null' => true])
                ->addForeignKey('inv_cost_period_id', 'inv_cost_periods', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('dre_import_id', 'inv_cost_dre_imports', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addIndex(['inv_cost_period_id', 'account_code'], ['name' => 'idx_inv_cost_exp_pool_period_acct'])
                ->create();
        }

        if (!$this->hasTable('inv_cost_allocation_rules')) {
            $this->table('inv_cost_allocation_rules')
                ->addColumn('expense_pool_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('criterion', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('weight_pct', 'decimal', ['precision' => 8, 'scale' => 4, 'default' => 100])
                ->addColumn('created_at', 'timestamp', ['null' => true])
                ->addColumn('updated_at', 'timestamp', ['null' => true])
                ->addForeignKey('expense_pool_id', 'inv_cost_expense_pools', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addIndex(['expense_pool_id'], ['name' => 'idx_inv_cost_alloc_pool'])
                ->create();
        }

        if (!$this->hasTable('inv_cost_period_items')) {
            $this->table('inv_cost_period_items')
                ->addColumn('inv_cost_period_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('inv_item_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('batch_size_theoretical', 'decimal', ['precision' => 15, 'scale' => 6, 'null' => true])
                ->addColumn('batch_size_adopted', 'decimal', ['precision' => 15, 'scale' => 6, 'null' => true])
                ->addColumn('efficiency_pct', 'decimal', ['precision' => 8, 'scale' => 4, 'null' => true])
                ->addColumn('production_line', 'string', ['limit' => 40, 'null' => true])
                ->addColumn('energy_class', 'string', ['limit' => 20, 'null' => true])
                ->addColumn('complexity_level', 'string', ['limit' => 20, 'null' => true])
                ->addColumn('analysis_count', 'integer', ['default' => 0])
                ->addColumn('sale_price_net', 'decimal', ['precision' => 15, 'scale' => 4, 'null' => true])
                ->addColumn('target_margin_pct', 'decimal', ['precision' => 8, 'scale' => 4, 'null' => true])
                ->addColumn('created_at', 'timestamp', ['null' => true])
                ->addColumn('updated_at', 'timestamp', ['null' => true])
                ->addForeignKey('inv_cost_period_id', 'inv_cost_periods', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('inv_item_id', 'inv_items', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addIndex(['inv_cost_period_id', 'inv_item_id'], ['unique' => true, 'name' => 'uniq_inv_cost_period_item'])
                ->create();
        }

        $this->registerPages();
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
                'name' => 'Visualizar Período de Custeio',
                'controller' => 'ViewInvCostPeriod',
                'controller_url' => 'view-inventory-cost-period',
                'directory' => 'inventory',
                'obs' => 'DRE, despesas fixas e critérios de rateio do período.',
            ],
            [
                'name' => 'Editar Período de Custeio',
                'controller' => 'UpdateInvCostPeriod',
                'controller_url' => 'update-inventory-cost-period',
                'directory' => 'inventory',
                'obs' => 'Edição de período analítico de custeio.',
            ],
            [
                'name' => 'Importar DRE (Custeio)',
                'controller' => 'ImportInvCostDre',
                'controller_url' => 'import-inventory-cost-dre',
                'directory' => 'inventory',
                'obs' => 'Importação CSV do balancete DRE para o período.',
            ],
            [
                'name' => 'Salvar Regras de Rateio (Custeio)',
                'controller' => 'SaveInvCostAllocationRules',
                'controller_url' => 'save-inventory-cost-allocation-rules',
                'directory' => 'inventory',
                'obs' => 'Persistência dos critérios de rateio por conta despesa.',
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
                    'default_page' => 0,
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
        foreach (['inv_cost_allocation_rules', 'inv_cost_expense_pools', 'inv_cost_dre_imports', 'inv_cost_period_items'] as $table) {
            if ($this->hasTable($table)) {
                $this->table($table)->drop()->save();
            }
        }
        if ($this->hasTable('adms_pages')) {
            $this->execute("DELETE FROM adms_pages WHERE controller IN (
                'ViewInvCostPeriod',
                'UpdateInvCostPeriod',
                'ImportInvCostDre',
                'SaveInvCostAllocationRules'
            )");
        }
    }
}
