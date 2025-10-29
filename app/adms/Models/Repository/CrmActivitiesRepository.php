<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

/**
 * Repository responsável pelas atividades do CRM
 *
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class CrmActivitiesRepository extends DbConnection
{
    /**
     * Buscar atividades recentes
     */
    public function getRecentActivities(int $limit = 10): array
    {
        $sql = 'SELECT 
                    a.*,
                    p.name as partner_name,
                    o.title as opportunity_title,
                    u.name as responsible_name
                FROM crm_activities a
                LEFT JOIN crm_partners p ON a.partner_id = p.id
                LEFT JOIN crm_opportunities o ON a.opportunity_id = o.id
                INNER JOIN adms_users u ON a.responsible_user_id = u.id
                ORDER BY a.completed_date DESC, a.created_at DESC
                LIMIT :limit';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar tarefas pendentes de um usuário
     */
    public function getUserPendingTasks(int $userId): array
    {
        $sql = 'SELECT 
                    a.*,
                    p.name as partner_name,
                    o.title as opportunity_title
                FROM crm_activities a
                LEFT JOIN crm_partners p ON a.partner_id = p.id
                LEFT JOIN crm_opportunities o ON a.opportunity_id = o.id
                WHERE a.responsible_user_id = :user_id
                AND a.status = "Pendente"
                ORDER BY a.scheduled_date ASC';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar atividades do dia de um usuário
     */
    public function getUserTodayActivities(int $userId): array
    {
        $sql = 'SELECT 
                    a.*,
                    p.name as partner_name,
                    o.title as opportunity_title
                FROM crm_activities a
                LEFT JOIN crm_partners p ON a.partner_id = p.id
                LEFT JOIN crm_opportunities o ON a.opportunity_id = o.id
                WHERE a.responsible_user_id = :user_id
                AND DATE(a.scheduled_date) = CURDATE()
                ORDER BY a.scheduled_date ASC';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Criar atividade
     */
    public function createActivity(array $data): bool|int
    {
        try {
            $sql = 'INSERT INTO crm_activities (
                        type, partner_id, opportunity_id, responsible_user_id,
                        title, description, scheduled_date, duration_minutes,
                        status, priority, reminder_date, created_by, created_at
                    ) VALUES (
                        :type, :partner_id, :opportunity_id, :responsible_user_id,
                        :title, :description, :scheduled_date, :duration_minutes,
                        :status, :priority, :reminder_date, :created_by, NOW()
                    )';

            $stmt = $this->getConnection()->prepare($sql);

            $stmt->bindValue(':type', $data['type']);
            $stmt->bindValue(':partner_id', $data['partner_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':opportunity_id', $data['opportunity_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':responsible_user_id', $data['responsible_user_id'], PDO::PARAM_INT);
            
            $stmt->bindValue(':title', $data['title']);
            $stmt->bindValue(':description', $data['description'] ?? null);
            $stmt->bindValue(':scheduled_date', $data['scheduled_date'] ?? null);
            $stmt->bindValue(':duration_minutes', $data['duration_minutes'] ?? null, PDO::PARAM_INT);
            
            $stmt->bindValue(':status', $data['status'] ?? 'Pendente');
            $stmt->bindValue(':priority', $data['priority'] ?? 'Média');
            $stmt->bindValue(':reminder_date', $data['reminder_date'] ?? null);
            
            $stmt->bindValue(':created_by', $_SESSION['user_id'] ?? 1, PDO::PARAM_INT);

            $stmt->execute();

            return $this->getConnection()->lastInsertId();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Atividade não cadastrada.", [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Marcar atividade como concluída
     */
    public function completeActivity(int $id, array $data = []): bool
    {
        try {
            $sql = 'UPDATE crm_activities SET
                        status = "Concluída",
                        completed_date = NOW(),
                        outcome = :outcome,
                        outcome_notes = :outcome_notes,
                        updated_by = :updated_by
                    WHERE id = :id';

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':outcome', $data['outcome'] ?? null);
            $stmt->bindValue(':outcome_notes', $data['outcome_notes'] ?? null);
            $stmt->bindValue(':updated_by', $_SESSION['user_id'] ?? 1, PDO::PARAM_INT);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Atividade não concluída.", [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Buscar próximos follow-ups de um usuário
     */
    public function getUserNextFollowups(int $userId, int $limit = 5): array
    {
        $sql = 'SELECT 
                    a.*,
                    p.name as partner_name,
                    o.title as opportunity_title
                FROM crm_activities a
                LEFT JOIN crm_partners p ON a.partner_id = p.id
                LEFT JOIN crm_opportunities o ON a.opportunity_id = o.id
                WHERE a.responsible_user_id = :user_id
                AND a.status = "Pendente"
                AND a.scheduled_date > NOW()
                ORDER BY a.scheduled_date ASC
                LIMIT :limit';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar atividades de um parceiro
     */
    public function getActivitiesByPartner(int $partnerId): array
    {
        $sql = 'SELECT 
                    a.*,
                    u.name as responsible_name,
                    o.title as opportunity_title
                FROM crm_activities a
                LEFT JOIN adms_users u ON a.responsible_user_id = u.id
                LEFT JOIN crm_opportunities o ON a.opportunity_id = o.id
                WHERE a.partner_id = :partner_id
                ORDER BY a.scheduled_date DESC, a.created_at DESC';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':partner_id', $partnerId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar atividades de uma oportunidade
     */
    public function getActivitiesByOpportunity(int $opportunityId): array
    {
        $sql = 'SELECT 
                    a.*,
                    u.name as responsible_name,
                    p.name as partner_name
                FROM crm_activities a
                LEFT JOIN adms_users u ON a.responsible_user_id = u.id
                LEFT JOIN crm_partners p ON a.partner_id = p.id
                WHERE a.opportunity_id = :opportunity_id
                ORDER BY a.scheduled_date DESC, a.created_at DESC';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':opportunity_id', $opportunityId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar total de atividades do mês atual
     */
    public function getTotalActivitiesThisMonth(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as total 
                FROM crm_activities a
                LEFT JOIN crm_opportunities o ON a.opportunity_id = o.id
                LEFT JOIN crm_partners p ON a.partner_id = p.id OR o.partner_id = p.id
                LEFT JOIN crm_pipeline_stages s ON o.stage_id = s.id
                WHERE MONTH(a.created_at) = MONTH(NOW()) 
                AND YEAR(a.created_at) = YEAR(NOW())";
        
        $params = [];
        
        if (!empty($filters['responsible_user_id'])) {
            $sql .= " AND a.responsible_user_id = :responsible_user_id";
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
        }

        if (!empty($filters['filter_segment'])) {
            $sql .= " AND p.segment = :filter_segment";
            $params[':filter_segment'] = $filters['filter_segment'];
        }

        if (!empty($filters['filter_stage'])) {
            $sql .= " AND s.name = :filter_stage";
            $params[':filter_stage'] = $filters['filter_stage'];
        }
        
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        return (int) $stmt->fetchColumn();
    }

    /**
     * Buscar atividades pendentes
     */
    public function getPendingActivities(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as total 
                FROM crm_activities a
                LEFT JOIN crm_opportunities o ON a.opportunity_id = o.id
                LEFT JOIN crm_partners p ON a.partner_id = p.id OR o.partner_id = p.id
                LEFT JOIN crm_pipeline_stages s ON o.stage_id = s.id
                WHERE a.status = 'Pendente'
                AND (a.scheduled_date IS NULL OR a.scheduled_date >= CURDATE())";
        
        $params = [];
        
        if (!empty($filters['responsible_user_id'])) {
            $sql .= " AND a.responsible_user_id = :responsible_user_id";
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
        }

        if (!empty($filters['filter_segment'])) {
            $sql .= " AND p.segment = :filter_segment";
            $params[':filter_segment'] = $filters['filter_segment'];
        }

        if (!empty($filters['filter_stage'])) {
            $sql .= " AND s.name = :filter_stage";
            $params[':filter_stage'] = $filters['filter_stage'];
        }
        
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        return (int) $stmt->fetchColumn();
    }

    /**
     * Buscar atividades por tipo
     */
    public function getActivitiesByType(array $filters = []): array
    {
        $sql = "SELECT 
                    type as activity_type,
                    COUNT(*) as total
                FROM crm_activities
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";

        $params = [];
        
        if (!empty($filters['responsible_user_id'])) {
            $sql .= " AND responsible_user_id = :responsible_user_id";
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
        }
        
        $sql .= " GROUP BY type
                  ORDER BY total DESC";

        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar uma atividade específica por ID
     */
    public function getActivityById(int $id): array|bool
    {
        $sql = 'SELECT 
                    a.*,
                    p.name as partner_name,
                    o.title as opportunity_title,
                    u.name as responsible_name
                FROM crm_activities a
                LEFT JOIN crm_partners p ON a.partner_id = p.id
                LEFT JOIN crm_opportunities o ON a.opportunity_id = o.id
                INNER JOIN adms_users u ON a.responsible_user_id = u.id
                WHERE a.id = :id';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Deletar atividade
     */
    public function deleteActivity(int $id): bool
    {
        try {
            $sql = 'DELETE FROM crm_activities WHERE id = :id';

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao deletar atividade", [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Listar todas as atividades com filtros
     */
    public function getAllActivities(array $filters = []): array
    {
        $sql = 'SELECT 
                    a.*,
                    p.name as partner_name,
                    o.title as opportunity_title,
                    u.name as responsible_name
                FROM crm_activities a
                LEFT JOIN crm_partners p ON a.partner_id = p.id
                LEFT JOIN crm_opportunities o ON a.opportunity_id = o.id
                INNER JOIN adms_users u ON a.responsible_user_id = u.id
                WHERE 1=1';

        $params = [];

        // Filtros
        if (!empty($filters['responsible_user_id'])) {
            $sql .= ' AND a.responsible_user_id = :responsible_user_id';
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
        }

        if (!empty($filters['type'])) {
            $sql .= ' AND a.type = :type';
            $params[':type'] = $filters['type'];
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND a.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['priority'])) {
            $sql .= ' AND a.priority = :priority';
            $params[':priority'] = $filters['priority'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= ' AND DATE(a.scheduled_date) >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= ' AND DATE(a.scheduled_date) <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        $sql .= ' ORDER BY a.scheduled_date ASC, a.created_at DESC';

        $stmt = $this->getConnection()->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar atividades de um mês específico (para calendário)
     */
    public function getActivitiesByMonth(string $month, array $filters = []): array
    {
        $sql = 'SELECT 
                    a.*,
                    p.name as partner_name,
                    o.title as opportunity_title,
                    u.name as responsible_name
                FROM crm_activities a
                LEFT JOIN crm_partners p ON a.partner_id = p.id
                LEFT JOIN crm_opportunities o ON a.opportunity_id = o.id
                INNER JOIN adms_users u ON a.responsible_user_id = u.id
                WHERE DATE_FORMAT(a.scheduled_date, "%Y-%m") = :month';

        $params = [':month' => $month];

        // Filtros adicionais
        if (!empty($filters['responsible_user_id'])) {
            $sql .= ' AND a.responsible_user_id = :responsible_user_id';
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
        }

        if (!empty($filters['type'])) {
            $sql .= ' AND a.type = :type';
            $params[':type'] = $filters['type'];
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND a.status = :status';
            $params[':status'] = $filters['status'];
        }

        $sql .= ' ORDER BY a.scheduled_date ASC';

        $stmt = $this->getConnection()->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar atividades atrasadas de um usuário
     */
    public function getOverdueActivities(int $userId): array
    {
        $sql = 'SELECT 
                    a.*,
                    p.name as partner_name,
                    o.title as opportunity_title
                FROM crm_activities a
                LEFT JOIN crm_partners p ON a.partner_id = p.id
                LEFT JOIN crm_opportunities o ON a.opportunity_id = o.id
                WHERE a.responsible_user_id = :user_id
                AND a.status = "Pendente"
                AND a.scheduled_date < NOW()
                ORDER BY a.scheduled_date ASC
                LIMIT 10';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar total de atividades com filtros
     */
    public function getTotalActivities(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as total 
                FROM crm_activities a
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['responsible_user_id'])) {
            $sql .= " AND a.responsible_user_id = :responsible_user_id";
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
        }
        
        if (!empty($filters['periodo_inicio'])) {
            $sql .= " AND a.created_at >= :periodo_inicio";
            $params[':periodo_inicio'] = $filters['periodo_inicio'];
        }
        
        if (!empty($filters['periodo_fim'])) {
            $sql .= " AND a.created_at <= :periodo_fim";
            $params[':periodo_fim'] = $filters['periodo_fim'] . ' 23:59:59';
        }
        
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Buscar total de atividades concluídas com filtros
     */
    public function getCompletedActivities(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as total 
                FROM crm_activities a
                WHERE a.status = 'Concluída'";
        
        $params = [];
        
        if (!empty($filters['responsible_user_id'])) {
            $sql .= " AND a.responsible_user_id = :responsible_user_id";
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
        }
        
        if (!empty($filters['periodo_inicio'])) {
            $sql .= " AND a.created_at >= :periodo_inicio";
            $params[':periodo_inicio'] = $filters['periodo_inicio'];
        }
        
        if (!empty($filters['periodo_fim'])) {
            $sql .= " AND a.created_at <= :periodo_fim";
            $params[':periodo_fim'] = $filters['periodo_fim'] . ' 23:59:59';
        }
        
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Buscar total de atividades atrasadas com filtros (retorna contagem)
     */
    public function getOverdueActivitiesCount(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as total 
                FROM crm_activities a
                WHERE a.status = 'Pendente'
                AND a.scheduled_date < NOW()";
        
        $params = [];
        
        if (!empty($filters['responsible_user_id'])) {
            $sql .= " AND a.responsible_user_id = :responsible_user_id";
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
        }
        
        if (!empty($filters['periodo_inicio'])) {
            $sql .= " AND a.created_at >= :periodo_inicio";
            $params[':periodo_inicio'] = $filters['periodo_inicio'];
        }
        
        if (!empty($filters['periodo_fim'])) {
            $sql .= " AND a.created_at <= :periodo_fim";
            $params[':periodo_fim'] = $filters['periodo_fim'] . ' 23:59:59';
        }
        
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
}

