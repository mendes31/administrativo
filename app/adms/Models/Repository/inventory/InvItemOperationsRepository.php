<?php

namespace App\adms\Models\Repository\inventory;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

class InvItemOperationsRepository extends DbConnection
{
    /**
     * Retorna as operações (rota) de um item, com nome da operação.
     */
    public function getByItem(int $invItemId): array
    {
        $sql = 'SELECT io.id,
                       io.inv_operation_id,
                       io.sequence,
                       io.time_per_batch_hours,
                       io.time_unit,
                       io.notes,
                       op.code AS operation_code,
                       op.name AS operation_name,
                       op.default_cost_per_hour AS operation_cost_per_hour
                FROM inv_item_operations io
                INNER JOIN inv_operations op ON op.id = io.inv_operation_id
                WHERE io.inv_item_id = :inv_item_id
                ORDER BY io.sequence ASC, op.name ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':inv_item_id', $invItemId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Substitui completamente a rota de um item pelas linhas informadas.
     *
     * @param int   $invItemId
     * @param array $lines Each line: ['inv_operation_id' => int, 'sequence' => int, 'time_per_batch_hours' => float, 'notes' => string|null]
     */
    public function replaceForItem(int $invItemId, array $lines): bool
    {
        $conn = $this->getConnection();
        try {
            $conn->beginTransaction();

            // Apagar rota atual
            $stmtDelete = $conn->prepare('DELETE FROM inv_item_operations WHERE inv_item_id = :inv_item_id');
            $stmtDelete->bindValue(':inv_item_id', $invItemId, PDO::PARAM_INT);
            $stmtDelete->execute();

            // Inserir novas linhas
            if ($lines) {
                $sql = 'INSERT INTO inv_item_operations (inv_item_id, inv_operation_id, sequence, time_per_batch_hours, time_unit, notes, created_at)
                        VALUES (:inv_item_id, :inv_operation_id, :sequence, :time_per_batch_hours, :time_unit, :notes, :created_at)';
                $stmtInsert = $conn->prepare($sql);
                foreach ($lines as $line) {
                    $stmtInsert->bindValue(':inv_item_id', $invItemId, PDO::PARAM_INT);
                    $stmtInsert->bindValue(':inv_operation_id', (int)$line['inv_operation_id'], PDO::PARAM_INT);
                    $stmtInsert->bindValue(':sequence', (int)($line['sequence'] ?? 1), PDO::PARAM_INT);
                    $stmtInsert->bindValue(':time_per_batch_hours', (float)($line['time_per_batch_hours'] ?? 0));
                    $timeUnit = strtoupper((string)($line['time_unit'] ?? 'MIN'));
                    if (!in_array($timeUnit, ['MIN', 'H'], true)) {
                        $timeUnit = 'MIN';
                    }
                    $stmtInsert->bindValue(':time_unit', $timeUnit);
                    $stmtInsert->bindValue(':notes', $line['notes'] ?? null, PDO::PARAM_STR);
                    $stmtInsert->bindValue(':created_at', date('Y-m-d H:i:s'));
                    $stmtInsert->execute();
                }
            }

            $conn->commit();
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
}

