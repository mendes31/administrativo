<?php

namespace App\adms\Models\Repository\inventory;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

class InvItemBomRepository extends DbConnection
{
    /**
     * Retorna a lista de materiais (BOM) de um item, com código/descrição do componente.
     */
    public function getByItem(int $invItemId): array
    {
        $sql = 'SELECT b.id,
                       b.component_item_id,
                       b.quantity_per_batch,
                       b.scrap_percent,
                       i.code AS component_code,
                       i.description AS component_description,
                       u.name AS unit_name,
                       i.average_cost AS component_cost
                FROM inv_item_bom b
                INNER JOIN inv_items i ON i.id = b.component_item_id
                LEFT JOIN inv_units u ON u.id = i.inv_unit_id
                WHERE b.inv_item_id = :inv_item_id
                ORDER BY i.description ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':inv_item_id', $invItemId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Substitui completamente a BOM de um item pelas linhas informadas.
     *
     * @param int   $invItemId
     * @param array $lines Each line: ['component_item_id' => int, 'quantity_per_batch' => float, 'scrap_percent' => float]
     */
    public function replaceForItem(int $invItemId, array $lines): bool
    {
        $conn = $this->getConnection();
        try {
            $conn->beginTransaction();

            $oldSnapshot = $this->snapshotBomJson($conn, $invItemId);

            // Apagar BOM atual
            $stmtDelete = $conn->prepare('DELETE FROM inv_item_bom WHERE inv_item_id = :inv_item_id');
            $stmtDelete->bindValue(':inv_item_id', $invItemId, PDO::PARAM_INT);
            $stmtDelete->execute();

            // Inserir novas linhas (se houver)
            if ($lines) {
                $sql = 'INSERT INTO inv_item_bom (inv_item_id, component_item_id, quantity_per_batch, scrap_percent, created_at)
                        VALUES (:inv_item_id, :component_item_id, :quantity_per_batch, :scrap_percent, :created_at)';
                $stmtInsert = $conn->prepare($sql);
                foreach ($lines as $line) {
                    $stmtInsert->bindValue(':inv_item_id', $invItemId, PDO::PARAM_INT);
                    $stmtInsert->bindValue(':component_item_id', (int)$line['component_item_id'], PDO::PARAM_INT);
                    $stmtInsert->bindValue(':quantity_per_batch', (float)$line['quantity_per_batch']);
                    $stmtInsert->bindValue(':scrap_percent', (float)($line['scrap_percent'] ?? 0));
                    $stmtInsert->bindValue(':created_at', date('Y-m-d H:i:s'));
                    $stmtInsert->execute();
                }
            }

            $conn->commit();

            $newSnapshot = $this->snapshotBomJson($conn, $invItemId);
            if ($oldSnapshot !== $newSnapshot) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'inv_item_bom',
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
            GenerateLog::generateLog('error', 'Falha ao salvar BOM do item', [
                'inv_item_id' => $invItemId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function snapshotBomJson(\PDO $conn, int $invItemId): string
    {
        $stmt = $conn->prepare('SELECT * FROM inv_item_bom WHERE inv_item_id = :id ORDER BY id ASC');
        $stmt->bindValue(':id', $invItemId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return json_encode($rows, JSON_UNESCAPED_UNICODE);
    }
}

