<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class InformativosRepository extends DbConnection
{
    /**
     * Listar informativos com paginação e filtros
     * @param int $page
     * @param int $perPage
     * @param array $filters
     * @return array
     */
    public function getAllInformativos(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        // Garantir que a página seja sempre pelo menos 1
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        
        $whereConditions = [];
        $params = [];
        
        if (!empty($filters['categoria'])) {
            $whereConditions[] = 'i.categoria = :categoria';
            $params[':categoria'] = $filters['categoria'];
        }
        
        if (isset($filters['ativo']) && $filters['ativo'] !== '') {
            $whereConditions[] = 'i.ativo = :ativo';
            $params[':ativo'] = $filters['ativo'];
        }
        
        if (!empty($filters['urgente'])) {
            $whereConditions[] = 'i.urgente = :urgente';
            $params[':urgente'] = $filters['urgente'];
        }
        
        if (!empty($filters['data_inicio'])) {
            $whereConditions[] = 'DATE(i.created_at) >= :data_inicio';
            $params[':data_inicio'] = $filters['data_inicio'];
        }
        
        if (!empty($filters['data_fim'])) {
            $whereConditions[] = 'DATE(i.created_at) <= :data_fim';
            $params[':data_fim'] = $filters['data_fim'];
        }
        
        if (!empty($filters['busca'])) {
            $whereConditions[] = '(i.titulo LIKE :busca OR i.conteudo LIKE :busca OR i.resumo LIKE :busca)';
            $params[':busca'] = '%' . $filters['busca'] . '%';
        }
        
        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }
        
        $sql = "SELECT i.*, u.name as usuario_nome
                FROM adms_informativos i
                LEFT JOIN adms_users u ON i.usuario_id = u.id
                {$whereClause}
                ORDER BY i.urgente DESC, i.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Contar total de informativos com filtros
     * @param array $filters
     * @return int
     */
    public function getTotalInformativos(array $filters = []): int
    {
        $whereConditions = [];
        $params = [];
        
        if (!empty($filters['categoria'])) {
            $whereConditions[] = 'categoria = :categoria';
            $params[':categoria'] = $filters['categoria'];
        }
        
        if (isset($filters['ativo']) && $filters['ativo'] !== '') {
            $whereConditions[] = 'ativo = :ativo';
            $params[':ativo'] = $filters['ativo'];
        }
        
        if (!empty($filters['urgente'])) {
            $whereConditions[] = 'urgente = :urgente';
            $params[':urgente'] = $filters['urgente'];
        }
        
        if (!empty($filters['data_inicio'])) {
            $whereConditions[] = 'DATE(created_at) >= :data_inicio';
            $params[':data_inicio'] = $filters['data_inicio'];
        }
        
        if (!empty($filters['data_fim'])) {
            $whereConditions[] = 'DATE(created_at) <= :data_fim';
            $params[':data_fim'] = $filters['data_fim'];
        }
        
        if (!empty($filters['busca'])) {
            $whereConditions[] = '(titulo LIKE :busca OR conteudo LIKE :busca OR resumo LIKE :busca)';
            $params[':busca'] = '%' . $filters['busca'] . '%';
        }
        
        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }
        
        $sql = "SELECT COUNT(*) as total FROM adms_informativos {$whereClause}";
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int) $result['total'];
    }
    
    /**
     * Buscar informativo por ID
     * @param int $id
     * @return array|null
     */
    public function getInformativoById(int $id): ?array
    {
        $sql = "SELECT i.*, u.name as usuario_nome
                FROM adms_informativos i
                LEFT JOIN adms_users u ON i.usuario_id = u.id
                WHERE i.id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    /**
     * Criar novo informativo
     * @param array $data
     * @return int
     */
    public function createInformativo(array $data): int
    {
        $sql = "INSERT INTO adms_informativos (titulo, conteudo, resumo, categoria, imagem, anexo, urgente, ativo, usuario_id, created_at, updated_at)
                VALUES (:titulo, :conteudo, :resumo, :categoria, :imagem, :anexo, :urgente, :ativo, :usuario_id, NOW(), NOW())";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':titulo', $data['titulo'], PDO::PARAM_STR);
        $stmt->bindValue(':conteudo', $data['conteudo'], PDO::PARAM_STR);
        $stmt->bindValue(':resumo', $data['resumo'], PDO::PARAM_STR);
        $stmt->bindValue(':categoria', $data['categoria'], PDO::PARAM_STR);
        $stmt->bindValue(':imagem', $data['imagem'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':anexo', $data['anexo'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':urgente', $data['urgente'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindValue(':ativo', $data['ativo'] ?? true, PDO::PARAM_BOOL);
        $stmt->bindValue(':usuario_id', $data['usuario_id'], PDO::PARAM_INT);
        
        $stmt->execute();
        
        return (int) $this->getConnection()->lastInsertId();
    }
    
    /**
     * Atualizar informativo
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateInformativo(int $id, array $data): bool
    {
        $sql = "UPDATE adms_informativos 
                SET titulo = :titulo, conteudo = :conteudo, resumo = :resumo, categoria = :categoria, 
                    imagem = :imagem, anexo = :anexo, urgente = :urgente, ativo = :ativo, updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':titulo', $data['titulo'], PDO::PARAM_STR);
        $stmt->bindValue(':conteudo', $data['conteudo'], PDO::PARAM_STR);
        $stmt->bindValue(':resumo', $data['resumo'], PDO::PARAM_STR);
        $stmt->bindValue(':categoria', $data['categoria'], PDO::PARAM_STR);
        $stmt->bindValue(':imagem', $data['imagem'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':anexo', $data['anexo'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':urgente', $data['urgente'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindValue(':ativo', $data['ativo'] ?? true, PDO::PARAM_BOOL);
        
        return $stmt->execute();
    }
    
    /**
     * Excluir informativo
     * @param int $id
     * @return bool
     */
    public function deleteInformativo(int $id): bool
    {
        $sql = "DELETE FROM adms_informativos WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Listar categorias disponíveis
     * @return array
     */
    public function getCategorias(): array
    {
        $sql = "SELECT DISTINCT categoria FROM adms_informativos ORDER BY categoria";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        
        $categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Adicionar categorias padrão se não existirem
        $categoriasPadrao = ['Geral', 'RH', 'Financeiro', 'TI', 'Produção', 'Qualidade', 'Segurança'];
        $categorias = array_merge($categoriasPadrao, $categorias);
        $categorias = array_unique($categorias);
        sort($categorias);
        
        return $categorias;
    }
    
    /**
     * Listar informativos para o dashboard (mais recentes e ativos)
     * @param int $limit
     * @param string|null $categoria
     * @return array
     */
    public function getInformativosDashboard(int $limit = 5, ?string $categoria = null): array
    {
        $whereClause = 'WHERE i.ativo = 1';
        $params = [];
        
        if ($categoria) {
            $whereClause .= ' AND i.categoria = :categoria';
            $params[':categoria'] = $categoria;
        }
        
        $sql = "SELECT i.*, u.name as usuario_nome
                FROM adms_informativos i
                LEFT JOIN adms_users u ON i.usuario_id = u.id
                {$whereClause}
                ORDER BY i.urgente DESC, i.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Contar informativos urgentes ativos
     * @return int
     */
    public function countInformativosUrgentes(): int
    {
        $sql = "SELECT COUNT(*) as total FROM adms_informativos WHERE urgente = 1 AND ativo = 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['total'];
    }
} 