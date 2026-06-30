<?php

declare(strict_types=1);

namespace App\adms\Models\Repository\inventory;

use App\adms\Models\Services\DbConnection;
use PDO;

class InvComplexityLevelFactorsRepository extends DbConnection
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listActive(): array
    {
        $sql = 'SELECT id, code, label, multiplier, notes, sort_order, is_active, updated_at
                FROM inv_complexity_level_factors
                WHERE is_active = 1
                ORDER BY sort_order ASC, code ASC';
        $stmt = $this->getConnection()->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listAll(): array
    {
        $sql = 'SELECT id, code, label, multiplier, notes, sort_order, is_active, updated_at
                FROM inv_complexity_level_factors
                ORDER BY sort_order ASC, code ASC';
        $stmt = $this->getConnection()->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<string, float> code => multiplier
     */
    public function getMultiplierMap(): array
    {
        $map = [];
        foreach ($this->listActive() as $row) {
            $code = mb_strtoupper(trim((string)($row['code'] ?? '')), 'UTF-8');
            if ($code === '') {
                continue;
            }
            $map[$code] = (float)($row['multiplier'] ?? 1.0);
        }

        return $map;
    }

    /**
     * @param array<int, array{multiplier: float, label?: string, notes?: string|null}> $updatesById
     */
    public function updateMultipliers(array $updatesById): void
    {
        if ($updatesById === []) {
            return;
        }

        $sql = 'UPDATE inv_complexity_level_factors
                SET multiplier = :multiplier,
                    label = COALESCE(:label, label),
                    notes = :notes,
                    updated_at = :updated_at
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $now = date('Y-m-d H:i:s');

        foreach ($updatesById as $id => $data) {
            $id = (int)$id;
            if ($id <= 0) {
                continue;
            }

            $label = isset($data['label']) ? trim((string)$data['label']) : null;
            $notes = array_key_exists('notes', $data) ? ($data['notes'] !== null ? (string)$data['notes'] : null) : null;

            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':multiplier', round((float)($data['multiplier'] ?? 1.0), 4));
            $stmt->bindValue(':label', $label !== '' ? $label : null, $label !== '' && $label !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':notes', $notes, $notes !== null && $notes !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':updated_at', $now);
            $stmt->execute();
        }
    }
}
