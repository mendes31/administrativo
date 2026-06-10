<?php

namespace App\adms\Models\Repository\inventory;

use App\adms\Models\Services\DbConnection;
use PDO;

class InvCostPeriodsRepository extends DbConnection
{
    /**
     * @return list<array<string, mixed>>
     */
    public function getAll(int $page = 1, int $limit = 20): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $sql = 'SELECT id, name, date_from, date_to, status, kwh_tariff, notes, created_at, updated_at
                FROM inv_cost_periods
                ORDER BY date_from DESC, id DESC
                LIMIT :limit OFFSET :offset';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countAll(): int
    {
        $stmt = $this->getConnection()->query('SELECT COUNT(*) FROM inv_cost_periods');

        return (int)$stmt->fetchColumn();
    }

    public function getOne(int $id): array|false
    {
        $stmt = $this->getConnection()->prepare('SELECT * FROM inv_cost_periods WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getForSelect(): array
    {
        $sql = 'SELECT id, name, date_from, date_to, status
                FROM inv_cost_periods
                ORDER BY date_from DESC, name ASC';
        $stmt = $this->getConnection()->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO inv_cost_periods (name, date_from, date_to, status, kwh_tariff, notes, created_at, updated_at)
                VALUES (:name, :date_from, :date_to, :status, :kwh_tariff, :notes, :created_at, :updated_at)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':name', (string)$data['name']);
        $stmt->bindValue(':date_from', (string)$data['date_from']);
        $stmt->bindValue(':date_to', (string)$data['date_to']);
        $stmt->bindValue(':status', (string)($data['status'] ?? 'draft'));
        $tariff = $data['kwh_tariff'] ?? null;
        $stmt->bindValue(':kwh_tariff', $tariff !== null && $tariff !== '' ? (float)$tariff : null, $tariff !== null && $tariff !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':notes', $data['notes'] ?? null);
        $stmt->bindValue(':created_at', $now);
        $stmt->bindValue(':updated_at', $now);
        $stmt->execute();

        return (int)$this->getConnection()->lastInsertId();
    }
}
