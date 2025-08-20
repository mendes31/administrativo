<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

/**
 * Repository responsável em buscar e manipular modelos de avaliação no banco de dados.
 *
 * Esta classe fornece métodos para recuperar, criar, atualizar e deletar modelos de avaliação no banco de dados.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class EvaluationModelsRepository extends DbConnection
{
    /** @var bool $result Resultado da última operação */
    private bool $result = false;

    /**
     * Recuperar todos os modelos de avaliação com paginação e filtros.
     *
     * Este método retorna uma lista de modelos de avaliação da tabela `adms_evaluation_models`, 
     * com suporte à paginação e filtros.
     *
     * @param array $criteria Critérios de busca
     * @param int $page Número da página para recuperação (começa do 1).
     * @param int $limitResult Número máximo de resultados por página.
     * @return array Lista de modelos recuperados do banco de dados.
     */
    public function getAllModels(array $criteria = [], int $page = 1, int $limitResult = 20): array
    {
        // Calcular o registro inicial, função max para garantir valor mínimo 0
        $offset = max(0, ($page - 1) * $limitResult);

        // Inicializa a parte WHERE da consulta
        $whereClauses = [];
        $parameters = [];

        // Verifica se o 'search' foi passado e adiciona à cláusula WHERE
        if (!empty($criteria['search'])) {
            $whereClauses[] = 'em.titulo LIKE :search OR em.descricao LIKE :search';
            $parameters[':search'] = '%' . $criteria['search'] . '%';
        }

        // Verifica se cada critério de pesquisa adicional foi passado
        if (!empty($criteria['training_id'])) {
            $whereClauses[] = 'em.adms_training_id = :training_id';
            $parameters[':training_id'] = $criteria['training_id'];
        }

        if (isset($criteria['ativo'])) {
            $whereClauses[] = 'em.ativo = :ativo';
            $parameters[':ativo'] = (int) $criteria['ativo'];
        }

        // Se houver cláusulas WHERE, junta elas com 'AND'
        $whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

        // Consulta SQL com JOIN para buscar nome do treinamento
        $sql = 'SELECT em.id, em.titulo, em.descricao, em.ativo, em.created_at, em.updated_at,
                       at.nome as training_name
                FROM adms_evaluation_models em
                LEFT JOIN adms_trainings at ON em.adms_training_id = at.id
                ' . $whereSql . '
                ORDER BY em.created_at DESC
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
     * Recuperar a quantidade total de modelos de avaliação para paginação.
     *
     * Este método retorna a quantidade total de modelos na tabela `adms_evaluation_models`, 
     * útil para a paginação.
     *
     * @param array $criteria Critérios de busca
     * @return int Quantidade total de modelos encontrados no banco de dados.
     */
    public function getAmountModels(array $criteria = []): int
    {
        // Inicializa a parte WHERE da consulta
        $whereClauses = [];
        $parameters = [];

        // Verifica se o 'search' foi passado e adiciona à cláusula WHERE
        if (!empty($criteria['search'])) {
            $whereClauses[] = 'titulo LIKE :search OR descricao LIKE :search';
            $parameters[':search'] = '%' . $criteria['search'] . '%';
        }

        // Verifica se cada critério de pesquisa adicional foi passado
        if (!empty($criteria['training_id'])) {
            $whereClauses[] = 'adms_training_id = :training_id';
            $parameters[':training_id'] = $criteria['training_id'];
        }

        if (isset($criteria['ativo'])) {
            $whereClauses[] = 'ativo = :ativo';
            $parameters[':ativo'] = (int) $criteria['ativo'];
        }

        // Se houver cláusulas WHERE, junta elas com 'AND'
        $whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

        // QUERY para recuperar a quantidade de registros
        $sql = 'SELECT COUNT(id) as amount_records
                FROM adms_evaluation_models ' . $whereSql;

        $stmt = $this->getConnection()->prepare($sql);

        // Bind dos parâmetros
        foreach ($parameters as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }

        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records'] ?? 0);
    }

    /**
     * Recuperar um modelo de avaliação específico pelo ID.
     *
     * Este método retorna os detalhes de um modelo específico identificado pelo ID.
     *
     * @param int $id ID do modelo a ser recuperado.
     * @return array|bool Detalhes do modelo recuperado ou `false` se não encontrado.
     */
    public function getModel(int $id): array|bool
    {
        // QUERY para recuperar o registro do banco de dados
        $sql = 'SELECT em.id, em.adms_training_id, em.titulo, em.descricao, em.ativo, em.created_at, em.updated_at,
                       at.nome as training_name
                FROM adms_evaluation_models em
                LEFT JOIN adms_trainings at ON em.adms_training_id = at.id
                WHERE em.id = :id';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        // Executar a QUERY
        $stmt->execute();

        // Ler o registro e retornar
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Criar um novo modelo de avaliação.
     *
     * @param array $data Dados do modelo a ser criado
     * @return bool True se criado com sucesso, false caso contrário
     */
    public function createModel(array $data): bool
    {
        try {
            $sql = 'INSERT INTO adms_evaluation_models (adms_training_id, titulo, descricao, ativo, created_at, updated_at) 
                    VALUES (:training_id, :titulo, :descricao, :ativo, NOW(), NOW())';

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':training_id', $data['training_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':titulo', $data['titulo'], PDO::PARAM_STR);
            $stmt->bindValue(':descricao', $data['descricao'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':ativo', $data['ativo'] ?? 1, PDO::PARAM_INT);

            $this->result = $stmt->execute();
            return $this->result;
        } catch (Exception $e) {
            GenerateLog::generateLog('ERROR', "Erro ao criar modelo de avaliação: " . $e->getMessage(), null);
            $this->result = false;
            return false;
        }
    }

    /**
     * Atualizar um modelo de avaliação existente.
     *
     * @param int $id ID do modelo a ser atualizado
     * @param array $data Dados do modelo a ser atualizado
     * @return bool True se atualizado com sucesso, false caso contrário
     */
    public function updateModel(int $id, array $data): bool
    {
        try {
            $sql = 'UPDATE adms_evaluation_models 
                    SET adms_training_id = :training_id, titulo = :titulo, descricao = :descricao, 
                        ativo = :ativo, updated_at = NOW()
                    WHERE id = :id';

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':training_id', $data['training_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':titulo', $data['titulo'], PDO::PARAM_STR);
            $stmt->bindValue(':descricao', $data['descricao'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':ativo', $data['ativo'] ?? 1, PDO::PARAM_INT);

            $this->result = $stmt->execute();
            return $this->result;
        } catch (Exception $e) {
            GenerateLog::generateLog('ERROR', "Erro ao atualizar modelo de avaliação: " . $e->getMessage(), null);
            $this->result = false;
            return false;
        }
    }

    /**
     * Deletar um modelo de avaliação.
     *
     * @param int $id ID do modelo a ser deletado
     * @return bool True se deletado com sucesso, false caso contrário
     */
    public function deleteModel(int $id): bool
    {
        try {
            $sql = 'DELETE FROM adms_evaluation_models WHERE id = :id';

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            $this->result = $stmt->execute();
            return $this->result;
        } catch (Exception $e) {
            GenerateLog::generateLog('ERROR', "Erro ao deletar modelo de avaliação: " . $e->getMessage(), null);
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