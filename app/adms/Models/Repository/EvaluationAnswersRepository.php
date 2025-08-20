<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

/**
 * Repository responsável por buscar e manipular respostas de avaliação no banco de dados.
 *
 * Métodos para recuperar, filtrar, exportar e visualizar respostas detalhadas.
 *
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class EvaluationAnswersRepository extends DbConnection
{
    /** @var bool $result Resultado da última operação */
    private bool $result = false;

    /**
     * Recuperar todas as respostas de avaliação com filtros e paginação.
     *
     * @param array $criteria Critérios de busca
     * @param int $page Página atual
     * @param int $limitResult Limite de resultados por página
     * @return array Lista de respostas
     */
    public function getAllAnswers(array $criteria = [], int $page = 1, int $limitResult = 20): array
    {
        $offset = max(0, ($page - 1) * $limitResult);
        $whereClauses = [];
        $parameters = [];

        if (!empty($criteria['usuario_id'])) {
            $whereClauses[] = 'ea.usuario_id = :usuario_id';
            $parameters[':usuario_id'] = $criteria['usuario_id'];
        }
        if (!empty($criteria['modelo_id'])) {
            $whereClauses[] = 'em.id = :modelo_id';
            $parameters[':modelo_id'] = $criteria['modelo_id'];
        }
        if (!empty($criteria['pergunta_id'])) {
            $whereClauses[] = 'eq.id = :pergunta_id';
            $parameters[':pergunta_id'] = $criteria['pergunta_id'];
        }
        if (!empty($criteria['status'])) {
            $whereClauses[] = 'ea.status = :status';
            $parameters[':status'] = $criteria['status'];
        }
        if (!empty($criteria['data_ini'])) {
            $whereClauses[] = 'ea.created_at >= :data_ini';
            $parameters[':data_ini'] = $criteria['data_ini'] . ' 00:00:00';
        }
        if (!empty($criteria['data_fim'])) {
            $whereClauses[] = 'ea.created_at <= :data_fim';
            $parameters[':data_fim'] = $criteria['data_fim'] . ' 23:59:59';
        }

        $whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

        $sql = 'SELECT ea.id, ea.usuario_id, u.name as usuario_nome, ea.evaluation_model_id, em.titulo as modelo_titulo,
                       ea.evaluation_question_id, eq.pergunta as pergunta, ea.resposta, ea.pontuacao, ea.comentario, ea.status, ea.created_at
                FROM adms_evaluation_answers ea
                LEFT JOIN adms_users u ON ea.usuario_id = u.id
                LEFT JOIN adms_evaluation_models em ON ea.evaluation_model_id = em.id
                LEFT JOIN adms_evaluation_questions eq ON ea.evaluation_question_id = eq.id
                ' . $whereSql . '
                ORDER BY ea.created_at DESC
                LIMIT :limit OFFSET :offset';

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($parameters as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limitResult, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recuperar a quantidade total de respostas para paginação.
     *
     * @param array $criteria Critérios de busca
     * @return int Quantidade total
     */
    public function getAmountAnswers(array $criteria = []): int
    {
        $whereClauses = [];
        $parameters = [];
        if (!empty($criteria['usuario_id'])) {
            $whereClauses[] = 'usuario_id = :usuario_id';
            $parameters[':usuario_id'] = $criteria['usuario_id'];
        }
        if (!empty($criteria['modelo_id'])) {
            $whereClauses[] = 'evaluation_model_id = :modelo_id';
            $parameters[':modelo_id'] = $criteria['modelo_id'];
        }
        if (!empty($criteria['pergunta_id'])) {
            $whereClauses[] = 'evaluation_question_id = :pergunta_id';
            $parameters[':pergunta_id'] = $criteria['pergunta_id'];
        }
        if (!empty($criteria['status'])) {
            $whereClauses[] = 'status = :status';
            $parameters[':status'] = $criteria['status'];
        }
        if (!empty($criteria['data_ini'])) {
            $whereClauses[] = 'created_at >= :data_ini';
            $parameters[':data_ini'] = $criteria['data_ini'] . ' 00:00:00';
        }
        if (!empty($criteria['data_fim'])) {
            $whereClauses[] = 'created_at <= :data_fim';
            $parameters[':data_fim'] = $criteria['data_fim'] . ' 23:59:59';
        }
        $whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
        $sql = 'SELECT COUNT(id) as amount_records FROM adms_evaluation_answers ' . $whereSql;
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($parameters as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records'] ?? 0);
    }

    /**
     * Exportar respostas filtradas para CSV.
     *
     * @param array $criteria Critérios de busca
     * @return array Lista de respostas para exportação
     */
    public function exportAnswersCSV(array $criteria = []): array
    {
        // Reaproveita a query de getAllAnswers, mas sem LIMIT/OFFSET
        $whereClauses = [];
        $parameters = [];
        if (!empty($criteria['usuario_id'])) {
            $whereClauses[] = 'ea.usuario_id = :usuario_id';
            $parameters[':usuario_id'] = $criteria['usuario_id'];
        }
        if (!empty($criteria['modelo_id'])) {
            $whereClauses[] = 'em.id = :modelo_id';
            $parameters[':modelo_id'] = $criteria['modelo_id'];
        }
        if (!empty($criteria['pergunta_id'])) {
            $whereClauses[] = 'eq.id = :pergunta_id';
            $parameters[':pergunta_id'] = $criteria['pergunta_id'];
        }
        if (!empty($criteria['status'])) {
            $whereClauses[] = 'ea.status = :status';
            $parameters[':status'] = $criteria['status'];
        }
        if (!empty($criteria['data_ini'])) {
            $whereClauses[] = 'ea.created_at >= :data_ini';
            $parameters[':data_ini'] = $criteria['data_ini'] . ' 00:00:00';
        }
        if (!empty($criteria['data_fim'])) {
            $whereClauses[] = 'ea.created_at <= :data_fim';
            $parameters[':data_fim'] = $criteria['data_fim'] . ' 23:59:59';
        }
        $whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
        $sql = 'SELECT ea.id, u.name as usuario, em.titulo as modelo, eq.pergunta, ea.resposta, ea.pontuacao, ea.comentario, ea.status, ea.created_at
                FROM adms_evaluation_answers ea
                LEFT JOIN adms_users u ON ea.usuario_id = u.id
                LEFT JOIN adms_evaluation_models em ON ea.evaluation_model_id = em.id
                LEFT JOIN adms_evaluation_questions eq ON ea.evaluation_question_id = eq.id
                ' . $whereSql . '
                ORDER BY ea.created_at DESC';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($parameters as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar resposta detalhada por ID.
     *
     * @param int $id
     * @return array|bool
     */
    public function getAnswerDetail(int $id): array|bool
    {
        $sql = 'SELECT ea.*, u.name as usuario_nome, em.titulo as modelo_titulo, eq.pergunta
                FROM adms_evaluation_answers ea
                LEFT JOIN adms_users u ON ea.usuario_id = u.id
                LEFT JOIN adms_evaluation_models em ON ea.evaluation_model_id = em.id
                LEFT JOIN adms_evaluation_questions eq ON ea.evaluation_question_id = eq.id
                WHERE ea.id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Métodos para criar, editar e deletar respostas podem ser adicionados conforme necessidade do fluxo.
    
    /**
     * Criar nova resposta de avaliação.
     *
     * @param array $data Dados da resposta
     * @return bool
     */
    public function create(array $data): bool
    {
        try {
            $sql = 'INSERT INTO adms_evaluation_answers (usuario_id, evaluation_model_id, evaluation_question_id, resposta, pontuacao, comentario, status, created_at, updated_at) 
                    VALUES (:usuario_id, :evaluation_model_id, :evaluation_question_id, :resposta, :pontuacao, :comentario, :status, :created_at, :updated_at)';
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':usuario_id', $data['usuario_id'], PDO::PARAM_INT);
            $stmt->bindValue(':evaluation_model_id', $data['evaluation_model_id'], PDO::PARAM_INT);
            $stmt->bindValue(':evaluation_question_id', $data['evaluation_question_id'], PDO::PARAM_INT);
            $stmt->bindValue(':resposta', $data['resposta'], PDO::PARAM_STR);
            $stmt->bindValue(':pontuacao', $data['pontuacao'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':comentario', $data['comentario'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':status', $data['status'] ?? 'ativo', PDO::PARAM_STR);
            $stmt->bindValue(':created_at', $data['created_at'], PDO::PARAM_STR);
            $stmt->bindValue(':updated_at', $data['updated_at'], PDO::PARAM_STR);
            
            $this->result = $stmt->execute();
            
            if ($this->result) {
                GenerateLog::generateLog("info", "Resposta de avaliação criada com sucesso", null);
            }
            
            return $this->result;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao criar resposta de avaliação: " . $e->getMessage(), null);
            return false;
        }
    }

    /**
     * Atualizar resposta de avaliação.
     *
     * @param array $data Dados da resposta
     * @param int $id ID da resposta
     * @return bool
     */
    public function update(array $data, int $id): bool
    {
        try {
            $sql = 'UPDATE adms_evaluation_answers 
                    SET usuario_id = :usuario_id, evaluation_model_id = :evaluation_model_id, 
                        evaluation_question_id = :evaluation_question_id, resposta = :resposta, 
                        pontuacao = :pontuacao, comentario = :comentario, status = :status, 
                        updated_at = :updated_at 
                    WHERE id = :id';
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':usuario_id', $data['usuario_id'], PDO::PARAM_INT);
            $stmt->bindValue(':evaluation_model_id', $data['evaluation_model_id'], PDO::PARAM_INT);
            $stmt->bindValue(':evaluation_question_id', $data['evaluation_question_id'], PDO::PARAM_INT);
            $stmt->bindValue(':resposta', $data['resposta'], PDO::PARAM_STR);
            $stmt->bindValue(':pontuacao', $data['pontuacao'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':comentario', $data['comentario'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':status', $data['status'] ?? 'ativo', PDO::PARAM_STR);
            $stmt->bindValue(':updated_at', date('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            
            $this->result = $stmt->execute();
            
            if ($this->result) {
                GenerateLog::generateLog("info", "Resposta de avaliação atualizada com sucesso", null);
            }
            
            return $this->result;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao atualizar resposta de avaliação: " . $e->getMessage(), null);
            return false;
        }
    }

    /**
     * Deletar resposta de avaliação.
     *
     * @param int $id ID da resposta
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            $sql = 'DELETE FROM adms_evaluation_answers WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            
            $this->result = $stmt->execute();
            
            if ($this->result) {
                GenerateLog::generateLog("info", "Resposta de avaliação deletada com sucesso", null);
            }
            
            return $this->result;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao deletar resposta de avaliação: " . $e->getMessage(), null);
            return false;
        }
    }

    /**
     * Buscar resposta por ID para edição.
     *
     * @param int $id
     * @return array|bool
     */
    public function getAnswerById(int $id): array|bool
    {
        $sql = 'SELECT * FROM adms_evaluation_answers WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar avaliações/questionários pendentes de um colaborador
     *
     * @param int $usuarioId
     * @return array
     */
    public function getPendingEvaluationsByUser(int $usuarioId): array
    {
        $sql = 'SELECT em.id as modelo_id, em.titulo as modelo_titulo, em.data_limite, eq.id as pergunta_id, eq.pergunta, eq.tipo
                FROM adms_evaluation_models em
                INNER JOIN adms_evaluation_questions eq ON eq.evaluation_model_id = em.id
                LEFT JOIN adms_evaluation_answers ea ON ea.evaluation_model_id = em.id AND ea.evaluation_question_id = eq.id AND ea.usuario_id = :usuario_id
                WHERE em.status = 1
                  AND (ea.id IS NULL OR ea.status = "pendente")
                ORDER BY em.data_limite ASC, em.titulo, eq.ordem';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':usuario_id', $usuarioId, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
} 