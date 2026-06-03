<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddCostFieldsToInvItemOperations extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_item_operations')) {
            return;
        }

        $table = $this->table('inv_item_operations');

        if (!$table->hasColumn('operators_qty')) {
            $table->addColumn('operators_qty', 'integer', [
                'null' => false,
                'default' => 1,
                'signed' => false,
                'after' => 'time_unit',
                'comment' => 'Quantidade de operadores necessários na operação',
            ]);
        }

        if (!$table->hasColumn('labor_cost_per_min')) {
            $table->addColumn('labor_cost_per_min', 'decimal', [
                'precision' => 15,
                'scale' => 6,
                'null' => false,
                'default' => 0,
                'after' => 'operators_qty',
                'comment' => 'Custo de mão de obra por minuto (por operador)',
            ]);
        }

        if (!$table->hasColumn('machine_cost_per_min')) {
            $table->addColumn('machine_cost_per_min', 'decimal', [
                'precision' => 15,
                'scale' => 6,
                'null' => false,
                'default' => 0,
                'after' => 'labor_cost_per_min',
                'comment' => 'Custo de máquina por minuto',
            ]);
        }

        if (!$table->hasColumn('energy_cost_per_min')) {
            $table->addColumn('energy_cost_per_min', 'decimal', [
                'precision' => 15,
                'scale' => 6,
                'null' => false,
                'default' => 0,
                'after' => 'machine_cost_per_min',
                'comment' => 'Custo de energia por minuto',
            ]);
        }

        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('inv_item_operations')) {
            return;
        }

        $table = $this->table('inv_item_operations');

        if ($table->hasColumn('energy_cost_per_min')) {
            $table->removeColumn('energy_cost_per_min');
        }
        if ($table->hasColumn('machine_cost_per_min')) {
            $table->removeColumn('machine_cost_per_min');
        }
        if ($table->hasColumn('labor_cost_per_min')) {
            $table->removeColumn('labor_cost_per_min');
        }
        if ($table->hasColumn('operators_qty')) {
            $table->removeColumn('operators_qty');
        }

        $table->update();
    }
}

