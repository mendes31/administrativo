<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use PDO;
use Exception;

/**
 * Repository responsável por buscar e manipular registros ROPA no banco de dados.
 *
 * Esta classe fornece métodos para recuperar, criar, atualizar e deletar registros ROPA.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 */
class LgpdRopaRepository extends DbConnection
{
    /**
     * Recuperar todos os registros ROPA com paginação e filtros.
     *
     * @param array $filters Filtros opcionais (departamento_id, status, atividade, processing_purpose, data_subject)
     * @param int $page Página atual
     * @param int $limit Quantidade de registros por página
     * @return array Lista de registros ROPA
     */
    public function getAll(array $filters = [], int $page = 1, int $limit = 10): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $sql = 'SELECT r.*, d.name as departamento_nome, i.area as inventory_area 
                FROM lgpd_ropa r 
                INNER JOIN adms_departments d ON r.departamento_id = d.id 
                LEFT JOIN lgpd_inventory i ON r.inventory_id = i.id 
                WHERE 1=1';
        $params = [];
        
        if (!empty($filters['departamento_id'])) {
            $sql .= ' AND r.departamento_id = :departamento_id';
            $params[':departamento_id'] = $filters['departamento_id'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= ' AND r.status = :status';
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['atividade'])) {
            $sql .= ' AND r.atividade LIKE :atividade';
            $params[':atividade'] = '%' . $filters['atividade'] . '%';
        }
        
        if (!empty($filters['processing_purpose'])) {
            $sql .= ' AND r.processing_purpose LIKE :processing_purpose';
            $params[':processing_purpose'] = '%' . $filters['processing_purpose'] . '%';
        }
        
        if (!empty($filters['data_subject'])) {
            $sql .= ' AND r.data_subject LIKE :data_subject';
            $params[':data_subject'] = '%' . $filters['data_subject'] . '%';
        }
        
        if (!empty($filters['inventory_id'])) {
            $sql .= ' AND r.inventory_id = :inventory_id';
            $params[':inventory_id'] = $filters['inventory_id'];
        }
        
        $sql .= ' ORDER BY r.id DESC LIMIT :limit OFFSET :offset';
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Garantir que os campos existam em todos os registros
        foreach ($results as &$result) {
            $result['medidas_seguranca'] = $result['medidas_seguranca'] ?? null;
            $result['observacoes'] = $result['observacoes'] ?? null;
            $result['processing_purpose'] = $result['processing_purpose'] ?? null;
            $result['data_subject'] = $result['data_subject'] ?? null;
            $result['personal_data'] = $result['personal_data'] ?? null;
            $result['sharing'] = $result['sharing'] ?? null;
            $result['inventory_id'] = $result['inventory_id'] ?? null;
            $result['inventory_area'] = $result['inventory_area'] ?? null;
        }
        
        return $results;
    }

    /**
     * Recuperar a quantidade total de registros ROPA para paginação.
     *
     * @param array $filters Filtros opcionais
     * @return int Quantidade total de registros encontrados
     */
    public function getAmountRopa(array $filters = []): int
    {
        $sql = 'SELECT COUNT(r.id) as amount_records FROM lgpd_ropa r 
                INNER JOIN adms_departments d ON r.departamento_id = d.id 
                LEFT JOIN lgpd_inventory i ON r.inventory_id = i.id 
                WHERE 1=1';
        $params = [];
        
        if (!empty($filters['departamento_id'])) {
            $sql .= ' AND r.departamento_id = :departamento_id';
            $params[':departamento_id'] = $filters['departamento_id'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= ' AND r.status = :status';
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['atividade'])) {
            $sql .= ' AND r.atividade LIKE :atividade';
            $params[':atividade'] = '%' . $filters['atividade'] . '%';
        }
        
        if (!empty($filters['processing_purpose'])) {
            $sql .= ' AND r.processing_purpose LIKE :processing_purpose';
            $params[':processing_purpose'] = '%' . $filters['processing_purpose'] . '%';
        }
        
        if (!empty($filters['data_subject'])) {
            $sql .= ' AND r.data_subject LIKE :data_subject';
            $params[':data_subject'] = '%' . $filters['data_subject'] . '%';
        }
        
        if (!empty($filters['inventory_id'])) {
            $sql .= ' AND r.inventory_id = :inventory_id';
            $params[':inventory_id'] = $filters['inventory_id'];
        }
        
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records'] ?? 0);
    }

    /**
     * Recuperar um registro ROPA específico pelo ID.
     *
     * @param int $id ID do registro
     * @return array|bool Registro encontrado ou false
     */
    public function getById(int $id): array|bool
    {
        $sql = 'SELECT r.*, d.name as departamento_nome, i.area as inventory_area 
                FROM lgpd_ropa r 
                INNER JOIN adms_departments d ON r.departamento_id = d.id 
                LEFT JOIN lgpd_inventory i ON r.inventory_id = i.id 
                WHERE r.id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Garantir que os campos existam mesmo que não estejam na tabela
            $result['medidas_seguranca'] = $result['medidas_seguranca'] ?? null;
            $result['observacoes'] = $result['observacoes'] ?? null;
            $result['processing_purpose'] = $result['processing_purpose'] ?? null;
            $result['data_subject'] = $result['data_subject'] ?? null;
            $result['personal_data'] = $result['personal_data'] ?? null;
            $result['sharing'] = $result['sharing'] ?? null;
            $result['inventory_id'] = $result['inventory_id'] ?? null;
            $result['inventory_area'] = $result['inventory_area'] ?? null;
        }
        
        return $result;
    }

    /**
     * Cadastrar um novo registro ROPA.
     *
     * @param array $data Dados do registro
     * @return int|bool ID do registro criado ou false
     */
    public function create(array $data): int|bool
    {
        try {
            $sql = 'INSERT INTO lgpd_ropa (codigo, atividade, departamento_id, base_legal, retencao, riscos, 
                    processing_purpose, data_subject, personal_data, sharing, inventory_id, status, ultima_atualizacao, 
                    medidas_seguranca, responsavel, observacoes, created_at) 
                    VALUES (:codigo, :atividade, :departamento_id, :base_legal, :retencao, :riscos, 
                    :processing_purpose, :data_subject, :personal_data, :sharing, :inventory_id, :status, :ultima_atualizacao, 
                    :medidas_seguranca, :responsavel, :observacoes, NOW())';
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':codigo', $data['codigo']);
            $stmt->bindValue(':atividade', $data['atividade']);
            $stmt->bindValue(':departamento_id', $data['departamento_id'], PDO::PARAM_INT);
            $stmt->bindValue(':base_legal', $data['base_legal']);
            $stmt->bindValue(':retencao', $data['retencao']);
            $stmt->bindValue(':riscos', $data['riscos']);
            $stmt->bindValue(':processing_purpose', $data['processing_purpose'] ?? null);
            $stmt->bindValue(':data_subject', $data['data_subject'] ?? null);
            $stmt->bindValue(':personal_data', $data['personal_data'] ?? null);
            $stmt->bindValue(':sharing', $data['sharing'] ?? null);
            $stmt->bindValue(':inventory_id', $data['inventory_id'] ?? null);
            $stmt->bindValue(':status', $data['status']);
            $stmt->bindValue(':ultima_atualizacao', $data['ultima_atualizacao'] ?? date('Y-m-d'));
            $stmt->bindValue(':medidas_seguranca', $data['medidas_seguranca'] ?? null);
            $stmt->bindValue(':responsavel', $data['responsavel'] ?? null);
            $stmt->bindValue(':observacoes', $data['observacoes'] ?? null);
            $stmt->execute();
            
            return $this->getConnection()->lastInsertId();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Registro ROPA não cadastrado.", ['codigo' => $data['codigo'], 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Atualizar um registro ROPA existente.
     *
     * @param array $data Dados atualizados, incluindo o ID
     * @return bool true se atualizado, false se erro
     */
    public function update(array $data): bool
    {
        try {
            $sql = 'UPDATE lgpd_ropa SET codigo = :codigo, atividade = :atividade, departamento_id = :departamento_id, 
                    base_legal = :base_legal, retencao = :retencao, riscos = :riscos, processing_purpose = :processing_purpose, 
                    data_subject = :data_subject, personal_data = :personal_data, sharing = :sharing, inventory_id = :inventory_id, 
                    status = :status, ultima_atualizacao = :ultima_atualizacao, medidas_seguranca = :medidas_seguranca, 
                    responsavel = :responsavel, observacoes = :observacoes, updated_at = NOW() WHERE id = :id';
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':codigo', $data['codigo']);
            $stmt->bindValue(':atividade', $data['atividade']);
            $stmt->bindValue(':departamento_id', $data['departamento_id'], PDO::PARAM_INT);
            $stmt->bindValue(':base_legal', $data['base_legal']);
            $stmt->bindValue(':retencao', $data['retencao']);
            $stmt->bindValue(':riscos', $data['riscos']);
            $stmt->bindValue(':processing_purpose', $data['processing_purpose'] ?? null);
            $stmt->bindValue(':data_subject', $data['data_subject'] ?? null);
            $stmt->bindValue(':personal_data', $data['personal_data'] ?? null);
            $stmt->bindValue(':sharing', $data['sharing'] ?? null);
            $stmt->bindValue(':inventory_id', $data['inventory_id'] ?? null);
            $stmt->bindValue(':status', $data['status']);
            $stmt->bindValue(':ultima_atualizacao', $data['ultima_atualizacao'] ?? date('Y-m-d'));
            $stmt->bindValue(':medidas_seguranca', $data['medidas_seguranca'] ?? null);
            $stmt->bindValue(':responsavel', $data['responsavel'] ?? null);
            $stmt->bindValue(':observacoes', $data['observacoes'] ?? null);
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Registro ROPA não editado.", ['id' => $data['id'], 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Deletar um registro ROPA pelo ID.
     *
     * @param int $id ID do registro
     * @return bool true se deletado, false se erro
     */
    public function delete(int $id): bool
    {
        try {
            $sql = 'DELETE FROM lgpd_ropa WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Registro ROPA não apagado.", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Recuperar ROPAs por inventário.
     *
     * @param int $inventoryId ID do item do inventário
     * @return array Lista de ROPAs relacionadas
     */
    public function getByInventoryId(int $inventoryId): array
    {
        $sql = 'SELECT r.*, d.name as departamento_nome, i.area as inventory_area 
                FROM lgpd_ropa r 
                INNER JOIN adms_departments d ON r.departamento_id = d.id 
                LEFT JOIN lgpd_inventory i ON r.inventory_id = i.id 
                WHERE r.inventory_id = :inventory_id ORDER BY r.id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':inventory_id', $inventoryId, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Garantir que os campos existam em todos os registros
        foreach ($results as &$result) {
            $result['medidas_seguranca'] = $result['medidas_seguranca'] ?? null;
            $result['observacoes'] = $result['observacoes'] ?? null;
            $result['processing_purpose'] = $result['processing_purpose'] ?? null;
            $result['data_subject'] = $result['data_subject'] ?? null;
            $result['personal_data'] = $result['personal_data'] ?? null;
            $result['sharing'] = $result['sharing'] ?? null;
            $result['inventory_id'] = $result['inventory_id'] ?? null;
            $result['inventory_area'] = $result['inventory_area'] ?? null;
        }
        
        return $results;
    }

    /**
     * Recuperar estatísticas da ROPA.
     *
     * @return array Estatísticas (total, por status, por departamento)
     */
    public function getStatistics(): array
    {
        $stats = [];
        
        // Total de registros
        $sql = 'SELECT COUNT(*) as total FROM lgpd_ropa';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $stats['total'] = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Por status
        $sql = 'SELECT status, COUNT(*) as count FROM lgpd_ropa GROUP BY status';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $stats['by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Por departamento
        $sql = 'SELECT d.name as departamento, COUNT(r.id) as count 
                FROM lgpd_ropa r 
                INNER JOIN adms_departments d ON r.departamento_id = d.id 
                GROUP BY r.departamento_id, d.name 
                ORDER BY count DESC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $stats['by_department'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ROPAs com inventário relacionado
        $sql = 'SELECT COUNT(*) as count FROM lgpd_ropa WHERE inventory_id IS NOT NULL';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $stats['with_inventory'] = (int) $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        return $stats;
    }

    /**
     * Recuperar finalidades únicas para filtros.
     *
     * @return array Lista de finalidades únicas
     */
    public function getUniqueProcessingPurposes(): array
    {
        $sql = 'SELECT DISTINCT processing_purpose FROM lgpd_ropa WHERE processing_purpose IS NOT NULL ORDER BY processing_purpose';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        
        $purposes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return array_filter($purposes); // Remove valores vazios
    }

    /**
     * Recuperar titulares únicos para filtros.
     *
     * @return array Lista de titulares únicos
     */
    public function getUniqueDataSubjects(): array
    {
        $sql = 'SELECT DISTINCT data_subject FROM lgpd_ropa WHERE data_subject IS NOT NULL ORDER BY data_subject';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        
        $subjects = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return array_filter($subjects); // Remove valores vazios
    }

    /**
     * Criar ROPA automaticamente a partir de dados do inventário.
     *
     * @param int $inventoryId ID do item do inventário
     * @param array $additionalData Dados adicionais para a ROPA
     * @return int|bool ID da ROPA criada ou false se erro
     */
    public function createFromInventory(int $inventoryId, array $additionalData = []): int|bool
    {
        try {
            // Buscar dados do inventário
            $inventoryRepo = new \App\adms\Models\Repository\LgpdInventoryRepository();
            $inventory = $inventoryRepo->getById($inventoryId);
            
            if (!$inventory) {
                return false;
            }
            
            // Buscar grupos de dados associados
            $dataGroups = $inventoryRepo->getDataGroupsByInventoryId($inventoryId);
            $personalData = [];
            $hasSensitiveData = false;
            
            foreach ($dataGroups as $group) {
                $personalData[] = $group['name'];
                if ($group['data_category'] === 'Sensível') {
                    $hasSensitiveData = true;
                }
            }
            
            // Preparar dados para ROPA
            $ropaData = [
                'codigo' => 'ROPA-' . str_pad($this->getNextCode(), 3, '0', STR_PAD_LEFT),
                'atividade' => $additionalData['atividade'] ?? 'Processamento baseado no inventário',
                'departamento_id' => $inventory['department_id'],
                'base_legal' => $additionalData['base_legal'] ?? 'Execução de contrato',
                'retencao' => $additionalData['retencao'] ?? '5 anos',
                'riscos' => $hasSensitiveData ? 'Alto - Dados sensíveis envolvidos' : 'Médio',
                'status' => 'Ativo',
                'processing_purpose' => $additionalData['processing_purpose'] ?? 'Processamento de dados do inventário',
                'data_subject' => $inventory['data_subject'],
                'personal_data' => implode(', ', $personalData),
                'sharing' => $additionalData['sharing'] ?? 'Não há',
                'inventory_id' => $inventoryId,
                'medidas_seguranca' => $additionalData['medidas_seguranca'] ?? 'Acesso restrito, criptografia',
                'observacoes' => $hasSensitiveData ? '⚠️ ATENÇÃO: Dados sensíveis envolvidos. Recomenda-se DPIA.' : null
            ];
            
            return $this->create($ropaData);
            
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao criar ROPA a partir do inventário.", [
                'inventory_id' => $inventoryId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Gerar próximo código sequencial para ROPA.
     *
     * @return int Próximo número disponível
     */
    public function getNextCode(): int
    {
        $sql = 'SELECT MAX(CAST(SUBSTRING(codigo, 6) AS UNSIGNED)) as max_code FROM lgpd_ropa WHERE codigo LIKE "ROPA-%"';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($result['max_code'] ?? 0) + 1;
    }

    /**
     * Recupera todas as ROPAs para uso em formulários (select).
     *
     * @return array Lista de ROPAs para select
     */
    public function getAllRopaForSelect(): array
    {
        $sql = 'SELECT id, codigo, atividade FROM lgpd_ropa WHERE status = "Ativo" ORDER BY codigo ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 