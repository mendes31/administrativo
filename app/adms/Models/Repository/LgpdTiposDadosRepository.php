<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use PDO;
use Exception;

/**
 * Repository responsável por buscar e manipular registros de Tipos de Dados no banco de dados.
 *
 * Esta classe fornece métodos para recuperar, criar, atualizar e deletar registros de Tipos de Dados.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 */
class LgpdTiposDadosRepository extends DbConnection
{
    /**
     * Recuperar todos os registros de Tipos de Dados com paginação e filtros.
     *
     * @param array $filters Filtros opcionais (tipo_dado, status)
     * @param int $page Página atual
     * @param int $limit Quantidade de registros por página
     * @return array Lista de registros de Tipos de Dados
     */
    public function getAll(array $filters = [], int $page = 1, int $limit = 10): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $sql = 'SELECT * FROM lgpd_tipos_dados WHERE 1=1';
        $params = [];
        
        if (!empty($filters['tipo_dado'])) {
            $sql .= ' AND tipo_dado LIKE :tipo_dado';
            $params[':tipo_dado'] = '%' . $filters['tipo_dado'] . '%';
        }
        
        if (!empty($filters['status'])) {
            $sql .= ' AND status = :status';
            $params[':status'] = $filters['status'];
        }
        
        $sql .= ' ORDER BY id DESC LIMIT :limit OFFSET :offset';
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
     * Recuperar a quantidade total de registros de Tipos de Dados para paginação.
     *
     * @param array $filters Filtros opcionais
     * @return int Quantidade total de registros encontrados
     */
    public function getAmountTiposDados(array $filters = []): int
    {
        $sql = 'SELECT COUNT(id) as amount_records FROM lgpd_tipos_dados WHERE 1=1';
        $params = [];
        
        if (!empty($filters['tipo_dado'])) {
            $sql .= ' AND tipo_dado LIKE :tipo_dado';
            $params[':tipo_dado'] = '%' . $filters['tipo_dado'] . '%';
        }
        
        if (!empty($filters['status'])) {
            $sql .= ' AND status = :status';
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
     * Recuperar um registro de Tipo de Dados específico pelo ID.
     *
     * @param int $id ID do registro
     * @return array|bool Registro encontrado ou false
     */
    public function getById(int $id): array|bool
    {
        $sql = 'SELECT * FROM lgpd_tipos_dados WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cadastrar um novo registro de Tipo de Dados.
     *
     * @param array $data Dados do registro
     * @return int|bool ID do registro criado ou false
     */
    public function create(array $data): int|bool
    {
        try {
            $sql = 'INSERT INTO lgpd_tipos_dados (tipo_dado, exemplos, status, created_at, updated_at) VALUES (:tipo_dado, :exemplos, :status, NOW(), NOW())';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':tipo_dado', $data['tipo_dado']);
            $stmt->bindValue(':exemplos', $data['exemplos'] ?? '');
            $stmt->bindValue(':status', $data['status']);
            $stmt->execute();
            
            return $this->getConnection()->lastInsertId();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Tipo de Dados não cadastrado.", ['tipo_dado' => $data['tipo_dado'], 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Atualizar um registro de Tipo de Dados existente.
     *
     * @param array $data Dados atualizados, incluindo o ID
     * @return bool true se atualizado, false se erro
     */
    public function update(array $data): bool
    {
        try {
            $sql = 'UPDATE lgpd_tipos_dados SET tipo_dado = :tipo_dado, exemplos = :exemplos, status = :status, updated_at = NOW() WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':tipo_dado', $data['tipo_dado']);
            $stmt->bindValue(':exemplos', $data['exemplos'] ?? '');
            $stmt->bindValue(':status', $data['status']);
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Tipo de Dados não editado.", ['id' => $data['id'], 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Deletar um registro de Tipo de Dados pelo ID.
     *
     * @param int $id ID do registro
     * @return bool true se deletado, false se erro
     */
    public function delete(int $id): bool
    {
        try {
            $sql = 'DELETE FROM lgpd_tipos_dados WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Tipo de Dados não apagado.", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Recuperar todos os tipos de dados ativos para uso em dropdowns.
     *
     * @return array Lista de tipos de dados ativos
     */
    public function getActiveTiposDados(): array
    {
        $sql = 'SELECT id, tipo_dado FROM lgpd_tipos_dados WHERE status = "Ativo" ORDER BY tipo_dado ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 