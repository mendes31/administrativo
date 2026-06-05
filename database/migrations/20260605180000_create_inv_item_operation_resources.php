<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInvItemOperationResources extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_item_operation_resources')) {
            $this->table('inv_item_operation_resources')
                ->addColumn('inv_item_operation_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('inv_production_resource_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('qty', 'integer', ['signed' => false, 'null' => false, 'default' => 1])
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
                ->addColumn('created_at', 'timestamp')
                ->addForeignKey('inv_item_operation_id', 'inv_item_operations', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                ])
                ->addForeignKey('inv_production_resource_id', 'inv_production_resources', 'id', [
                    'delete' => 'RESTRICT',
                    'update' => 'CASCADE',
                ])
                ->create();
        }

        if (
            $this->hasTable('inv_item_operations')
            && $this->hasTable('inv_production_resources')
            && $this->table('inv_item_operations')->hasColumn('inv_production_resource_id')
        ) {
            $this->execute(
                'INSERT INTO inv_item_operation_resources
                    (inv_item_operation_id, inv_production_resource_id, qty, machine_cost_per_min, energy_cost_per_min, created_at)
                 SELECT io.id, io.inv_production_resource_id, 1,
                        COALESCE(io.machine_cost_per_min, pr.machine_cost_per_min, 0),
                        COALESCE(io.energy_cost_per_min, pr.energy_cost_per_min, 0),
                        NOW()
                 FROM inv_item_operations io
                 INNER JOIN inv_production_resources pr ON pr.id = io.inv_production_resource_id
                 WHERE io.inv_production_resource_id IS NOT NULL
                   AND NOT EXISTS (
                       SELECT 1 FROM inv_item_operation_resources ior
                       WHERE ior.inv_item_operation_id = io.id
                   )'
            );
        }
    }

    public function down(): void
    {
        if ($this->hasTable('inv_item_operation_resources')) {
            $this->table('inv_item_operation_resources')->drop()->save();
        }
    }
}
