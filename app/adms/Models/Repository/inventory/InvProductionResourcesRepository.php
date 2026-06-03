<?php

namespace App\adms\Models\Repository\inventory;

use App\adms\Models\Services\DbConnection;
use PDO;

class InvProductionResourcesRepository extends DbConnection
{
    /**
     * @return array{labor_cost_per_min: float, machine_cost_per_min: float, energy_cost_per_min: float}
     */
    public function resolveRouteCosts(string $resourceCode, string $resourceName = ''): array
    {
        $resourceCode = strtoupper(trim($resourceCode));
        $resourceName = trim($resourceName);

        if ($resourceCode !== '') {
            $byCode = $this->findByErpCode($resourceCode);
            if ($byCode !== null) {
                return $this->mapCosts($byCode);
            }
        }

        $guess = $this->inferCostsFromName($resourceCode !== '' ? $resourceCode : $resourceName);
        return $guess;
    }

    public function findByErpCode(string $erpCode): ?array
    {
        $erpCode = strtoupper(trim($erpCode));
        if ($erpCode === '') {
            return null;
        }

        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM inv_production_resources WHERE UPPER(erp_code) = :erp_code AND active = 1 LIMIT 1'
        );
        $stmt->bindValue(':erp_code', $erpCode);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * @return array{labor_cost_per_min: float, machine_cost_per_min: float, energy_cost_per_min: float}
     */
    private function mapCosts(array $row): array
    {
        return [
            'labor_cost_per_min' => round((float)($row['labor_cost_per_min'] ?? 0), 6),
            'machine_cost_per_min' => round((float)($row['machine_cost_per_min'] ?? 0), 6),
            'energy_cost_per_min' => round((float)($row['energy_cost_per_min'] ?? 0), 6),
        ];
    }

    /**
     * @return array{labor_cost_per_min: float, machine_cost_per_min: float, energy_cost_per_min: float}
     */
    private function inferCostsFromName(string $name): array
    {
        $upper = mb_strtoupper($name, 'UTF-8');

        if (
            str_contains($upper, 'OPERADOR') ||
            str_contains($upper, 'MANIPULADOR') ||
            str_contains($upper, 'AUXILIAR') ||
            str_contains($upper, 'CARTONAGEM')
        ) {
            return ['labor_cost_per_min' => 0.05, 'machine_cost_per_min' => 0.0, 'energy_cost_per_min' => 0.0];
        }

        if (
            str_contains($upper, 'MAQUINA') ||
            str_contains($upper, 'MISTURADOR') ||
            str_contains($upper, 'PENEIRA') ||
            str_contains($upper, 'DATADORA') ||
            str_contains($upper, 'SACHE')
        ) {
            return ['labor_cost_per_min' => 0.0, 'machine_cost_per_min' => 0.05, 'energy_cost_per_min' => 0.01];
        }

        return ['labor_cost_per_min' => 0.0, 'machine_cost_per_min' => 0.0, 'energy_cost_per_min' => 0.0];
    }
}
