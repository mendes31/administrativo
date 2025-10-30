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
     * Atualizar uma atividade
     */
    public function updateActivity(array $data): bool
    {
        try {
            $sql = 'UPDATE crm_activities SET
                        type = :type,
                        partner_id = :partner_id,
                        opportunity_id = :opportunity_id,
                        title = :title,
                        description = :description,
                        scheduled_date = :scheduled_date,
                        duration_minutes = :duration_minutes,
                        status = :status,
                        priority = :priority,
                        updated_by = :updated_by,
                        updated_at = NOW()
                    WHERE id = :id';

            $stmt = $this->getConnection()->prepare($sql);

            $stmt->bindValue(':type', $data['type']);
            $stmt->bindValue(':partner_id', $data['partner_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':opportunity_id', $data['opportunity_id'] ?? null, PDO::PARAM_INT);
            
            $stmt->bindValue(':title', $data['title']);
            $stmt->bindValue(':description', $data['description'] ?? null);
            $stmt->bindValue(':scheduled_date', $data['scheduled_date'] ?? null);
            $stmt->bindValue(':duration_minutes', $data['duration_minutes'] ?? null, PDO::PARAM_INT);
            
            $stmt->bindValue(':status', $data['status']);
            $stmt->bindValue(':priority', $data['priority']);
            
            $stmt->bindValue(':updated_by', $_SESSION['user_id'] ?? 1, PDO::PARAM_INT);
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);

            $result = $stmt->execute();

            return $result;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Atividade não atualizada.", [
                'id' => $data['id'] ?? null,
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
                    type,
                    COUNT(*) as total
                FROM crm_activities
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";

        $params = [];
        
        if (!empty($filters['responsible_user_id'])) {
            $sql .= " AND responsible_user_id = :responsible_user_id";
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
        }
        
        if (!empty($filters['periodo_inicio'])) {
            $sql .= " AND DATE(scheduled_date) >= :periodo_inicio";
            $params[':periodo_inicio'] = $filters['periodo_inicio'];
        }
        
        if (!empty($filters['periodo_fim'])) {
            $sql .= " AND DATE(scheduled_date) <= :periodo_fim";
            $params[':periodo_fim'] = $filters['periodo_fim'];
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
     * Buscar atividades recentes com detalhes para dashboard
     */
    public function getRecentActivitiesWithDetails(array $filters = [], int $limit = 10): array
    {
        $sql = "SELECT 
                    a.id,
                    a.type,
                    a.title,
                    a.description,
                    a.scheduled_date,
                    a.status,
                    a.priority,
                    p.name as partner_name,
                    o.title as opportunity_title
                FROM crm_activities a
                LEFT JOIN crm_partners p ON a.partner_id = p.id
                LEFT JOIN crm_opportunities o ON a.opportunity_id = o.id
                WHERE 1=1";

        $params = [];
        
        if (!empty($filters['responsible_user_id'])) {
            $sql .= " AND a.responsible_user_id = :responsible_user_id";
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
        }
        
        if (!empty($filters['periodo_inicio'])) {
            $sql .= " AND DATE(a.scheduled_date) >= :periodo_inicio";
            $params[':periodo_inicio'] = $filters['periodo_inicio'];
        }
        
        if (!empty($filters['periodo_fim'])) {
            $sql .= " AND DATE(a.scheduled_date) <= :periodo_fim";
            $params[':periodo_fim'] = $filters['periodo_fim'];
        }
        
        $sql .= " ORDER BY a.scheduled_date DESC, a.created_at DESC
                  LIMIT :limit";

        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
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
        // Detectar se vai usar array de IDs
        $usePositional = !empty($filters['allowed_user_ids']) && is_array($filters['allowed_user_ids']);
        
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

        if ($usePositional) {
            // MODO POSICIONAL (com array de IDs)
            
            // Filtro por array de IDs permitidos (hierarquia)
            $placeholders = implode(',', array_fill(0, count($filters['allowed_user_ids']), '?'));
            $sql .= " AND a.responsible_user_id IN ($placeholders)";
            
            foreach ($filters['allowed_user_ids'] as $userId) {
                $params[] = (int)$userId;
            }
            
            // Filtros adicionais
            if (!empty($filters['type'])) {
                $sql .= ' AND a.type = ?';
                $params[] = $filters['type'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= ' AND a.status = ?';
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['priority'])) {
                $sql .= ' AND a.priority = ?';
                $params[] = $filters['priority'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= ' AND DATE(a.scheduled_date) >= ?';
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= ' AND DATE(a.scheduled_date) <= ?';
                $params[] = $filters['date_to'];
            }
            
        } else {
            // MODO NOMEADO (sem array de IDs)
            
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
        }

        $sql .= ' ORDER BY a.scheduled_date ASC, a.created_at DESC';

        $stmt = $this->getConnection()->prepare($sql);

        if ($usePositional) {
            $stmt->execute(array_values($params));
        } else {
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar atividades de um mês específico (para calendário)
     */
    public function getActivitiesByMonth(string $month, array $filters = []): array
    {
        // Detectar se vai usar array de IDs
        $usePositional = !empty($filters['allowed_user_ids']) && is_array($filters['allowed_user_ids']);
        
        $sql = 'SELECT 
                    a.*,
                    p.name as partner_name,
                    o.title as opportunity_title,
                    u.name as responsible_name
                FROM crm_activities a
                LEFT JOIN crm_partners p ON a.partner_id = p.id
                LEFT JOIN crm_opportunities o ON a.opportunity_id = o.id
                INNER JOIN adms_users u ON a.responsible_user_id = u.id
                WHERE DATE_FORMAT(a.scheduled_date, "%Y-%m") = ?';

        $params = [$month];

        if ($usePositional) {
            // Filtro por array de IDs permitidos (hierarquia)
            $placeholders = implode(',', array_fill(0, count($filters['allowed_user_ids']), '?'));
            $sql .= " AND a.responsible_user_id IN ($placeholders)";
            
            foreach ($filters['allowed_user_ids'] as $userId) {
                $params[] = (int)$userId;
            }
            
            // Filtros adicionais
            if (!empty($filters['type'])) {
                $sql .= ' AND a.type = ?';
                $params[] = $filters['type'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= ' AND a.status = ?';
                $params[] = $filters['status'];
            }
            
        } else {
            // Filtros adicionais (modo nomeado)
            if (!empty($filters['responsible_user_id'])) {
                $sql .= ' AND a.responsible_user_id = ?';
                $params[] = $filters['responsible_user_id'];
            }
            
            if (!empty($filters['type'])) {
                $sql .= ' AND a.type = ?';
                $params[] = $filters['type'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= ' AND a.status = ?';
                $params[] = $filters['status'];
            }
        }

        $sql .= ' ORDER BY a.scheduled_date ASC';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(array_values($params));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Buscar atividades de uma semana específica
     */
    public function getActivitiesByWeek(string $date, array $filters = []): array
    {
        // Calcular início e fim da semana
        $dateObj = new \DateTime($date);
        $dayOfWeek = (int)$dateObj->format('N'); // 1=Monday, 7=Sunday
        
        // Ajustar para segunda-feira da semana
        $startOfWeek = (clone $dateObj)->modify('-' . ($dayOfWeek - 1) . ' days')->format('Y-m-d');
        $endOfWeek = (clone $dateObj)->modify('+' . (7 - $dayOfWeek) . ' days')->format('Y-m-d');
        
        // Detectar se vai usar array de IDs
        $usePositional = !empty($filters['allowed_user_ids']) && is_array($filters['allowed_user_ids']);
        
        $sql = 'SELECT 
                    a.*,
                    p.name as partner_name,
                    o.title as opportunity_title,
                    u.name as responsible_name
                FROM crm_activities a
                LEFT JOIN crm_partners p ON a.partner_id = p.id
                LEFT JOIN crm_opportunities o ON a.opportunity_id = o.id
                INNER JOIN adms_users u ON a.responsible_user_id = u.id
                WHERE DATE(a.scheduled_date) >= ?
                  AND DATE(a.scheduled_date) <= ?';

        $params = [$startOfWeek, $endOfWeek];

        if ($usePositional) {
            // Filtro por array de IDs permitidos (hierarquia)
            $placeholders = implode(',', array_fill(0, count($filters['allowed_user_ids']), '?'));
            $sql .= " AND a.responsible_user_id IN ($placeholders)";
            
            foreach ($filters['allowed_user_ids'] as $userId) {
                $params[] = (int)$userId;
            }
            
            // Filtros adicionais
            if (!empty($filters['type'])) {
                $sql .= ' AND a.type = ?';
                $params[] = $filters['type'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= ' AND a.status = ?';
                $params[] = $filters['status'];
            }
            
        } else {
            // Filtros adicionais (modo padrão)
            if (!empty($filters['responsible_user_id'])) {
                $sql .= ' AND a.responsible_user_id = ?';
                $params[] = $filters['responsible_user_id'];
            }
            
            if (!empty($filters['type'])) {
                $sql .= ' AND a.type = ?';
                $params[] = $filters['type'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= ' AND a.status = ?';
                $params[] = $filters['status'];
            }
        }

        $sql .= ' ORDER BY a.scheduled_date ASC';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(array_values($params));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verificar conflito de horário para uma atividade
     * 
     * ⚠️ IMPORTANTE: Verifica conflitos POR USUÁRIO!
     * - Cada usuário tem sua própria agenda
     * - Múltiplos usuários podem ter atividades no mesmo horário
     * - Conflito existe apenas se o MESMO usuário tiver sobreposição
     * 
     * @param int $responsibleUserId ID do usuário responsável pela atividade
     * @param string $scheduledDate Data/hora agendada (Y-m-d H:i:s)
     * @param int|null $durationMinutes Duração em minutos (padrão: 30)
     * @param int|null $excludeActivityId ID da atividade a excluir (para edição)
     * @return array Lista de atividades conflitantes do mesmo usuário
     */
    public function checkScheduleConflict(
        int $responsibleUserId, 
        string $scheduledDate, 
        ?int $durationMinutes = null, 
        ?int $excludeActivityId = null
    ): array {
        // Se não tem data agendada, não há conflito
        if (empty($scheduledDate)) {
            return [];
        }
        
        // Calcular horário de fim (padrão 30 minutos se não especificado)
        $duration = $durationMinutes ?? 30;
        $startTime = new \DateTime($scheduledDate);
        $endTime = (clone $startTime)->modify("+{$duration} minutes");
        
        // ✅ BUSCAR APENAS ATIVIDADES DO MESMO USUÁRIO NO MESMO DIA
        // Isso permite que diferentes usuários tenham atividades no mesmo horário
        $sql = 'SELECT 
                    a.id, a.title, a.scheduled_date, a.duration_minutes,
                    p.name as partner_name, o.title as opportunity_title,
                    u.name as responsible_name
                FROM crm_activities a
                LEFT JOIN crm_partners p ON a.partner_id = p.id
                LEFT JOIN crm_opportunities o ON a.opportunity_id = o.id
                INNER JOIN adms_users u ON a.responsible_user_id = u.id
                WHERE a.responsible_user_id = :user_id
                  AND DATE(a.scheduled_date) = :activity_date
                  AND a.status != "Cancelada"';
        
        // Excluir atividade atual (para edição)
        if ($excludeActivityId) {
            $sql .= ' AND a.id != :exclude_id';
        }
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $responsibleUserId);
        $stmt->bindValue(':activity_date', $startTime->format('Y-m-d'));
        
        if ($excludeActivityId) {
            $stmt->bindValue(':exclude_id', $excludeActivityId);
        }
        
        $stmt->execute();
        $activities = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Verificar sobreposição de horários
        $conflicts = [];
        foreach ($activities as $activity) {
            if (empty($activity['scheduled_date'])) {
                continue;
            }
            
            $existingStart = new \DateTime($activity['scheduled_date']);
            $existingDuration = $activity['duration_minutes'] ?? 30;
            $existingEnd = (clone $existingStart)->modify("+{$existingDuration} minutes");
            
            // Verifica se há sobreposição:
            // Nova começa antes da existente terminar E nova termina depois da existente começar
            if ($startTime < $existingEnd && $endTime > $existingStart) {
                $conflicts[] = [
                    'id' => $activity['id'],
                    'title' => $activity['title'],
                    'scheduled_date' => $activity['scheduled_date'],
                    'duration_minutes' => $activity['duration_minutes'],
                    'partner_name' => $activity['partner_name'],
                    'opportunity_title' => $activity['opportunity_title'],
                    'responsible_name' => $activity['responsible_name'] ?? 'N/A',
                    'start' => $existingStart->format('H:i'),
                    'end' => $existingEnd->format('H:i')
                ];
            }
        }
        
        return $conflicts;
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

