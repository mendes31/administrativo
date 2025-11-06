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
     * Buscar dashboard por ID (com múltiplos relatórios)
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT 
                    d.*,
                    u.name as creator_name
                FROM adms_dashboards d
                INNER JOIN adms_users u ON d.created_by = u.id
                WHERE d.id = :id AND d.status = 1";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return null;
        }
        
        // Buscar relatórios vinculados
        $result['reports'] = $this->getDashboardReports($id);
        
        // Relatório principal (para compatibilidade)
        $primaryReport = array_filter($result['reports'], fn($r) => $r['is_primary']);
        if (!empty($primaryReport)) {
            $primary = reset($primaryReport);
            $result['report_name'] = $primary['name'];
            $result['custom_sql'] = $primary['custom_sql'];
            $result['data_source'] = $primary['data_source'];
            $result['query_mode'] = $primary['query_mode'];
        }
        
        // Decodificar JSONs
        $result['measures_config'] = $result['measures_config'] ? json_decode($result['measures_config'], true) : [];
        $result['kpis_config'] = $result['kpis_config'] ? json_decode($result['kpis_config'], true) : [];
        $result['charts_config'] = $result['charts_config'] ? json_decode($result['charts_config'], true) : [];
        $result['filters_config'] = $result['filters_config'] ? json_decode($result['filters_config'], true) : [];
        
        return $result;
    }
    
    /**
     * Buscar relatórios vinculados ao dashboard
     */
    public function getDashboardReports(int $dashboardId): array
    {
        $sql = "SELECT 
                    dr.dashboard_id,
                    dr.report_id,
                    dr.is_primary,
                    dr.display_order,
                    r.name,
                    r.description,
                    r.custom_sql,
                    r.data_source,
                    r.query_mode,
                    r.category
                FROM adms_dashboard_reports dr
                INNER JOIN adms_dynamic_reports r ON dr.report_id = r.id
                WHERE dr.dashboard_id = :dashboard_id
                ORDER BY dr.is_primary DESC, dr.display_order ASC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':dashboard_id', $dashboardId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Adicionar relatório ao dashboard
     */
    public function addReport(int $dashboardId, int $reportId, bool $isPrimary = false): bool
    {
        // Verificar se já existe
        $sql = "SELECT 1 FROM adms_dashboard_reports WHERE dashboard_id = :dashboard_id AND report_id = :report_id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':dashboard_id' => $dashboardId, ':report_id' => $reportId]);
        
        if ($stmt->fetch()) {
            return true; // Já existe
        }
        
        // Se é primário, remover flag de outros
        if ($isPrimary) {
            $this->getConnection()->exec("UPDATE adms_dashboard_reports SET is_primary = 0 WHERE dashboard_id = {$dashboardId}");
        }
        
        // Calcular próximo display_order ANTES do INSERT (evita erro 1093)
        $sql = "SELECT COALESCE(MAX(display_order), 0) + 1 AS next_order 
                FROM adms_dashboard_reports 
                WHERE dashboard_id = :dashboard_id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':dashboard_id' => $dashboardId]);
        $nextOrder = $stmt->fetchColumn() ?: 1;
        
        // Inserir novo
        $sql = "INSERT INTO adms_dashboard_reports (dashboard_id, report_id, is_primary, display_order)
                VALUES (:dashboard_id, :report_id, :is_primary, :display_order)";
        
        $stmt = $this->getConnection()->prepare($sql);
        return $stmt->execute([
            ':dashboard_id' => $dashboardId,
            ':report_id' => $reportId,
            ':is_primary' => $isPrimary ? 1 : 0,
            ':display_order' => $nextOrder
        ]);
    }
    
    /**
     * Remover relatório do dashboard
     */
    public function removeReport(int $dashboardId, int $reportId): bool
    {
        $sql = "DELETE FROM adms_dashboard_reports WHERE dashboard_id = :dashboard_id AND report_id = :report_id";
        $stmt = $this->getConnection()->prepare($sql);
        return $stmt->execute([':dashboard_id' => $dashboardId, ':report_id' => $reportId]);
    }
    
    /**
     * Atualizar relatórios do dashboard (substituir todos)
     */
    public function updateReports(int $dashboardId, array $reportIds): bool
    {
        // Remover todos os relatórios atuais
        $this->getConnection()->exec("DELETE FROM adms_dashboard_reports WHERE dashboard_id = {$dashboardId}");
        
        // Adicionar novos
        foreach ($reportIds as $index => $reportId) {
            $isPrimary = ($index === 0); // Primeiro é primário
            $this->addReport($dashboardId, (int)$reportId, $isPrimary);
        }
        
        return true;
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
        // Super admin tem acesso a tudo
        $isSuperAdmin = isset($_SESSION['user_access_level_id']) && $_SESSION['user_access_level_id'] == 1;
        
        if ($isSuperAdmin) {
            $sql = "SELECT id FROM adms_dashboards WHERE id = :id AND status = 1";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $dashboardId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        }
        
        // Usuários normais: apenas dashboards próprios ou públicos
        $sql = "SELECT id, created_by, is_public FROM adms_dashboards 
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

