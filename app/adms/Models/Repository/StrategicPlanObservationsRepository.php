<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class StrategicPlanObservationsRepository extends DbConnection
{
    /**
     * Adicionar nova observação ao plano estratégico
     */
    public function addObservation(int $strategicPlanId, int $userId, string $observation): int
    {
        $sql = 'INSERT INTO adms_strategic_plan_observations (strategic_plan_id, user_id, observation, created_at) 
                VALUES (:strategic_plan_id, :user_id, :observation, NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            'strategic_plan_id' => $strategicPlanId,
            'user_id' => $userId,
            'observation' => $observation
        ]);
        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $row = $this->getTableRowById($newId);
            if (is_array($row)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? ($userId ?: 1));
                LogAlteracaoService::registrarAlteracao(
                    'adms_strategic_plan_observations',
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

    /**
     * Buscar todas as observações de um plano estratégico
     */
    public function getByStrategicPlanId(int $strategicPlanId): array
    {
        $sql = 'SELECT 
                    o.*,
                    u.name as user_name,
                    u.email as user_email,
                    d.name as department_name
                FROM adms_strategic_plan_observations o
                LEFT JOIN adms_users u ON o.user_id = u.id
                LEFT JOIN adms_departments d ON u.user_department_id = d.id
                WHERE o.strategic_plan_id = :strategic_plan_id
                ORDER BY o.created_at ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['strategic_plan_id' => $strategicPlanId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar última observação de um plano estratégico
     */
    public function getLastObservation(int $strategicPlanId): ?array
    {
        $sql = 'SELECT 
                    o.*,
                    u.name as user_name,
                    u.email as user_email,
                    d.name as department_name
                FROM adms_strategic_plan_observations o
                LEFT JOIN adms_users u ON o.user_id = u.id
                LEFT JOIN adms_departments d ON u.user_department_id = d.id
                WHERE o.strategic_plan_id = :strategic_plan_id
                ORDER BY o.created_at DESC
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['strategic_plan_id' => $strategicPlanId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Buscar observações recentes (últimas 5) de um plano
     */
    public function getRecentObservations(int $strategicPlanId, int $limit = 5): array
    {
        $sql = 'SELECT 
                    o.*,
                    u.name as user_name,
                    u.email as user_email,
                    d.name as department_name
                FROM adms_strategic_plan_observations o
                LEFT JOIN adms_users u ON o.user_id = u.id
                LEFT JOIN adms_departments d ON u.user_department_id = d.id
                WHERE o.strategic_plan_id = :strategic_plan_id
                ORDER BY o.created_at DESC
                LIMIT :limit';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            'strategic_plan_id' => $strategicPlanId,
            'limit' => $limit
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Contar total de observações de um plano
     */
    public function countByStrategicPlanId(int $strategicPlanId): int
    {
        $sql = 'SELECT COUNT(*) as total FROM adms_strategic_plan_observations WHERE strategic_plan_id = :strategic_plan_id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['strategic_plan_id' => $strategicPlanId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Buscar observação por ID
     */
    public function getById(int $id): ?array
    {
        $sql = 'SELECT 
                    o.*,
                    u.name as user_name,
                    u.email as user_email,
                    d.name as department_name
                FROM adms_strategic_plan_observations o
                LEFT JOIN adms_users u ON o.user_id = u.id
                LEFT JOIN adms_departments d ON u.user_department_id = d.id
                WHERE o.id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getTableRowById(int $id): ?array
    {
        $sql = 'SELECT * FROM adms_strategic_plan_observations WHERE id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }
}
