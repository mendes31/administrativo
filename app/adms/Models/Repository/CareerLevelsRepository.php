<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class CareerLevelsRepository extends DbConnection
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO adms_career_levels
                (career_track_id, name, level_order, position_id, description)
                VALUES (:career_track_id, :name, :level_order, :position_id, :description)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':career_track_id', (int) $data['career_track_id'], PDO::PARAM_INT);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':level_order', (int) ($data['level_order'] ?? 1), PDO::PARAM_INT);
        if (empty($data['position_id'])) {
            $stmt->bindValue(':position_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':position_id', (int) $data['position_id'], PDO::PARAM_INT);
        }
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->execute();
        return (int) $this->getConnection()->lastInsertId();
    }

    public function getByTrackId(int $trackId): array
    {
        $sql = 'SELECT l.*, p.name AS position_name
                FROM adms_career_levels l
                LEFT JOIN adms_positions p ON p.id = l.position_id
                WHERE l.career_track_id = :track_id
                ORDER BY l.level_order ASC, l.id ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':track_id', $trackId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $sql = 'SELECT * FROM adms_career_levels WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE adms_career_levels SET
                    name = :name, level_order = :level_order, position_id = :position_id,
                    description = :description, updated_at = NOW()
                WHERE id = :id AND career_track_id = :career_track_id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':career_track_id', (int) $data['career_track_id'], PDO::PARAM_INT);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':level_order', (int) ($data['level_order'] ?? 1), PDO::PARAM_INT);
        if (empty($data['position_id'])) {
            $stmt->bindValue(':position_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':position_id', (int) $data['position_id'], PDO::PARAM_INT);
        }
        $stmt->bindValue(':description', $data['description'] ?? null);
        return $stmt->execute();
    }

    public function delete(int $id, int $trackId): bool
    {
        $sql = 'DELETE FROM adms_career_levels WHERE id = :id AND career_track_id = :track_id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':track_id', $trackId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
