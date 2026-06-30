<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInvCostPeriodSkuSnapshots extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_cost_period_sku_snapshots')) {
            $this->table('inv_cost_period_sku_snapshots')
                ->addColumn('inv_cost_period_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('inv_item_id', 'integer', ['signed' => false, 'null' => true])
                ->addColumn('erp_code', 'string', ['limit' => 40, 'null' => false, 'default' => ''])
                ->addColumn('item_description', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('category_name', 'string', ['limit' => 120, 'null' => true])
                ->addColumn('linked', 'boolean', ['default' => false])
                ->addColumn('is_scenario', 'boolean', ['default' => false])
                ->addColumn('has_scenario', 'boolean', ['default' => false])
                ->addColumn('is_produto_acabado', 'boolean', ['default' => false])
                ->addColumn('batches_count', 'integer', ['default' => 0])
                ->addColumn('total_qty', 'decimal', ['precision' => 15, 'scale' => 4, 'null' => true])
                ->addColumn('qty_planned', 'decimal', ['precision' => 15, 'scale' => 4, 'null' => true])
                ->addColumn('efficiency_ratio', 'decimal', ['precision' => 10, 'scale' => 6, 'null' => true])
                ->addColumn('efficiency_pct', 'decimal', ['precision' => 8, 'scale' => 4, 'null' => true])
                ->addColumn('standard_batch_size', 'decimal', ['precision' => 15, 'scale' => 6, 'null' => true])
                ->addColumn('energy_class', 'string', ['limit' => 20, 'null' => true])
                ->addColumn('complexity_level', 'string', ['limit' => 20, 'null' => true])
                ->addColumn('complexity_factor', 'decimal', ['precision' => 8, 'scale' => 4, 'null' => true])
                ->addColumn('production_line', 'string', ['limit' => 40, 'null' => true])
                ->addColumn('mp_lines', 'integer', ['default' => 0])
                ->addColumn('mae_lines', 'integer', ['default' => 0])
                ->addColumn('analysis_lines_per_batch', 'integer', ['default' => 0])
                ->addColumn('analysis_count_total', 'decimal', ['precision' => 12, 'scale' => 4, 'null' => true])
                ->addColumn('driver_4', 'decimal', ['precision' => 12, 'scale' => 6, 'null' => true])
                ->addColumn('driver_6', 'decimal', ['precision' => 12, 'scale' => 6, 'null' => true])
                ->addColumn('share_criterion_4', 'decimal', ['precision' => 10, 'scale' => 4, 'null' => true])
                ->addColumn('share_criterion_6', 'decimal', ['precision' => 10, 'scale' => 4, 'null' => true])
                ->addColumn('energy_class_from_item', 'boolean', ['default' => false])
                ->addColumn('suggested_energy_class', 'string', ['limit' => 20, 'null' => true])
                ->addColumn('cvar_sim_unit', 'decimal', ['precision' => 15, 'scale' => 6, 'null' => true])
                ->addColumn('cvar_mp_unit', 'decimal', ['precision' => 15, 'scale' => 6, 'null' => true])
                ->addColumn('cvar_energy_unit', 'decimal', ['precision' => 15, 'scale' => 6, 'null' => true])
                ->addColumn('cfix_total', 'decimal', ['precision' => 15, 'scale' => 4, 'null' => true])
                ->addColumn('cfix_unit', 'decimal', ['precision' => 15, 'scale' => 6, 'null' => true])
                ->addColumn('full_cost_unit', 'decimal', ['precision' => 15, 'scale' => 6, 'null' => true])
                ->addColumn('computed_at', 'timestamp', ['null' => true])
                ->addForeignKey('inv_cost_period_id', 'inv_cost_periods', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('inv_item_id', 'inv_items', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addIndex(['inv_cost_period_id', 'erp_code'], ['unique' => true, 'name' => 'uniq_inv_cost_period_sku_snap'])
                ->addIndex(['inv_cost_period_id'], ['name' => 'idx_inv_cost_period_sku_snap_period'])
                ->create();
        }

        if ($this->hasTable('inv_cost_periods')) {
            $table = $this->table('inv_cost_periods');
            if (!$table->hasColumn('snapshot_computed_at')) {
                $table->addColumn('snapshot_computed_at', 'timestamp', ['null' => true, 'after' => 'updated_at']);
            }
            if (!$table->hasColumn('snapshot_row_count')) {
                $table->addColumn('snapshot_row_count', 'integer', ['signed' => false, 'default' => 0, 'after' => 'snapshot_computed_at']);
            }
            if (!$table->hasColumn('snapshot_inputs_hash')) {
                $table->addColumn('snapshot_inputs_hash', 'string', ['limit' => 64, 'null' => true, 'after' => 'snapshot_row_count']);
            }
            $table->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('inv_cost_period_sku_snapshots')) {
            $this->table('inv_cost_period_sku_snapshots')->drop()->save();
        }

        if ($this->hasTable('inv_cost_periods')) {
            $table = $this->table('inv_cost_periods');
            foreach (['snapshot_inputs_hash', 'snapshot_row_count', 'snapshot_computed_at'] as $column) {
                if ($table->hasColumn($column)) {
                    $table->removeColumn($column);
                }
            }
            $table->update();
        }
    }
}
