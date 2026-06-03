<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class AddInvProductionResources extends AbstractSeed
{
    public function run(): void
    {
        if (!$this->hasTable('inv_production_resources')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $rows = [
            ['erp_code' => 'MANIPULADOR', 'name' => 'Manipulador', 'resource_type' => 'LABOR', 'labor' => 0.050000, 'machine' => 0, 'energy' => 0],
            ['erp_code' => 'OPERADOR', 'name' => 'Operador', 'resource_type' => 'LABOR', 'labor' => 0.050000, 'machine' => 0, 'energy' => 0],
            ['erp_code' => 'AUXILIAR_PRODUCAO', 'name' => 'Auxiliar de Produção', 'resource_type' => 'LABOR', 'labor' => 0.040000, 'machine' => 0, 'energy' => 0],
            ['erp_code' => 'PENEIRA_ELETRICA', 'name' => 'Peneira Elétrica', 'resource_type' => 'MACHINE', 'labor' => 0, 'machine' => 0.020000, 'energy' => 0.005000],
            ['erp_code' => 'MISTURADOR_V_1500L', 'name' => 'Misturador V 1500L', 'resource_type' => 'MACHINE', 'labor' => 0, 'machine' => 0.030000, 'energy' => 0.010000],
            ['erp_code' => 'MAQUINA_SACHE', 'name' => 'Máquina Sachê', 'resource_type' => 'MACHINE', 'labor' => 0, 'machine' => 0.080000, 'energy' => 0.015000],
            ['erp_code' => 'DATADORA_LINX', 'name' => 'Datadora Linx', 'resource_type' => 'MACHINE', 'labor' => 0, 'machine' => 0.040000, 'energy' => 0.005000],
            ['erp_code' => 'CARTONAGEM_MANUAL', 'name' => 'Cartonagem Manual', 'resource_type' => 'LABOR', 'labor' => 0.045000, 'machine' => 0, 'energy' => 0],
        ];

        foreach ($rows as $row) {
            $codeSql = "'" . str_replace("'", "''", $row['erp_code']) . "'";
            $exists = $this->fetchRow(
                'SELECT id FROM inv_production_resources WHERE erp_code = ' . $codeSql . ' LIMIT 1'
            );
            if ($exists) {
                continue;
            }

            $this->table('inv_production_resources')->insert([
                'erp_code' => $row['erp_code'],
                'name' => $row['name'],
                'resource_type' => $row['resource_type'],
                'labor_cost_per_min' => $row['labor'],
                'machine_cost_per_min' => $row['machine'],
                'energy_cost_per_min' => $row['energy'],
                'active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ])->saveData();
        }
    }
}
