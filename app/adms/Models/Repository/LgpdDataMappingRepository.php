<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use PDO;
use Exception;

/**
 * Repository responsável por buscar e manipular registros do Data Mapping LGPD no banco de dados.
 *
 * Esta classe fornece métodos para recuperar, criar, atualizar e deletar registros do mapeamento técnico de fluxo de dados.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 */
class LgpdDataMappingRepository extends DbConnection
{
    /**
     * Recuperar todos os registros do data mapping com paginação e filtros.
     *
     * @param array $filters Filtros opcionais (source_system, destination_system, ropa_id, inventory_id)
     * @param int $page Página atual
     * @param int $limit Quantidade de registros por página
     * @return array Lista de registros do data mapping
     */
    public function getAll(array $filters = [], int $page = 1, int $limit = 10): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $sql = 'SELECT dm.*, r.atividade as ropa_atividade, i.area as inventory_area 
                FROM lgpd_data_mapping dm 
                LEFT JOIN lgpd_ropa r ON dm.ropa_id = r.id 
                LEFT JOIN lgpd_inventory i ON dm.inventory_id = i.id 
                WHERE 1=1';
        $params = [];
        
        if (!empty($filters['source_system'])) {
            $sql .= ' AND dm.source_system LIKE :source_system';
            $params[':source_system'] = '%' . $filters['source_system'] . '%';
        }
        
        if (!empty($filters['destination_system'])) {
            $sql .= ' AND dm.destination_system LIKE :destination_system';
            $params[':destination_system'] = '%' . $filters['destination_system'] . '%';
        }
        
        if (!empty($filters['ropa_id'])) {
            $sql .= ' AND dm.ropa_id = :ropa_id';
            $params[':ropa_id'] = $filters['ropa_id'];
        }
        
        if (!empty($filters['inventory_id'])) {
            $sql .= ' AND dm.inventory_id = :inventory_id';
            $params[':inventory_id'] = $filters['inventory_id'];
        }
        
        $sql .= ' ORDER BY dm.id DESC LIMIT :limit OFFSET :offset';
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
     * Recuperar a quantidade total de registros do data mapping para paginação.
     *
     * @param array $filters Filtros opcionais
     * @return int Quantidade total de registros encontrados
     */
    public function getAmountDataMapping(array $filters = []): int
    {
        $sql = 'SELECT COUNT(dm.id) as amount_records FROM lgpd_data_mapping dm 
                LEFT JOIN lgpd_ropa r ON dm.ropa_id = r.id 
                LEFT JOIN lgpd_inventory i ON dm.inventory_id = i.id 
                WHERE 1=1';
        $params = [];
        
        if (!empty($filters['source_system'])) {
            $sql .= ' AND dm.source_system LIKE :source_system';
            $params[':source_system'] = '%' . $filters['source_system'] . '%';
        }
        
        if (!empty($filters['destination_system'])) {
            $sql .= ' AND dm.destination_system LIKE :destination_system';
            $params[':destination_system'] = '%' . $filters['destination_system'] . '%';
        }
        
        if (!empty($filters['ropa_id'])) {
            $sql .= ' AND dm.ropa_id = :ropa_id';
            $params[':ropa_id'] = $filters['ropa_id'];
        }
        
        if (!empty($filters['inventory_id'])) {
            $sql .= ' AND dm.inventory_id = :inventory_id';
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
     * Recuperar um registro do data mapping específico pelo ID.
     *
     * @param int $id ID do registro
     * @return array|bool Registro encontrado ou false
     */
    public function getById(int $id): array|bool
    {
        $sql = 'SELECT dm.*, r.atividade as ropa_atividade, i.area as inventory_area 
                FROM lgpd_data_mapping dm 
                LEFT JOIN lgpd_ropa r ON dm.ropa_id = r.id 
                LEFT JOIN lgpd_inventory i ON dm.inventory_id = i.id 
                WHERE dm.id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cadastrar um novo registro do data mapping.
     *
     * @param array $data Dados do registro
     * @return int|bool ID do registro criado ou false
     */
    public function create(array $data): int|bool
    {
        try {
            $sql = 'INSERT INTO lgpd_data_mapping (source_system, source_field, transformation_rule, destination_system, destination_field, observation, ropa_id, inventory_id, finalidade_relacionada, prazo_retencao_relacionado, created_at) 
                    VALUES (:source_system, :source_field, :transformation_rule, :destination_system, :destination_field, :observation, :ropa_id, :inventory_id, :finalidade_relacionada, :prazo_retencao_relacionado, NOW())';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':source_system', $data['source_system']);
            $stmt->bindValue(':source_field', $data['source_field']);
            $stmt->bindValue(':transformation_rule', $data['transformation_rule'] ?? null);
            $stmt->bindValue(':destination_system', $data['destination_system']);
            $stmt->bindValue(':destination_field', $data['destination_field']);
            $stmt->bindValue(':observation', $data['observation'] ?? null);
            $stmt->bindValue(':ropa_id', $data['ropa_id'] ?? null);
            $stmt->bindValue(':inventory_id', $data['inventory_id'] ?? null);
            $stmt->bindValue(':finalidade_relacionada', $data['finalidade_relacionada'] ?? null);
            $stmt->bindValue(':prazo_retencao_relacionado', $data['prazo_retencao_relacionado'] ?? null);
            $stmt->execute();
            
            return $this->getConnection()->lastInsertId();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Data Mapping LGPD não cadastrado.", ['source_system' => $data['source_system'], 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Atualizar um registro do data mapping existente.
     *
     * @param array $data Dados atualizados, incluindo o ID
     * @return bool true se atualizado, false se erro
     */
    public function update(array $data): bool
    {
        try {
            $sql = 'UPDATE lgpd_data_mapping SET source_system = :source_system, source_field = :source_field, 
                    transformation_rule = :transformation_rule, destination_system = :destination_system, 
                    destination_field = :destination_field, observation = :observation, ropa_id = :ropa_id, 
                    inventory_id = :inventory_id, finalidade_relacionada = :finalidade_relacionada, 
                    prazo_retencao_relacionado = :prazo_retencao_relacionado, updated_at = NOW() WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':source_system', $data['source_system']);
            $stmt->bindValue(':source_field', $data['source_field']);
            $stmt->bindValue(':transformation_rule', $data['transformation_rule'] ?? null);
            $stmt->bindValue(':destination_system', $data['destination_system']);
            $stmt->bindValue(':destination_field', $data['destination_field']);
            $stmt->bindValue(':observation', $data['observation'] ?? null);
            $stmt->bindValue(':ropa_id', $data['ropa_id'] ?? null);
            $stmt->bindValue(':inventory_id', $data['inventory_id'] ?? null);
            $stmt->bindValue(':finalidade_relacionada', $data['finalidade_relacionada'] ?? null);
            $stmt->bindValue(':prazo_retencao_relacionado', $data['prazo_retencao_relacionado'] ?? null);
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Data Mapping LGPD não editado.", ['id' => $data['id'], 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Deletar um registro do data mapping.
     *
     * @param int $id ID do registro
     * @return bool true se deletado, false se erro
     */
    public function delete(int $id): bool
    {
        try {
            $sql = 'DELETE FROM lgpd_data_mapping WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Data Mapping LGPD não excluído.", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Recuperar mapeamentos por ROPA.
     *
     * @param int $ropaId ID da operação ROPA
     * @return array Lista de mapeamentos
     */
    public function getByRopaId(int $ropaId): array
    {
        $sql = 'SELECT dm.*, i.area as inventory_area FROM lgpd_data_mapping dm 
                LEFT JOIN lgpd_inventory i ON dm.inventory_id = i.id 
                WHERE dm.ropa_id = :ropa_id ORDER BY dm.id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':ropa_id', $ropaId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recuperar mapeamentos por inventário.
     *
     * @param int $inventoryId ID do item do inventário
     * @return array Lista de mapeamentos
     */
    public function getByInventoryId(int $inventoryId): array
    {
        $sql = 'SELECT dm.*, r.atividade as ropa_atividade FROM lgpd_data_mapping dm 
                LEFT JOIN lgpd_ropa r ON dm.ropa_id = r.id 
                WHERE dm.inventory_id = :inventory_id ORDER BY dm.id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':inventory_id', $inventoryId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recuperar estatísticas do data mapping.
     *
     * @return array Estatísticas (total, por sistema origem, por sistema destino)
     */
    public function getStatistics(): array
    {
        $stats = [];
        
        // Total de registros
        $sql = 'SELECT COUNT(*) as total FROM lgpd_data_mapping';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $stats['total'] = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Por sistema origem
        $sql = 'SELECT source_system, COUNT(*) as count FROM lgpd_data_mapping GROUP BY source_system ORDER BY count DESC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $stats['by_source_system'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Por sistema destino
        $sql = 'SELECT destination_system, COUNT(*) as count FROM lgpd_data_mapping GROUP BY destination_system ORDER BY count DESC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $stats['by_destination_system'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }

    /**
     * Recuperar sistemas únicos de origem para filtros.
     *
     * @return array Lista de sistemas únicos
     */
    public function getUniqueSourceSystems(): array
    {
        $sql = 'SELECT DISTINCT source_system FROM lgpd_data_mapping ORDER BY source_system';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        
        $systems = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return array_filter($systems); // Remove valores vazios
    }

    /**
     * Recuperar sistemas únicos de destino para filtros.
     *
     * @return array Lista de sistemas únicos
     */
    public function getUniqueDestinationSystems(): array
    {
        $sql = 'SELECT DISTINCT destination_system FROM lgpd_data_mapping ORDER BY destination_system';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        
        $systems = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return array_filter($systems); // Remove valores vazios
    }

    /**
     * Criar Data Mapping automaticamente a partir de dados do ROPA.
     *
     * @param int $ropaId ID da operação ROPA
     * @param array $technicalFlows Fluxos técnicos específicos
     * @return int|bool ID do Data Mapping criado ou false se erro
     */
    public function createFromRopa(int $ropaId, array $technicalFlows = []): int|bool
    {
        try {
            // Buscar dados do ROPA
            $ropaRepo = new \App\adms\Models\Repository\LgpdRopaRepository();
            $ropa = $ropaRepo->getById($ropaId);
            
            if (!$ropa) {
                return false;
            }
            
            // Buscar dados do inventário relacionado
            $inventoryRepo = new \App\adms\Models\Repository\LgpdInventoryRepository();
            $inventory = null;
            if ($ropa['inventory_id']) {
                $inventory = $inventoryRepo->getById($ropa['inventory_id']);
            }
            
            // Se não há fluxos técnicos definidos, criar sugestões baseadas na finalidade
            if (empty($technicalFlows)) {
                $technicalFlows = $this->suggestTechnicalFlows($ropa, $inventory);
            }
            
            $createdMappings = [];
            
            foreach ($technicalFlows as $flow) {
                $mappingData = [
                    'source_system' => $flow['source_system'],
                    'source_field' => $flow['source_field'],
                    'transformation_rule' => $flow['transformation_rule'] ?? null,
                    'destination_system' => $flow['destination_system'],
                    'destination_field' => $flow['destination_field'],
                    'observation' => $flow['observation'] ?? 'Fluxo técnico baseado na ROPA',
                    'ropa_id' => $ropaId,
                    'inventory_id' => $ropa['inventory_id'] ?? null
                ];
                
                $mappingId = $this->create($mappingData);
                if ($mappingId) {
                    $createdMappings[] = $mappingId;
                }
            }
            
            return !empty($createdMappings) ? $createdMappings[0] : false;
            
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao criar Data Mapping a partir do ROPA.", [
                'ropa_id' => $ropaId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Sugerir fluxos técnicos baseados na finalidade do ROPA.
     *
     * @param array $ropa Dados do ROPA
     * @param array|null $inventory Dados do inventário
     * @return array Fluxos técnicos sugeridos
     */
    public function suggestTechnicalFlows(array $ropa, ?array $inventory): array
    {
        $flows = [];
        
        // Fluxo básico baseado na finalidade
        $purpose = strtolower($ropa['processing_purpose'] ?? '');
        $activity = strtolower($ropa['atividade'] ?? '');
        
        if (strpos($purpose, 'pagamento') !== false || strpos($activity, 'folha') !== false) {
            $flows[] = [
                'source_system' => 'ERP RH',
                'source_field' => 'dados_funcionario',
                'transformation_rule' => 'Criptografar dados sensíveis',
                'destination_system' => 'Sistema Bancário',
                'destination_field' => 'dados_pagamento',
                'observation' => 'Dados sensíveis - exige criptografia e contrato de operador'
            ];
        }
        
        if (strpos($purpose, 'venda') !== false || strpos($activity, 'cliente') !== false) {
            $flows[] = [
                'source_system' => 'CRM',
                'source_field' => 'dados_cliente',
                'transformation_rule' => 'Validar e sanitizar dados',
                'destination_system' => 'Sistema de Faturamento',
                'destination_field' => 'dados_faturamento',
                'observation' => 'Dados pessoais - exige consentimento'
            ];
        }
        
        if (strpos($purpose, 'marketing') !== false) {
            $flows[] = [
                'source_system' => 'Website',
                'source_field' => 'email_lead',
                'transformation_rule' => 'Validar formato e duplicidade',
                'destination_system' => 'Sistema de Email Marketing',
                'destination_field' => 'lista_contatos',
                'observation' => 'Dados pessoais - exige consentimento explícito'
            ];
        }
        
        // Fluxo padrão se nenhum específico foi encontrado
        if (empty($flows)) {
            $flows[] = [
                'source_system' => $inventory['storage_location'] ?? 'Sistema Principal',
                'source_field' => 'dados_principais',
                'transformation_rule' => 'Processamento padrão',
                'destination_system' => 'Sistema Destino',
                'destination_field' => 'dados_processados',
                'observation' => 'Fluxo técnico padrão - revisar conforme necessidade'
            ];
        }
        
        return $flows;
    }
}