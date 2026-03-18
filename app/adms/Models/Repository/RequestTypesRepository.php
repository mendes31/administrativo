<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Repository para gerenciar tipos de solicitações
 */
class RequestTypesRepository extends DbConnection
{
    /**
     * Buscar todos os tipos (com filtros opcionais futuros)
     */
    public function getAll(): array
    {
        $sql = "SELECT rt.*, 
                       u.name as default_responsible_name
                FROM adms_request_types rt
                LEFT JOIN adms_users u ON rt.default_responsible_user_id = u.id
                ORDER BY rt.name ASC";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Buscar todos os tipos ativos
     */
    public function getAllActive(): array
    {
        $sql = "SELECT rt.*, 
                       u.name as default_responsible_name
                FROM adms_request_types rt
                LEFT JOIN adms_users u ON rt.default_responsible_user_id = u.id
                WHERE (rt.status = 1 OR rt.is_active = 1)
                ORDER BY rt.name ASC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar por código
     */
    public function getByCode(string $code): ?array
    {
        $sql = "SELECT rt.*, 
                       u.name as default_responsible_name
                FROM adms_request_types rt
                LEFT JOIN adms_users u ON rt.default_responsible_user_id = u.id
                WHERE rt.code = :code";
        
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
        $sql = "SELECT rt.*, 
                       u.name as default_responsible_name
                FROM adms_request_types rt
                LEFT JOIN adms_users u ON rt.default_responsible_user_id = u.id
                WHERE rt.id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Criar tipo
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO adms_request_types 
                (code, name, description, requires_responsible, default_responsible_user_id, 
                 requires_quantity, is_active)
                VALUES 
                (:code, :name, :description, :requires_responsible, :default_responsible_user_id,
                 :requires_quantity, :is_active)";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':code', $data['code']);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':requires_responsible', $data['requires_responsible'] ?? true, PDO::PARAM_BOOL);
        $stmt->bindValue(':default_responsible_user_id', $data['default_responsible_user_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':requires_quantity', $data['requires_quantity'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindValue(':is_active', $data['is_active'] ?? true, PDO::PARAM_BOOL);
        
        $stmt->execute();
        
        return (int)$this->getConnection()->lastInsertId();
    }

    /**
     * Atualizar tipo
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'code', 'name', 'description', 'requires_responsible', 'default_responsible_user_id',
            'requires_quantity', 'is_active'
        ];
        
        $updates = [];
        $params = [':id' => $id];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $updates[] = "updated_at = NOW()";
        
        $sql = "UPDATE adms_request_types 
                SET " . implode(', ', $updates) . "
                WHERE id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        return $stmt->execute();
    }

    /**
     * Deletar tipo
     */
    public function delete(int $id): bool
    {
        // Verificar se está em uso
        $checkSql = "SELECT COUNT(*) as total FROM adms_booking_additional_requests WHERE request_type = (SELECT code FROM adms_request_types WHERE id = :id)";
        $checkStmt = $this->getConnection()->prepare($checkSql);
        $checkStmt->bindValue(':id', $id, PDO::PARAM_INT);
        $checkStmt->execute();
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ((int)($result['total'] ?? 0) > 0) {
            return false; // Não pode deletar se está em uso
        }
        
        $sql = "DELETE FROM adms_request_types WHERE id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
}
