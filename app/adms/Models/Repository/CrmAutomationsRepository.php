<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

/**
 * Repository para Automações do CRM
 * 
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class CrmAutomationsRepository extends DbConnection
{
    /**
     * Buscar todas as automações
     */
    public function getAllAutomations(array $filters = []): array
    {
        $sql = 'SELECT a.*, u.name as created_by_name
                FROM crm_automations a
                LEFT JOIN adms_users u ON a.created_by = u.id
                WHERE 1=1';
        
        $params = [];
        
        if (!empty($filters['entity_type'])) {
            $sql .= ' AND a.entity_type = :entity_type';
            $params[':entity_type'] = $filters['entity_type'];
        }
        
        if (isset($filters['is_active'])) {
            $sql .= ' AND a.is_active = :is_active';
            $params[':is_active'] = $filters['is_active'];
        }
        
        $sql .= ' ORDER BY a.priority ASC, a.id ASC';
        
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar automação por ID
     */
    public function getAutomationById(int $id): array|bool
    {
        $sql = 'SELECT * FROM crm_automations WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Criar nova automação
     */
    public function createAutomation(array $data): bool|int
    {
        try {
            $sql = 'INSERT INTO crm_automations 
                    (name, description, entity_type, trigger_event, trigger_conditions, 
                     action_type, action_config, is_active, priority, created_by, created_at, updated_at)
                    VALUES (:name, :description, :entity_type, :trigger_event, :trigger_conditions,
                            :action_type, :action_config, :is_active, :priority, :created_by, NOW(), NOW())';
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':name', $data['name']);
            $stmt->bindValue(':description', $data['description'] ?? null);
            $stmt->bindValue(':entity_type', $data['entity_type']);
            $stmt->bindValue(':trigger_event', $data['trigger_event']);
            $stmt->bindValue(':trigger_conditions', $data['trigger_conditions'] ?? null);
            $stmt->bindValue(':action_type', $data['action_type']);
            $stmt->bindValue(':action_config', $data['action_config'] ?? null);
            $stmt->bindValue(':is_active', $data['is_active'] ?? 1, PDO::PARAM_INT);
            $stmt->bindValue(':priority', $data['priority'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':created_by', $_SESSION['user_id'] ?? 1, PDO::PARAM_INT);
            
            $stmt->execute();
            return $this->getConnection()->lastInsertId();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao criar automação", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Atualizar automação
     */
    public function updateAutomation(array $data): bool
    {
        try {
            $sql = 'UPDATE crm_automations SET
                        name = :name,
                        description = :description,
                        entity_type = :entity_type,
                        trigger_event = :trigger_event,
                        trigger_conditions = :trigger_conditions,
                        action_type = :action_type,
                        action_config = :action_config,
                        is_active = :is_active,
                        priority = :priority,
                        updated_at = NOW()
                    WHERE id = :id';
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
            $stmt->bindValue(':name', $data['name']);
            $stmt->bindValue(':description', $data['description'] ?? null);
            $stmt->bindValue(':entity_type', $data['entity_type']);
            $stmt->bindValue(':trigger_event', $data['trigger_event']);
            $stmt->bindValue(':trigger_conditions', $data['trigger_conditions'] ?? null);
            $stmt->bindValue(':action_type', $data['action_type']);
            $stmt->bindValue(':action_config', $data['action_config'] ?? null);
            $stmt->bindValue(':is_active', $data['is_active'] ?? 1, PDO::PARAM_INT);
            $stmt->bindValue(':priority', $data['priority'] ?? 0, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao atualizar automação", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Deletar automação
     */
    public function deleteAutomation(int $id): bool
    {
        try {
            $sql = 'DELETE FROM crm_automations WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao deletar automação", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Buscar automações ativas por gatilho
     */
    public function getActiveAutomationsByTrigger(string $entityType, string $triggerEvent): array
    {
        $sql = 'SELECT * FROM crm_automations 
                WHERE entity_type = :entity_type 
                AND trigger_event = :trigger_event
                AND is_active = 1
                ORDER BY priority ASC';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':entity_type', $entityType);
        $stmt->bindValue(':trigger_event', $triggerEvent);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Registrar log de execução
     */
    public function logExecution(int $automationId, string $entityType, int $entityId, string $status, ?string $errorMessage = null, ?array $executionData = null): bool
    {
        try {
            $sql = 'INSERT INTO crm_automation_logs 
                    (automation_id, entity_type, entity_id, status, error_message, execution_data, executed_at)
                    VALUES (:automation_id, :entity_type, :entity_id, :status, :error_message, :execution_data, NOW())';
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':automation_id', $automationId, PDO::PARAM_INT);
            $stmt->bindValue(':entity_type', $entityType);
            $stmt->bindValue(':entity_id', $entityId, PDO::PARAM_INT);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':error_message', $errorMessage);
            $stmt->bindValue(':execution_data', $executionData ? json_encode($executionData) : null);
            
            $stmt->execute();
            
            // Atualizar contador e última execução
            $sqlUpdate = 'UPDATE crm_automations 
                          SET execution_count = execution_count + 1, 
                              last_execution = NOW() 
                          WHERE id = :id';
            $stmtUpdate = $this->getConnection()->prepare($sqlUpdate);
            $stmtUpdate->bindValue(':id', $automationId, PDO::PARAM_INT);
            $stmtUpdate->execute();
            
            return true;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao registrar log de automação", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Buscar logs de execução de uma automação
     */
    public function getAutomationLogs(int $automationId, int $limit = 50): array
    {
        $sql = 'SELECT * FROM crm_automation_logs 
                WHERE automation_id = :automation_id 
                ORDER BY executed_at DESC 
                LIMIT :limit';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':automation_id', $automationId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

