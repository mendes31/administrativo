<?php

declare(strict_types=1);

namespace App\adms\Models\Repository\inventory;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

class InvItemRouteConsolidatedRepository extends DbConnection
{
    public function hasForItem(int $invItemId): bool
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT 1 FROM inv_item_route_consolidated WHERE inv_item_id = :inv_item_id LIMIT 1'
        );
        $stmt->bindValue(':inv_item_id', $invItemId, PDO::PARAM_INT);
        $stmt->execute();

        return (bool)$stmt->fetchColumn();
    }

    public function getByItem(int $invItemId): array
    {
        if (!$this->hasTable()) {
            return [];
        }

        $sql = 'SELECT rc.id,
                       rc.inv_operation_id,
                       rc.sequence,
                       rc.sap_group_pos_id,'
            . ($this->hasSapGroupPosTextColumn() ? '
                       rc.sap_group_pos_text,' : '') . '
                       rc.time_per_batch_hours,
                       rc.time_unit,
                       rc.notes,
                       op.code AS operation_code,
                       op.name AS operation_name,
                       op.default_cost_per_hour AS operation_cost_per_hour
                FROM inv_item_route_consolidated rc
                INNER JOIN inv_operations op ON op.id = rc.inv_operation_id
                WHERE rc.inv_item_id = :inv_item_id
                ORDER BY rc.sequence ASC, op.name ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':inv_item_id', $invItemId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if ($rows === []) {
            return [];
        }

        $ids = array_map(static fn(array $r): int => (int)($r['id'] ?? 0), $rows);
        $laborByOp = $this->getLaborLinesByIds($ids);
        $resourceByOp = $this->getResourceLinesByIds($ids);
        foreach ($rows as &$row) {
            $rowId = (int)($row['id'] ?? 0);
            $row['labor_lines'] = $laborByOp[$rowId] ?? [];
            $row['resource_lines'] = $resourceByOp[$rowId] ?? [];
        }
        unset($row);

        return $rows;
    }

    /**
     * @param list<int> $ids
     * @return array<int, list<array<string, mixed>>>
     */
    private function getLaborLinesByIds(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'SELECT iol.id, iol.inv_route_consolidated_id, iol.inv_labor_role_id, iol.qty, iol.line_time_minutes, iol.cost_per_min,
                       lr.code AS role_code, lr.name AS role_name, lr.default_cost_per_min
                FROM inv_item_route_consolidated_labor iol
                INNER JOIN inv_labor_roles lr ON lr.id = iol.inv_labor_role_id
                WHERE iol.inv_route_consolidated_id IN (' . $placeholders . ')
                ORDER BY iol.id ASC';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($ids as $i => $id) {
            $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $grouped = [];
        foreach ($rows as $row) {
            $key = (int)($row['inv_route_consolidated_id'] ?? 0);
            $grouped[$key][] = $row;
        }

        return $grouped;
    }

    /**
     * @param list<int> $ids
     * @return array<int, list<array<string, mixed>>>
     */
    private function getResourceLinesByIds(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'SELECT ior.id, ior.inv_route_consolidated_id, ior.inv_production_resource_id, ior.qty, ior.line_time_minutes,
                       ior.machine_cost_per_min, ior.energy_cost_per_min,
                       pr.erp_code AS resource_erp_code, pr.name AS resource_name, pr.resource_type, pr.power_kw,
                       pr.machine_cost_per_min AS resource_machine_cost_per_min, pr.energy_cost_per_min AS resource_energy_cost_per_min
                FROM inv_item_route_consolidated_resources ior
                INNER JOIN inv_production_resources pr ON pr.id = ior.inv_production_resource_id
                WHERE ior.inv_route_consolidated_id IN (' . $placeholders . ')
                ORDER BY ior.id ASC';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($ids as $i => $id) {
            $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $grouped = [];
        foreach ($rows as $row) {
            $key = (int)($row['inv_route_consolidated_id'] ?? 0);
            $grouped[$key][] = $row;
        }

        return $grouped;
    }

    /**
     * @param list<array<string, mixed>> $lines
     */
    public function replaceForItem(int $invItemId, array $lines): bool
    {
        if (!$this->hasTable()) {
            return false;
        }

        $conn = $this->getConnection();
        try {
            $conn->beginTransaction();

            $stmtDeleteLabor = $conn->prepare(
                'DELETE iol FROM inv_item_route_consolidated_labor iol
                 INNER JOIN inv_item_route_consolidated rc ON rc.id = iol.inv_route_consolidated_id
                 WHERE rc.inv_item_id = :inv_item_id'
            );
            $stmtDeleteLabor->bindValue(':inv_item_id', $invItemId, PDO::PARAM_INT);
            $stmtDeleteLabor->execute();

            $stmtDeleteResources = $conn->prepare(
                'DELETE ior FROM inv_item_route_consolidated_resources ior
                 INNER JOIN inv_item_route_consolidated rc ON rc.id = ior.inv_route_consolidated_id
                 WHERE rc.inv_item_id = :inv_item_id'
            );
            $stmtDeleteResources->bindValue(':inv_item_id', $invItemId, PDO::PARAM_INT);
            $stmtDeleteResources->execute();

            $stmtDelete = $conn->prepare('DELETE FROM inv_item_route_consolidated WHERE inv_item_id = :inv_item_id');
            $stmtDelete->bindValue(':inv_item_id', $invItemId, PDO::PARAM_INT);
            $stmtDelete->execute();

            if ($lines !== []) {
                $hasGroupPosText = $this->hasSapGroupPosTextColumn();
                $stmtInsert = $conn->prepare(
                    'INSERT INTO inv_item_route_consolidated
                        (inv_item_id, inv_operation_id, sequence, sap_group_pos_id'
                    . ($hasGroupPosText ? ', sap_group_pos_text' : '')
                    . ', time_per_batch_hours, time_unit, notes, created_at, updated_at)
                     VALUES
                        (:inv_item_id, :inv_operation_id, :sequence, :sap_group_pos_id'
                    . ($hasGroupPosText ? ', :sap_group_pos_text' : '')
                    . ', :time_per_batch_hours, :time_unit, :notes, :created_at, :updated_at)'
                );
                $stmtLabor = $conn->prepare(
                    'INSERT INTO inv_item_route_consolidated_labor
                        (inv_route_consolidated_id, inv_labor_role_id, qty, line_time_minutes, cost_per_min, created_at)
                     VALUES (:inv_route_consolidated_id, :inv_labor_role_id, :qty, :line_time_minutes, :cost_per_min, :created_at)'
                );
                $stmtResource = $conn->prepare(
                    'INSERT INTO inv_item_route_consolidated_resources
                        (inv_route_consolidated_id, inv_production_resource_id, qty, line_time_minutes, machine_cost_per_min, energy_cost_per_min, created_at)
                     VALUES (:inv_route_consolidated_id, :inv_production_resource_id, :qty, :line_time_minutes, :machine_cost_per_min, :energy_cost_per_min, :created_at)'
                );

                $now = date('Y-m-d H:i:s');
                foreach ($lines as $line) {
                    $operationId = (int)($line['inv_operation_id'] ?? 0);
                    if ($operationId <= 0) {
                        continue;
                    }

                    $timeUnit = strtoupper((string)($line['time_unit'] ?? 'MIN'));
                    if (!in_array($timeUnit, ['MIN', 'H'], true)) {
                        $timeUnit = 'MIN';
                    }

                    $groupPosId = isset($line['sap_group_pos_id']) && (int)$line['sap_group_pos_id'] > 0
                        ? (int)$line['sap_group_pos_id']
                        : null;
                    $groupPosText = isset($line['sap_group_pos_text']) && (int)$line['sap_group_pos_text'] > 0
                        ? (int)$line['sap_group_pos_text']
                        : null;

                    $stmtInsert->bindValue(':inv_item_id', $invItemId, PDO::PARAM_INT);
                    $stmtInsert->bindValue(':inv_operation_id', $operationId, PDO::PARAM_INT);
                    $stmtInsert->bindValue(':sequence', max(1, (int)($line['sequence'] ?? 1)), PDO::PARAM_INT);
                    $stmtInsert->bindValue(':sap_group_pos_id', $groupPosId, $groupPosId ? PDO::PARAM_INT : PDO::PARAM_NULL);
                    if ($hasGroupPosText) {
                        $stmtInsert->bindValue(':sap_group_pos_text', $groupPosText, $groupPosText ? PDO::PARAM_INT : PDO::PARAM_NULL);
                    }
                    $stmtInsert->bindValue(':time_per_batch_hours', (float)($line['time_per_batch_hours'] ?? 0));
                    $stmtInsert->bindValue(':time_unit', $timeUnit);
                    $stmtInsert->bindValue(':notes', $line['notes'] ?? null, PDO::PARAM_STR);
                    $stmtInsert->bindValue(':created_at', $now);
                    $stmtInsert->bindValue(':updated_at', $now);
                    $stmtInsert->execute();

                    $rowId = (int)$conn->lastInsertId();

                    $resourceLines = is_array($line['resources'] ?? null) ? $line['resources'] : [];
                    foreach ($resourceLines as $resourceLine) {
                        $resourceId = (int)($resourceLine['inv_production_resource_id'] ?? 0);
                        if ($resourceId <= 0) {
                            continue;
                        }
                        $stmtResource->bindValue(':inv_route_consolidated_id', $rowId, PDO::PARAM_INT);
                        $stmtResource->bindValue(':inv_production_resource_id', $resourceId, PDO::PARAM_INT);
                        $stmtResource->bindValue(':qty', max(1, (int)($resourceLine['qty'] ?? 1)), PDO::PARAM_INT);
                        $lineTime = isset($resourceLine['line_time_minutes']) && $resourceLine['line_time_minutes'] !== ''
                            ? max(0, (float)$resourceLine['line_time_minutes'])
                            : null;
                        $stmtResource->bindValue(':line_time_minutes', $lineTime, $lineTime !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                        $stmtResource->bindValue(':machine_cost_per_min', max(0, (float)($resourceLine['machine_cost_per_min'] ?? 0)));
                        $stmtResource->bindValue(':energy_cost_per_min', max(0, (float)($resourceLine['energy_cost_per_min'] ?? 0)));
                        $stmtResource->bindValue(':created_at', $now);
                        $stmtResource->execute();
                    }

                    $laborLines = is_array($line['labor'] ?? null) ? $line['labor'] : [];
                    foreach ($laborLines as $laborLine) {
                        $roleId = (int)($laborLine['inv_labor_role_id'] ?? 0);
                        if ($roleId <= 0) {
                            continue;
                        }
                        $stmtLabor->bindValue(':inv_route_consolidated_id', $rowId, PDO::PARAM_INT);
                        $stmtLabor->bindValue(':inv_labor_role_id', $roleId, PDO::PARAM_INT);
                        $stmtLabor->bindValue(':qty', max(1, (int)($laborLine['qty'] ?? 1)), PDO::PARAM_INT);
                        $lineTime = isset($laborLine['line_time_minutes']) && $laborLine['line_time_minutes'] !== ''
                            ? max(0, (float)$laborLine['line_time_minutes'])
                            : null;
                        $stmtLabor->bindValue(':line_time_minutes', $lineTime, $lineTime !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                        $stmtLabor->bindValue(':cost_per_min', max(0, (float)($laborLine['cost_per_min'] ?? 0)));
                        $stmtLabor->bindValue(':created_at', $now);
                        $stmtLabor->execute();
                    }
                }
            }

            $conn->commit();

            return true;
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            GenerateLog::generateLog('error', 'Falha ao salvar rota consolidada', [
                'inv_item_id' => $invItemId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function hasTable(): bool
    {
        $stmt = $this->getConnection()->query("SHOW TABLES LIKE 'inv_item_route_consolidated'");

        return (bool)$stmt->fetchColumn();
    }

    public function hasSapGroupPosTextColumn(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        if (!$this->hasTable()) {
            $cached = false;

            return false;
        }
        $stmt = $this->getConnection()->query(
            "SHOW COLUMNS FROM inv_item_route_consolidated LIKE 'sap_group_pos_text'"
        );
        $cached = (bool)$stmt->fetch(PDO::FETCH_ASSOC);

        return $cached;
    }
}
