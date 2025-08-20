<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use PDO;
use Exception;

/**
 * Repository responsável por buscar e manipular registros de Bases Legais no banco de dados.
 *
 * Esta classe fornece métodos para recuperar, criar, atualizar e deletar registros de Bases Legais.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 */
class LgpdBasesLegaisRepository extends DbConnection
{
    /**
     * Recuperar todos os registros de Bases Legais com paginação e filtros.
     *
     * @param array $filters Filtros opcionais (base_legal, status)
     * @param int $page Página atual
     * @param int $limit Quantidade de registros por página
     * @return array Lista de registros de Bases Legais
     */
    public function getAll(array $filters = [], int $page = 1, int $limit = 10): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $sql = 'SELECT * FROM lgpd_bases_legais WHERE 1=1';
        $params = [];
        
        if (!empty($filters['base_legal'])) {
            $sql .= ' AND base_legal LIKE :base_legal';
            $params[':base_legal'] = '%' . $filters['base_legal'] . '%';
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
     * Recuperar a quantidade total de registros de Bases Legais para paginação.
     *
     * @param array $filters Filtros opcionais
     * @return int Quantidade total de registros encontrados
     */
    public function getAmountBasesLegais(array $filters = []): int
    {
        $sql = 'SELECT COUNT(id) as amount_records FROM lgpd_bases_legais WHERE 1=1';
        $params = [];
        
        if (!empty($filters['base_legal'])) {
            $sql .= ' AND base_legal LIKE :base_legal';
            $params[':base_legal'] = '%' . $filters['base_legal'] . '%';
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
     * Recuperar um registro de Base Legal específico pelo ID.
     *
     * @param int $id ID do registro
     * @return array|bool Registro encontrado ou false
     */
    public function getById(int $id): array|bool
    {
        $sql = 'SELECT * FROM lgpd_bases_legais WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cadastrar um novo registro de Base Legal.
     *
     * @param array $data Dados do registro
     * @return int|bool ID do registro criado ou false
     */
    public function create(array $data): int|bool
    {
        try {
            $sql = 'INSERT INTO lgpd_bases_legais (base_legal, descricao, exemplo, status, created_at, updated_at) VALUES (:base_legal, :descricao, :exemplo, :status, NOW(), NOW())';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':base_legal', $data['base_legal']);
            $stmt->bindValue(':descricao', $data['descricao'] ?? null);
            $stmt->bindValue(':exemplo', $data['exemplo'] ?? null);
            $stmt->bindValue(':status', $data['status']);
            $stmt->execute();
            
            return $this->getConnection()->lastInsertId();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Base Legal não cadastrada.", ['base_legal' => $data['base_legal'], 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Atualizar um registro de Base Legal existente.
     *
     * @param array $data Dados atualizados, incluindo o ID
     * @return bool true se atualizado, false se erro
     */
    public function update(array $data): bool
    {
        try {
            $sql = 'UPDATE lgpd_bases_legais SET base_legal = :base_legal, descricao = :descricao, exemplo = :exemplo, status = :status, updated_at = NOW() WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':base_legal', $data['base_legal']);
            $stmt->bindValue(':descricao', $data['descricao'] ?? null);
            $stmt->bindValue(':exemplo', $data['exemplo'] ?? null);
            $stmt->bindValue(':status', $data['status']);
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Base Legal não editada.", ['id' => $data['id'], 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Deletar um registro de Base Legal pelo ID.
     *
     * @param int $id ID do registro
     * @return bool true se deletado, false se erro
     */
    public function delete(int $id): bool
    {
        try {
            $sql = 'DELETE FROM lgpd_bases_legais WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Base Legal não apagada.", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Recuperar todas as bases legais ativas para uso em dropdowns.
     *
     * @return array Lista de bases legais ativas
     */
    public function getActiveBasesLegais(): array
    {
        $sql = 'SELECT id, base_legal FROM lgpd_bases_legais WHERE status = "Ativo" ORDER BY base_legal ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 