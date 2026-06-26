<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ExtendInvCostPeriodEnergyRedistribution extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_cost_periods')) {
            return;
        }

        $table = $this->table('inv_cost_periods');

        if (!$table->hasColumn('energy_kwh_hvac')) {
            $table->addColumn('energy_kwh_hvac', 'decimal', [
                'precision' => 15,
                'scale' => 4,
                'null' => true,
                'after' => 'kwh_tariff',
                'comment' => 'Consumo kWh HVAC no período (Pasta 7 / CF R1190)',
            ]);
        }
        if (!$table->hasColumn('energy_kwh_production_common')) {
            $table->addColumn('energy_kwh_production_common', 'decimal', [
                'precision' => 15,
                'scale' => 4,
                'null' => true,
                'after' => 'energy_kwh_hvac',
                'comment' => 'kWh produção área comum (CFIX crit. 3)',
            ]);
        }
        if (!$table->hasColumn('energy_kwh_direct_cfix')) {
            $table->addColumn('energy_kwh_direct_cfix', 'decimal', [
                'precision' => 15,
                'scale' => 4,
                'null' => true,
                'after' => 'energy_kwh_production_common',
                'comment' => 'kWh direto CFIX (crit. 7) — pode calcular do cadastro',
            ]);
        }
        if (!$table->hasColumn('energy_auto_split')) {
            $table->addColumn('energy_auto_split', 'boolean', [
                'default' => true,
                'after' => 'energy_kwh_direct_cfix',
                'comment' => 'Redistribuir conta energia ao importar DRE',
            ]);
        }

        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('inv_cost_periods')) {
            return;
        }

        $table = $this->table('inv_cost_periods');
        foreach (['energy_auto_split', 'energy_kwh_direct_cfix', 'energy_kwh_production_common', 'energy_kwh_hvac'] as $col) {
            if ($table->hasColumn($col)) {
                $table->removeColumn($col);
            }
        }
        $table->update();
    }
}
