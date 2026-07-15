<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInvCostPeriodScenarioProduction extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('inv_cost_period_scenario_production')) {
            return;
        }

        $this->table('inv_cost_period_scenario_production')
            ->addColumn('inv_cost_period_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('inv_item_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('erp_code', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('item_description', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('batches_count', 'integer', ['default' => 0])
            ->addColumn('qty_produced', 'decimal', ['precision' => 15, 'scale' => 6, 'default' => 0])
            ->addColumn('notes', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['null' => true])
            ->addColumn('updated_at', 'timestamp', ['null' => true])
            ->addForeignKey('inv_cost_period_id', 'inv_cost_periods', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('inv_item_id', 'inv_items', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addIndex(['inv_cost_period_id', 'erp_code'], ['name' => 'idx_inv_cost_scenario_period_erp'])
            ->create();

        $this->registerScenarioPage();
    }

    private function registerScenarioPage(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'SaveInvCostPeriodScenarioProduction' LIMIT 1");
        if ($exists) {
            return;
        }

        $groupRow = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name LIKE '%Estoque%' OR name LIKE '%Inventory%' LIMIT 1");
        $groupId = $groupRow ? (int)$groupRow['id'] : 33;
        $now = date('Y-m-d H:i:s');

        $this->table('adms_pages')->insert([
            [
                'name' => 'Salvar Produção Simulada (Custeio)',
                'controller' => 'SaveInvCostPeriodScenarioProduction',
                'controller_url' => 'save-inventory-cost-scenario-production',
                'directory' => 'inventory',
                'obs' => 'Inclui lotes fictícios no rateio do período (rascunho).',
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

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            $this->execute("DELETE FROM adms_pages WHERE controller = 'SaveInvCostPeriodScenarioProduction'");
        }
        if ($this->hasTable('inv_cost_period_scenario_production')) {
            $this->table('inv_cost_period_scenario_production')->drop()->save();
        }
    }
}
