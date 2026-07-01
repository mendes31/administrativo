<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInvItemRouteConsolidated extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_item_route_consolidated')) {
            $this->table('inv_item_route_consolidated')
                ->addColumn('inv_item_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('inv_operation_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('sequence', 'integer', ['signed' => false, 'null' => false, 'default' => 1])
                ->addColumn('sap_group_pos_id', 'integer', [
                    'signed' => false,
                    'null' => true,
                    'comment' => 'POS_ID mestre do grupo SAP (MASTER_POS_ID ou POS_ID)',
                ])
                ->addColumn('time_per_batch_hours', 'decimal', [
                    'precision' => 12,
                    'scale' => 6,
                    'null' => false,
                    'default' => 0,
                ])
                ->addColumn('time_unit', 'string', ['limit' => 3, 'null' => false, 'default' => 'MIN'])
                ->addColumn('notes', 'text', ['null' => true])
                ->addColumn('created_at', 'timestamp', ['null' => true])
                ->addColumn('updated_at', 'timestamp', ['null' => true])
                ->addIndex(['inv_item_id', 'sequence'], ['name' => 'idx_inv_route_cons_item_seq'])
                ->addForeignKey('inv_item_id', 'inv_items', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('inv_operation_id', 'inv_operations', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('inv_item_route_consolidated_resources')) {
            $this->table('inv_item_route_consolidated_resources')
                ->addColumn('inv_route_consolidated_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('inv_production_resource_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('qty', 'integer', ['signed' => false, 'null' => false, 'default' => 1])
                ->addColumn('machine_cost_per_min', 'decimal', ['precision' => 15, 'scale' => 6, 'null' => false, 'default' => 0])
                ->addColumn('energy_cost_per_min', 'decimal', ['precision' => 15, 'scale' => 6, 'null' => false, 'default' => 0])
                ->addColumn('created_at', 'timestamp', ['null' => true])
                ->addForeignKey('inv_route_consolidated_id', 'inv_item_route_consolidated', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                ])
                ->addForeignKey('inv_production_resource_id', 'inv_production_resources', 'id', [
                    'delete' => 'RESTRICT',
                    'update' => 'CASCADE',
                ])
                ->create();
        }

        if (!$this->hasTable('inv_item_route_consolidated_labor')) {
            $this->table('inv_item_route_consolidated_labor')
                ->addColumn('inv_route_consolidated_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('inv_labor_role_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('qty', 'integer', ['signed' => false, 'null' => false, 'default' => 1])
                ->addColumn('cost_per_min', 'decimal', ['precision' => 15, 'scale' => 6, 'null' => false, 'default' => 0])
                ->addColumn('created_at', 'timestamp', ['null' => true])
                ->addForeignKey('inv_route_consolidated_id', 'inv_item_route_consolidated', 'id', [
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
        if ($this->hasTable('inv_item_route_consolidated_labor')) {
            $this->table('inv_item_route_consolidated_labor')->drop()->save();
        }
        if ($this->hasTable('inv_item_route_consolidated_resources')) {
            $this->table('inv_item_route_consolidated_resources')->drop()->save();
        }
        if ($this->hasTable('inv_item_route_consolidated')) {
            $this->table('inv_item_route_consolidated')->drop()->save();
        }
    }
}
