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
}

