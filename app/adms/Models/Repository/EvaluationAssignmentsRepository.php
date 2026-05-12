<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use App\adms\Helpers\GenerateLog;
use PDO;
use Exception;

/**
 * Repository para gerenciar atribuições de avaliações para usuários
 * 
 * @package App\adms\Models\Repository
 */
class EvaluationAssignmentsRepository extends DbConnection
{
    /**
     * Inserir nova atribuição
     */
    public function insert(array $data): int|bool
    {
        try {
            $userId = (int) ($data['adms_user_id'] ?? 0);
            $modelId = (int) ($data['evaluation_model_id'] ?? 0);
            $before = $this->getRawAssignmentByUserAndModel($userId, $modelId);

            $sql = 'INSERT INTO adms_evaluation_assignments 
                    (evaluation_model_id, adms_user_id, created_by, data_atribuicao, 
                     data_limite, status, tentativas, nota_maxima, created_at, updated_at)
                    VALUES (:evaluation_model_id, :adms_user_id, :created_by, :data_atribuicao,
                            :data_limite, :status, :tentativas, :nota_maxima, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE 
                        data_limite = VALUES(data_limite),
                        updated_at = NOW()';
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':evaluation_model_id', $data['evaluation_model_id'], PDO::PARAM_INT);
            $stmt->bindValue(':adms_user_id', $data['adms_user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':created_by', $data['created_by'], PDO::PARAM_INT);
            $stmt->bindValue(':data_atribuicao', $data['data_atribuicao'] ?? date('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':data_limite', $data['data_limite'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':status', $data['status'] ?? 'pendente', PDO::PARAM_STR);
            $stmt->bindValue(':tentativas', $data['tentativas'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':nota_maxima', $data['nota_maxima'] ?? null, PDO::PARAM_STR);
            
            $stmt->execute();

            $after = $this->getRawAssignmentByUserAndModel($userId, $modelId);
            if (is_array($after)) {
                $aid = (int) $after['id'];
                $logUid = (int) ($_SESSION['user_id'] ?? 1);
                if ($before === null) {
                    LogAlteracaoService::registrarAlteracao(
                        'adms_evaluation_assignments',
                        $aid,
                        $logUid,
                        'INSERT',
                        [],
                        $after
                    );
                } else {
                    LogAlteracaoService::registrarAlteracao(
                        'adms_evaluation_assignments',
                        $aid,
                        $logUid,
                        'UPDATE',
                        $before,
                        $after
                    );
                }

                return $aid;
            }

            return (int) $this->getConnection()->lastInsertId() ?: false;
            
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao inserir atribuição de avaliação", [
                'model_id' => $data['evaluation_model_id'] ?? null,
                'user_id' => $data['adms_user_id'] ?? null,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Atualizar atribuição existente
     */
    public function update(int $id, array $data): bool
    {
        try {
            $oldRow = $this->getRawAssignmentById($id);
            $sql = 'UPDATE adms_evaluation_assignments 
                    SET status = :status,
                        tentativas = :tentativas,
                        nota_maxima = :nota_maxima,
                        data_limite = :data_limite,
                        updated_at = NOW()
                    WHERE id = :id';
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':status', $data['status'], PDO::PARAM_STR);
            $stmt->bindValue(':tentativas', $data['tentativas'], PDO::PARAM_INT);
            $stmt->bindValue(':nota_maxima', $data['nota_maxima'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':data_limite', $data['data_limite'] ?? null, PDO::PARAM_STR);
            
            $ok = $stmt->execute();
            if ($ok && is_array($oldRow)) {
                $newRow = $this->getRawAssignmentById($id);
                if (is_array($newRow)) {
                    $logUid = (int) ($_SESSION['user_id'] ?? 1);
                    LogAlteracaoService::registrarAlteracao(
                        'adms_evaluation_assignments',
                        $id,
                        $logUid,
                        'UPDATE',
                        $oldRow,
                        $newRow
                    );
                }
            }

            return $ok;
            
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao atualizar atribuição", [
                'assignment_id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Buscar atribuição por ID
     */
    public function getById(int $id): ?array
    {
        $sql = 'SELECT ea.*, 
                       em.titulo as model_titulo,
                       em.nota_minima_aprovacao,
                       em.permitir_refazer,
                       em.max_tentativas,
                       em.mostrar_gabarito,
                       u.name as user_name,
                       u.email as user_email
                FROM adms_evaluation_assignments ea
                INNER JOIN adms_evaluation_models em ON em.id = ea.evaluation_model_id
                INNER JOIN adms_users u ON u.id = ea.adms_user_id
                WHERE ea.id = :id';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Buscar atribuição por usuário e modelo
     */
    public function getByUserAndModel(int $userId, int $modelId): ?array
    {
        $sql = 'SELECT * FROM adms_evaluation_assignments 
                WHERE adms_user_id = :user_id AND evaluation_model_id = :model_id';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':model_id', $modelId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Buscar todas as atribuições de um usuário
     */
    public function getAssignmentsByUser(int $userId, ?string $status = null): array
    {
        $sql = 'SELECT ea.*,
                       em.titulo,
                       em.descricao,
                       em.nota_minima_aprovacao,
                       em.max_tentativas,
                       em.permitir_refazer,
                       at.nome as training_name,
                       (SELECT COUNT(*) FROM adms_evaluation_questions WHERE evaluation_model_id = em.id) as total_questoes
                FROM adms_evaluation_assignments ea
                INNER JOIN adms_evaluation_models em ON em.id = ea.evaluation_model_id
                LEFT JOIN adms_trainings at ON at.id = em.adms_training_id
                WHERE ea.adms_user_id = :user_id';
        
        if ($status) {
            $sql .= ' AND ea.status = :status';
        }
        
        $sql .= ' ORDER BY CASE ea.status
                    WHEN \'pendente\' THEN 1
                    WHEN \'em_andamento\' THEN 2
                    WHEN \'reprovado\' THEN 3
                    WHEN \'aprovado\' THEN 4
                    WHEN \'concluido\' THEN 5
                    WHEN \'cancelado\' THEN 6
                END, ea.data_limite ASC';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        
        if ($status) {
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Buscar todas as atribuições de um modelo
     */
    public function getAssignmentsByModel(int $modelId): array
    {
        $sql = 'SELECT ea.*,
                       u.name as user_name,
                       u.email as user_email
                FROM adms_evaluation_assignments ea
                INNER JOIN adms_users u ON u.id = ea.adms_user_id
                WHERE ea.evaluation_model_id = :model_id
                ORDER BY ea.status, ea.data_limite ASC';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':model_id', $modelId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Cancelar atribuição
     */
    public function cancelAssignment(int $id, int $canceladoPor, string $motivo): bool
    {
        try {
            $oldRow = $this->getRawAssignmentById($id);
            $sql = 'UPDATE adms_evaluation_assignments 
                    SET status = \'cancelado\',
                        cancelado_em = NOW(),
                        cancelado_por = :cancelado_por,
                        motivo_cancelamento = :motivo,
                        updated_at = NOW()
                    WHERE id = :id';
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':cancelado_por', $canceladoPor, PDO::PARAM_INT);
            $stmt->bindValue(':motivo', $motivo, PDO::PARAM_STR);
            
            $ok = $stmt->execute();
            if ($ok && is_array($oldRow)) {
                $newRow = $this->getRawAssignmentById($id);
                if (is_array($newRow)) {
                    LogAlteracaoService::registrarAlteracao(
                        'adms_evaluation_assignments',
                        $id,
                        (int) ($_SESSION['user_id'] ?? 1),
                        'UPDATE',
                        $oldRow,
                        $newRow
                    );
                }
            }

            return $ok;
            
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao cancelar atribuição", [
                'assignment_id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Buscar atribuições ativas de um usuário (para cancelamento em lote)
     */
    public function getActiveAssignmentsByUser(int $userId): array
    {
        $sql = 'SELECT ea.id, ea.status, em.titulo
                FROM adms_evaluation_assignments ea
                INNER JOIN adms_evaluation_models em ON em.id = ea.evaluation_model_id
                WHERE ea.adms_user_id = :user_id
                AND ea.status NOT IN (\'aprovado\', \'concluido\', \'cancelado\')';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Buscar atribuições próximas do prazo (para notificações)
     */
    public function getAssignmentsNearDeadline(int $diasAntecedencia = 3): array
    {
        $sql = 'SELECT ea.*,
                       u.name as user_name,
                       u.email as user_email,
                       em.titulo as model_titulo
                FROM adms_evaluation_assignments ea
                INNER JOIN adms_users u ON u.id = ea.adms_user_id
                INNER JOIN adms_evaluation_models em ON em.id = ea.evaluation_model_id
                WHERE ea.status IN (\'pendente\', \'em_andamento\')
                AND ea.data_limite IS NOT NULL
                AND ea.data_limite BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :dias DAY)';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':dias', $diasAntecedencia, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Buscar atribuições vencidas
     */
    public function getExpiredAssignments(): array
    {
        $sql = 'SELECT ea.*,
                       u.name as user_name,
                       u.email as user_email,
                       em.titulo as model_titulo
                FROM adms_evaluation_assignments ea
                INNER JOIN adms_users u ON u.id = ea.adms_user_id
                INNER JOIN adms_evaluation_models em ON em.id = ea.evaluation_model_id
                WHERE ea.status IN (\'pendente\', \'em_andamento\')
                AND ea.data_limite IS NOT NULL
                AND ea.data_limite < CURDATE()';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Contar atribuições por status
     */
    public function countByStatus(int $modelId): array
    {
        $sql = 'SELECT status, COUNT(*) as total
                FROM adms_evaluation_assignments
                WHERE evaluation_model_id = :model_id
                GROUP BY status';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':model_id', $modelId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['status']] = (int)$row['total'];
        }
        
        return $result;
    }

    /**
     * Verificar se usuário pode responder
     */
    public function canUserAnswer(int $assignmentId): bool
    {
        $assignment = $this->getById($assignmentId);
        
        if (!$assignment) {
            return false;
        }
        
        // Pendente ou em andamento pode responder
        if (in_array($assignment['status'], ['pendente', 'em_andamento'])) {
            return true;
        }
        
        // Reprovado e permite refazer
        if ($assignment['status'] === 'reprovado' && $assignment['permitir_refazer']) {
            // Verificar limite de tentativas
            if ($assignment['max_tentativas'] === null) {
                return true;
            }
            return $assignment['tentativas'] < $assignment['max_tentativas'];
        }
        
        return false;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getRawAssignmentById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare('SELECT * FROM adms_evaluation_assignments WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getRawAssignmentByUserAndModel(int $userId, int $modelId): ?array
    {
        if ($userId <= 0 || $modelId <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_evaluation_assignments 
             WHERE adms_user_id = :user_id AND evaluation_model_id = :model_id LIMIT 1'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':model_id', $modelId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }
}

