<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use PDO;
use Exception;

/**
 * Repository responsável por buscar e manipular registros de Classificações de Dados no banco de dados.
 *
 * Esta classe fornece métodos para recuperar, criar, atualizar e deletar registros de Classificações de Dados.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 */
class LgpdClassificacoesDadosRepository extends DbConnection
{
    /**
     * Recuperar todos os registros de Classificações de Dados com paginação e filtros.
     *
     * @param array $filters Filtros opcionais (classificacao, base_legal_id, status)
     * @param int $page Página atual
     * @param int $limit Quantidade de registros por página
     * @return array Lista de registros de Classificações de Dados
     */
    public function getAll(array $filters = [], int $page = 1, int $limit = 10): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $sql = 'SELECT cd.*, bl.base_legal as base_legal_nome FROM lgpd_classificacoes_dados cd 
                INNER JOIN lgpd_bases_legais bl ON cd.base_legal_id = bl.id 
                WHERE 1=1';
        $params = [];
        
        if (!empty($filters['classificacao'])) {
            $sql .= ' AND cd.classificacao LIKE :classificacao';
            $params[':classificacao'] = '%' . $filters['classificacao'] . '%';
        }
        
        if (!empty($filters['base_legal_id'])) {
            $sql .= ' AND cd.base_legal_id = :base_legal_id';
            $params[':base_legal_id'] = $filters['base_legal_id'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= ' AND cd.status = :status';
            $params[':status'] = $filters['status'];
        }
        
        $sql .= ' ORDER BY cd.id DESC LIMIT :limit OFFSET :offset';
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recuperar a quantidade total de registros de Classificações de Dados para paginação.
     *
     * @param array $filters Filtros opcionais
     * @return int Quantidade total de registros encontrados
     */
    public function getAmountClassificacoesDados(array $filters = []): int
    {
        $sql = 'SELECT COUNT(cd.id) as amount_records FROM lgpd_classificacoes_dados cd 
                INNER JOIN lgpd_bases_legais bl ON cd.base_legal_id = bl.id 
                WHERE 1=1';
        $params = [];
        
        if (!empty($filters['classificacao'])) {
            $sql .= ' AND cd.classificacao LIKE :classificacao';
            $params[':classificacao'] = '%' . $filters['classificacao'] . '%';
        }
        
        if (!empty($filters['base_legal_id'])) {
            $sql .= ' AND cd.base_legal_id = :base_legal_id';
            $params[':base_legal_id'] = $filters['base_legal_id'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= ' AND cd.status = :status';
            $params[':status'] = $filters['status'];
        }
        
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records'] ?? 0);
    }

    /**
     * Recuperar um registro de Classificação de Dados específico pelo ID.
     *
     * @param int $id ID do registro
     * @return array|bool Registro encontrado ou false
     */
    public function getById(int $id): array|bool
    {
        $sql = 'SELECT cd.*, bl.base_legal as base_legal_nome FROM lgpd_classificacoes_dados cd 
                INNER JOIN lgpd_bases_legais bl ON cd.base_legal_id = bl.id 
                WHERE cd.id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cadastrar um novo registro de Classificação de Dados.
     *
     * @param array $data Dados do registro
     * @return int|bool ID do registro criado ou false
     */
    public function create(array $data): int|bool
    {
        try {
            $sql = 'INSERT INTO lgpd_classificacoes_dados (classificacao, exemplos, base_legal_id, status, created_at, updated_at) VALUES (:classificacao, :exemplos, :base_legal_id, :status, NOW(), NOW())';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':classificacao', $data['classificacao']);
            $stmt->bindValue(':exemplos', $data['exemplos'] ?? null);
            $stmt->bindValue(':base_legal_id', $data['base_legal_id'], PDO::PARAM_INT);
            $stmt->bindValue(':status', $data['status']);
            $stmt->execute();
            
            return $this->getConnection()->lastInsertId();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Classificação de Dados não cadastrada.", ['classificacao' => $data['classificacao'], 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Atualizar um registro de Classificação de Dados existente.
     *
     * @param array $data Dados atualizados, incluindo o ID
     * @return bool true se atualizado, false se erro
     */
    public function update(array $data): bool
    {
        try {
            $sql = 'UPDATE lgpd_classificacoes_dados SET classificacao = :classificacao, exemplos = :exemplos, base_legal_id = :base_legal_id, status = :status, updated_at = NOW() WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':classificacao', $data['classificacao']);
            $stmt->bindValue(':exemplos', $data['exemplos'] ?? null);
            $stmt->bindValue(':base_legal_id', $data['base_legal_id'], PDO::PARAM_INT);
            $stmt->bindValue(':status', $data['status']);
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Classificação de Dados não editada.", ['id' => $data['id'], 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Deletar um registro de Classificação de Dados pelo ID.
     *
     * @param int $id ID do registro
     * @return bool true se deletado, false se erro
     */
    public function delete(int $id): bool
    {
        try {
            $sql = 'DELETE FROM lgpd_classificacoes_dados WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Classificação de Dados não apagada.", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Recuperar todas as classificações de dados ativas para uso em dropdowns.
     *
     * @return array Lista de classificações de dados ativas
     */
    public function getActiveClassificacoesDados(): array
    {
        $sql = 'SELECT cd.id, cd.classificacao, bl.base_legal as base_legal_nome 
                FROM lgpd_classificacoes_dados cd 
                INNER JOIN lgpd_bases_legais bl ON cd.base_legal_id = bl.id 
                WHERE cd.status = "Ativo" 
                ORDER BY cd.classificacao ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 