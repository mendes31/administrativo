<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInvCostSimulations extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_cost_simulations')) {
            $this->table('inv_cost_simulations')
                ->addColumn('inv_item_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('title', 'string', ['limit' => 150, 'null' => false])
                ->addColumn('material_adjust_pct', 'decimal', ['precision' => 9, 'scale' => 4, 'default' => 0])
                ->addColumn('operations_adjust_pct', 'decimal', ['precision' => 9, 'scale' => 4, 'default' => 0])
                ->addColumn('global_adjust_pct', 'decimal', ['precision' => 9, 'scale' => 4, 'default' => 0])
                ->addColumn('standard_batch_size', 'decimal', ['precision' => 15, 'scale' => 6, 'default' => 1])
                ->addColumn('base_total_unit', 'decimal', ['precision' => 15, 'scale' => 6, 'default' => 0])
                ->addColumn('base_total_batch', 'decimal', ['precision' => 15, 'scale' => 6, 'default' => 0])
                ->addColumn('simulated_total_unit', 'decimal', ['precision' => 15, 'scale' => 6, 'default' => 0])
                ->addColumn('simulated_total_batch', 'decimal', ['precision' => 15, 'scale' => 6, 'default' => 0])
                ->addColumn('breakdown_json', 'text', ['null' => false, 'limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_LONG])
                ->addColumn('created_by', 'integer', ['signed' => false, 'null' => true])
                ->addColumn('created_at', 'timestamp')
                ->addForeignKey('inv_item_id', 'inv_items', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addIndex(['inv_item_id', 'created_at'], ['name' => 'idx_inv_cost_sim_item_created'])
                ->create();
        }

        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $group = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE id = 33 LIMIT 1");
        $groupId = $group ? (int)$group['id'] : 33;
        $now = date('Y-m-d H:i:s');

        $pages = [
            [
                'name' => 'Salvar Simulação de Custo',
                'controller' => 'SaveInventoryCostSimulation',
                'controller_url' => 'save-inventory-cost-simulation',
                'directory' => 'inventory',
                'obs' => 'Persiste cenário de simulação de custo do item.',
            ],
            [
                'name' => 'Exportar Simulação de Custo PDF',
                'controller' => 'ExportInventoryCostSimulationPdf',
                'controller_url' => 'export-inventory-cost-simulation-pdf',
                'directory' => 'inventory',
                'obs' => 'Gera PDF da simulação de custo (salva ou atual).',
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
        if ($this->hasTable('inv_cost_simulations')) {
            $this->table('inv_cost_simulations')->drop()->save();
        }
        if ($this->hasTable('adms_pages')) {
            $this->execute("DELETE FROM adms_pages WHERE controller IN ('SaveInventoryCostSimulation', 'ExportInventoryCostSimulationPdf')");
        }
    }
}
