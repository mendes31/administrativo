<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddInventoryProductionResourcesLabor extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('inv_item_operations') && !$this->table('inv_item_operations')->hasColumn('inv_production_resource_id')) {
            $this->table('inv_item_operations')
                ->addColumn('inv_production_resource_id', 'integer', [
                    'signed' => false,
                    'null' => true,
                    'after' => 'inv_operation_id',
                    'comment' => 'Recurso de produção (máquina/energia) vinculado à linha da rota',
                ])
                ->addForeignKey('inv_production_resource_id', 'inv_production_resources', 'id', [
                    'delete' => 'SET_NULL',
                    'update' => 'CASCADE',
                ])
                ->update();
        }

        if ($this->hasTable('inv_production_resources') && !$this->table('inv_production_resources')->hasColumn('power_kw')) {
            $this->table('inv_production_resources')
                ->addColumn('power_kw', 'decimal', [
                    'precision' => 12,
                    'scale' => 4,
                    'null' => true,
                    'default' => null,
                    'after' => 'energy_cost_per_min',
                    'comment' => 'Potência em kW (energia calculada)',
                ])
                ->update();
        }

        if (!$this->hasTable('inv_labor_roles')) {
            $this->table('inv_labor_roles')
                ->addColumn('code', 'string', ['limit' => 40, 'null' => true])
                ->addColumn('name', 'string', ['limit' => 120, 'null' => false])
                ->addColumn('default_cost_per_min', 'decimal', [
                    'precision' => 15,
                    'scale' => 6,
                    'null' => false,
                    'default' => 0,
                ])
                ->addColumn('adms_position_id', 'integer', [
                    'signed' => false,
                    'null' => true,
                    'comment' => 'Vínculo opcional com cargo RH',
                ])
                ->addColumn('active', 'boolean', ['default' => 1, 'null' => false])
                ->addColumn('created_at', 'timestamp')
                ->addColumn('updated_at', 'timestamp')
                ->addIndex(['name'], ['name' => 'idx_inv_labor_roles_name'])
                ->create();

            $this->table('inv_labor_roles')->insert([
                ['code' => 'OPERADOR', 'name' => 'Operador', 'default_cost_per_min' => 0.05, 'active' => 1, 'created_at' => date('Y-m-d H:i:s')],
                ['code' => 'MANIPULADOR', 'name' => 'Manipulador', 'default_cost_per_min' => 0.05, 'active' => 1, 'created_at' => date('Y-m-d H:i:s')],
                ['code' => 'AUXILIAR', 'name' => 'Auxiliar', 'default_cost_per_min' => 0.04, 'active' => 1, 'created_at' => date('Y-m-d H:i:s')],
                ['code' => 'FACILITADOR', 'name' => 'Facilitador de produção', 'default_cost_per_min' => 0.04, 'active' => 1, 'created_at' => date('Y-m-d H:i:s')],
            ])->saveData();
        }

        if (!$this->hasTable('inv_item_operation_labor')) {
            $this->table('inv_item_operation_labor')
                ->addColumn('inv_item_operation_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('inv_labor_role_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('qty', 'integer', ['signed' => false, 'null' => false, 'default' => 1])
                ->addColumn('cost_per_min', 'decimal', [
                    'precision' => 15,
                    'scale' => 6,
                    'null' => false,
                    'default' => 0,
                ])
                ->addColumn('created_at', 'timestamp')
                ->addForeignKey('inv_item_operation_id', 'inv_item_operations', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                ])
                ->addForeignKey('inv_labor_role_id', 'inv_labor_roles', 'id', [
                    'delete' => 'RESTRICT',
                    'update' => 'CASCADE',
                ])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('inv_item_operation_labor')) {
            $this->table('inv_item_operation_labor')->drop()->save();
        }
        if ($this->hasTable('inv_labor_roles')) {
            $this->table('inv_labor_roles')->drop()->save();
        }
        if ($this->hasTable('inv_item_operations') && $this->table('inv_item_operations')->hasColumn('inv_production_resource_id')) {
            $this->table('inv_item_operations')->dropForeignKey('inv_production_resource_id')->save();
            $this->table('inv_item_operations')->removeColumn('inv_production_resource_id')->update();
        }
        if ($this->hasTable('inv_production_resources') && $this->table('inv_production_resources')->hasColumn('power_kw')) {
            $this->table('inv_production_resources')->removeColumn('power_kw')->update();
        }
    }
}
