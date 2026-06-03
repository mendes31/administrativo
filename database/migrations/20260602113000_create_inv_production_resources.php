<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInvProductionResources extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_production_resources')) {
            $this->table('inv_production_resources')
                ->addColumn('erp_code', 'string', [
                    'limit' => 80,
                    'null' => false,
                    'comment' => 'Código do recurso no SAP/Beas (ex.: MANIPULADOR)',
                ])
                ->addColumn('name', 'string', [
                    'limit' => 120,
                    'null' => false,
                    'comment' => 'Nome do recurso',
                ])
                ->addColumn('resource_type', 'string', [
                    'limit' => 20,
                    'null' => false,
                    'default' => 'LABOR',
                    'comment' => 'LABOR, MACHINE, ENERGY ou MIXED',
                ])
                ->addColumn('labor_cost_per_min', 'decimal', [
                    'precision' => 15,
                    'scale' => 6,
                    'null' => false,
                    'default' => 0,
                ])
                ->addColumn('machine_cost_per_min', 'decimal', [
                    'precision' => 15,
                    'scale' => 6,
                    'null' => false,
                    'default' => 0,
                ])
                ->addColumn('energy_cost_per_min', 'decimal', [
                    'precision' => 15,
                    'scale' => 6,
                    'null' => false,
                    'default' => 0,
                ])
                ->addColumn('active', 'boolean', [
                    'default' => 1,
                    'null' => false,
                ])
                ->addColumn('created_at', 'timestamp')
                ->addColumn('updated_at', 'timestamp')
                ->addIndex(['erp_code'], ['unique' => true, 'name' => 'idx_inv_production_resources_erp_code'])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('inv_production_resources')) {
            $this->table('inv_production_resources')->drop()->save();
        }
    }
}
