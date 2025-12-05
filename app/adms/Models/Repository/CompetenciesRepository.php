<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Repository para gerenciar competências
 */
class CompetenciesRepository extends DbConnection
{
    /**
     * Criar nova competência
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO adms_competencies 
                (name, description, competency_type, category, 
                 level_1_description, level_2_description, level_3_description, 
                 level_4_description, level_5_description, status)
                VALUES 
                (:name, :description, :competency_type, :category,
                 :level_1_description, :level_2_description, :level_3_description,
                 :level_4_description, :level_5_description, :status)";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':competency_type', $data['competency_type']);
        $stmt->bindValue(':category', $data['category'] ?? null);
        $stmt->bindValue(':level_1_description', $data['level_1_description'] ?? null);
        $stmt->bindValue(':level_2_description', $data['level_2_description'] ?? null);
        $stmt->bindValue(':level_3_description', $data['level_3_description'] ?? null);
        $stmt->bindValue(':level_4_description', $data['level_4_description'] ?? null);
        $stmt->bindValue(':level_5_description', $data['level_5_description'] ?? null);
        $stmt->bindValue(':status', $data['status'] ?? true, PDO::PARAM_BOOL);
        
        $stmt->execute();
        
        return (int)$this->getConnection()->lastInsertId();
    }

    /**
     * Buscar por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM adms_competencies WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Listar todas as competências
     */
    public function getAll(array $filters = []): array
    {
        $where = ['status = 1'];
        $params = [];
        
        if (!empty($filters['competency_type'])) {
            $where[] = 'competency_type = :competency_type';
            $params[':competency_type'] = $filters['competency_type'];
        }
        
        if (!empty($filters['category'])) {
            $where[] = 'category = :category';
            $params[':category'] = $filters['category'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = '(name LIKE :search OR description LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $sql = "SELECT * FROM adms_competencies 
                WHERE " . implode(' AND ', $where) . "
                ORDER BY competency_type, name";
        
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Atualizar competência
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        
        $allowedFields = ['name', 'description', 'competency_type', 'category',
                         'level_1_description', 'level_2_description', 'level_3_description',
                         'level_4_description', 'level_5_description', 'status'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = :{$field}";
                $values[":{$field}"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $values[':id'] = $id;
        $fields[] = "updated_at = NOW()";
        
        $sql = "UPDATE adms_competencies SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($values as $key => $value) {
            $type = PDO::PARAM_STR;
            if ($key === ':id') {
                $type = PDO::PARAM_INT;
            } elseif ($key === ':status') {
                $type = PDO::PARAM_BOOL;
            }
            $stmt->bindValue($key, $value, $type);
        }
        
        return $stmt->execute();
    }

    /**
     * Deletar competência (soft delete)
     */
    public function delete(int $id): bool
    {
        $sql = "UPDATE adms_competencies SET status = 0, updated_at = NOW() WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Obter competências por cargo (matriz)
     */
    public function getByPosition(int $positionId): array
    {
        $sql = "SELECT c.*, cm.required_level, cm.is_mandatory
                FROM adms_competency_matrix cm
                INNER JOIN adms_competencies c ON cm.competency_id = c.id
                WHERE cm.position_id = :position_id AND c.status = 1
                ORDER BY cm.is_mandatory DESC, c.competency_type, c.name";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':position_id', $positionId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

