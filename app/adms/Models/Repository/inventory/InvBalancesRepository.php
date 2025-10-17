<?php

namespace App\adms\Models\Repository\inventory;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use PDO;
use Exception;

class InvBalancesRepository extends DbConnection
{
    public function getBalance(int $itemId, int $stockId, ?int $positionId, ?string $batchCode, ?string $expirationDate): array|bool
    {
        $sql = 'SELECT * FROM inv_balances WHERE inv_item_id = :item AND inv_stock_id = :stock AND ' .
            '(inv_position_id ' . ($positionId ? '= :position' : 'IS NULL') . ') AND ' .
            '(batch_code ' . ($batchCode !== null && $batchCode !== '' ? '= :batch' : 'IS NULL') . ') AND ' .
            '(expiration_date ' . ($expirationDate ? '= :exp' : 'IS NULL') . ') LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':item', $itemId, PDO::PARAM_INT);
        $stmt->bindValue(':stock', $stockId, PDO::PARAM_INT);
        if ($positionId) { $stmt->bindValue(':position', $positionId, PDO::PARAM_INT); }
        if ($batchCode !== null && $batchCode !== '') { $stmt->bindValue(':batch', $batchCode); }
        if ($expirationDate) { $stmt->bindValue(':exp', $expirationDate); }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getBalancesByItem(int $itemId): array
    {
        $sql = 'SELECT b.*, s.name AS stock_name, p.code AS position_code, p.description AS position_description
                FROM inv_balances b
                LEFT JOIN inv_stocks s ON s.id = b.inv_stock_id
                LEFT JOIN inv_positions p ON p.id = b.inv_position_id
                WHERE b.inv_item_id = :item
                ORDER BY s.name, (p.code IS NULL), p.code, b.batch_code, b.expiration_date';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':item', $itemId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function increase(int $itemId, int $stockId, ?int $positionId, ?string $batchCode, ?string $expirationDate, float $qty, float $unitCost): void
    {
        $conn = $this->getConnection();
        $row = $this->getBalance($itemId, $stockId, $positionId, $batchCode, $expirationDate);

        if ($row) {
            $newQty = (float)$row['qty'] + $qty;
            // média móvel
            $prevTotal = (float)$row['qty'] * (float)$row['average_cost'];
            $newAverage = $newQty > 0 ? ($prevTotal + ($qty * $unitCost)) / $newQty : $unitCost;
            $sql = 'UPDATE inv_balances SET qty = :qty, average_cost = :avg, updated_at = NOW() WHERE id = :id';
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':qty', $newQty);
            $stmt->bindValue(':avg', round($newAverage, 6));
            $stmt->bindValue(':id', $row['id'], PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $sql = 'INSERT INTO inv_balances (inv_item_id, inv_stock_id, inv_position_id, batch_code, expiration_date, qty, average_cost, created_at)
                    VALUES (:item,:stock,:position,:batch,:exp,:qty,:avg,NOW())';
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':item', $itemId, PDO::PARAM_INT);
            $stmt->bindValue(':stock', $stockId, PDO::PARAM_INT);
            $stmt->bindValue(':position', $positionId ?: null, $positionId ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':batch', $batchCode ?: null, $batchCode !== null && $batchCode !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':exp', $expirationDate ?: null, $expirationDate ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':qty', $qty);
            $stmt->bindValue(':avg', round($unitCost, 6));
            $stmt->execute();
        }
    }

    public function decrease(int $itemId, int $stockId, ?int $positionId, ?string $batchCode, ?string $expirationDate, float $qty): void
    {
        $conn = $this->getConnection();
        $row = $this->getBalance($itemId, $stockId, $positionId, $batchCode, $expirationDate);
        if (!$row) {
            throw new Exception('Saldo inexistente para saída.');
        }
        if ((float)$row['qty'] < $qty) {
            throw new Exception('Saldo insuficiente para saída.');
        }
        $newQty = (float)$row['qty'] - $qty;
        $sql = 'UPDATE inv_balances SET qty = :qty, updated_at = NOW() WHERE id = :id';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':qty', $newQty);
        $stmt->bindValue(':id', $row['id'], PDO::PARAM_INT);
        $stmt->execute();
    }

    // Relatório de saldos atual por item/estoque/posição/lote/validade
    public function reportBalances(int $page = 1, int $limit = 10, array $filters = []): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $params = [];
        $wheres = [];
        if (!empty($filters['inv_item_id'])) { $wheres[] = 'b.inv_item_id = :item'; $params[':item'] = (int)$filters['inv_item_id']; }
        if (!empty($filters['inv_stock_id'])) { $wheres[] = 'b.inv_stock_id = :stock'; $params[':stock'] = (int)$filters['inv_stock_id']; }
        if (!empty($filters['inv_position_id'])) { $wheres[] = 'b.inv_position_id = :pos'; $params[':pos'] = (int)$filters['inv_position_id']; }
        if ((isset($filters['batch_code'])) && $filters['batch_code'] !== '') { $wheres[] = 'b.batch_code LIKE :batch'; $params[':batch'] = '%' . $filters['batch_code'] . '%'; }
        if (!empty($filters['expiration_date'])) { $wheres[] = 'b.expiration_date = :exp'; $params[':exp'] = $filters['expiration_date']; }
        $whereSql = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';

        $sql = 'SELECT b.*, i.code AS item_code, i.description AS item_description, s.name AS stock_name,
                       p.code AS position_code, p.description AS position_description,
                       (b.qty * b.average_cost) AS total_value
                FROM inv_balances b
                LEFT JOIN inv_items i ON i.id = b.inv_item_id
                LEFT JOIN inv_stocks s ON s.id = b.inv_stock_id
                LEFT JOIN inv_positions p ON p.id = b.inv_position_id
                ' . $whereSql . '
                ORDER BY i.description, s.name, (p.code IS NULL), p.code
                LIMIT :limit OFFSET :offset';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR); }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countReportBalances(array $filters = []): int
    {
        $params = [];
        $wheres = [];
        if (!empty($filters['inv_item_id'])) { $wheres[] = 'inv_item_id = :item'; $params[':item'] = (int)$filters['inv_item_id']; }
        if (!empty($filters['inv_stock_id'])) { $wheres[] = 'inv_stock_id = :stock'; $params[':stock'] = (int)$filters['inv_stock_id']; }
        if (!empty($filters['inv_position_id'])) { $wheres[] = 'inv_position_id = :pos'; $params[':pos'] = (int)$filters['inv_position_id']; }
        if ((isset($filters['batch_code'])) && $filters['batch_code'] !== '') { $wheres[] = 'batch_code LIKE :batch'; $params[':batch'] = '%' . $filters['batch_code'] . '%'; }
        if (!empty($filters['expiration_date'])) { $wheres[] = 'expiration_date = :exp'; $params[':exp'] = $filters['expiration_date']; }
        $whereSql = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';
        $stmt = $this->getConnection()->prepare('SELECT COUNT(*) FROM inv_balances ' . $whereSql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR); }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
}



