<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Repository para gerenciar tipos de solicitação
 */
class RequestTypesRepository extends DbConnection
{
    /**
     * Buscar todos os tipos ativos
     */
    public function getAllActive(): array
    {
        $sql = "SELECT * FROM adms_request_types 
                WHERE status = 1 
                ORDER BY sort_order ASC, name ASC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar por código
     */
    public function getByCode(string $code): ?array
    {
        $sql = "SELECT * FROM adms_request_types WHERE code = :code AND status = 1 LIMIT 1";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':code', $code);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Buscar por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM adms_request_types WHERE id = :id LIMIT 1";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Listar todos (incluindo inativos)
     */
    public function getAll(): array
    {
        $sql = "SELECT * FROM adms_request_types 
                ORDER BY sort_order ASC, name ASC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Criar tipo
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO adms_request_types 
                (code, name, description, requires_manager_approval, requires_dates, 
                 requires_days, requires_amount, icon, color, status, sort_order)
                VALUES 
                (:code, :name, :description, :requires_manager_approval, :requires_dates,
                 :requires_days, :requires_amount, :icon, :color, :status, :sort_order)";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':code', $data['code']);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':requires_manager_approval', isset($data['requires_manager_approval']) ? ($data['requires_manager_approval'] ? 1 : 0) : 1, PDO::PARAM_INT);
        $stmt->bindValue(':requires_dates', isset($data['requires_dates']) ? ($data['requires_dates'] ? 1 : 0) : 0, PDO::PARAM_INT);
        $stmt->bindValue(':requires_days', isset($data['requires_days']) ? ($data['requires_days'] ? 1 : 0) : 0, PDO::PARAM_INT);
        $stmt->bindValue(':requires_amount', isset($data['requires_amount']) ? ($data['requires_amount'] ? 1 : 0) : 0, PDO::PARAM_INT);
        $stmt->bindValue(':icon', $data['icon'] ?? null);
        $stmt->bindValue(':color', $data['color'] ?? 'primary');
        $stmt->bindValue(':status', isset($data['status']) ? ($data['status'] ? 1 : 0) : 1, PDO::PARAM_INT);
        $stmt->bindValue(':sort_order', $data['sort_order'] ?? 0, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return (int)$this->getConnection()->lastInsertId();
    }

    /**
     * Atualizar tipo
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        
        $allowedFields = ['name', 'description', 'requires_manager_approval', 'requires_dates',
                         'requires_days', 'requires_amount', 'icon', 'color', 'status', 'sort_order'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = :{$field}";
                if (in_array($field, ['requires_manager_approval', 'requires_dates', 'requires_days', 'requires_amount', 'status'])) {
                    $values[":{$field}"] = $data[$field] ? 1 : 0;
                } else {
                    $values[":{$field}"] = $data[$field];
                }
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $values[':id'] = $id;
        $fields[] = "updated_at = NOW()";
        
        $sql = "UPDATE adms_request_types SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($values as $key => $value) {
            $type = PDO::PARAM_STR;
            if ($key === ':id' || $key === ':sort_order' || 
                in_array($key, [':requires_manager_approval', ':requires_dates', ':requires_days', ':requires_amount', ':status'])) {
                $type = PDO::PARAM_INT;
            }
            $stmt->bindValue($key, $value, $type);
        }
        
        return $stmt->execute();
    }

    /**
     * Deletar tipo
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM adms_request_types WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}

