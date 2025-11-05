<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class DashboardsRepository extends DbConnection
{
    /**
     * Listar todos os dashboards do usuário
     */
    public function getUserDashboards(int $userId, bool $includePublic = true): array
    {
        $sql = "SELECT 
                    d.*,
                    r.name as report_name,
                    r.category as report_category,
                    u.name as creator_name
                FROM adms_dashboards d
                INNER JOIN adms_dynamic_reports r ON d.dynamic_report_id = r.id
                INNER JOIN adms_users u ON d.created_by = u.id
                WHERE d.status = 1
                AND (d.created_by = :user_id" . ($includePublic ? " OR d.is_public = 1" : "") . ")
                ORDER BY d.views_count DESC, d.created_at DESC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Buscar dashboard por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT 
                    d.*,
                    r.name as report_name,
                    r.custom_sql,
                    r.data_source,
                    r.query_mode,
                    u.name as creator_name
                FROM adms_dashboards d
                INNER JOIN adms_dynamic_reports r ON d.dynamic_report_id = r.id
                INNER JOIN adms_users u ON d.created_by = u.id
                WHERE d.id = :id AND d.status = 1";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Decodificar JSONs
        if ($result) {
            $result['measures_config'] = $result['measures_config'] ? json_decode($result['measures_config'], true) : [];
            $result['kpis_config'] = $result['kpis_config'] ? json_decode($result['kpis_config'], true) : [];
            $result['charts_config'] = $result['charts_config'] ? json_decode($result['charts_config'], true) : [];
            $result['filters_config'] = $result['filters_config'] ? json_decode($result['filters_config'], true) : [];
        }
        
        return $result ?: null;
    }
    
    /**
     * Criar novo dashboard
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO adms_dashboards 
                (name, description, dynamic_report_id, created_by, is_public, category, 
                 measures_config, kpis_config, charts_config, filters_config, layout, created_at)
                VALUES 
                (:name, :description, :report_id, :created_by, :is_public, :category,
                 :measures_config, :kpis_config, :charts_config, :filters_config, :layout, NOW())";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':report_id', $data['dynamic_report_id'], PDO::PARAM_INT);
        $stmt->bindValue(':created_by', $data['created_by'], PDO::PARAM_INT);
        $stmt->bindValue(':is_public', $data['is_public'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindValue(':category', $data['category'] ?? null);
        $stmt->bindValue(':measures_config', json_encode($data['measures_config'] ?? []));
        $stmt->bindValue(':kpis_config', json_encode($data['kpis_config'] ?? []));
        $stmt->bindValue(':charts_config', json_encode($data['charts_config'] ?? []));
        $stmt->bindValue(':filters_config', json_encode($data['filters_config'] ?? []));
        $stmt->bindValue(':layout', $data['layout'] ?? 'default');
        $stmt->execute();
        
        return (int) $this->getConnection()->lastInsertId();
    }
    
    /**
     * Atualizar dashboard
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE adms_dashboards SET 
                name = :name,
                description = :description,
                is_public = :is_public,
                category = :category,
                measures_config = :measures_config,
                kpis_config = :kpis_config,
                charts_config = :charts_config,
                filters_config = :filters_config,
                layout = :layout,
                updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':is_public', $data['is_public'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindValue(':category', $data['category'] ?? null);
        $stmt->bindValue(':measures_config', json_encode($data['measures_config'] ?? []));
        $stmt->bindValue(':kpis_config', json_encode($data['kpis_config'] ?? []));
        $stmt->bindValue(':charts_config', json_encode($data['charts_config'] ?? []));
        $stmt->bindValue(':filters_config', json_encode($data['filters_config'] ?? []));
        $stmt->bindValue(':layout', $data['layout'] ?? 'default');
        
        return $stmt->execute();
    }
    
    /**
     * Deletar dashboard
     */
    public function delete(int $id): bool
    {
        $sql = "UPDATE adms_dashboards SET status = 0 WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    /**
     * Incrementar contador de visualizações
     */
    public function incrementViews(int $id): void
    {
        $sql = "UPDATE adms_dashboards 
                SET views_count = views_count + 1, 
                    last_viewed_at = NOW() 
                WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    /**
     * Verificar se usuário pode acessar dashboard
     */
    public function canAccess(int $dashboardId, int $userId): bool
    {
        $sql = "SELECT id FROM adms_dashboards 
                WHERE id = :id 
                AND status = 1
                AND (created_by = :user_id OR is_public = 1)";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $dashboardId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }
}

