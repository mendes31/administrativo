<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Helpers\GenerateLog;
use PDO;
use Exception;

/**
 * Repository para gerenciar histórico de tentativas de avaliações
 * 
 * @package App\adms\Models\Repository
 */
class EvaluationAttemptsRepository extends DbConnection
{
    /**
     * Inserir nova tentativa
     */
    public function insert(array $data): int|bool
    {
        try {
            $sql = 'INSERT INTO adms_evaluation_attempts 
                    (assignment_id, tentativa_numero, nota_obtida, total_questoes,
                     questoes_corretas, questoes_erradas, percentual, respostas,
                     data_inicio, data_finalizacao, tempo_gasto, created_at)
                    VALUES (:assignment_id, :tentativa_numero, :nota_obtida, :total_questoes,
                            :questoes_corretas, :questoes_erradas, :percentual, :respostas,
                            :data_inicio, :data_finalizacao, :tempo_gasto, NOW())';
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':assignment_id', $data['assignment_id'], PDO::PARAM_INT);
            $stmt->bindValue(':tentativa_numero', $data['tentativa_numero'], PDO::PARAM_INT);
            $stmt->bindValue(':nota_obtida', $data['nota_obtida'], PDO::PARAM_STR);
            $stmt->bindValue(':total_questoes', $data['total_questoes'], PDO::PARAM_INT);
            $stmt->bindValue(':questoes_corretas', $data['questoes_corretas'], PDO::PARAM_INT);
            $stmt->bindValue(':questoes_erradas', $data['questoes_erradas'], PDO::PARAM_INT);
            $stmt->bindValue(':percentual', $data['percentual'], PDO::PARAM_STR);
            $stmt->bindValue(':respostas', $data['respostas'], PDO::PARAM_STR); // JSON
            $stmt->bindValue(':data_inicio', $data['data_inicio'] ?? date('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':data_finalizacao', $data['data_finalizacao'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':tempo_gasto', $data['tempo_gasto'] ?? null, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return $this->getConnection()->lastInsertId();
            
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao inserir tentativa de avaliação", [
                'assignment_id' => $data['assignment_id'] ?? null,
                'tentativa_numero' => $data['tentativa_numero'] ?? null,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Buscar tentativa por ID
     */
    public function getById(int $id): ?array
    {
        $sql = 'SELECT att.*,
                       ea.adms_user_id,
                       ea.evaluation_model_id,
                       em.titulo as model_titulo,
                       em.nota_minima_aprovacao,
                       em.mostrar_gabarito,
                       em.permitir_refazer
                FROM adms_evaluation_attempts att
                INNER JOIN adms_evaluation_assignments ea ON ea.id = att.assignment_id
                INNER JOIN adms_evaluation_models em ON em.id = ea.evaluation_model_id
                WHERE att.id = :id';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Buscar todas as tentativas de uma atribuição
     */
    public function getAllAttemptsByAssignment(int $assignmentId): array
    {
        $sql = 'SELECT * FROM adms_evaluation_attempts 
                WHERE assignment_id = :assignment_id
                ORDER BY tentativa_numero DESC';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':assignment_id', $assignmentId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Buscar última tentativa de uma atribuição
     */
    public function getLatestAttempt(int $assignmentId): ?array
    {
        $sql = 'SELECT * FROM adms_evaluation_attempts 
                WHERE assignment_id = :assignment_id
                ORDER BY tentativa_numero DESC
                LIMIT 1';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':assignment_id', $assignmentId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Buscar melhor tentativa (maior nota)
     */
    public function getBestAttempt(int $assignmentId): ?array
    {
        $sql = 'SELECT * FROM adms_evaluation_attempts 
                WHERE assignment_id = :assignment_id
                ORDER BY nota_obtida DESC, tentativa_numero ASC
                LIMIT 1';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':assignment_id', $assignmentId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Contar tentativas de uma atribuição
     */
    public function countAttempts(int $assignmentId): int
    {
        $sql = 'SELECT COUNT(*) as total FROM adms_evaluation_attempts 
                WHERE assignment_id = :assignment_id';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':assignment_id', $assignmentId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Buscar estatísticas de tentativas de um usuário
     */
    public function getUserAttemptStats(int $userId): array
    {
        $sql = 'SELECT 
                    COUNT(DISTINCT att.assignment_id) as total_avaliacoes,
                    COUNT(att.id) as total_tentativas,
                    AVG(att.nota_obtida) as media_notas,
                    MAX(att.nota_obtida) as melhor_nota,
                    MIN(att.nota_obtida) as pior_nota,
                    SUM(CASE WHEN att.nota_obtida >= em.nota_minima_aprovacao THEN 1 ELSE 0 END) as total_aprovacoes,
                    SUM(CASE WHEN att.nota_obtida < em.nota_minima_aprovacao THEN 1 ELSE 0 END) as total_reprovacoes
                FROM adms_evaluation_attempts att
                INNER JOIN adms_evaluation_assignments ea ON ea.id = att.assignment_id
                INNER JOIN adms_evaluation_models em ON em.id = ea.evaluation_model_id
                WHERE ea.adms_user_id = :user_id';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Buscar estatísticas de um modelo
     */
    public function getModelAttemptStats(int $modelId): array
    {
        $sql = 'SELECT 
                    COUNT(DISTINCT ea.adms_user_id) as total_usuarios,
                    COUNT(att.id) as total_tentativas,
                    AVG(att.nota_obtida) as media_notas,
                    AVG(att.percentual) as media_percentual,
                    AVG(att.tempo_gasto) as media_tempo_segundos,
                    SUM(CASE WHEN att.nota_obtida >= em.nota_minima_aprovacao THEN 1 ELSE 0 END) as total_aprovacoes,
                    SUM(CASE WHEN att.nota_obtida < em.nota_minima_aprovacao THEN 1 ELSE 0 END) as total_reprovacoes
                FROM adms_evaluation_attempts att
                INNER JOIN adms_evaluation_assignments ea ON ea.id = att.assignment_id
                INNER JOIN adms_evaluation_models em ON em.id = ea.evaluation_model_id
                WHERE ea.evaluation_model_id = :model_id';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':model_id', $modelId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Buscar histórico completo com informações detalhadas
     */
    public function getDetailedHistory(int $assignmentId): array
    {
        $sql = 'SELECT 
                    att.*,
                    ea.evaluation_model_id,
                    ea.adms_user_id,
                    em.titulo as model_titulo,
                    em.nota_minima_aprovacao,
                    u.name as user_name,
                    CASE 
                        WHEN att.nota_obtida >= em.nota_minima_aprovacao THEN \'aprovado\'
                        ELSE \'reprovado\'
                    END as resultado
                FROM adms_evaluation_attempts att
                INNER JOIN adms_evaluation_assignments ea ON ea.id = att.assignment_id
                INNER JOIN adms_evaluation_models em ON em.id = ea.evaluation_model_id
                INNER JOIN adms_users u ON u.id = ea.adms_user_id
                WHERE att.assignment_id = :assignment_id
                ORDER BY att.tentativa_numero DESC';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':assignment_id', $assignmentId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Deletar todas as tentativas de uma atribuição (usado ao deletar modelo)
     */
    public function deleteByAssignment(int $assignmentId): bool
    {
        try {
            $sql = 'DELETE FROM adms_evaluation_attempts WHERE assignment_id = :assignment_id';
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':assignment_id', $assignmentId, PDO::PARAM_INT);
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao deletar tentativas", [
                'assignment_id' => $assignmentId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}

