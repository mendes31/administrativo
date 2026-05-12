<?php

namespace App\adms\Models\Repository\inventory;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

class InvMovementsRepository extends DbConnection
{
    private InvBalancesRepository $balancesRepo;

    public function __construct()
    {
        $this->balancesRepo = new InvBalancesRepository();
    }

    /**
     * Registra movimento com transação, atualizando saldos por média móvel.
     * Regras de lote/série:
     * - Se item admin_type = 'serial': exigir lista de seriais (um por linha) e gravar em inv_item_serials.
     * - Se 'lot': exigir batch_code e opcional expiration_date.
     */
    public function registerMovement(array $movement, array $items, array $serialsByItem = []): int
    {
        $conn = $this->getConnection();
        try {
            $conn->beginTransaction();

            // Sequência por tipo: atualiza e captura doc_number
            $docNumber = null;
            if (!empty($movement['type'])) {
                // lock row / upsert se necessário
                $type = $movement['type'];
                // garante existência
                $conn->prepare('INSERT IGNORE INTO inv_movement_sequences (type, current_number) VALUES (:t, 0)')->execute([':t' => $type]);
                // incrementa com lock
                $conn->prepare('UPDATE inv_movement_sequences SET current_number = current_number + 1 WHERE type = :t')->execute([':t' => $type]);
                $stmtSeq = $conn->prepare('SELECT current_number FROM inv_movement_sequences WHERE type = :t FOR UPDATE');
                $stmtSeq->execute([':t' => $type]);
                $docNumber = (int)$stmtSeq->fetchColumn();
            }

            $stmt = $conn->prepare('INSERT INTO inv_movements (doc_number, type, movement_date, user_id, from_stock_id, to_stock_id, from_position_id, to_position_id, reason_id, equipment_id, notes, created_at)
                VALUES (:doc, :type, :date, :user, :from_stock, :to_stock, :from_pos, :to_pos, :reason, :equip, :notes, NOW())');
            $stmt->execute([
                ':doc' => $docNumber,
                ':type' => $movement['type'],
                ':date' => $movement['movement_date'] ?? date('Y-m-d H:i:s'),
                ':user' => $movement['user_id'],
                ':from_stock' => $movement['from_stock_id'] ?? null,
                ':to_stock' => $movement['to_stock_id'] ?? null,
                ':from_pos' => $movement['from_position_id'] ?? null,
                ':to_pos' => $movement['to_position_id'] ?? null,
                ':reason' => $movement['reason_id'] ?? null,
                ':equip' => $movement['equipment_id'] ?? null,
                ':notes' => $movement['notes'] ?? null,
            ]);
            $movementId = (int)$conn->lastInsertId();

            foreach ($items as $it) {
                $itemId = (int)$it['inv_item_id'];
                $qty = (float)$it['qty'];
                $adminType = $this->getItemAdminType($itemId);

                // Serial handling: aceitar serial_list do POST (um por linha)
                $serialListText = trim((string)($it['serial_list'] ?? ''));
                $serials = [];
                if ($adminType === 'serial') {
                    if ($serialListText === '') {
                        throw new Exception('Obrigatório informar os números de série para o item ' . $itemId);
                    }
                    $lines = preg_split('/\r?\n/', $serialListText);
                    foreach ($lines as $line) {
                        $code = trim($line);
                        if ($code !== '') { $serials[] = $code; }
                    }
                    if (count($serials) !== (int)$qty) {
                        throw new Exception('Quantidade de séries diferente da quantidade para o item ' . $itemId);
                    }
                } elseif ($adminType === 'lot') {
                    if (!array_key_exists('batch_code', $it) || $it['batch_code'] === '') {
                        throw new Exception('Obrigatório informar o lote para o item ' . $itemId);
                    }
                }

                $unitCost = isset($it['unit_cost']) ? (float)$it['unit_cost'] : null;

                $stmtItem = $conn->prepare('INSERT INTO inv_movement_items (inv_movement_id, inv_item_id, qty, unit_cost, total_cost, batch_code, expiration_date, created_at)
                    VALUES (:mov, :item, :qty, :unit, :total, :batch, :exp, NOW())');

                $total = $unitCost !== null ? $unitCost * $qty : null;
                $stmtItem->execute([
                    ':mov' => $movementId,
                    ':item' => $itemId,
                    ':qty' => $qty,
                    ':unit' => $unitCost,
                    ':total' => $total,
                    ':batch' => $it['batch_code'] ?? null,
                    ':exp' => $it['expiration_date'] ?? null,
                ]);
                $movementItemId = (int)$conn->lastInsertId();

                switch ($movement['type']) {
                    case 'entry':
                        $toStock = (int)($it['to_stock_id'] ?? $movement['to_stock_id']);
                        $toPos = $it['to_position_id'] ?? $movement['to_position_id'];
                        $this->balancesRepo->increase($itemId, $toStock, $toPos ? (int)$toPos : null, $it['batch_code'] ?? null, $it['expiration_date'] ?? null, $qty, $unitCost ?? $this->getCurrentAverageCost($itemId, $toStock, $toPos ? (int)$toPos : null, $it['batch_code'] ?? null, $it['expiration_date'] ?? null));
                        break;
                    case 'exit':
                        $this->balancesRepo->decrease($itemId, (int)$movement['from_stock_id'], $movement['from_position_id'] ?? null, $it['batch_code'] ?? null, $it['expiration_date'] ?? null, $qty);
                        break;
                    case 'transfer':
                        $this->balancesRepo->decrease($itemId, (int)$movement['from_stock_id'], $movement['from_position_id'] ?? null, $it['batch_code'] ?? null, $it['expiration_date'] ?? null, $qty);
                        $avg = $this->getCurrentAverageCost($itemId, (int)$movement['from_stock_id'], $movement['from_position_id'] ?? null, $it['batch_code'] ?? null, $it['expiration_date'] ?? null);
                        $this->balancesRepo->increase($itemId, (int)$movement['to_stock_id'], $movement['to_position_id'] ?? null, $it['batch_code'] ?? null, $it['expiration_date'] ?? null, $qty, $avg);
                        break;
                    case 'adjust':
                        if ($qty >= 0) {
                            $this->balancesRepo->increase($itemId, (int)$movement['to_stock_id'], $movement['to_position_id'] ?? null, $it['batch_code'] ?? null, $it['expiration_date'] ?? null, $qty, $unitCost ?? $this->getCurrentAverageCost($itemId, (int)$movement['to_stock_id'], $movement['to_position_id'] ?? null, $it['batch_code'] ?? null, $it['expiration_date'] ?? null));
                        } else {
                            $this->balancesRepo->decrease($itemId, (int)$movement['from_stock_id'], $movement['from_position_id'] ?? null, $it['batch_code'] ?? null, $it['expiration_date'] ?? null, abs($qty));
                        }
                        break;
                }

                // Gravação de seriais na entrada
                if ($adminType === 'serial') {
                    foreach ($serials as $code) {
                        // insere (ou reusa) inv_item_serials
                        $sid = $this->ensureItemSerial($conn, $itemId, $code);
                        $stmtS = $conn->prepare('INSERT INTO inv_movement_serials (inv_movement_item_id, inv_item_serial_id, created_at) VALUES (:mi, :sid, NOW())');
                        $stmtS->execute([':mi' => $movementItemId, ':sid' => $sid]);
                    }
                }
            }

            $conn->commit();
            $movRow = $this->getMovementRowById($conn, $movementId);
            if (is_array($movRow)) {
                $usuarioId = (int) ($movement['user_id'] ?? ($_SESSION['user_id'] ?? 1));
                LogAlteracaoService::registrarAlteracao(
                    'inv_movements',
                    $movementId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $movRow
                );
            }

            return $movementId;
        } catch (Exception $e) {
            $conn->rollBack();
            GenerateLog::generateLog('error', 'Falha ao registrar movimento', ['error' => $e->getMessage(), 'movement' => $movement]);
            throw $e;
        }
    }

    private function getItemAdminType(int $itemId): string
    {
        $stmt = $this->getConnection()->prepare('SELECT admin_type FROM inv_items WHERE id = :id');
        $stmt->bindValue(':id', $itemId, PDO::PARAM_INT);
        $stmt->execute();
        $type = $stmt->fetchColumn();
        return $type ?: 'none';
    }

    private function getCurrentAverageCost(int $itemId, int $stockId, ?int $positionId, ?string $batchCode, ?string $exp): float
    {
        $stmt = $this->getConnection()->prepare('SELECT average_cost FROM inv_balances WHERE inv_item_id = :item AND inv_stock_id = :stock AND ' .
            '(inv_position_id ' . ($positionId ? '= :pos' : 'IS NULL') . ') AND ' .
            '(batch_code ' . ($batchCode !== null && $batchCode !== '' ? '= :batch' : 'IS NULL') . ') AND ' .
            '(expiration_date ' . ($exp ? '= :exp' : 'IS NULL') . ') LIMIT 1');
        $stmt->bindValue(':item', $itemId, PDO::PARAM_INT);
        $stmt->bindValue(':stock', $stockId, PDO::PARAM_INT);
        if ($positionId) { $stmt->bindValue(':pos', $positionId, PDO::PARAM_INT); }
        if ($batchCode !== null && $batchCode !== '') { $stmt->bindValue(':batch', $batchCode); }
        if ($exp) { $stmt->bindValue(':exp', $exp); }
        $stmt->execute();
        $avg = $stmt->fetchColumn();
        return $avg !== false ? (float)$avg : 0.0;
    }

    private function ensureItemSerial(PDO $conn, int $itemId, string $serialCode): int
    {
        $s = $conn->prepare('SELECT id FROM inv_item_serials WHERE inv_item_id = :item AND serial_code = :code LIMIT 1');
        $s->execute([':item' => $itemId, ':code' => $serialCode]);
        $id = $s->fetchColumn();
        if ($id) { return (int)$id; }
        $ins = $conn->prepare('INSERT INTO inv_item_serials (inv_item_id, serial_code, created_at) VALUES (:item, :code, NOW())');
        $ins->execute([':item' => $itemId, ':code' => $serialCode]);
        return (int)$conn->lastInsertId();
    }

    public function reportMovements(int $page = 1, int $limit = 10, array $filters = []): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $params = [];
        $wheres = [];
        if (!empty($filters['type'])) { $wheres[] = 'm.type = :type'; $params[':type'] = $filters['type']; }
        if (!empty($filters['from'])) { $wheres[] = 'm.movement_date >= :from'; $params[':from'] = $filters['from'] . ' 00:00:00'; }
        if (!empty($filters['to'])) { $wheres[] = 'm.movement_date <= :to'; $params[':to'] = $filters['to'] . ' 23:59:59'; }
        if (!empty($filters['inv_item_id'])) { $wheres[] = 'mi.inv_item_id = :item'; $params[':item'] = (int)$filters['inv_item_id']; }
        if (!empty($filters['inv_stock_id'])) { $wheres[] = '(m.from_stock_id = :stock OR m.to_stock_id = :stock)'; $params[':stock'] = (int)$filters['inv_stock_id']; }
        if (!empty($filters['movement_id'])) { $wheres[] = 'm.id = :movement_id'; $params[':movement_id'] = (int)$filters['movement_id']; }
        $whereSql = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';

        $sql = 'SELECT m.id, m.type, m.movement_date, m.user_id, m.from_stock_id, m.to_stock_id, m.reason_id, m.equipment_id,
                       mi.inv_item_id, mi.qty, mi.unit_cost, mi.batch_code, mi.expiration_date
                FROM inv_movements m
                JOIN inv_movement_items mi ON mi.inv_movement_id = m.id
                ' . $whereSql . '
                ORDER BY m.movement_date DESC, m.id DESC
                LIMIT :limit OFFSET :offset';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countReportMovements(array $filters = []): int
    {
        $params = [];
        $wheres = [];
        if (!empty($filters['type'])) { $wheres[] = 'm.type = :type'; $params[':type'] = $filters['type']; }
        if (!empty($filters['from'])) { $wheres[] = 'm.movement_date >= :from'; $params[':from'] = $filters['from'] . ' 00:00:00'; }
        if (!empty($filters['to'])) { $wheres[] = 'm.movement_date <= :to'; $params[':to'] = $filters['to'] . ' 23:59:59'; }
        if (!empty($filters['inv_item_id'])) { $wheres[] = 'mi.inv_item_id = :item'; $params[':item'] = (int)$filters['inv_item_id']; }
        if (!empty($filters['inv_stock_id'])) { $wheres[] = '(m.from_stock_id = :stock OR m.to_stock_id = :stock)'; $params[':stock'] = (int)$filters['inv_stock_id']; }
        if (!empty($filters['movement_id'])) { $wheres[] = 'm.id = :movement_id'; $params[':movement_id'] = (int)$filters['movement_id']; }
        $whereSql = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';
        $stmt = $this->getConnection()->prepare('SELECT COUNT(*) FROM inv_movements m JOIN inv_movement_items mi ON mi.inv_movement_id = m.id ' . $whereSql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Retorna o próximo AUTO_INCREMENT previsto para inv_movements
     */
    public function getNextId(): int
    {
        $stmt = $this->getConnection()->query("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inv_movements'");
        $val = $stmt->fetchColumn();
        return $val ? (int)$val : 1;
    }

    /**
     * Próximo número sequencial por tipo (sem incrementar) — para exibição no formulário
     */
    public function getNextSequence(string $type): int
    {
        // garante existência
        $conn = $this->getConnection();
        $stmtIns = $conn->prepare('INSERT IGNORE INTO inv_movement_sequences (type, current_number) VALUES (:t, 0)');
        $stmtIns->execute([':t' => $type]);
        $stmt = $conn->prepare('SELECT current_number FROM inv_movement_sequences WHERE type = :t');
        $stmt->execute([':t' => $type]);
        $current = (int)$stmt->fetchColumn();
        return $current + 1;
    }

    public function getLastEntryUnitCost(int $itemId): ?float
    {
        $sql = 'SELECT mi.unit_cost
                FROM inv_movement_items mi
                JOIN inv_movements m ON m.id = mi.inv_movement_id
                WHERE m.type = :type AND mi.inv_item_id = :item AND mi.unit_cost IS NOT NULL
                ORDER BY m.movement_date DESC, m.id DESC, mi.id DESC
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':type', 'entry');
        $stmt->bindValue(':item', $itemId, PDO::PARAM_INT);
        $stmt->execute();
        $val = $stmt->fetchColumn();
        return $val !== false ? (float)$val : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getMovementRowById(\PDO $conn, int $id): ?array
    {
        $stmt = $conn->prepare('SELECT * FROM inv_movements WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }
}



