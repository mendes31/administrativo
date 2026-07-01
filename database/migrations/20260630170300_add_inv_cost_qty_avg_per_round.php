<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddInvCostQtyAvgPerRound extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_cost_period_items')) {
            return;
        }

        $table = $this->table('inv_cost_period_items');
        if (!$table->hasColumn('qty_avg_per_round')) {
            $table->addColumn('qty_avg_per_round', 'decimal', [
                'precision' => 15,
                'scale' => 4,
                'null' => true,
                'after' => 'batch_size_theoretical',
            ])->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('inv_cost_period_items')) {
            return;
        }

        $table = $this->table('inv_cost_period_items');
        if ($table->hasColumn('qty_avg_per_round')) {
            $table->removeColumn('qty_avg_per_round')->update();
        }
    }
}
