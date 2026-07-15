<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInvCostRhDistribution extends AbstractMigration
{
    public function up(): void
    {
        $periods = $this->table('inv_cost_periods');
        if (!$periods->hasColumn('rh_simulation_increase_pct')) {
            $periods->addColumn('rh_simulation_increase_pct', 'decimal', [
                'precision' => 8,
                'scale' => 4,
                'null' => true,
                'after' => 'energy_auto_split',
                'comment' => 'Simulação: % de aumento sobre pool de pessoal (não altera snapshot oficial)',
            ])->update();
        }

        if (!$this->hasTable('inv_cost_rh_distribution_imports')) {
            $this->table('inv_cost_rh_distribution_imports')
                ->addColumn('inv_cost_period_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('filename', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('rows_imported', 'integer', ['default' => 0])
                ->addColumn('replace_previous', 'boolean', ['default' => true])
                ->addColumn('imported_by', 'integer', ['signed' => false, 'null' => true])
                ->addColumn('imported_at', 'timestamp', ['null' => true])
                ->addColumn('notes', 'text', ['null' => true, 'limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_REGULAR])
                ->addForeignKey('inv_cost_period_id', 'inv_cost_periods', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addIndex(['inv_cost_period_id'], ['name' => 'idx_inv_cost_rh_import_period'])
                ->create();
        }

        if (!$this->hasTable('inv_cost_rh_distribution_lines')) {
            $this->table('inv_cost_rh_distribution_lines')
                ->addColumn('inv_cost_period_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('rh_import_id', 'integer', ['signed' => false, 'null' => true])
                ->addColumn('area_name', 'string', ['limit' => 120, 'null' => false])
                ->addColumn('amount', 'decimal', ['precision' => 15, 'scale' => 4, 'default' => 0])
                ->addColumn('share_pct', 'decimal', ['precision' => 10, 'scale' => 6, 'null' => true])
                ->addColumn('criterion', 'integer', ['signed' => false, 'null' => false, 'default' => 2])
                ->addColumn('sort_order', 'integer', ['signed' => false, 'default' => 0])
                ->addColumn('created_at', 'timestamp', ['null' => true])
                ->addColumn('updated_at', 'timestamp', ['null' => true])
                ->addForeignKey('inv_cost_period_id', 'inv_cost_periods', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('rh_import_id', 'inv_cost_rh_distribution_imports', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addIndex(['inv_cost_period_id', 'area_name'], ['name' => 'idx_inv_cost_rh_line_period_area'])
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
                'name' => 'Importar Distribuição RH (Custeio)',
                'controller' => 'ImportInvCostRhDistribution',
                'controller_url' => 'import-inventory-cost-rh-distribution',
                'obs' => 'Importação CSV da distribuição de folha por área (Pasta 9).',
            ],
            [
                'name' => 'Salvar Distribuição RH (Custeio)',
                'controller' => 'SaveInvCostRhDistribution',
                'controller_url' => 'save-inventory-cost-rh-distribution',
                'obs' => 'Cadastro manual e simulação % da distribuição RH.',
            ],
            [
                'name' => 'Template Distribuição RH (Custeio)',
                'controller' => 'DownloadInvCostRhDistributionTemplate',
                'controller_url' => 'download-inventory-cost-rh-distribution-template',
                'obs' => 'CSV modelo área;valor;criterio.',
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
                    'directory' => 'inventory',
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
        if ($this->hasTable('inv_cost_rh_distribution_lines')) {
            $this->table('inv_cost_rh_distribution_lines')->drop()->save();
        }
        if ($this->hasTable('inv_cost_rh_distribution_imports')) {
            $this->table('inv_cost_rh_distribution_imports')->drop()->save();
        }

        $periods = $this->table('inv_cost_periods');
        if ($periods->hasColumn('rh_simulation_increase_pct')) {
            $periods->removeColumn('rh_simulation_increase_pct')->update();
        }

        if ($this->hasTable('adms_pages')) {
            $this->execute("DELETE FROM adms_pages WHERE controller IN (
                'ImportInvCostRhDistribution',
                'SaveInvCostRhDistribution',
                'DownloadInvCostRhDistributionTemplate'
            )");
        }
    }
}
