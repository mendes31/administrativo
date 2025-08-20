<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use PDO;
use Exception;

/**
 * Repository responsável por buscar e manipular registros de Categorias de Titulares no banco de dados.
 *
 * Esta classe fornece métodos para recuperar, criar, atualizar e deletar registros de Categorias de Titulares.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 */
class LgpdCategoriasTitularesRepository extends DbConnection
{
    /**
     * Recuperar todos os registros de Categorias de Titulares com paginação e filtros.
     *
     * @param array $filters Filtros opcionais (titular, status)
     * @param int $page Página atual
     * @param int $limit Quantidade de registros por página
     * @return array Lista de registros de Categorias de Titulares
     */
    public function getAll(array $filters = [], int $page = 1, int $limit = 10): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $sql = 'SELECT * FROM lgpd_categorias_titulares WHERE 1=1';
        $params = [];
        
        if (!empty($filters['titular'])) {
            $sql .= ' AND titular LIKE :titular';
            $params[':titular'] = '%' . $filters['titular'] . '%';
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
     * Recuperar a quantidade total de registros de Categorias de Titulares para paginação.
     *
     * @param array $filters Filtros opcionais
     * @return int Quantidade total de registros encontrados
     */
    public function getAmountCategoriasTitulares(array $filters = []): int
    {
        $sql = 'SELECT COUNT(id) as amount_records FROM lgpd_categorias_titulares WHERE 1=1';
        $params = [];
        
        if (!empty($filters['titular'])) {
            $sql .= ' AND titular LIKE :titular';
            $params[':titular'] = '%' . $filters['titular'] . '%';
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
     * Recuperar um registro de Categoria de Titulares específico pelo ID.
     *
     * @param int $id ID do registro
     * @return array|bool Registro encontrado ou false
     */
    public function getById(int $id): array|bool
    {
        $sql = 'SELECT * FROM lgpd_categorias_titulares WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cadastrar um novo registro de Categoria de Titulares.
     *
     * @param array $data Dados do registro
     * @return int|bool ID do registro criado ou false
     */
    public function create(array $data): int|bool
    {
        try {
            $sql = 'INSERT INTO lgpd_categorias_titulares (titular, exemplo, status, created_at, updated_at) VALUES (:titular, :exemplo, :status, NOW(), NOW())';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':titular', $data['titular']);
            $stmt->bindValue(':exemplo', $data['exemplo'] ?? null);
            $stmt->bindValue(':status', $data['status']);
            $stmt->execute();
            
            return $this->getConnection()->lastInsertId();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Categoria de Titulares não cadastrada.", ['titular' => $data['titular'], 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Atualizar um registro de Categoria de Titulares existente.
     *
     * @param array $data Dados atualizados, incluindo o ID
     * @return bool true se atualizado, false se erro
     */
    public function update(array $data): bool
    {
        try {
            $sql = 'UPDATE lgpd_categorias_titulares SET titular = :titular, exemplo = :exemplo, status = :status, updated_at = NOW() WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':titular', $data['titular']);
            $stmt->bindValue(':exemplo', $data['exemplo'] ?? null);
            $stmt->bindValue(':status', $data['status']);
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Categoria de Titulares não editada.", ['id' => $data['id'], 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Deletar um registro de Categoria de Titulares pelo ID.
     *
     * @param int $id ID do registro
     * @return bool true se deletado, false se erro
     */
    public function delete(int $id): bool
    {
        try {
            $sql = 'DELETE FROM lgpd_categorias_titulares WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Categoria de Titulares não apagada.", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Recuperar todas as categorias de titulares ativas para uso em dropdowns.
     *
     * @return array Lista de categorias de titulares ativas
     */
    public function getActiveCategoriasTitulares(): array
    {
        $sql = 'SELECT id, titular FROM lgpd_categorias_titulares WHERE status = "Ativo" ORDER BY titular ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 