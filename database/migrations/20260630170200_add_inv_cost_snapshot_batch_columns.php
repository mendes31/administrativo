<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddInvCostSnapshotBatchColumns extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_cost_period_sku_snapshots')) {
            return;
        }

        $table = $this->table('inv_cost_period_sku_snapshots');
        $columns = [
            'cvar_mae_unit' => ['decimal', ['precision' => 15, 'scale' => 6, 'null' => true, 'after' => 'cvar_mp_unit']],
            'cvar_batch' => ['decimal', ['precision' => 15, 'scale' => 4, 'null' => true, 'after' => 'cvar_energy_unit']],
            'cfix_batch' => ['decimal', ['precision' => 15, 'scale' => 4, 'null' => true, 'after' => 'cfix_unit']],
            'full_cost_batch' => ['decimal', ['precision' => 15, 'scale' => 4, 'null' => true, 'after' => 'full_cost_unit']],
            'sale_price_net' => ['decimal', ['precision' => 15, 'scale' => 4, 'null' => true, 'after' => 'full_cost_batch']],
            'markup_pct' => ['decimal', ['precision' => 10, 'scale' => 4, 'null' => true, 'after' => 'sale_price_net']],
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
        foreach (['markup_pct', 'sale_price_net', 'full_cost_batch', 'cfix_batch', 'cvar_batch', 'cvar_mae_unit'] as $column) {
            if ($table->hasColumn($column)) {
                $table->removeColumn($column);
            }
        }
        $table->update();
    }
}
