<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Repository para gerenciar a matriz de competências por cargo
 */
class CompetencyMatrixRepository extends DbConnection
{
    /**
     * Buscar matriz completa (todos os cargos vs todas as competências)
     */
    public function getFullMatrix(): array
    {
        $sql = "SELECT 
                    cm.position_id,
                    cm.competency_id,
                    cm.required_level,
                    cm.is_mandatory,
                    p.name as position_name,
                    c.name as competency_name,
                    c.competency_type,
                    c.category
                FROM adms_competency_matrix cm
                INNER JOIN adms_positions p ON cm.position_id = p.id
                INNER JOIN adms_competencies c ON cm.competency_id = c.id
                WHERE c.status = 1
                ORDER BY p.name, c.competency_type, c.name";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar nível requerido para um cargo e competência específicos
     */
    public function getRequiredLevel(int $positionId, int $competencyId): ?array
    {
        $sql = "SELECT * FROM adms_competency_matrix 
                WHERE position_id = :position_id AND competency_id = :competency_id
                LIMIT 1";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':position_id', $positionId, PDO::PARAM_INT);
        $stmt->bindValue(':competency_id', $competencyId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Criar ou atualizar nível requerido na matriz
     */
    public function upsert(array $data): bool
    {
        // Verificar se já existe
        $existing = $this->getRequiredLevel($data['position_id'], $data['competency_id']);
        
        if ($existing) {
            // Atualizar
            $sql = "UPDATE adms_competency_matrix 
                    SET required_level = :required_level,
                        is_mandatory = :is_mandatory,
                        updated_at = NOW()
                    WHERE position_id = :position_id AND competency_id = :competency_id";
        } else {
            // Criar
            $sql = "INSERT INTO adms_competency_matrix 
                    (position_id, competency_id, required_level, is_mandatory, created_at, updated_at)
                    VALUES 
                    (:position_id, :competency_id, :required_level, :is_mandatory, NOW(), NOW())";
        }
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':position_id', $data['position_id'], PDO::PARAM_INT);
        $stmt->bindValue(':competency_id', $data['competency_id'], PDO::PARAM_INT);
        $stmt->bindValue(':required_level', $data['required_level'], PDO::PARAM_INT);
        $stmt->bindValue(':is_mandatory', $data['is_mandatory'] ?? false, PDO::PARAM_BOOL);
        
        return $stmt->execute();
    }

    /**
     * Remover competência de um cargo
     */
    public function delete(int $positionId, int $competencyId): bool
    {
        $sql = "DELETE FROM adms_competency_matrix 
                WHERE position_id = :position_id AND competency_id = :competency_id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':position_id', $positionId, PDO::PARAM_INT);
        $stmt->bindValue(':competency_id', $competencyId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Buscar competências por cargo com nível requerido
     */
    public function getCompetenciesByPosition(int $positionId): array
    {
        $sql = "SELECT 
                    c.*,
                    cm.competency_id,
                    cm.required_level,
                    cm.is_mandatory
                FROM adms_competency_matrix cm
                INNER JOIN adms_competencies c ON cm.competency_id = c.id
                WHERE cm.position_id = :position_id AND c.status = 1
                ORDER BY cm.is_mandatory DESC, c.competency_type, c.name";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':position_id', $positionId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar cargos por competência
     */
    public function getPositionsByCompetency(int $competencyId): array
    {
        $sql = "SELECT 
                    p.*,
                    cm.required_level,
                    cm.is_mandatory
                FROM adms_competency_matrix cm
                INNER JOIN adms_positions p ON cm.position_id = p.id
                WHERE cm.competency_id = :competency_id
                ORDER BY p.name";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':competency_id', $competencyId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar estatísticas da matriz
     */
    public function getMatrixStats(): array
    {
        // Contar total de cargos cadastrados
        $sqlPositions = "SELECT COUNT(*) as total FROM adms_positions";
        $stmtPositions = $this->getConnection()->prepare($sqlPositions);
        $stmtPositions->execute();
        $totalPositions = $stmtPositions->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        // Contar total de competências ativas
        $sqlCompetencies = "SELECT COUNT(*) as total FROM adms_competencies WHERE status = 1";
        $stmtCompetencies = $this->getConnection()->prepare($sqlCompetencies);
        $stmtCompetencies->execute();
        $totalCompetencies = $stmtCompetencies->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        // Estatísticas da matriz (associações)
        $sql = "SELECT 
                    COUNT(*) as total_assignments,
                    AVG(required_level) as avg_required_level,
                    SUM(CASE WHEN is_mandatory = 1 THEN 1 ELSE 0 END) as mandatory_count
                FROM adms_competency_matrix";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $matrixStats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        
        return [
            'total_positions' => (int)$totalPositions,
            'total_competencies' => (int)$totalCompetencies,
            'total_assignments' => (int)($matrixStats['total_assignments'] ?? 0),
            'avg_required_level' => $matrixStats['avg_required_level'] ? (float)$matrixStats['avg_required_level'] : 0.0,
            'mandatory_count' => (int)($matrixStats['mandatory_count'] ?? 0)
        ];
    }
}

