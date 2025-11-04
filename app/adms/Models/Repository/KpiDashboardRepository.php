<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class KpiDashboardRepository extends DbConnection
{
    /**
     * Listar todos os dashboards acessíveis pelo usuário
     */
    public function getAccessibleDashboards(int $userId): array
    {
        $sql = "SELECT DISTINCT d.*, 
                    u.name as creator_name,
                    COUNT(w.id) as widget_count
                FROM adms_kpi_dashboards d
                LEFT JOIN adms_users u ON u.id = d.created_by
                LEFT JOIN adms_kpi_widgets w ON w.dashboard_id = d.id
                LEFT JOIN adms_kpi_dashboard_permissions p ON p.dashboard_id = d.id
                WHERE d.is_public = 1 
                   OR d.created_by = :user_id
                   OR p.user_id = :user_id
                GROUP BY d.id, d.name, d.description, d.layout, d.refresh_interval, 
                         d.is_public, d.created_by, d.created_at, d.updated_at, u.name
                ORDER BY d.created_at DESC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Buscar dashboard por ID
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT d.*, u.name as creator_name
                FROM adms_kpi_dashboards d
                LEFT JOIN adms_users u ON u.id = d.created_by
                WHERE d.id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Criar novo dashboard
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO adms_kpi_dashboards 
                (name, description, layout, refresh_interval, is_public, created_by, created_at)
                VALUES (:name, :description, :layout, :refresh_interval, :is_public, :created_by, NOW())";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindValue(':description', $data['description'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':layout', $data['layout'] ?? 'grid', PDO::PARAM_STR);
        $stmt->bindValue(':refresh_interval', $data['refresh_interval'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':is_public', $data['is_public'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindValue(':created_by', $data['created_by'], PDO::PARAM_INT);
        $stmt->execute();
        
        return (int) $this->getConnection()->lastInsertId();
    }

    /**
     * Atualizar dashboard
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE adms_kpi_dashboards 
                SET name = :name, 
                    description = :description, 
                    layout = :layout, 
                    refresh_interval = :refresh_interval, 
                    is_public = :is_public,
                    updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindValue(':description', $data['description'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':layout', $data['layout'] ?? 'grid', PDO::PARAM_STR);
        $stmt->bindValue(':refresh_interval', $data['refresh_interval'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':is_public', $data['is_public'] ?? false, PDO::PARAM_BOOL);
        
        return $stmt->execute();
    }

    /**
     * Deletar dashboard
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM adms_kpi_dashboards WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Buscar widgets de um dashboard
     */
    public function getWidgets(int $dashboardId): array
    {
        $sql = "SELECT w.*, r.name as report_name, r.visualization_type
                FROM adms_kpi_widgets w
                LEFT JOIN adms_dynamic_reports r ON r.id = w.report_id
                WHERE w.dashboard_id = :dashboard_id
                ORDER BY w.position_order ASC, w.id ASC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':dashboard_id', $dashboardId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Criar widget
     */
    public function createWidget(array $data): int
    {
        $sql = "INSERT INTO adms_kpi_widgets 
                (dashboard_id, report_id, title, widget_type, size, position_order, 
                 color_scheme, icon, value_format, value_prefix, value_suffix, 
                 target_value, config_json, created_at)
                VALUES (:dashboard_id, :report_id, :title, :widget_type, :size, :position_order,
                        :color_scheme, :icon, :value_format, :value_prefix, :value_suffix,
                        :target_value, :config_json, NOW())";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':dashboard_id', $data['dashboard_id'], PDO::PARAM_INT);
        $stmt->bindValue(':report_id', $data['report_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':title', $data['title'], PDO::PARAM_STR);
        $stmt->bindValue(':widget_type', $data['widget_type'] ?? 'number', PDO::PARAM_STR);
        $stmt->bindValue(':size', $data['size'] ?? 'medium', PDO::PARAM_STR);
        $stmt->bindValue(':position_order', $data['position_order'] ?? 0, PDO::PARAM_INT);
        $stmt->bindValue(':color_scheme', $data['color_scheme'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':icon', $data['icon'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':value_format', $data['value_format'] ?? 'number', PDO::PARAM_STR);
        $stmt->bindValue(':value_prefix', $data['value_prefix'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':value_suffix', $data['value_suffix'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':target_value', $data['target_value'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':config_json', $data['config_json'] ?? null, PDO::PARAM_STR);
        $stmt->execute();
        
        return (int) $this->getConnection()->lastInsertId();
    }

    /**
     * Atualizar widget
     */
    public function updateWidget(int $id, array $data): bool
    {
        $sql = "UPDATE adms_kpi_widgets 
                SET title = :title,
                    widget_type = :widget_type,
                    size = :size,
                    position_order = :position_order,
                    color_scheme = :color_scheme,
                    icon = :icon,
                    value_format = :value_format,
                    value_prefix = :value_prefix,
                    value_suffix = :value_suffix,
                    target_value = :target_value,
                    config_json = :config_json,
                    updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':title', $data['title'], PDO::PARAM_STR);
        $stmt->bindValue(':widget_type', $data['widget_type'] ?? 'number', PDO::PARAM_STR);
        $stmt->bindValue(':size', $data['size'] ?? 'medium', PDO::PARAM_STR);
        $stmt->bindValue(':position_order', $data['position_order'] ?? 0, PDO::PARAM_INT);
        $stmt->bindValue(':color_scheme', $data['color_scheme'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':icon', $data['icon'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':value_format', $data['value_format'] ?? 'number', PDO::PARAM_STR);
        $stmt->bindValue(':value_prefix', $data['value_prefix'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':value_suffix', $data['value_suffix'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':target_value', $data['target_value'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':config_json', $data['config_json'] ?? null, PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    /**
     * Deletar widget
     */
    public function deleteWidget(int $id): bool
    {
        $sql = "DELETE FROM adms_kpi_widgets WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}

