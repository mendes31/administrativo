<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use PDO;

class StrategicIndicatorsRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM adms_strategic_indicators ORDER BY created_at DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM adms_strategic_indicators WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO adms_strategic_indicators (strategic_plan_id, name, description, target_value, current_value, unit, frequency, responsible_id, status, created_by, created_at, updated_at) VALUES (:strategic_plan_id, :name, :description, :target_value, :current_value, :unit, :frequency, :responsible_id, :status, :created_by, NOW(), NOW())';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'strategic_plan_id' => $data['strategic_plan_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'target_value' => $data['target_value'],
            'current_value' => $data['current_value'],
            'unit' => $data['unit'],
            'frequency' => $data['frequency'],
            'responsible_id' => $data['responsible_id'],
            'status' => $data['status'],
            'created_by' => $data['created_by'],
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE adms_strategic_indicators SET strategic_plan_id = :strategic_plan_id, name = :name, description = :description, target_value = :target_value, current_value = :current_value, unit = :unit, frequency = :frequency, responsible_id = :responsible_id, status = :status, updated_at = NOW() WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'strategic_plan_id' => $data['strategic_plan_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'target_value' => $data['target_value'],
            'current_value' => $data['current_value'],
            'unit' => $data['unit'],
            'frequency' => $data['frequency'],
            'responsible_id' => $data['responsible_id'],
            'status' => $data['status'],
            'id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM adms_strategic_indicators WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
} 