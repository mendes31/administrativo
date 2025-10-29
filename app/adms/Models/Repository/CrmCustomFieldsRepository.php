<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

/**
 * Repository para Campos Customizáveis do CRM
 */
class CrmCustomFieldsRepository extends DbConnection
{
    /**
     * Buscar campos customizados ativos de uma entidade
     */
    public function getFieldsByEntity(string $entityType): array
    {
        $sql = 'SELECT * FROM crm_custom_fields 
                WHERE entity_type = :entity_type AND is_active = 1 
                ORDER BY display_order ASC, id ASC';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':entity_type', $entityType);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar valores dos campos para um parceiro
     */
    public function getPartnerFieldValues(int $partnerId): array
    {
        $sql = 'SELECT cfv.*, cf.field_name, cf.field_label, cf.field_type
                FROM crm_custom_field_values_partners cfv
                INNER JOIN crm_custom_fields cf ON cfv.custom_field_id = cf.id
                WHERE cfv.partner_id = :partner_id';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':partner_id', $partnerId, PDO::PARAM_INT);
        $stmt->execute();
        
        $values = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $values[$row['field_name']] = $row['field_value'];
        }
        
        return $values;
    }

    /**
     * Buscar valores dos campos para uma oportunidade
     */
    public function getOpportunityFieldValues(int $opportunityId): array
    {
        $sql = 'SELECT cfv.*, cf.field_name, cf.field_label, cf.field_type
                FROM crm_custom_field_values_opportunities cfv
                INNER JOIN crm_custom_fields cf ON cfv.custom_field_id = cf.id
                WHERE cfv.opportunity_id = :opportunity_id';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':opportunity_id', $opportunityId, PDO::PARAM_INT);
        $stmt->execute();
        
        $values = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $values[$row['field_name']] = $row['field_value'];
        }
        
        return $values;
    }

    /**
     * Salvar valores de campos customizados para parceiro
     */
    public function savePartnerFieldValues(int $partnerId, array $fieldValues): bool
    {
        try {
            foreach ($fieldValues as $fieldId => $value) {
                // Se for array (checkbox), converter para string separada por vírgulas
                if (is_array($value)) {
                    $value = implode(',', $value);
                }
                
                $sql = 'INSERT INTO crm_custom_field_values_partners 
                        (partner_id, custom_field_id, field_value, created_at, updated_at)
                        VALUES (:partner_id, :custom_field_id, :field_value, NOW(), NOW())
                        ON DUPLICATE KEY UPDATE field_value = :field_value, updated_at = NOW()';
                
                $stmt = $this->getConnection()->prepare($sql);
                $stmt->bindValue(':partner_id', $partnerId, PDO::PARAM_INT);
                $stmt->bindValue(':custom_field_id', $fieldId, PDO::PARAM_INT);
                $stmt->bindValue(':field_value', $value);
                $stmt->execute();
            }
            
            return true;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao salvar campos customizados do parceiro", [
                'partner_id' => $partnerId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Salvar valores de campos customizados para oportunidade
     */
    public function saveOpportunityFieldValues(int $opportunityId, array $fieldValues): bool
    {
        try {
            foreach ($fieldValues as $fieldId => $value) {
                // Se for array (checkbox), converter para string separada por vírgulas
                if (is_array($value)) {
                    $value = implode(',', $value);
                }
                
                $sql = 'INSERT INTO crm_custom_field_values_opportunities 
                        (opportunity_id, custom_field_id, field_value, created_at, updated_at)
                        VALUES (:opportunity_id, :custom_field_id, :field_value, NOW(), NOW())
                        ON DUPLICATE KEY UPDATE field_value = :field_value, updated_at = NOW()';
                
                $stmt = $this->getConnection()->prepare($sql);
                $stmt->bindValue(':opportunity_id', $opportunityId, PDO::PARAM_INT);
                $stmt->bindValue(':custom_field_id', $fieldId, PDO::PARAM_INT);
                $stmt->bindValue(':field_value', $value);
                $stmt->execute();
            }
            
            return true;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao salvar campos customizados da oportunidade", [
                'opportunity_id' => $opportunityId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Criar novo campo customizado
     */
    public function createField(array $data): bool|int
    {
        try {
            $sql = 'INSERT INTO crm_custom_fields 
                    (entity_type, field_name, field_label, field_type, field_options, 
                     is_required, display_order, is_active, created_at, updated_at)
                    VALUES (:entity_type, :field_name, :field_label, :field_type, :field_options,
                            :is_required, :display_order, :is_active, NOW(), NOW())';
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':entity_type', $data['entity_type']);
            $stmt->bindValue(':field_name', $data['field_name']);
            $stmt->bindValue(':field_label', $data['field_label']);
            $stmt->bindValue(':field_type', $data['field_type']);
            $stmt->bindValue(':field_options', $data['options'] ?? null);
            $stmt->bindValue(':is_required', $data['is_required'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':display_order', $data['display_order'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':is_active', ($data['status'] ?? 'active') === 'active' ? 1 : 0, PDO::PARAM_INT);
            
            $stmt->execute();
            return $this->getConnection()->lastInsertId();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao criar campo customizado", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Listar todos os campos customizados
     */
    public function getAllFields(array $filters = []): array
    {
        $sql = 'SELECT * FROM crm_custom_fields WHERE 1=1';
        
        if (!empty($filters['entity_type'])) {
            $sql .= ' AND entity_type = :entity_type';
        }
        
        $sql .= ' ORDER BY entity_type ASC, display_order ASC';
        
        $stmt = $this->getConnection()->prepare($sql);
        
        if (!empty($filters['entity_type'])) {
            $stmt->bindValue(':entity_type', $filters['entity_type']);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar campo por ID
     */
    public function getFieldById(int $id): array|bool
    {
        $sql = 'SELECT * FROM crm_custom_fields WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Atualizar campo customizado
     */
    public function updateField(array $data): bool
    {
        try {
            $sql = 'UPDATE crm_custom_fields SET
                        entity_type = :entity_type,
                        field_name = :field_name,
                        field_label = :field_label,
                        field_type = :field_type,
                        field_options = :field_options,
                        is_required = :is_required,
                        display_order = :display_order,
                        is_active = :is_active,
                        updated_at = NOW()
                    WHERE id = :id';
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
            $stmt->bindValue(':entity_type', $data['entity_type']);
            $stmt->bindValue(':field_name', $data['field_name']);
            $stmt->bindValue(':field_label', $data['field_label']);
            $stmt->bindValue(':field_type', $data['field_type']);
            $stmt->bindValue(':field_options', $data['options'] ?? null);
            $stmt->bindValue(':is_required', $data['is_required'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':display_order', $data['display_order'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':is_active', ($data['status'] ?? 'active') === 'active' ? 1 : 0, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao atualizar campo customizado", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Deletar campo customizado
     */
    public function deleteField(int $id): bool
    {
        try {
            $sql = 'DELETE FROM crm_custom_fields WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao deletar campo customizado", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}

