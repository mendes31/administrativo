<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddInvCostSnapshotBatchAdoptedColumns extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_cost_period_sku_snapshots')) {
            return;
        }

        $table = $this->table('inv_cost_period_sku_snapshots');
        $columns = [
            'batch_size_adopted' => ['decimal', ['precision' => 15, 'scale' => 4, 'null' => true, 'after' => 'standard_batch_size']],
            'qty_avg_per_round' => ['decimal', ['precision' => 15, 'scale' => 4, 'null' => true, 'after' => 'batch_size_adopted']],
            'batches_produced' => ['decimal', ['precision' => 15, 'scale' => 6, 'null' => true, 'after' => 'qty_avg_per_round']],
        ];

        foreach ($columns as $name => $def) {
            if (!$table->hasColumn($name)) {
                $table->addColumn($name, $def[0], $def[1]);
            }
        }

        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('inv_cost_period_sku_snapshots')) {
            return;
        }

        $table = $this->table('inv_cost_period_sku_snapshots');
        foreach (['batches_produced', 'qty_avg_per_round', 'batch_size_adopted'] as $column) {
            if ($table->hasColumn($column)) {
                $table->removeColumn($column);
            }
        }
        $table->update();
    }
}
