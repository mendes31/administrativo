<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use PDO;
use Exception;

/**
 * Repository responsável por buscar e manipular registros do Inventário LGPD no banco de dados.
 *
 * Esta classe fornece métodos para recuperar, criar, atualizar e deletar registros do inventário de dados pessoais.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 */
class LgpdInventoryRepository extends DbConnection
{
    /**
     * Recuperar todos os registros do inventário com paginação e filtros.
     *
     * @param array $filters Filtros opcionais (area, data_category, risk_level, department_id)
     * @param int $page Página atual
     * @param int $limit Quantidade de registros por página
     * @return array Lista de registros do inventário
     */
    public function getAll(array $filters = [], int $page = 1, int $limit = 10): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $sql = 'SELECT i.*, d.name as departamento_nome,
                       GROUP_CONCAT(DISTINCT idg.data_category) as data_categories,
                       GROUP_CONCAT(DISTINCT idg.risk_level) as risk_levels,
                       COUNT(DISTINCT idg.id) as total_groups
                FROM lgpd_inventory i 
                LEFT JOIN adms_departments d ON i.department_id = d.id 
                LEFT JOIN lgpd_inventory_data_groups idg ON i.id = idg.lgpd_inventory_id
                WHERE 1=1';
        $params = [];
        
        if (!empty($filters['area'])) {
            $sql .= ' AND i.area LIKE :area';
            $params[':area'] = '%' . $filters['area'] . '%';
        }
        
        if (!empty($filters['data_type'])) {
            $sql .= ' AND i.data_type LIKE :data_type';
            $params[':data_type'] = '%' . $filters['data_type'] . '%';
        }
        
        if (!empty($filters['data_category'])) {
            $sql .= ' AND idg.data_category = :data_category';
            $params[':data_category'] = $filters['data_category'];
        }
        
        if (!empty($filters['risk_level'])) {
            $sql .= ' AND idg.risk_level = :risk_level';
            $params[':risk_level'] = $filters['risk_level'];
        }
        
        if (!empty($filters['department_id'])) {
            $sql .= ' AND i.department_id = :department_id';
            $params[':department_id'] = $filters['department_id'];
        }
        
        $sql .= ' GROUP BY i.id ORDER BY d.name ASC, i.data_subject ASC LIMIT :limit OFFSET :offset';
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Processar os resultados para determinar categoria e risco predominantes
        foreach ($results as &$row) {
            $categories = $row['data_categories'] ? explode(',', $row['data_categories']) : [];
            $riskLevels = $row['risk_levels'] ? explode(',', $row['risk_levels']) : [];
            
            // Determinar categoria predominante (Sensível tem prioridade)
            $row['data_category'] = in_array('Sensível', $categories) ? 'Sensível' : 
                                   (in_array('Pessoal', $categories) ? 'Pessoal' : 'N/A');
            
            // Determinar risco predominante (Alto > Médio > Baixo)
            if (in_array('Alto', $riskLevels)) {
                $row['risk_level'] = 'Alto';
            } elseif (in_array('Médio', $riskLevels)) {
                $row['risk_level'] = 'Médio';
            } elseif (in_array('Baixo', $riskLevels)) {
                $row['risk_level'] = 'Baixo';
            } else {
                $row['risk_level'] = 'N/A';
            }
        }
        
        return $results;
    }

    /**
     * Recuperar a quantidade total de registros do inventário para paginação.
     *
     * @param array $filters Filtros opcionais
     * @return int Quantidade total de registros encontrados
     */
    public function getAmountInventory(array $filters = []): int
    {
        $sql = 'SELECT COUNT(DISTINCT i.id) as amount_records FROM lgpd_inventory i 
                LEFT JOIN adms_departments d ON i.department_id = d.id 
                LEFT JOIN lgpd_inventory_data_groups idg ON i.id = idg.lgpd_inventory_id
                WHERE 1=1';
        $params = [];
        
        if (!empty($filters['area'])) {
            $sql .= ' AND i.area LIKE :area';
            $params[':area'] = '%' . $filters['area'] . '%';
        }
        
        if (!empty($filters['data_type'])) {
            $sql .= ' AND i.data_type LIKE :data_type';
            $params[':data_type'] = '%' . $filters['data_type'] . '%';
        }
        
        if (!empty($filters['data_category'])) {
            $sql .= ' AND idg.data_category = :data_category';
            $params[':data_category'] = $filters['data_category'];
        }
        
        if (!empty($filters['risk_level'])) {
            $sql .= ' AND idg.risk_level = :risk_level';
            $params[':risk_level'] = $filters['risk_level'];
        }
        
        if (!empty($filters['department_id'])) {
            $sql .= ' AND i.department_id = :department_id';
            $params[':department_id'] = $filters['department_id'];
        }
        
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records'] ?? 0);
    }

    /**
     * Recuperar um registro do inventário específico pelo ID.
     *
     * @param int $id ID do registro
     * @return array|bool Registro encontrado ou false
     */
    public function getById(int $id): array|bool
    {
        $sql = 'SELECT i.*, d.name as departamento_nome FROM lgpd_inventory i 
                LEFT JOIN adms_departments d ON i.department_id = d.id 
                WHERE i.id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Criar um novo registro no inventário.
     *
     * @param array $data Dados do inventário
     * @return int|bool ID do registro criado ou false se erro
     */
    public function create(array $data): int|bool
    {
        try {
            $sql = 'INSERT INTO lgpd_inventory (area, data_type, data_subject, storage_location, access_level, department_id, created_at) 
                    VALUES (:area, :data_type, :data_subject, :storage_location, :access_level, :department_id, :created_at)';
            
            $stmt = $this->getConnection()->prepare($sql);
            
            $stmt->bindValue(':area', $data['area']);
            $stmt->bindValue(':data_type', $data['data_type']);
            $stmt->bindValue(':data_subject', $data['data_subject']);
            $stmt->bindValue(':storage_location', $data['storage_location']);
            $stmt->bindValue(':access_level', $data['access_level']);
            $stmt->bindValue(':department_id', $data['department_id'], PDO::PARAM_INT);
            $stmt->bindValue(':created_at', date('Y-m-d H:i:s'));
            
            $stmt->execute();
            
            $inventoryId = $this->getConnection()->lastInsertId();
            
            if ($inventoryId) {
                GenerateLog::generateLog("info", "Inventário LGPD cadastrado com sucesso.", ['inventory_id' => $inventoryId, 'data_subject' => $data['data_subject'] ?? 'N/A']);
            }
            
            return $inventoryId;
            
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Inventário LGPD não cadastrado.", ['data_subject' => $data['data_subject'] ?? 'N/A', 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Atualizar um registro do inventário existente.
     *
     * @param array $data Dados atualizados, incluindo o ID
     * @return bool true se atualizado, false se erro
     */
    public function update(array $data): bool
    {
        try {
            $sql = 'UPDATE lgpd_inventory SET 
                    area = :area, 
                    data_type = :data_type, 
                    data_subject = :data_subject, 
                    storage_location = :storage_location, 
                    access_level = :access_level, 
                    department_id = :department_id, 
                    updated_at = :updated_at 
                    WHERE id = :id';
            
            $stmt = $this->getConnection()->prepare($sql);
            
            $stmt->bindValue(':area', $data['area']);
            $stmt->bindValue(':data_type', $data['data_type']);
            $stmt->bindValue(':data_subject', $data['data_subject']);
            $stmt->bindValue(':storage_location', $data['storage_location']);
            $stmt->bindValue(':access_level', $data['access_level']);
            $stmt->bindValue(':department_id', $data['department_id'], PDO::PARAM_INT);
            $stmt->bindValue(':updated_at', date('Y-m-d H:i:s'));
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
            
            $stmt->execute();
            
            GenerateLog::generateLog("info", "Inventário LGPD atualizado com sucesso.", ['inventory_id' => $data['id'], 'data_subject' => $data['data_subject'] ?? 'N/A']);
            
            return true;
            
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Inventário LGPD não atualizado.", ['inventory_id' => $data['id'] ?? 'N/A', 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Deletar um registro do inventário.
     *
     * @param int $id ID do registro
     * @return bool true se deletado, false se erro
     */
    public function delete(int $id): bool
    {
        try {
            $sql = 'DELETE FROM lgpd_inventory WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Inventário LGPD não excluído.", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Recupera estatísticas do inventário.
     *
     * @return array Estatísticas do inventário
     */
    public function getStatistics(): array
    {
        $sql = 'SELECT 
                    COUNT(DISTINCT i.id) as total_records,
                    COUNT(DISTINCT i.department_id) as total_departments,
                    COUNT(CASE WHEN idg.risk_level = "Alto" THEN 1 END) as high_risk,
                    COUNT(CASE WHEN idg.risk_level = "Médio" THEN 1 END) as medium_risk,
                    COUNT(CASE WHEN idg.risk_level = "Baixo" THEN 1 END) as low_risk,
                    COUNT(CASE WHEN idg.data_category = "Sensível" THEN 1 END) as sensitive_data,
                    COUNT(CASE WHEN idg.data_category = "Pessoal" THEN 1 END) as personal_data
                FROM lgpd_inventory i
                LEFT JOIN lgpd_inventory_data_groups idg ON i.id = idg.lgpd_inventory_id';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Recupera áreas únicas do inventário.
     *
     * @return array Lista de áreas únicas
     */
    public function getUniqueAreas(): array
    {
        $sql = 'SELECT DISTINCT area FROM lgpd_inventory WHERE area IS NOT NULL AND area != "" ORDER BY area';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Recupera categorias únicas dos grupos de dados associados.
     *
     * @return array Lista de categorias únicas
     */
    public function getUniqueCategories(): array
    {
        $sql = 'SELECT DISTINCT idg.data_category FROM lgpd_inventory_data_groups idg 
                WHERE idg.data_category IS NOT NULL AND idg.data_category != "" 
                ORDER BY idg.data_category';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Recupera os grupos de dados associados a um item do inventário.
     *
     * @param int $inventoryId ID do item do inventário
     * @return array Lista de grupos de dados associados
     */
    public function getDataGroupsByInventoryId(int $inventoryId): array
    {
        $sql = 'SELECT dg.id, dg.name, dg.category as default_category, dg.is_sensitive, dg.example_fields,
                       idg.risk_level, idg.data_category, idg.notes
                FROM lgpd_data_groups dg
                INNER JOIN lgpd_inventory_data_groups idg ON dg.id = idg.lgpd_data_group_id
                WHERE idg.lgpd_inventory_id = :inventory_id
                ORDER BY dg.name';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':inventory_id', $inventoryId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Associa grupos de dados a um item do inventário.
     *
     * @param int $inventoryId ID do item do inventário
     * @param array $dataGroups Array com os dados dos grupos (id, risk_level, data_category, notes)
     * @return bool True se a operação foi bem-sucedida
     */
    public function associateDataGroups(int $inventoryId, array $dataGroups): bool
    {
        try {
            // Primeiro, remove todas as associações existentes
            $sqlDelete = 'DELETE FROM lgpd_inventory_data_groups WHERE lgpd_inventory_id = :inventory_id';
            $stmtDelete = $this->getConnection()->prepare($sqlDelete);
            $stmtDelete->bindValue(':inventory_id', $inventoryId, PDO::PARAM_INT);
            $stmtDelete->execute();

            // Se não há grupos para associar, retorna true
            if (empty($dataGroups)) {
                return true;
            }

            // Insere as novas associações com dados específicos
            $sqlInsert = 'INSERT INTO lgpd_inventory_data_groups (lgpd_inventory_id, lgpd_data_group_id, risk_level, data_category, notes, created_at, updated_at) VALUES (:inventory_id, :group_id, :risk_level, :data_category, :notes, NOW(), NOW())';
            $stmtInsert = $this->getConnection()->prepare($sqlInsert);

            foreach ($dataGroups as $groupData) {
                $groupId = is_array($groupData) ? $groupData['id'] : $groupData;
                $riskLevel = is_array($groupData) ? ($groupData['risk_level'] ?? 'Médio') : 'Médio';
                $dataCategory = is_array($groupData) ? ($groupData['data_category'] ?? 'Pessoal') : 'Pessoal';
                $notes = is_array($groupData) ? ($groupData['notes'] ?? '') : '';
                
                $stmtInsert->bindValue(':inventory_id', $inventoryId, PDO::PARAM_INT);
                $stmtInsert->bindValue(':group_id', $groupId, PDO::PARAM_INT);
                $stmtInsert->bindValue(':risk_level', $riskLevel);
                $stmtInsert->bindValue(':data_category', $dataCategory);
                $stmtInsert->bindValue(':notes', $notes);
                $stmtInsert->execute();
            }

            return true;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao associar grupos de dados ao inventário.", [
                'inventory_id' => $inventoryId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Recupera itens do inventário que contêm dados sensíveis.
     *
     * @return array Lista de itens com dados sensíveis
     */
    public function getItemsWithSensitiveData(): array
    {
        $sql = 'SELECT DISTINCT i.*, d.name as departamento_nome
                FROM lgpd_inventory i
                LEFT JOIN adms_departments d ON i.department_id = d.id
                INNER JOIN lgpd_inventory_data_groups idg ON i.id = idg.lgpd_inventory_id
                INNER JOIN lgpd_data_groups dg ON idg.lgpd_data_group_id = dg.id
                WHERE dg.is_sensitive = 1
                ORDER BY i.risk_level DESC, i.id DESC';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Adiciona novos grupos de dados a um item do inventário sem remover os existentes.
     *
     * @param int $inventoryId ID do item do inventário
     * @param array $dataGroups Array com os IDs dos grupos a serem adicionados
     * @return bool True se a operação foi bem-sucedida
     */
    public function addDataGroups(int $inventoryId, array $dataGroups): bool
    {
        try {
            // Se não há grupos para adicionar, retorna true
            if (empty($dataGroups)) {
                return true;
            }

            // Buscar grupos já associados para evitar duplicatas
            $existingGroups = $this->getDataGroupsByInventoryId($inventoryId);
            $existingGroupIds = array_column($existingGroups, 'id');

            // Insere apenas os novos grupos
            $sqlInsert = 'INSERT INTO lgpd_inventory_data_groups (lgpd_inventory_id, lgpd_data_group_id, risk_level, data_category, notes, created_at, updated_at) VALUES (:inventory_id, :group_id, :risk_level, :data_category, :notes, NOW(), NOW())';
            $stmtInsert = $this->getConnection()->prepare($sqlInsert);

            foreach ($dataGroups as $groupId) {
                // Pular se o grupo já existe
                if (in_array($groupId, $existingGroupIds)) {
                    continue;
                }

                // Buscar informações do grupo para definir valores padrão
                $sqlGroup = 'SELECT category, is_sensitive FROM lgpd_data_groups WHERE id = :group_id';
                $stmtGroup = $this->getConnection()->prepare($sqlGroup);
                $stmtGroup->bindValue(':group_id', $groupId, PDO::PARAM_INT);
                $stmtGroup->execute();
                $groupInfo = $stmtGroup->fetch(PDO::FETCH_ASSOC);

                $riskLevel = 'Médio'; // Valor padrão
                $dataCategory = $groupInfo['is_sensitive'] ? 'Sensível' : 'Pessoal';
                $notes = '';
                
                $stmtInsert->bindValue(':inventory_id', $inventoryId, PDO::PARAM_INT);
                $stmtInsert->bindValue(':group_id', $groupId, PDO::PARAM_INT);
                $stmtInsert->bindValue(':risk_level', $riskLevel);
                $stmtInsert->bindValue(':data_category', $dataCategory);
                $stmtInsert->bindValue(':notes', $notes);
                $stmtInsert->execute();
            }

            return true;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao adicionar grupos de dados ao inventário.", [
                'inventory_id' => $inventoryId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Recupera estatísticas por grupo de dados.
     *
     * @return array Estatísticas por grupo de dados
     */
    public function getStatisticsByDataGroup(): array
    {
        $sql = 'SELECT 
                    dg.name as group_name,
                    dg.category,
                    dg.is_sensitive,
                    COUNT(idg.lgpd_inventory_id) as usage_count
                FROM lgpd_data_groups dg
                LEFT JOIN lgpd_inventory_data_groups idg ON dg.id = idg.lgpd_data_group_id
                GROUP BY dg.id, dg.name, dg.category, dg.is_sensitive
                ORDER BY usage_count DESC, dg.name';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gerar relatório completo do fluxo LGPD (Inventário → ROPA → Data Mapping).
     *
     * @param int $inventoryId ID do inventário (opcional)
     * @param int $ropaId ID da ROPA (opcional)
     * @return array Relatório completo do fluxo
     */
    public function getCompleteWorkflowReport(?int $inventoryId = null, ?int $ropaId = null): array
    {
        try {
            $report = [
                'summary' => [],
                'inventory' => [],
                'ropa' => [],
                'data_mapping' => [],
                'compliance_status' => [],
                'recommendations' => []
            ];
            
            // Buscar dados do inventário
            if ($inventoryId) {
                $inventory = $this->getById($inventoryId);
                if ($inventory) {
                    $report['inventory'] = $inventory;
                    $report['inventory']['data_groups'] = $this->getDataGroupsByInventoryId($inventoryId);
                }
            } else {
                $report['inventory'] = $this->getAll([], 1, 1000);
            }
            
            // Buscar ROPAs relacionadas
            $ropaRepo = new \App\adms\Models\Repository\LgpdRopaRepository();
            if ($inventoryId) {
                $report['ropa'] = $ropaRepo->getByInventoryId($inventoryId);
            } elseif ($ropaId) {
                $report['ropa'] = [$ropaRepo->getById($ropaId)];
            } else {
                $report['ropa'] = $ropaRepo->getAll([], 1, 1000);
            }
            
            // Buscar Data Mappings relacionados
            $dataMappingRepo = new \App\adms\Models\Repository\LgpdDataMappingRepository();
            if ($inventoryId) {
                $report['data_mapping'] = $dataMappingRepo->getByInventoryId($inventoryId);
            } elseif ($ropaId) {
                $report['data_mapping'] = $dataMappingRepo->getByRopaId($ropaId);
            } else {
                $report['data_mapping'] = $dataMappingRepo->getAll([], 1, 1000);
            }
            
            // Gerar resumo e status de compliance
            $report['summary'] = $this->generateWorkflowSummary($report);
            $report['compliance_status'] = $this->analyzeComplianceStatus($report);
            $report['recommendations'] = $this->generateRecommendations($report);
            
            return $report;
            
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao gerar relatório completo do fluxo LGPD.", [
                'inventory_id' => $inventoryId,
                'ropa_id' => $ropaId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Gerar resumo do fluxo de trabalho.
     *
     * @param array $report Dados do relatório
     * @return array Resumo
     */
    private function generateWorkflowSummary(array $report): array
    {
        $summary = [
            'total_inventory_items' => count($report['inventory']),
            'total_ropa_operations' => count($report['ropa']),
            'total_data_mappings' => count($report['data_mapping']),
            'sensitive_data_count' => 0,
            'high_risk_count' => 0,
            'missing_ropa_count' => 0,
            'missing_mapping_count' => 0
        ];
        
        // Contar dados sensíveis e de alto risco
        foreach ($report['inventory'] as $item) {
            if (isset($item['data_groups'])) {
                foreach ($item['data_groups'] as $group) {
                    if ($group['data_category'] === 'Sensível') {
                        $summary['sensitive_data_count']++;
                    }
                    if ($group['risk_level'] === 'Alto') {
                        $summary['high_risk_count']++;
                    }
                }
            }
        }
        
        // Verificar itens sem ROPA
        $inventoryIds = array_column($report['inventory'], 'id');
        $ropaInventoryIds = array_column($report['ropa'], 'inventory_id');
        $summary['missing_ropa_count'] = count(array_diff($inventoryIds, $ropaInventoryIds));
        
        // Verificar ROPAs sem Data Mapping
        $ropaIds = array_column($report['ropa'], 'id');
        $mappingRopaIds = array_column($report['data_mapping'], 'ropa_id');
        $summary['missing_mapping_count'] = count(array_diff($ropaIds, $mappingRopaIds));
        
        return $summary;
    }
    
    /**
     * Analisar status de compliance.
     *
     * @param array $report Dados do relatório
     * @return array Status de compliance
     */
    private function analyzeComplianceStatus(array $report): array
    {
        $status = [
            'overall_status' => 'Compliant',
            'issues' => [],
            'warnings' => [],
            'critical_issues' => []
        ];
        
        // Verificar dados sensíveis sem DPIA
        if ($report['summary']['sensitive_data_count'] > 0) {
            $status['warnings'][] = 'Dados sensíveis identificados - considerar DPIA';
        }
        
        // Verificar itens sem ROPA
        if ($report['summary']['missing_ropa_count'] > 0) {
            $status['issues'][] = $report['summary']['missing_ropa_count'] . ' itens do inventário sem ROPA';
        }
        
        // Verificar ROPAs sem Data Mapping
        if ($report['summary']['missing_mapping_count'] > 0) {
            $status['issues'][] = $report['summary']['missing_mapping_count'] . ' ROPAs sem Data Mapping';
        }
        
        // Definir status geral
        if (!empty($status['critical_issues'])) {
            $status['overall_status'] = 'Non-Compliant';
        } elseif (!empty($status['issues'])) {
            $status['overall_status'] = 'Needs Attention';
        }
        
        return $status;
    }
    
    /**
     * Gerar recomendações baseadas no relatório.
     *
     * @param array $report Dados do relatório
     * @return array Recomendações
     */
    private function generateRecommendations(array $report): array
    {
        $recommendations = [];
        
        if ($report['summary']['sensitive_data_count'] > 0) {
            $recommendations[] = 'Realizar DPIA para dados sensíveis identificados';
        }
        
        if ($report['summary']['missing_ropa_count'] > 0) {
            $recommendations[] = 'Criar ROPAs para todos os itens do inventário';
        }
        
        if ($report['summary']['missing_mapping_count'] > 0) {
            $recommendations[] = 'Mapear fluxos técnicos para todas as ROPAs';
        }
        
        if ($report['summary']['high_risk_count'] > 0) {
            $recommendations[] = 'Implementar medidas de segurança adicionais para dados de alto risco';
        }
        
        if (empty($recommendations)) {
            $recommendations[] = 'Sistema em conformidade - manter monitoramento regular';
        }
        
        return $recommendations;
    }
}