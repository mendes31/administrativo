<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class StrategicIndicatorsRepository extends DbConnection
{

    public function getAll(): array
    {
        $stmt = $this->getConnection()->query('SELECT * FROM adms_strategic_indicators ORDER BY created_at DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->getConnection()->prepare('SELECT * FROM adms_strategic_indicators WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO adms_strategic_indicators (strategic_plan_id, name, description, target_value, current_value, unit, frequency, responsible_id, status, created_by, created_at, updated_at) VALUES (:strategic_plan_id, :name, :description, :target_value, :current_value, :unit, :frequency, :responsible_id, :status, :created_by, NOW(), NOW())';
        $stmt = $this->getConnection()->prepare($sql);
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
        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $row = $this->getById($newId);
            if (is_array($row)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_strategic_indicators',
                    $newId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $row
                );
            }
        }

        return $newId;
    }

    public function update(int $id, array $data): bool
    {
        $oldRow = $this->getById($id);
        $sql = 'UPDATE adms_strategic_indicators SET strategic_plan_id = :strategic_plan_id, name = :name, description = :description, target_value = :target_value, current_value = :current_value, unit = :unit, frequency = :frequency, responsible_id = :responsible_id, status = :status, updated_at = NOW() WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $ok = $stmt->execute([
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
        if ($ok && is_array($oldRow)) {
            $newRow = $this->getById($id);
            if (is_array($newRow)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_strategic_indicators',
                    $id,
                    $usuarioId,
                    'UPDATE',
                    $oldRow,
                    $newRow
                );
            }
        }

        return $ok;
    }

    public function delete(int $id): bool
    {
        $oldRow = $this->getById($id);
        $stmt = $this->getConnection()->prepare('DELETE FROM adms_strategic_indicators WHERE id = :id');
        $ok = $stmt->execute(['id' => $id]);
        if ($ok && is_array($oldRow)) {
            $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao(
                'adms_strategic_indicators',
                $id,
                $usuarioId,
                'DELETE',
                $oldRow,
                []
            );
        }

        return $ok;
    }

    public function getByStrategicPlanId(int $planId): array
    {
        $stmt = $this->getConnection()->prepare('SELECT * FROM adms_strategic_indicators WHERE strategic_plan_id = :plan_id ORDER BY created_at DESC');
        $stmt->execute(['plan_id' => $planId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalCount(): int
    {
        $stmt = $this->getConnection()->query('SELECT COUNT(*) as total FROM adms_strategic_indicators');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    public function getActiveCount(): int
    {
        $stmt = $this->getConnection()->query("SELECT COUNT(*) as total FROM adms_strategic_indicators WHERE status = 'Ativo'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }
} 