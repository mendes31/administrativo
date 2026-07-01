<?php

namespace App\adms\Models\Repository\inventory;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\InvRouteConsolidationService;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

class InvItemOperationsRepository extends DbConnection
{
    /**
     * Retorna as operações (rota) de um item, com nome da operação e linhas de MO.
     */
    public function getByItem(int $invItemId): array
    {
        $sapCols = $this->hasSapPosColumns()
            ? 'io.sap_pos_id, io.sap_master_pos_id,'
            : '';
        $sapMetaCols = $this->hasSapSortPosTextColumns()
            ? 'io.sap_sort_id, io.sap_pos_text,'
            : '';
        $orderBy = $this->hasSapSortPosTextColumns()
            ? 'COALESCE(NULLIF(io.sap_sort_id, 0), io.sequence) ASC, io.id ASC'
            : 'io.sequence ASC, op.name ASC';
        $sql = 'SELECT io.id,
                       io.inv_operation_id,
                       io.inv_production_resource_id,
                       io.sequence,
                       ' . $sapCols . $sapMetaCols . '
                       io.time_per_batch_hours,
                       io.time_unit,
                       io.operators_qty,
                       io.labor_cost_per_min,
                       io.machine_cost_per_min,
                       io.energy_cost_per_min,
                       io.notes,
                       op.code AS operation_code,
                       op.name AS operation_name,
                       op.default_cost_per_hour AS operation_cost_per_hour,
                       pr.erp_code AS resource_erp_code,
                       pr.name AS resource_name,
                       pr.resource_type AS resource_type
                FROM inv_item_operations io
                INNER JOIN inv_operations op ON op.id = io.inv_operation_id
                LEFT JOIN inv_production_resources pr ON pr.id = io.inv_production_resource_id
                WHERE io.inv_item_id = :inv_item_id
                ORDER BY ' . $orderBy;
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':inv_item_id', $invItemId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if ($rows === []) {
            return [];
        }

        $ids = array_map(static fn(array $r): int => (int) ($r['id'] ?? 0), $rows);
        $laborByOp = $this->getLaborLinesByOperationIds($ids);
        $resourceByOp = $this->getResourceLinesByOperationIds($ids);
        foreach ($rows as &$row) {
            $opId = (int) ($row['id'] ?? 0);
            $row['labor_lines'] = $laborByOp[$opId] ?? [];
            $row['resource_lines'] = $resourceByOp[$opId] ?? [];
        }
        unset($row);

        return $rows;
    }

    /**
     * Rota usada no custeio: consolidada quando existir; senão rota SAP/local.
     */
    public function getByItemForCosting(int $invItemId): array
    {
        $consolidatedRepo = new InvItemRouteConsolidatedRepository();
        if ($consolidatedRepo->hasForItem($invItemId)) {
            return InvRouteConsolidationService::toCostingOperationRows(
                $consolidatedRepo->getByItem($invItemId)
            );
        }

        return $this->getByItem($invItemId);
    }

    /**
     * @param list<int> $operationIds
     * @return array<int, list<array<string, mixed>>>
     */
    public function getResourceLinesByOperationIds(array $operationIds): array
    {
        if (!$this->hasResourceLinesTable()) {
            return [];
        }

        $operationIds = array_values(array_filter(array_map('intval', $operationIds)));
        if ($operationIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($operationIds), '?'));
        $sql = 'SELECT ior.id, ior.inv_item_operation_id, ior.inv_production_resource_id, ior.qty,
                       ior.machine_cost_per_min, ior.energy_cost_per_min,
                       pr.erp_code AS resource_erp_code, pr.name AS resource_name, pr.resource_type,
                       pr.power_kw
                FROM inv_item_operation_resources ior
                INNER JOIN inv_production_resources pr ON pr.id = ior.inv_production_resource_id
                WHERE ior.inv_item_operation_id IN (' . $placeholders . ')
                ORDER BY ior.id ASC';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($operationIds as $i => $id) {
            $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $grouped = [];
        foreach ($rows as $row) {
            $key = (int) ($row['inv_item_operation_id'] ?? 0);
            $grouped[$key][] = $row;
        }

        return $grouped;
    }

    /**
     * @param list<array<string, mixed>> $resourceLines
     * @return array{machine: float, energy: float}
     */
    public static function sumResourceCostsPerMinute(array $resourceLines): array
    {
        $machine = 0.0;
        $energy = 0.0;
        foreach ($resourceLines as $line) {
            $qty = max(0, (int) ($line['qty'] ?? 0));
            if ($qty <= 0) {
                continue;
            }
            $machine += $qty * max(0, (float) ($line['machine_cost_per_min'] ?? 0));
            $energy += $qty * max(0, (float) ($line['energy_cost_per_min'] ?? 0));
        }

        return [
            'machine' => round($machine, 6),
            'energy' => round($energy, 6),
        ];
    }

    private function hasResourceLinesTable(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $stmt = $this->getConnection()->query("SHOW TABLES LIKE 'inv_item_operation_resources'");
        $cached = (bool) $stmt->fetch(PDO::FETCH_NUM);

        return $cached;
    }

    private function hasSapPosColumns(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        if (!$this->hasTable('inv_item_operations')) {
            $cached = false;

            return false;
        }
        $stmt = $this->getConnection()->query(
            "SHOW COLUMNS FROM inv_item_operations LIKE 'sap_pos_id'"
        );
        $cached = (bool)$stmt->fetch(PDO::FETCH_ASSOC);

        return $cached;
    }

    public function hasSapSortPosTextColumns(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        if (!$this->hasSapPosColumns()) {
            $cached = false;

            return false;
        }
        $stmt = $this->getConnection()->query(
            "SHOW COLUMNS FROM inv_item_operations LIKE 'sap_sort_id'"
        );
        $cached = (bool)$stmt->fetch(PDO::FETCH_ASSOC);

        return $cached;
    }

    private function hasTable(string $table): bool
    {
        $stmt = $this->getConnection()->query("SHOW TABLES LIKE " . $this->getConnection()->quote($table));

        return (bool)$stmt->fetch(PDO::FETCH_NUM);
    }

    /**
     * @param list<int> $operationIds
     * @return array<int, list<array<string, mixed>>>
     */
    public function getLaborLinesByOperationIds(array $operationIds): array
    {
        $operationIds = array_values(array_filter(array_map('intval', $operationIds)));
        if ($operationIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($operationIds), '?'));
        $sql = 'SELECT iol.id, iol.inv_item_operation_id, iol.inv_labor_role_id, iol.qty, iol.cost_per_min,
                       lr.name AS role_name, lr.code AS role_code, lr.default_cost_per_min
                FROM inv_item_operation_labor iol
                INNER JOIN inv_labor_roles lr ON lr.id = iol.inv_labor_role_id
                WHERE iol.inv_item_operation_id IN (' . $placeholders . ')
                ORDER BY iol.id ASC';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($operationIds as $i => $id) {
            $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $grouped = [];
        foreach ($rows as $row) {
            $key = (int) ($row['inv_item_operation_id'] ?? 0);
            $grouped[$key][] = $row;
        }

        return $grouped;
    }

    /**
     * Soma MO/min a partir das linhas detalhadas: Σ(qty × cost_per_min).
     *
     * @param list<array<string, mixed>> $laborLines
     */
    public static function sumLaborCostPerMinute(array $laborLines): float
    {
        $sum = 0.0;
        foreach ($laborLines as $line) {
            $qty = max(0, (int) ($line['qty'] ?? 0));
            $cost = max(0, (float) ($line['cost_per_min'] ?? 0));
            if ($qty > 0 && $cost > 0) {
                $sum += $qty * $cost;
            }
        }

        return round($sum, 6);
    }

    /**
     * Verifica se as linhas informadas são equivalentes à rota já gravada no item.
     *
     * @param list<array<string, mixed>> $incomingLines
     */
    public function structureLinesMatch(int $invItemId, array $incomingLines): bool
    {
        $current = $this->normalizeComparableRouteLines($this->getByItem($invItemId));
        $incoming = $this->normalizeComparableRouteLines($incomingLines);

        return json_encode($current, JSON_UNESCAPED_UNICODE) === json_encode($incoming, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param list<array<string, mixed>> $lines
     * @return list<array<string, mixed>>
     */
    private function normalizeComparableRouteLines(array $lines): array
    {
        $normalized = [];
        foreach ($lines as $line) {
            $laborLines = is_array($line['labor'] ?? null)
                ? $line['labor']
                : (is_array($line['labor_lines'] ?? null) ? $line['labor_lines'] : []);
            $resourceLines = is_array($line['resources'] ?? null)
                ? $line['resources']
                : (is_array($line['resource_lines'] ?? null) ? $line['resource_lines'] : []);

            $laborTotalPerMin = self::sumLaborCostPerMinute($laborLines);
            $operatorsQty = max(1, (int)($line['operators_qty'] ?? 1));
            $laborCostPerMin = $laborTotalPerMin > 0
                ? $laborTotalPerMin
                : max(0, (float)($line['labor_cost_per_min'] ?? 0));

            $resourceTotals = self::sumResourceCostsPerMinute($resourceLines);
            $machineCostPerMin = $resourceTotals['machine'] > 0
                ? $resourceTotals['machine']
                : max(0, (float)($line['machine_cost_per_min'] ?? 0));
            $energyCostPerMin = $resourceTotals['energy'] > 0
                ? $resourceTotals['energy']
                : max(0, (float)($line['energy_cost_per_min'] ?? 0));

            $timeUnit = strtoupper((string)($line['time_unit'] ?? 'MIN'));
            if (!in_array($timeUnit, ['MIN', 'H'], true)) {
                $timeUnit = 'MIN';
            }

            $normalizedLabor = [];
            foreach ($laborLines as $laborLine) {
                $roleId = (int)($laborLine['inv_labor_role_id'] ?? 0);
                if ($roleId <= 0) {
                    continue;
                }
                $normalizedLabor[] = [
                    'inv_labor_role_id' => $roleId,
                    'qty' => max(1, (int)($laborLine['qty'] ?? 1)),
                    'cost_per_min' => round(max(0, (float)($laborLine['cost_per_min'] ?? 0)), 6),
                ];
            }
            usort($normalizedLabor, static fn(array $a, array $b): int => (
                ($a['inv_labor_role_id'] <=> $b['inv_labor_role_id'])
                ?: ($a['qty'] <=> $b['qty'])
                ?: ((float)$a['cost_per_min'] <=> (float)$b['cost_per_min'])
            ));

            $normalizedResources = [];
            foreach ($resourceLines as $resourceLine) {
                $resourceId = (int)($resourceLine['inv_production_resource_id'] ?? 0);
                if ($resourceId <= 0) {
                    continue;
                }
                $normalizedResources[] = [
                    'inv_production_resource_id' => $resourceId,
                    'qty' => max(1, (int)($resourceLine['qty'] ?? 1)),
                    'machine_cost_per_min' => round(max(0, (float)($resourceLine['machine_cost_per_min'] ?? 0)), 6),
                    'energy_cost_per_min' => round(max(0, (float)($resourceLine['energy_cost_per_min'] ?? 0)), 6),
                ];
            }
            usort($normalizedResources, static fn(array $a, array $b): int => (
                ($a['inv_production_resource_id'] <=> $b['inv_production_resource_id'])
                ?: ($a['qty'] <=> $b['qty'])
            ));

            $firstResourceId = null;
            foreach ($resourceLines as $resourceLine) {
                $rid = (int)($resourceLine['inv_production_resource_id'] ?? 0);
                if ($rid > 0) {
                    $firstResourceId = $rid;
                    break;
                }
            }
            if ($firstResourceId === null && !empty($line['inv_production_resource_id'])) {
                $firstResourceId = (int)$line['inv_production_resource_id'];
            }

            $normalized[] = [
                'inv_operation_id' => (int)($line['inv_operation_id'] ?? 0),
                'inv_production_resource_id' => $firstResourceId,
                'sequence' => (int)($line['sequence'] ?? 1),
                'sap_pos_id' => isset($line['sap_pos_id']) ? (int)$line['sap_pos_id'] : null,
                'sap_master_pos_id' => isset($line['sap_master_pos_id']) ? (int)$line['sap_master_pos_id'] : null,
                'sap_sort_id' => isset($line['sap_sort_id']) ? (int)$line['sap_sort_id'] : null,
                'sap_pos_text' => isset($line['sap_pos_text']) ? (int)$line['sap_pos_text'] : null,
                'time_per_batch_hours' => round((float)($line['time_per_batch_hours'] ?? 0), 6),
                'time_unit' => $timeUnit,
                'operators_qty' => $laborTotalPerMin > 0 ? 1 : $operatorsQty,
                'labor_cost_per_min' => round($laborCostPerMin, 6),
                'machine_cost_per_min' => round($machineCostPerMin, 6),
                'energy_cost_per_min' => round($energyCostPerMin, 6),
                'notes' => isset($line['notes']) ? (string)$line['notes'] : null,
                'labor' => $normalizedLabor,
                'resources' => $normalizedResources,
            ];
        }

        usort($normalized, static function (array $a, array $b): int {
            $sortA = (int)($a['sap_sort_id'] ?? 0);
            $sortB = (int)($b['sap_sort_id'] ?? 0);
            if ($sortA > 0 && $sortB > 0 && $sortA !== $sortB) {
                return $sortA <=> $sortB;
            }
            $seqCmp = ((int)($a['sequence'] ?? 0)) <=> ((int)($b['sequence'] ?? 0));
            if ($seqCmp !== 0) {
                return $seqCmp;
            }

            return ((int)($a['inv_operation_id'] ?? 0)) <=> ((int)($b['inv_operation_id'] ?? 0));
        });

        return $normalized;
    }

    /**
     * Substitui completamente a rota de um item pelas linhas informadas.
     *
     * @param array $lines Each line may include:
     *                     - labor: list of ['inv_labor_role_id', 'qty', 'cost_per_min']
     *                     - resources: list of ['inv_production_resource_id', 'qty', 'machine_cost_per_min', 'energy_cost_per_min']
     */
    public function replaceForItem(int $invItemId, array $lines): bool
    {
        $conn = $this->getConnection();
        $hasResourceTable = $this->hasResourceLinesTable();
        try {
            $conn->beginTransaction();

            $oldSnapshot = $this->snapshotOperationsJson($conn, $invItemId);

            $stmtDeleteLabor = $conn->prepare(
                'DELETE iol FROM inv_item_operation_labor iol
                 INNER JOIN inv_item_operations io ON io.id = iol.inv_item_operation_id
                 WHERE io.inv_item_id = :inv_item_id'
            );
            $stmtDeleteLabor->bindValue(':inv_item_id', $invItemId, PDO::PARAM_INT);
            $stmtDeleteLabor->execute();

            if ($hasResourceTable) {
                $stmtDeleteResources = $conn->prepare(
                    'DELETE ior FROM inv_item_operation_resources ior
                     INNER JOIN inv_item_operations io ON io.id = ior.inv_item_operation_id
                     WHERE io.inv_item_id = :inv_item_id'
                );
                $stmtDeleteResources->bindValue(':inv_item_id', $invItemId, PDO::PARAM_INT);
                $stmtDeleteResources->execute();
            }

            $stmtDelete = $conn->prepare('DELETE FROM inv_item_operations WHERE inv_item_id = :inv_item_id');
            $stmtDelete->bindValue(':inv_item_id', $invItemId, PDO::PARAM_INT);
            $stmtDelete->execute();

            if ($lines) {
                $sapCols = $this->hasSapPosColumns();
                $sapMetaCols = $this->hasSapSortPosTextColumns();
                $sql = 'INSERT INTO inv_item_operations (
                            inv_item_id,
                            inv_operation_id,
                            inv_production_resource_id,
                            sequence,'
                    . ($sapCols ? '
                            sap_pos_id,
                            sap_master_pos_id,' : '')
                    . ($sapMetaCols ? '
                            sap_sort_id,
                            sap_pos_text,' : '') . '
                            time_per_batch_hours,
                            time_unit,
                            operators_qty,
                            labor_cost_per_min,
                            machine_cost_per_min,
                            energy_cost_per_min,
                            notes,
                            created_at
                        ) VALUES (
                            :inv_item_id,
                            :inv_operation_id,
                            :inv_production_resource_id,
                            :sequence,'
                    . ($sapCols ? '
                            :sap_pos_id,
                            :sap_master_pos_id,' : '')
                    . ($sapMetaCols ? '
                            :sap_sort_id,
                            :sap_pos_text,' : '') . '
                            :time_per_batch_hours,
                            :time_unit,
                            :operators_qty,
                            :labor_cost_per_min,
                            :machine_cost_per_min,
                            :energy_cost_per_min,
                            :notes,
                            :created_at
                        )';
                $stmtInsert = $conn->prepare($sql);
                $stmtLabor = $conn->prepare(
                    'INSERT INTO inv_item_operation_labor (inv_item_operation_id, inv_labor_role_id, qty, cost_per_min, created_at)
                     VALUES (:inv_item_operation_id, :inv_labor_role_id, :qty, :cost_per_min, :created_at)'
                );
                $stmtResource = $hasResourceTable
                    ? $conn->prepare(
                        'INSERT INTO inv_item_operation_resources
                            (inv_item_operation_id, inv_production_resource_id, qty, machine_cost_per_min, energy_cost_per_min, created_at)
                         VALUES (:inv_item_operation_id, :inv_production_resource_id, :qty, :machine_cost_per_min, :energy_cost_per_min, :created_at)'
                    )
                    : null;

                foreach ($lines as $line) {
                    $laborLines = is_array($line['labor'] ?? null) ? $line['labor'] : [];
                    $resourceLines = is_array($line['resources'] ?? null) ? $line['resources'] : [];
                    $laborTotalPerMin = self::sumLaborCostPerMinute($laborLines);
                    $operatorsQty = max(1, (int) ($line['operators_qty'] ?? 1));
                    $laborCostPerMin = $laborTotalPerMin > 0
                        ? $laborTotalPerMin
                        : max(0, (float) ($line['labor_cost_per_min'] ?? 0));

                    $resourceTotals = self::sumResourceCostsPerMinute($resourceLines);
                    $machineCostPerMin = $resourceTotals['machine'] > 0
                        ? $resourceTotals['machine']
                        : max(0, (float) ($line['machine_cost_per_min'] ?? 0));
                    $energyCostPerMin = $resourceTotals['energy'] > 0
                        ? $resourceTotals['energy']
                        : max(0, (float) ($line['energy_cost_per_min'] ?? 0));

                    $firstResourceId = null;
                    foreach ($resourceLines as $resourceLine) {
                        $rid = (int) ($resourceLine['inv_production_resource_id'] ?? 0);
                        if ($rid > 0) {
                            $firstResourceId = $rid;
                            break;
                        }
                    }
                    if ($firstResourceId === null && !empty($line['inv_production_resource_id'])) {
                        $firstResourceId = (int) $line['inv_production_resource_id'];
                    }

                    $stmtInsert->bindValue(':inv_item_id', $invItemId, PDO::PARAM_INT);
                    $stmtInsert->bindValue(':inv_operation_id', (int) $line['inv_operation_id'], PDO::PARAM_INT);
                    $stmtInsert->bindValue(':inv_production_resource_id', $firstResourceId, $firstResourceId ? PDO::PARAM_INT : PDO::PARAM_NULL);
                    $stmtInsert->bindValue(':sequence', (int) ($line['sequence'] ?? 1), PDO::PARAM_INT);
                    if ($sapCols) {
                        $sapPosId = isset($line['sap_pos_id']) && (int)$line['sap_pos_id'] > 0
                            ? (int)$line['sap_pos_id']
                            : (int)($line['sequence'] ?? 0);
                        $masterPosId = isset($line['sap_master_pos_id']) ? (int)$line['sap_master_pos_id'] : 0;
                        $stmtInsert->bindValue(':sap_pos_id', $sapPosId > 0 ? $sapPosId : null, $sapPosId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
                        $stmtInsert->bindValue(':sap_master_pos_id', $masterPosId > 0 ? $masterPosId : null, $masterPosId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
                    }
                    if ($sapMetaCols) {
                        $sortId = isset($line['sap_sort_id']) ? (int)$line['sap_sort_id'] : 0;
                        $posText = isset($line['sap_pos_text']) ? (int)$line['sap_pos_text'] : 0;
                        $stmtInsert->bindValue(':sap_sort_id', $sortId > 0 ? $sortId : null, $sortId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
                        $stmtInsert->bindValue(':sap_pos_text', $posText > 0 ? $posText : null, $posText > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
                    }
                    $stmtInsert->bindValue(':time_per_batch_hours', (float) ($line['time_per_batch_hours'] ?? 0));
                    $timeUnit = strtoupper((string) ($line['time_unit'] ?? 'MIN'));
                    if (!in_array($timeUnit, ['MIN', 'H'], true)) {
                        $timeUnit = 'MIN';
                    }
                    $stmtInsert->bindValue(':time_unit', $timeUnit);
                    $stmtInsert->bindValue(':operators_qty', $laborTotalPerMin > 0 ? 1 : $operatorsQty, PDO::PARAM_INT);
                    $stmtInsert->bindValue(':labor_cost_per_min', $laborCostPerMin);
                    $stmtInsert->bindValue(':machine_cost_per_min', $machineCostPerMin);
                    $stmtInsert->bindValue(':energy_cost_per_min', $energyCostPerMin);
                    $stmtInsert->bindValue(':notes', $line['notes'] ?? null, PDO::PARAM_STR);
                    $stmtInsert->bindValue(':created_at', date('Y-m-d H:i:s'));
                    $stmtInsert->execute();

                    $operationRowId = (int) $conn->lastInsertId();

                    if ($stmtResource instanceof \PDOStatement) {
                        foreach ($resourceLines as $resourceLine) {
                            $resourceId = (int) ($resourceLine['inv_production_resource_id'] ?? 0);
                            if ($resourceId <= 0) {
                                continue;
                            }
                            $qty = max(1, (int) ($resourceLine['qty'] ?? 1));
                            $stmtResource->bindValue(':inv_item_operation_id', $operationRowId, PDO::PARAM_INT);
                            $stmtResource->bindValue(':inv_production_resource_id', $resourceId, PDO::PARAM_INT);
                            $stmtResource->bindValue(':qty', $qty, PDO::PARAM_INT);
                            $stmtResource->bindValue(':machine_cost_per_min', max(0, (float) ($resourceLine['machine_cost_per_min'] ?? 0)));
                            $stmtResource->bindValue(':energy_cost_per_min', max(0, (float) ($resourceLine['energy_cost_per_min'] ?? 0)));
                            $stmtResource->bindValue(':created_at', date('Y-m-d H:i:s'));
                            $stmtResource->execute();
                        }
                    }

                    foreach ($laborLines as $laborLine) {
                        $roleId = (int) ($laborLine['inv_labor_role_id'] ?? 0);
                        if ($roleId <= 0) {
                            continue;
                        }
                        $qty = max(1, (int) ($laborLine['qty'] ?? 1));
                        $costPerMin = max(0, (float) ($laborLine['cost_per_min'] ?? 0));
                        $stmtLabor->bindValue(':inv_item_operation_id', $operationRowId, PDO::PARAM_INT);
                        $stmtLabor->bindValue(':inv_labor_role_id', $roleId, PDO::PARAM_INT);
                        $stmtLabor->bindValue(':qty', $qty, PDO::PARAM_INT);
                        $stmtLabor->bindValue(':cost_per_min', $costPerMin);
                        $stmtLabor->bindValue(':created_at', date('Y-m-d H:i:s'));
                        $stmtLabor->execute();
                    }
                }
            }

            $conn->commit();

            $newSnapshot = $this->snapshotOperationsJson($conn, $invItemId);
            if ($oldSnapshot !== $newSnapshot) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'inv_item_operations',
                    $invItemId,
                    $usuarioId,
                    'UPDATE',
                    ['snapshot' => $oldSnapshot],
                    ['snapshot' => $newSnapshot]
                );
            }

            return true;
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            GenerateLog::generateLog('error', 'Falha ao salvar rota do item', [
                'inv_item_id' => $invItemId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function snapshotOperationsJson(\PDO $conn, int $invItemId): string
    {
        $stmt = $conn->prepare('SELECT * FROM inv_item_operations WHERE inv_item_id = :id ORDER BY id ASC');
        $stmt->bindValue(':id', $invItemId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return json_encode($rows, JSON_UNESCAPED_UNICODE);
    }
}
