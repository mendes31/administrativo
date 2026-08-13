<?php

declare(strict_types=1);

namespace App\adms\Models\Repository\cashFlow;

use App\adms\Models\Services\DbConnection;
use PDO;

class FinCashInvestmentRepository extends DbConnection
{
    public function tableExists(): bool
    {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }
        try {
            $stmt = $this->getConnection()->query("SHOW TABLES LIKE 'adms_fin_cash_investments'");
            $exists = (bool) $stmt->fetchColumn();
        } catch (\Throwable) {
            $exists = false;
        }
        return $exists;
    }

    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function getAll(int $page, int $limit, array $filters = []): array
    {
        if (!$this->tableExists()) {
            return [];
        }
        $offset = max(0, ($page - 1) * $limit);
        [$where, $params] = $this->buildWhere($filters);
        $sql = "SELECT * FROM adms_fin_cash_investments {$where}
                ORDER BY movement_date DESC, id DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function countAll(array $filters = []): int
    {
        if (!$this->tableExists()) {
            return 0;
        }
        [$where, $params] = $this->buildWhere($filters);
        $stmt = $this->getConnection()->prepare("SELECT COUNT(*) FROM adms_fin_cash_investments {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getById(int $id): ?array
    {
        if (!$this->tableExists() || $id <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_fin_cash_investments WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int|false
    {
        if (!$this->tableExists()) {
            return false;
        }
        $stmt = $this->getConnection()->prepare(
            'INSERT INTO adms_fin_cash_investments
                (movement_type, category, bank_label, account_id, movement_date, amount, description,
                 status, created_by, created_at, updated_at)
             VALUES
                (:type, :cat, :bank, :acc, :date, :amount, :desc, :status, :user, :now, :now)'
        );
        $ok = $stmt->execute([
            ':type' => $data['movement_type'],
            ':cat' => $data['category'],
            ':bank' => $data['bank_label'],
            ':acc' => $data['account_id'] ?: null,
            ':date' => $data['movement_date'],
            ':amount' => $data['amount'],
            ':desc' => $data['description'] ?: null,
            ':status' => $data['status'] ?? 'ACTIVE',
            ':user' => $data['created_by'] ?? ($_SESSION['user_id'] ?? null),
            ':now' => date('Y-m-d H:i:s'),
        ]);
        return $ok ? (int) $this->getConnection()->lastInsertId() : false;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        if (!$this->tableExists() || $id <= 0) {
            return false;
        }
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_fin_cash_investments SET
                movement_type = :type,
                category = :cat,
                bank_label = :bank,
                account_id = :acc,
                movement_date = :date,
                amount = :amount,
                description = :desc,
                status = :status,
                updated_at = :now
             WHERE id = :id'
        );
        return $stmt->execute([
            ':type' => $data['movement_type'],
            ':cat' => $data['category'],
            ':bank' => $data['bank_label'],
            ':acc' => $data['account_id'] ?: null,
            ':date' => $data['movement_date'],
            ':amount' => $data['amount'],
            ':desc' => $data['description'] ?: null,
            ':status' => $data['status'] ?? 'ACTIVE',
            ':now' => date('Y-m-d H:i:s'),
            ':id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        if (!$this->tableExists() || $id <= 0) {
            return false;
        }
        $stmt = $this->getConnection()->prepare('DELETE FROM adms_fin_cash_investments WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getActiveUntil(string $untilDate): array
    {
        if (!$this->tableExists()) {
            return [];
        }
        $stmt = $this->getConnection()->prepare(
            "SELECT * FROM adms_fin_cash_investments
             WHERE status = 'ACTIVE' AND movement_date <= :until
             ORDER BY movement_date ASC, id ASC"
        );
        $stmt->execute([':until' => $untilDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<string>
     */
    public function distinctBankLabels(): array
    {
        if (!$this->tableExists()) {
            return [];
        }
        $stmt = $this->getConnection()->query(
            "SELECT DISTINCT bank_label FROM adms_fin_cash_investments
             WHERE status = 'ACTIVE' ORDER BY bank_label"
        );
        return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{0:string,1:array<string,mixed>}
     */
    private function buildWhere(array $filters): array
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['bank_label'])) {
            $where[] = 'bank_label LIKE :bank';
            $params[':bank'] = '%' . $filters['bank_label'] . '%';
        }
        if (!empty($filters['movement_type'])) {
            $where[] = 'movement_type = :type';
            $params[':type'] = $filters['movement_type'];
        }
        if (!empty($filters['category'])) {
            $where[] = 'category = :cat';
            $params[':cat'] = $filters['category'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'movement_date >= :from';
            $params[':from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'movement_date <= :to';
            $params[':to'] = $filters['date_to'];
        }
        return ['WHERE ' . implode(' AND ', $where), $params];
    }
}
