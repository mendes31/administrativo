<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use PDO;
use Exception;

/**
 * Repositório para gestão de AIPD (Avaliação de Impacto à Proteção de Dados).
 *
 * Esta classe gerencia as operações de banco de dados relacionadas às AIPDs,
 * trabalhando com grupos de dados para manter consistência com a estrutura do projeto.
 *
 * @package App\adms\Models\Repository
 */
class LgpdAipdRepository extends DbConnection
{
    /**
     * Recupera todas as AIPDs com filtros e paginação.
     *
     * @param array $filters Filtros aplicados
     * @param int $page Página atual
     * @param int $limit Limite por página
     * @return array Lista de AIPDs
     */
    public function getAll(array $filters = [], int $page = 1, int $limit = 10): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $sql = 'SELECT a.*, d.name as departamento_nome, u.name as responsavel_nome,
                       r.atividade as ropa_atividade,
                       COUNT(DISTINCT adg.data_group_id) as total_grupos_dados
                FROM lgpd_aipd a 
                LEFT JOIN adms_departments d ON a.departamento_id = d.id 
                LEFT JOIN adms_users u ON a.responsavel_id = u.id
                LEFT JOIN lgpd_ropa r ON a.ropa_id = r.id
                LEFT JOIN lgpd_aipd_data_groups adg ON a.id = adg.aipd_id
                WHERE 1=1';
        $params = [];
        
        if (!empty($filters['departamento_id'])) {
            $sql .= ' AND a.departamento_id = :departamento_id';
            $params[':departamento_id'] = $filters['departamento_id'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= ' AND a.status = :status';
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['nivel_risco'])) {
            $sql .= ' AND a.nivel_risco = :nivel_risco';
            $params[':nivel_risco'] = $filters['nivel_risco'];
        }
        
        if (!empty($filters['titulo'])) {
            $sql .= ' AND a.titulo LIKE :titulo';
            $params[':titulo'] = '%' . $filters['titulo'] . '%';
        }
        
        $sql .= ' GROUP BY a.id ORDER BY a.created_at DESC LIMIT :limit OFFSET :offset';
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
     * Conta o total de AIPDs com filtros.
     *
     * @param array $filters Filtros aplicados
     * @return int Total de registros
     */
    public function getAmountAipd(array $filters = []): int
    {
        $sql = 'SELECT COUNT(DISTINCT a.id) as total FROM lgpd_aipd a WHERE 1=1';
        $params = [];
        
        if (!empty($filters['departamento_id'])) {
            $sql .= ' AND a.departamento_id = :departamento_id';
            $params[':departamento_id'] = $filters['departamento_id'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= ' AND a.status = :status';
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['nivel_risco'])) {
            $sql .= ' AND a.nivel_risco = :nivel_risco';
            $params[':nivel_risco'] = $filters['nivel_risco'];
        }
        
        if (!empty($filters['titulo'])) {
            $sql .= ' AND a.titulo LIKE :titulo';
            $params[':titulo'] = '%' . $filters['titulo'] . '%';
        }
        
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Recupera uma AIPD específica por ID.
     *
     * @param int $id ID da AIPD
     * @return array|null Dados da AIPD ou null se não encontrada
     */
    public function getAipdById(int $id): ?array
    {
        $sql = 'SELECT a.*, d.name as departamento_nome, u.name as responsavel_nome,
                       r.atividade as ropa_atividade, r.codigo as ropa_codigo
                FROM lgpd_aipd a 
                LEFT JOIN adms_departments d ON a.departamento_id = d.id 
                LEFT JOIN adms_users u ON a.responsavel_id = u.id
                LEFT JOIN lgpd_ropa r ON a.ropa_id = r.id
                WHERE a.id = :id';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Recupera os grupos de dados associados a uma AIPD.
     *
     * @param int $aipdId ID da AIPD
     * @return array Lista de grupos de dados associados
     */
    public function getDataGroupsByAipdId(int $aipdId): array
    {
        $sql = 'SELECT dg.id, dg.name, dg.category as default_category, dg.is_sensitive, dg.example_fields,
                       adg.impacto_privacidade, adg.probabilidade_ocorrencia, adg.medidas_mitigacao, adg.observacoes
                FROM lgpd_data_groups dg
                INNER JOIN lgpd_aipd_data_groups adg ON dg.id = adg.data_group_id
                WHERE adg.aipd_id = :aipd_id
                ORDER BY dg.name';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':aipd_id', $aipdId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cria uma nova AIPD.
     *
     * @param array $data Dados da AIPD
     * @return bool True se criada com sucesso
     */
    public function create(array $data): bool
    {
        try {
            $this->getConnection()->beginTransaction();
            
            // Gerar código único
            $data['codigo'] = $this->generateUniqueCode('AIPD');
            
            $sql = 'INSERT INTO lgpd_aipd (codigo, titulo, descricao, ropa_id, departamento_id, responsavel_id, 
                                          data_inicio, data_conclusao, nivel_risco, necessita_anpd, observacoes, created_at) 
                    VALUES (:codigo, :titulo, :descricao, :ropa_id, :departamento_id, :responsavel_id, 
                           :data_inicio, :data_conclusao, :nivel_risco, :necessita_anpd, :observacoes, NOW())';
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':codigo', $data['codigo']);
            $stmt->bindValue(':titulo', $data['titulo']);
            $stmt->bindValue(':descricao', $data['descricao'] ?? null);
            $stmt->bindValue(':ropa_id', $data['ropa_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':departamento_id', $data['departamento_id'], PDO::PARAM_INT);
            $stmt->bindValue(':responsavel_id', $data['responsavel_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':data_inicio', $data['data_inicio']);
            $stmt->bindValue(':data_conclusao', $data['data_conclusao'] ?? null);
            $stmt->bindValue(':nivel_risco', $data['nivel_risco'] ?? 'Médio');
            $stmt->bindValue(':necessita_anpd', $data['necessita_anpd'] ?? false, PDO::PARAM_BOOL);
            $stmt->bindValue(':observacoes', $data['observacoes'] ?? null);
            
            $stmt->execute();
            $aipdId = $this->getConnection()->lastInsertId();
            
            // Associar grupos de dados se fornecidos
            if (!empty($data['data_groups']) && is_array($data['data_groups'])) {
                $this->associateDataGroups($aipdId, $data['data_groups']);
            }
            
            $this->getConnection()->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->getConnection()->rollBack();
            return false;
        }
    }

    /**
     * Atualiza uma AIPD existente.
     *
     * @param int $id ID da AIPD
     * @param array $data Dados atualizados
     * @return bool True se atualizada com sucesso
     */
    public function update(int $id, array $data): bool
    {
        try {
            $this->getConnection()->beginTransaction();
            
            $sql = 'UPDATE lgpd_aipd SET titulo = :titulo, descricao = :descricao, ropa_id = :ropa_id, 
                                          departamento_id = :departamento_id, responsavel_id = :responsavel_id, 
                                          data_inicio = :data_inicio, data_conclusao = :data_conclusao, 
                                          nivel_risco = :nivel_risco, necessita_anpd = :necessita_anpd, 
                                          observacoes = :observacoes, updated_at = NOW() 
                    WHERE id = :id';
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':titulo', $data['titulo']);
            $stmt->bindValue(':descricao', $data['descricao'] ?? null);
            $stmt->bindValue(':ropa_id', $data['ropa_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':departamento_id', $data['departamento_id'], PDO::PARAM_INT);
            $stmt->bindValue(':responsavel_id', $data['responsavel_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':data_inicio', $data['data_inicio']);
            $stmt->bindValue(':data_conclusao', $data['data_conclusao'] ?? null);
            $stmt->bindValue(':nivel_risco', $data['nivel_risco'] ?? 'Médio');
            $stmt->bindValue(':necessita_anpd', $data['necessita_anpd'] ?? false, PDO::PARAM_BOOL);
            $stmt->bindValue(':observacoes', $data['observacoes'] ?? null);
            
            $stmt->execute();
            
            // Atualizar grupos de dados
            $this->updateDataGroups($id, $data['data_groups'] ?? []);
            
            $this->getConnection()->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->getConnection()->rollBack();
            return false;
        }
    }

    /**
     * Exclui uma AIPD.
     *
     * @param int $id ID da AIPD
     * @return bool True se excluída com sucesso
     */
    public function delete(int $id): bool
    {
        try {
            $this->getConnection()->beginTransaction();
            
            // Excluir associações com grupos de dados
            $sql = 'DELETE FROM lgpd_aipd_data_groups WHERE aipd_id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Excluir AIPD
            $sql = 'DELETE FROM lgpd_aipd WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $this->getConnection()->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->getConnection()->rollBack();
            return false;
        }
    }

    /**
     * Associa grupos de dados a uma AIPD.
     *
     * @param int $aipdId ID da AIPD
     * @param array $dataGroups Dados dos grupos
     * @return void
     */
    private function associateDataGroups(int $aipdId, array $dataGroups): void
    {
        $sql = 'INSERT INTO lgpd_aipd_data_groups (aipd_id, data_group_id, impacto_privacidade, 
                                                   probabilidade_ocorrencia, medidas_mitigacao, observacoes, created_at) 
                VALUES (:aipd_id, :data_group_id, :impacto_privacidade, :probabilidade_ocorrencia, 
                       :medidas_mitigacao, :observacoes, NOW())';
        
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($dataGroups as $group) {
            $stmt->bindValue(':aipd_id', $aipdId, PDO::PARAM_INT);
            $stmt->bindValue(':data_group_id', $group['data_group_id'], PDO::PARAM_INT);
            $stmt->bindValue(':impacto_privacidade', $group['impacto_privacidade']);
            $stmt->bindValue(':probabilidade_ocorrencia', $group['probabilidade_ocorrencia']);
            $stmt->bindValue(':medidas_mitigacao', $group['medidas_mitigacao'] ?? null);
            $stmt->bindValue(':observacoes', $group['observacoes'] ?? null);
            $stmt->execute();
        }
    }

    /**
     * Atualiza os grupos de dados de uma AIPD.
     *
     * @param int $aipdId ID da AIPD
     * @param array $dataGroups Novos dados dos grupos
     * @return void
     */
    private function updateDataGroups(int $aipdId, array $dataGroups): void
    {
        // Remover associações existentes
        $sql = 'DELETE FROM lgpd_aipd_data_groups WHERE aipd_id = :aipd_id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':aipd_id', $aipdId, PDO::PARAM_INT);
        $stmt->execute();
        
        // Adicionar novas associações
        if (!empty($dataGroups)) {
            $this->associateDataGroups($aipdId, $dataGroups);
        }
    }

    /**
     * Gera um código único para AIPD.
     *
     * @param string $prefix Prefixo do código
     * @return string Código único
     */
    private function generateUniqueCode(string $prefix): string
    {
        $sql = 'SELECT COUNT(*) as count FROM lgpd_aipd WHERE codigo LIKE :prefix';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':prefix', $prefix . '-%');
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = (int) ($result['count'] ?? 0);
        
        return $prefix . '-' . str_pad(($count + 1), 3, '0', STR_PAD_LEFT);
    }
}
