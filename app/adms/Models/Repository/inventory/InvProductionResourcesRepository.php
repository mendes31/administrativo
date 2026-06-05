<?php

namespace App\adms\Models\Repository\inventory;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

class InvProductionResourcesRepository extends DbConnection
{
    public function getAll(int $page = 1, int $limit = 10, array $filters = []): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $params = [];
        $wheres = [];

        if (!empty($filters['erp_code'])) {
            $wheres[] = 'erp_code LIKE :erp_code';
            $params[':erp_code'] = '%' . $filters['erp_code'] . '%';
        }
        if (!empty($filters['name'])) {
            $wheres[] = 'name LIKE :name';
            $params[':name'] = '%' . $filters['name'] . '%';
        }
        if (!empty($filters['resource_type'])) {
            $wheres[] = 'resource_type = :resource_type';
            $params[':resource_type'] = $filters['resource_type'];
        }
        if (isset($filters['active']) && $filters['active'] !== '') {
            $wheres[] = 'active = :active';
            $params[':active'] = (int) $filters['active'];
        }

        $whereSql = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';
        $sql = 'SELECT id, erp_code, name, resource_type, labor_cost_per_min, machine_cost_per_min,
                       energy_cost_per_min, power_kw, active
                FROM inv_production_resources ' . $whereSql . '
                ORDER BY name ASC
                LIMIT :limit OFFSET :offset';

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countAll(array $filters = []): int
    {
        $params = [];
        $wheres = [];
        if (!empty($filters['erp_code'])) {
            $wheres[] = 'erp_code LIKE :erp_code';
            $params[':erp_code'] = '%' . $filters['erp_code'] . '%';
        }
        if (!empty($filters['name'])) {
            $wheres[] = 'name LIKE :name';
            $params[':name'] = '%' . $filters['name'] . '%';
        }
        if (!empty($filters['resource_type'])) {
            $wheres[] = 'resource_type = :resource_type';
            $params[':resource_type'] = $filters['resource_type'];
        }
        if (isset($filters['active']) && $filters['active'] !== '') {
            $wheres[] = 'active = :active';
            $params[':active'] = (int) $filters['active'];
        }
        $whereSql = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';
        $stmt = $this->getConnection()->prepare('SELECT COUNT(*) AS total FROM inv_production_resources ' . $whereSql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($row['total'] ?? 0);
    }

    public function getOne(int $id): array|bool
    {
        $stmt = $this->getConnection()->prepare('SELECT * FROM inv_production_resources WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
    }

    public function getAllForSelect(): array
    {
        $stmt = $this->getConnection()->query(
            'SELECT id, erp_code, name, resource_type, labor_cost_per_min, machine_cost_per_min, energy_cost_per_min, power_kw
             FROM inv_production_resources WHERE active = 1 ORDER BY resource_type ASC, name ASC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function existsErpCode(string $erpCode, ?int $excludeId = null): bool
    {
        $erpCode = trim($erpCode);
        if ($erpCode === '') {
            return false;
        }
        $sql = 'SELECT id FROM inv_production_resources WHERE UPPER(erp_code) = UPPER(:erp_code)';
        if ($excludeId) {
            $sql .= ' AND id <> :id';
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':erp_code', $erpCode);
        if ($excludeId) {
            $stmt->bindValue(':id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    public function create(array $data): int|bool
    {
        try {
            $sql = 'INSERT INTO inv_production_resources
                (erp_code, name, resource_type, labor_cost_per_min, machine_cost_per_min, energy_cost_per_min, power_kw, active, created_at)
                VALUES (:erp_code, :name, :resource_type, :labor_cost_per_min, :machine_cost_per_min, :energy_cost_per_min, :power_kw, :active, :created_at)';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':erp_code', trim((string) $data['erp_code']));
            $stmt->bindValue(':name', trim((string) $data['name']));
            $stmt->bindValue(':resource_type', $this->normalizeType((string) ($data['resource_type'] ?? 'LABOR')));
            $stmt->bindValue(':labor_cost_per_min', (float) ($data['labor_cost_per_min'] ?? 0));
            $stmt->bindValue(':machine_cost_per_min', (float) ($data['machine_cost_per_min'] ?? 0));
            $stmt->bindValue(':energy_cost_per_min', (float) ($data['energy_cost_per_min'] ?? 0));
            $stmt->bindValue(':power_kw', !empty($data['power_kw']) ? (float) $data['power_kw'] : null, !empty($data['power_kw']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':active', isset($data['active']) ? (int) $data['active'] : 1, PDO::PARAM_INT);
            $stmt->bindValue(':created_at', date('Y-m-d H:i:s'));
            $stmt->execute();
            $newId = (int) $this->getConnection()->lastInsertId();
            if ($newId > 0) {
                $row = $this->getOne($newId);
                if (is_array($row)) {
                    LogAlteracaoService::registrarAlteracao('inv_production_resources', $newId, (int) ($_SESSION['user_id'] ?? 1), 'INSERT', [], $row);
                }
            }

            return $newId;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Falha ao criar recurso de produção', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function update(int $id, array $data): bool
    {
        try {
            $oldRow = $this->getOne($id);
            $sql = 'UPDATE inv_production_resources SET
                erp_code = :erp_code, name = :name, resource_type = :resource_type,
                labor_cost_per_min = :labor_cost_per_min, machine_cost_per_min = :machine_cost_per_min,
                energy_cost_per_min = :energy_cost_per_min, power_kw = :power_kw, active = :active, updated_at = :updated_at
                WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':erp_code', trim((string) $data['erp_code']));
            $stmt->bindValue(':name', trim((string) $data['name']));
            $stmt->bindValue(':resource_type', $this->normalizeType((string) ($data['resource_type'] ?? 'LABOR')));
            $stmt->bindValue(':labor_cost_per_min', (float) ($data['labor_cost_per_min'] ?? 0));
            $stmt->bindValue(':machine_cost_per_min', (float) ($data['machine_cost_per_min'] ?? 0));
            $stmt->bindValue(':energy_cost_per_min', (float) ($data['energy_cost_per_min'] ?? 0));
            $stmt->bindValue(':power_kw', !empty($data['power_kw']) ? (float) $data['power_kw'] : null, !empty($data['power_kw']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':active', isset($data['active']) ? (int) $data['active'] : 1, PDO::PARAM_INT);
            $stmt->bindValue(':updated_at', date('Y-m-d H:i:s'));
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $ok = $stmt->execute();
            if ($ok && is_array($oldRow)) {
                $newRow = $this->getOne($id);
                if (is_array($newRow)) {
                    LogAlteracaoService::registrarAlteracao('inv_production_resources', $id, (int) ($_SESSION['user_id'] ?? 1), 'UPDATE', $oldRow, $newRow);
                }
            }

            return $ok;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Falha ao atualizar recurso de produção', ['id' => $id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            $oldRow = $this->getOne($id);
            $stmt = $this->getConnection()->prepare('DELETE FROM inv_production_resources WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $ok = $stmt->execute();
            if ($ok && is_array($oldRow)) {
                LogAlteracaoService::registrarAlteracao('inv_production_resources', $id, (int) ($_SESSION['user_id'] ?? 1), 'DELETE', $oldRow, []);
            }

            return $ok;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Falha ao excluir recurso de produção', ['id' => $id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * @return array{labor_cost_per_min: float, machine_cost_per_min: float, energy_cost_per_min: float, resource_id: int|null}
     */
    public function resolveRouteCosts(string $resourceCode, string $resourceName = ''): array
    {
        $resourceCode = strtoupper(trim($resourceCode));
        $resourceName = trim($resourceName);

        if ($resourceCode !== '') {
            $byCode = $this->findByErpCode($resourceCode);
            if ($byCode !== null) {
                $costs = $this->mapCosts($byCode);
                $costs['resource_id'] = (int) ($byCode['id'] ?? 0) ?: null;

                return $costs;
            }
        }

        $guess = $this->inferCostsFromName($resourceCode !== '' ? $resourceCode : $resourceName);
        $guess['resource_id'] = null;

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
            'labor_cost_per_min' => round((float) ($row['labor_cost_per_min'] ?? 0), 6),
            'machine_cost_per_min' => round((float) ($row['machine_cost_per_min'] ?? 0), 6),
            'energy_cost_per_min' => round((float) ($row['energy_cost_per_min'] ?? 0), 6),
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

    private function normalizeType(string $type): string
    {
        $type = strtoupper(trim($type));
        $allowed = ['LABOR', 'MACHINE', 'ENERGY', 'MIXED'];
        if (!in_array($type, $allowed, true)) {
            return 'LABOR';
        }

        return $type;
    }
}
