<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

/**
 * Repository responsável em buscar e manipular perguntas de avaliação no banco de dados.
 *
 * Esta classe fornece métodos para recuperar, criar, atualizar e deletar perguntas de avaliação no banco de dados.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class EvaluationQuestionsRepository extends DbConnection
{
    /** @var bool $result Resultado da última operação */
    private bool $result = false;

    /**
     * Recuperar todas as perguntas de avaliação com paginação e filtros.
     *
     * Este método retorna uma lista de perguntas de avaliação da tabela `adms_evaluation_questions`, 
     * com suporte à paginação e filtros.
     *
     * @param array $criteria Critérios de busca
     * @param int $page Número da página para recuperação (começa do 1).
     * @param int $limitResult Número máximo de resultados por página.
     * @return array Lista de perguntas recuperadas do banco de dados.
     */
    public function getAllQuestions(array $criteria = [], int $page = 1, int $limitResult = 20): array
    {
        // Calcular o registro inicial, função max para garantir valor mínimo 0
        $offset = max(0, ($page - 1) * $limitResult);

        // Inicializa a parte WHERE da consulta
        $whereClauses = [];
        $parameters = [];

        // Verifica se o 'search' foi passado e adiciona à cláusula WHERE
        if (!empty($criteria['search'])) {
            $whereClauses[] = 'eq.pergunta LIKE :search';
            $parameters[':search'] = '%' . $criteria['search'] . '%';
        }

        // Verifica se cada critério de pesquisa adicional foi passado
        if (!empty($criteria['model_id'])) {
            $whereClauses[] = 'eq.evaluation_model_id = :model_id';
            $parameters[':model_id'] = $criteria['model_id'];
        }

        if (!empty($criteria['tipo'])) {
            $whereClauses[] = 'eq.tipo = :tipo';
            $parameters[':tipo'] = $criteria['tipo'];
        }

        // Se houver cláusulas WHERE, junta elas com 'AND'
        $whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

        // Consulta SQL com JOIN para buscar nome do modelo
        $sql = 'SELECT eq.id, eq.evaluation_model_id, eq.pergunta, eq.tipo, eq.opcoes, eq.ordem, eq.created_at, eq.updated_at,
                       em.titulo as model_name
                FROM adms_evaluation_questions eq
                LEFT JOIN adms_evaluation_models em ON eq.evaluation_model_id = em.id
                ' . $whereSql . '
                ORDER BY eq.ordem ASC, eq.created_at DESC
                LIMIT :limit OFFSET :offset';

        // Preparar a consulta
        $stmt = $this->getConnection()->prepare($sql);

        // Bind dos parâmetros
        foreach ($parameters as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limitResult, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        // Executar a consulta
        $stmt->execute();

        // Retornar os resultados
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recuperar a quantidade total de perguntas de avaliação para paginação.
     *
     * Este método retorna a quantidade total de perguntas na tabela `adms_evaluation_questions`, 
     * útil para a paginação.
     *
     * @param array $criteria Critérios de busca
     * @return int Quantidade total de perguntas encontradas no banco de dados.
     */
    public function getAmountQuestions(array $criteria = []): int
    {
        // Inicializa a parte WHERE da consulta
        $whereClauses = [];
        $parameters = [];

        // Verifica se o 'search' foi passado e adiciona à cláusula WHERE
        if (!empty($criteria['search'])) {
            $whereClauses[] = 'pergunta LIKE :search';
            $parameters[':search'] = '%' . $criteria['search'] . '%';
        }

        // Verifica se cada critério de pesquisa adicional foi passado
        if (!empty($criteria['model_id'])) {
            $whereClauses[] = 'evaluation_model_id = :model_id';
            $parameters[':model_id'] = $criteria['model_id'];
        }

        if (!empty($criteria['tipo'])) {
            $whereClauses[] = 'tipo = :tipo';
            $parameters[':tipo'] = $criteria['tipo'];
        }

        // Se houver cláusulas WHERE, junta elas com 'AND'
        $whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

        // QUERY para recuperar a quantidade de registros
        $sql = 'SELECT COUNT(id) as amount_records
                FROM adms_evaluation_questions ' . $whereSql;

        $stmt = $this->getConnection()->prepare($sql);

        // Bind dos parâmetros
        foreach ($parameters as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }

        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records'] ?? 0);
    }

    /**
     * Recuperar uma pergunta de avaliação específica pelo ID.
     *
     * Este método retorna os detalhes de uma pergunta específica identificada pelo ID.
     *
     * @param int $id ID da pergunta a ser recuperada.
     * @return array|bool Detalhes da pergunta recuperada ou `false` se não encontrada.
     */
    public function getQuestion(int $id): array|bool
    {
        // QUERY para recuperar o registro do banco de dados
        $sql = 'SELECT eq.id, eq.evaluation_model_id, eq.pergunta, eq.tipo, eq.opcoes, eq.ordem, eq.created_at, eq.updated_at,
                       em.titulo as model_name
                FROM adms_evaluation_questions eq
                LEFT JOIN adms_evaluation_models em ON eq.evaluation_model_id = em.id
                WHERE eq.id = :id';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        // Executar a QUERY
        $stmt->execute();

        // Ler o registro e retornar
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Recuperar perguntas por modelo de avaliação.
     *
     * @param int $modelId ID do modelo de avaliação
     * @return array Lista de perguntas do modelo
     */
    public function getQuestionsByModel(int $modelId): array
    {
        $sql = 'SELECT id, pergunta, tipo, opcoes, ordem
                FROM adms_evaluation_questions
                WHERE evaluation_model_id = :model_id
                ORDER BY ordem ASC, created_at ASC';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':model_id', $modelId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Criar uma nova pergunta de avaliação.
     *
     * @param array $data Dados da pergunta a ser criada
     * @return bool True se criada com sucesso, false caso contrário
     */
    public function createQuestion(array $data): bool
    {
        try {
            $sql = 'INSERT INTO adms_evaluation_questions (evaluation_model_id, pergunta, tipo, opcoes, ordem, created_at, updated_at) 
                    VALUES (:model_id, :pergunta, :tipo, :opcoes, :ordem, NOW(), NOW())';

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':model_id', $data['model_id'], PDO::PARAM_INT);
            $stmt->bindValue(':pergunta', $data['pergunta'], PDO::PARAM_STR);
            $stmt->bindValue(':tipo', $data['tipo'], PDO::PARAM_STR);
            $stmt->bindValue(':opcoes', $data['opcoes'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':ordem', $data['ordem'] ?? 1, PDO::PARAM_INT);

            $this->result = $stmt->execute();
            return $this->result;
        } catch (Exception $e) {
            GenerateLog::generateLog('ERROR', "Erro ao criar pergunta de avaliação: " . $e->getMessage(), null);
            $this->result = false;
            return false;
        }
    }

    /**
     * Atualizar uma pergunta de avaliação existente.
     *
     * @param int $id ID da pergunta a ser atualizada
     * @param array $data Dados da pergunta a ser atualizada
     * @return bool True se atualizada com sucesso, false caso contrário
     */
    public function updateQuestion(int $id, array $data): bool
    {
        try {
            $sql = 'UPDATE adms_evaluation_questions 
                    SET evaluation_model_id = :model_id, pergunta = :pergunta, tipo = :tipo, 
                        opcoes = :opcoes, ordem = :ordem, updated_at = NOW()
                    WHERE id = :id';

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':model_id', $data['model_id'], PDO::PARAM_INT);
            $stmt->bindValue(':pergunta', $data['pergunta'], PDO::PARAM_STR);
            $stmt->bindValue(':tipo', $data['tipo'], PDO::PARAM_STR);
            $stmt->bindValue(':opcoes', $data['opcoes'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':ordem', $data['ordem'] ?? 1, PDO::PARAM_INT);

            $this->result = $stmt->execute();
            return $this->result;
        } catch (Exception $e) {
            GenerateLog::generateLog('ERROR', "Erro ao atualizar pergunta de avaliação: " . $e->getMessage(), null);
            $this->result = false;
            return false;
        }
    }

    /**
     * Deletar uma pergunta de avaliação.
     *
     * @param int $id ID da pergunta a ser deletada
     * @return bool True se deletada com sucesso, false caso contrário
     */
    public function deleteQuestion(int $id): bool
    {
        try {
            $sql = 'DELETE FROM adms_evaluation_questions WHERE id = :id';

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            $this->result = $stmt->execute();
            return $this->result;
        } catch (Exception $e) {
            GenerateLog::generateLog('ERROR', "Erro ao deletar pergunta de avaliação: " . $e->getMessage(), null);
            $this->result = false;
            return false;
        }
    }

    /**
     * Retornar o resultado da última operação.
     *
     * @return bool Resultado da última operação
     */
    public function getResult(): bool
    {
        return $this->result;
    }
} 