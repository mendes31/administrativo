<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

/**
 * Repository responsável pela gestão de oportunidades do CRM
 *
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class CrmOpportunitiesRepository extends DbConnection
{
    /**
     * Buscar oportunidades por etapa do pipeline (para Kanban)
     *
     * @param int $stageId ID da etapa
     * @param array $filters Filtros adicionais
     * @return array Lista de oportunidades
     */
    public function getOpportunitiesByStage(int $stageId, array $filters = []): array
    {
        $sql = 'SELECT 
                    o.id,
                    o.code,
                    o.title,
                    o.value,
                    o.probability,
                    o.expected_close_date,
                    o.next_action,
                    o.stage_entered_at,
                    p.name as partner_name,
                    p.segment,
                    u.name as responsible_name,
                    DATEDIFF(NOW(), o.stage_entered_at) as days_in_stage
                FROM crm_opportunities o
                INNER JOIN crm_partners p ON o.partner_id = p.id
                INNER JOIN adms_users u ON o.responsible_user_id = u.id
                WHERE o.stage_id = :stage_id
                AND o.status = :status';

        $params = [
            ':stage_id' => $stageId,
            ':status' => 'Aberta'
        ];

        // Filtro por responsável
        if (!empty($filters['responsible_user_id'])) {
            $sql .= ' AND o.responsible_user_id = :responsible_user_id';
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
        }

        // Filtro por busca
        if (!empty($filters['search'])) {
            $sql .= ' AND (o.title LIKE :search OR p.name LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql .= ' ORDER BY o.value DESC';

        $stmt = $this->getConnection()->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar valor total de oportunidades por etapa
     *
     * @param int $stageId ID da etapa
     * @param array $filters Filtros adicionais
     * @return float Valor total
     */
    public function getTotalValueByStage(int $stageId, array $filters = []): float
    {
        $sql = 'SELECT SUM(o.value) as total
                FROM crm_opportunities o
                WHERE o.stage_id = :stage_id
                AND o.status = :status';

        $params = [
            ':stage_id' => $stageId,
            ':status' => 'Aberta'
        ];

        // Filtro por responsável
        if (!empty($filters['responsible_user_id'])) {
            $sql .= ' AND o.responsible_user_id = :responsible_user_id';
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
        }

        $stmt = $this->getConnection()->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (float)($result['total'] ?? 0);
    }

    /**
     * Buscar valor total do pipeline
     *
     * @param array $filters Filtros adicionais
     * @return float Valor total
     */
    public function getTotalPipelineValue(array $filters = []): float
    {
        $sql = 'SELECT SUM(o.value) as total
                FROM crm_opportunities o
                WHERE o.status = :status';

        $params = [':status' => 'Aberta'];

        // Filtro por responsável
        if (!empty($filters['responsible_user_id'])) {
            $sql .= ' AND o.responsible_user_id = :responsible_user_id';
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
        }

        $stmt = $this->getConnection()->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (float)($result['total'] ?? 0);
    }

    /**
     * Mover oportunidade para outra etapa
     *
     * @param int $opportunityId ID da oportunidade
     * @param int $newStageId ID da nova etapa
     * @param int $userId ID do usuário que moveu
     * @return bool Sucesso ou falha
     */
    public function moveOpportunity(int $opportunityId, int $newStageId, int $userId): bool
    {
        try {
            $this->getConnection()->beginTransaction();

            // Buscar oportunidade atual
            $opportunity = $this->getOpportunity($opportunityId);

            if (!$opportunity) {
                throw new Exception("Oportunidade não encontrada");
            }

            $oldStageId = $opportunity['stage_id'];

            // Calcular dias na etapa anterior
            $daysInStage = 0;
            if ($opportunity['stage_entered_at']) {
                $daysInStage = (new \DateTime())->diff(new \DateTime($opportunity['stage_entered_at']))->days;
            }

            // Atualizar a oportunidade
            $sql = 'UPDATE crm_opportunities 
                    SET stage_id = :new_stage_id,
                        previous_stage_id = :old_stage_id,
                        stage_entered_at = NOW(),
                        updated_by = :user_id,
                        updated_at = NOW()
                    WHERE id = :id';

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':new_stage_id', $newStageId, PDO::PARAM_INT);
            $stmt->bindValue(':old_stage_id', $oldStageId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':id', $opportunityId, PDO::PARAM_INT);
            $stmt->execute();

            // Registrar no histórico
            $sqlHistory = 'INSERT INTO crm_stage_history 
                          (opportunity_id, from_stage_id, to_stage_id, days_in_stage, moved_by, moved_at)
                          VALUES (:opportunity_id, :from_stage_id, :to_stage_id, :days_in_stage, :moved_by, NOW())';

            $stmtHistory = $this->getConnection()->prepare($sqlHistory);
            $stmtHistory->bindValue(':opportunity_id', $opportunityId, PDO::PARAM_INT);
            $stmtHistory->bindValue(':from_stage_id', $oldStageId, PDO::PARAM_INT);
            $stmtHistory->bindValue(':to_stage_id', $newStageId, PDO::PARAM_INT);
            $stmtHistory->bindValue(':days_in_stage', $daysInStage, PDO::PARAM_INT);
            $stmtHistory->bindValue(':moved_by', $userId, PDO::PARAM_INT);
            $stmtHistory->execute();

            // Registrar log de alteração
            if ($oldStageId != $newStageId) {
                LogAlteracaoService::registrarAlteracao(
                    'crm_opportunities',
                    $opportunityId,
                    $userId,
                    'UPDATE',
                    ['stage_id' => $oldStageId],
                    ['stage_id' => $newStageId]
                );
            }

            $this->getConnection()->commit();

            return true;
        } catch (Exception $e) {
            $this->getConnection()->rollBack();
            GenerateLog::generateLog("error", "Erro ao mover oportunidade", [
                'opportunity_id' => $opportunityId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Buscar uma oportunidade específica
     *
     * @param int $id ID da oportunidade
     * @return array|bool Dados da oportunidade ou false
     */
    public function getOpportunity(int $id): array|bool
    {
        $sql = 'SELECT 
                    o.*,
                    p.name as partner_name,
                    p.email as partner_email,
                    p.phone as partner_phone,
                    u.name as responsible_name,
                    s.name as stage_name,
                    s.color as stage_color
                FROM crm_opportunities o
                INNER JOIN crm_partners p ON o.partner_id = p.id
                INNER JOIN adms_users u ON o.responsible_user_id = u.id
                INNER JOIN crm_pipeline_stages s ON o.stage_id = s.id
                WHERE o.id = :id';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Criar nova oportunidade
     *
     * @param array $data Dados da oportunidade
     * @return bool|int ID da oportunidade criada ou false
     */
    public function createOpportunity(array $data): bool|int
    {
        try {
            $sql = 'INSERT INTO crm_opportunities (
                        code, title, description, partner_id, responsible_user_id,
                        stage_id, value, probability, expected_close_date,
                        next_action, next_action_date, source, products_services,
                        notes, tags, created_by, stage_entered_at, created_at
                    ) VALUES (
                        :code, :title, :description, :partner_id, :responsible_user_id,
                        :stage_id, :value, :probability, :expected_close_date,
                        :next_action, :next_action_date, :source, :products_services,
                        :notes, :tags, :created_by, NOW(), NOW()
                    )';

            $stmt = $this->getConnection()->prepare($sql);

            $stmt->bindValue(':code', $data['code']);
            $stmt->bindValue(':title', $data['title']);
            $stmt->bindValue(':description', $data['description'] ?? null);
            $stmt->bindValue(':partner_id', $data['partner_id'], PDO::PARAM_INT);
            $stmt->bindValue(':responsible_user_id', $data['responsible_user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':stage_id', $data['stage_id'], PDO::PARAM_INT);
            $stmt->bindValue(':value', $data['value']);
            $stmt->bindValue(':probability', $data['probability'] ?? 50, PDO::PARAM_INT);
            $stmt->bindValue(':expected_close_date', $data['expected_close_date'] ?? null);
            $stmt->bindValue(':next_action', $data['next_action'] ?? null);
            $stmt->bindValue(':next_action_date', $data['next_action_date'] ?? null);
            $stmt->bindValue(':source', $data['source'] ?? null);
            $stmt->bindValue(':products_services', $data['products_services'] ?? null);
            $stmt->bindValue(':notes', $data['notes'] ?? null);
            $stmt->bindValue(':tags', $data['tags'] ?? null);
            $stmt->bindValue(':created_by', $_SESSION['user_id'] ?? 1, PDO::PARAM_INT);

            $stmt->execute();

            $opportunityId = $this->getConnection()->lastInsertId();

            // Registrar log
            if ($opportunityId) {
                LogAlteracaoService::registrarAlteracao(
                    'crm_opportunities',
                    $opportunityId,
                    $_SESSION['user_id'] ?? 1,
                    'INSERT',
                    [],
                    $data
                );
            }

            return $opportunityId;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao criar oportunidade", [
                'data' => $data,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Gerar próximo código de oportunidade
     *
     * @return string Próximo código
     */
    public function getNextOpportunityCode(): string
    {
        try {
            $sql = "SELECT code FROM crm_opportunities ORDER BY code DESC LIMIT 1";

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute();

            $lastCode = $stmt->fetchColumn();

            if ($lastCode) {
                $numericPart = (int)substr($lastCode, 3);
                $nextNumber = $numericPart + 1;
            } else {
                $nextNumber = 1;
            }

            return 'OPP' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao gerar código de oportunidade", [
                'error' => $e->getMessage()
            ]);
            return 'OPP00001';
        }
    }

    /**
     * Buscar dados do funil para dashboard
     *
     * @return array Dados do funil
     */
    public function getFunnelData(): array
    {
        $sql = 'SELECT 
                    s.name,
                    s.color,
                    COUNT(o.id) as count,
                    SUM(o.value) as total_value
                FROM crm_pipeline_stages s
                LEFT JOIN crm_opportunities o ON s.id = o.stage_id AND o.status = "Aberta"
                WHERE s.is_active = 1 AND s.stage_type = "active"
                GROUP BY s.id, s.name, s.color, s.display_order
                ORDER BY s.display_order';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar taxa de conversão geral
     *
     * @return float Taxa de conversão em %
     */
    public function getConversionRate(): float
    {
        $sql = 'SELECT 
                    (COUNT(CASE WHEN status = "Ganha" THEN 1 END) * 100.0 / 
                    NULLIF(COUNT(*), 0)) as conversion_rate
                FROM crm_opportunities
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return round((float)($result['conversion_rate'] ?? 0), 1);
    }
}

