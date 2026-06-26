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
        $sql = 'INSERT INTO inv_cost_periods (name, date_from, date_to, status, kwh_tariff, energy_kwh_hvac, energy_kwh_production_common, energy_kwh_direct_cfix, energy_auto_split, notes, created_at, updated_at)
                VALUES (:name, :date_from, :date_to, :status, :kwh_tariff, :energy_kwh_hvac, :energy_kwh_production_common, :energy_kwh_direct_cfix, :energy_auto_split, :notes, :created_at, :updated_at)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':name', (string)$data['name']);
        $stmt->bindValue(':date_from', (string)$data['date_from']);
        $stmt->bindValue(':date_to', (string)$data['date_to']);
        $stmt->bindValue(':status', (string)($data['status'] ?? 'draft'));
        $this->bindNullableDecimal($stmt, ':kwh_tariff', $data['kwh_tariff'] ?? null);
        $this->bindNullableDecimal($stmt, ':energy_kwh_hvac', $data['energy_kwh_hvac'] ?? null);
        $this->bindNullableDecimal($stmt, ':energy_kwh_production_common', $data['energy_kwh_production_common'] ?? null);
        $this->bindNullableDecimal($stmt, ':energy_kwh_direct_cfix', $data['energy_kwh_direct_cfix'] ?? null);
        $stmt->bindValue(':energy_auto_split', (array_key_exists('energy_auto_split', $data) ? !empty($data['energy_auto_split']) : true) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':notes', $data['notes'] ?? null);
        $stmt->bindValue(':created_at', $now);
        $stmt->bindValue(':updated_at', $now);
        $stmt->execute();

        return (int)$this->getConnection()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'UPDATE inv_cost_periods
                SET name = :name,
                    date_from = :date_from,
                    date_to = :date_to,
                    status = :status,
                    kwh_tariff = :kwh_tariff,
                    energy_kwh_hvac = :energy_kwh_hvac,
                    energy_kwh_production_common = :energy_kwh_production_common,
                    energy_kwh_direct_cfix = :energy_kwh_direct_cfix,
                    energy_auto_split = :energy_auto_split,
                    notes = :notes,
                    updated_at = :updated_at
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':name', (string)$data['name']);
        $stmt->bindValue(':date_from', (string)$data['date_from']);
        $stmt->bindValue(':date_to', (string)$data['date_to']);
        $stmt->bindValue(':status', (string)($data['status'] ?? 'draft'));
        $this->bindNullableDecimal($stmt, ':kwh_tariff', $data['kwh_tariff'] ?? null);
        $this->bindNullableDecimal($stmt, ':energy_kwh_hvac', $data['energy_kwh_hvac'] ?? null);
        $this->bindNullableDecimal($stmt, ':energy_kwh_production_common', $data['energy_kwh_production_common'] ?? null);
        $this->bindNullableDecimal($stmt, ':energy_kwh_direct_cfix', $data['energy_kwh_direct_cfix'] ?? null);
        $stmt->bindValue(':energy_auto_split', (array_key_exists('energy_auto_split', $data) ? !empty($data['energy_auto_split']) : true) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':notes', $data['notes'] ?? null);
        $stmt->bindValue(':updated_at', $now);

        return $stmt->execute();
    }

    private function bindNullableDecimal(\PDOStatement $stmt, string $param, mixed $value): void
    {
        if ($value === null || $value === '') {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);

            return;
        }
        if (is_string($value)) {
            $value = str_replace(['.', ' '], ['', ''], $value);
            $value = str_replace(',', '.', trim($value));
        }
        if (!is_numeric($value)) {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);

            return;
        }
        $stmt->bindValue($param, round((float)$value, 4));
    }

    public function isClosed(int $id): bool
    {
        $row = $this->getOne($id);

        return is_array($row) && (string)($row['status'] ?? '') === 'closed';
    }

    public function delete(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $stmt = $this->getConnection()->prepare('DELETE FROM inv_cost_periods WHERE id = :id LIMIT 1');

        return $stmt->execute([':id' => $id]) && $stmt->rowCount() > 0;
    }
}
